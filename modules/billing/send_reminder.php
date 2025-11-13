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
    if (!isset($data['tenant_id'])) {
        throw new Exception("Tenant ID is required.");
    }

    $tenant_id = intval($data['tenant_id']);

    // --- Fetch tenant & billing info (latest bill) ---
    $stmt = $conn->prepare("
        SELECT t.tenant_name, t.contact_number, t.guardian_contact,
               r.room_number,
               b.bill_id, b.due_date, b.payment_date, b.base_rent, b.interest,
               b.payment_amount, b.payment_method
        FROM tenants t
        LEFT JOIN billing b ON t.tenant_id = b.tenant_id
        LEFT JOIN rooms r ON t.room_id = r.room_id
        WHERE t.tenant_id = ?
        ORDER BY b.due_date DESC
        LIMIT 1
    ");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $tenant = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$tenant) throw new Exception("Tenant not found.");

    if (empty($tenant['bill_id'])) {
        throw new Exception("No billing record found for this tenant.");
    }

    $billId = (int)$tenant['bill_id'];

    $utilityItems = getBillingUtilityItems($conn, $billId);
    $additionalItems = getBillingAdditionalItems($conn, $billId);

    $reminderMessage = composeReminderSMSMessage(
        [
            'tenant_name' => $tenant['tenant_name'] ?? '',
            'room_number' => $tenant['room_number'] ?? '',
            'due_date' => $tenant['due_date'] ?? null,
            'base_rent' => $tenant['base_rent'] ?? 0,
            'interest' => $tenant['interest'] ?? 0
        ],
        $utilityItems,
        $additionalItems
    );

    $message = $reminderMessage['message'];

    // --- Send SMS via IPROG API ---
    $smsHelper = new SMSHelper($conn);
    $smsHelper->setSMSEnabled(SMS_ENABLED);

    $numbers = [
        ['number' => $tenant['contact_number'], 'type' => 'Tenant'],
        ['number' => $tenant['guardian_contact'], 'type' => 'Guardian']
    ];

    $sent_numbers = [];
    $sms_results = [];

    foreach ($numbers as $recipient) {
        if (!empty($recipient['number'])) {
            $result = $smsHelper->sendSMS(
                $recipient['number'],
                $message,
                $tenant_id,
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
    $notifMessage = "Reminder sent regarding billing for Room {$tenant['room_number']} due on {$tenant['due_date']}.";
    $type = "Reminder";

    $stmtNotif = $conn->prepare("
        INSERT INTO notifications (tenant_id, type, message, is_read, created_at)
        VALUES (?, ?, ?, 0, NOW())
    ");
    if (!$stmtNotif) throw new Exception("Prepare failed for notification: " . $conn->error);

    $stmtNotif->bind_param("iss", $tenant_id, $type, $notifMessage);
    if (!$stmtNotif->execute()) throw new Exception("Execute failed for notification: " . $stmtNotif->error);
    $stmtNotif->close();

    // --- Return JSON response ---
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => count($sent_numbers) > 0 ? "Reminder sent successfully to " . count($sent_numbers) . " recipient(s)." : "Reminder logged but SMS not sent.",
        'sent_to' => $sent_numbers,
        'sms_results' => $sms_results,
        'sms_preview' => $message,
        'totals' => [
            'utilities' => $reminderMessage['total_utilities'],
            'additional' => $reminderMessage['total_additional'],
            'overall' => $reminderMessage['total_amount']
        ],
        'character_count' => mb_strlen($message),
        'credits_per_sms' => ceil(mb_strlen($message) / 157)
    ]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
