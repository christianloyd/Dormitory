<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Clear accidental output
ob_start();

include __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/BillingItems.php';

try {
    // --- Get JSON POST data ---
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['bill_id'])) {
        throw new Exception("Bill ID is required.");
    }

    $bill_id = intval($data['bill_id']);

    // --- Fetch payment details ---
    $stmt = $conn->prepare("
        SELECT t.tenant_id, t.tenant_name, t.contact_number, t.guardian_contact,
               r.room_number,
               b.bill_id, b.bill_date, b.due_date, b.payment_date, b.payment_amount,
               b.payment_method, b.total_amount, b.base_rent, b.interest, b.status
        FROM billing b
        INNER JOIN tenants t ON b.tenant_id = t.tenant_id
        INNER JOIN rooms r ON b.room_id = r.room_id
        WHERE b.bill_id = ?
    ");

    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

    $stmt->bind_param("i", $bill_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$payment) throw new Exception("Payment record not found.");

    // Only send confirmation for Partial or Settled payments
    if ($payment['status'] !== 'Partial' && $payment['status'] !== 'Settled') {
        throw new Exception("No payment confirmation needed for status: " . $payment['status']);
    }

    if (empty($payment['bill_id'])) {
        throw new Exception("Billing reference required for confirmation.");
    }

    $billId = (int)$payment['bill_id'];
    $utilityItems = getBillingUtilityItems($conn, $billId);
    $additionalItems = getBillingAdditionalItems($conn, $billId);

    $confirmationMessage = composePaymentConfirmationSMSMessage(
        $payment,
        $utilityItems,
        $additionalItems
    );

    $message = $confirmationMessage['message'];
    $remaining_balance = $confirmationMessage['remaining_balance'];
    $characterCount = mb_strlen($message);
    $segments = max(1, ceil($characterCount / 157));

    // --- Send SMS via IPROG API ---
    $smsHelper = new SMSHelper($conn);
    $smsHelper->setSMSEnabled(SMS_ENABLED);

    $numbers = [
        ['number' => $payment['contact_number'], 'type' => 'Tenant'],
        ['number' => $payment['guardian_contact'], 'type' => 'Guardian']
    ];

    $sent_numbers = [];
    $sms_results = [];

    foreach ($numbers as $recipient) {
        if (!empty($recipient['number'])) {
            $result = $smsHelper->sendSMS(
                $recipient['number'],
                $message,
                $payment['tenant_id'],
                $recipient['type']
            );

            if ($result['success']) {
                $sent_numbers[] = $recipient['number'];
            }

            $sms_results[] = [
                'number' => $recipient['number'],
                'type' => $recipient['type'],
                'status' => $result['success'] ? 'sent' : 'failed',
                'message' => $result['message']
            ];
        }
    }

    // --- Insert notification into database ---
    $notifMessage = "Payment confirmation sent for Room {$payment['room_number']}. Paid: ₱" . number_format($payment['payment_amount'], 2) . ", Total Bill: ₱" . number_format($payment['total_amount'], 2);
    $type = "confirmation";

    $stmtNotif = $conn->prepare("
        INSERT INTO notifications (tenant_id, type, message, is_read, created_at)
        VALUES (?, ?, ?, 0, NOW())
    ");

    if (!$stmtNotif) throw new Exception("Prepare failed for notification: " . $conn->error);

    $stmtNotif->bind_param("iss", $payment['tenant_id'], $type, $notifMessage);
    if (!$stmtNotif->execute()) throw new Exception("Execute failed for notification: " . $stmtNotif->error);
    $stmtNotif->close();

    // --- Return JSON response ---
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => count($sent_numbers) > 0 ? "Payment confirmation sent to " . count($sent_numbers) . " recipient(s)." : "Confirmation logged but SMS not sent.",
        'sent_to' => $sent_numbers,
        'sms_results' => $sms_results,
        'sms_preview' => $message,
        'character_count' => $characterCount,
        'segments' => $segments,
        'totals' => [
            'utilities' => $confirmationMessage['total_utilities'],
            'additional' => $confirmationMessage['total_additional'],
            'overall' => $confirmationMessage['total_amount']
        ],
        'remaining_balance' => $remaining_balance
    ]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
