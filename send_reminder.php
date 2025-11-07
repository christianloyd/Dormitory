<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Clear accidental output
ob_start();

include 'db.php';

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
               b.due_date, b.payment_date, b.base_rent, b.interest,
               b.utility_fee, b.utility_amount, b.add_charges, b.add_amount,
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

    // --- Safely decode JSON fields ---
    $utilityFees = json_decode($tenant['utility_fee'], true);
    if (!is_array($utilityFees)) $utilityFees = [];
    $utilityAmounts = json_decode($tenant['utility_amount'], true);
    if (!is_array($utilityAmounts)) $utilityAmounts = [];

    $addCharges = json_decode($tenant['add_charges'], true);
    if (!is_array($addCharges)) $addCharges = [];
    $addAmounts = json_decode($tenant['add_amount'], true);
    if (!is_array($addAmounts)) $addAmounts = [];

    // Calculate totals for hybrid message selection
    $total_utilities = array_sum($utilityAmounts);
    $total_additional = array_sum($addAmounts);
    $total_amount = $tenant['base_rent'] + $tenant['interest'] + $total_utilities + $total_additional;

    // --- HYBRID MESSAGE: Choose format based on amount ---
    // Threshold: ₱1,000 (configurable)
    $amount_threshold = 1000;
    $use_detailed = ($total_amount > $amount_threshold);

    if ($use_detailed) {
        // DETAILED MESSAGE (for amounts > ₱1,000)
        $message = "Ben and Sof Dormitory\n";
        $message .= "Purok 1A, Mati, San Miguel, ZDS\n\n";
        $message .= "Good day, {$tenant['tenant_name']}!\n";
        $message .= "Payment reminder for your room.\n\n";
        $message .= "Room: {$tenant['room_number']}\n";
        $message .= "Due: {$tenant['due_date']}\n\n";
        $message .= "Charges:\n";
        $message .= "- Rent: PHP " . number_format($tenant['base_rent'],2) . "\n";

        if ($tenant['interest'] > 0) {
            $message .= "- Interest: PHP " . number_format($tenant['interest'],2) . "\n";
        }

        if ($total_utilities > 0) {
            $message .= "- Utilities: PHP " . number_format($total_utilities,2) . "\n";
        }

        if ($total_additional > 0) {
            $message .= "- Other: PHP " . number_format($total_additional,2) . "\n";
        }

        $message .= "\nTotal: PHP " . number_format($total_amount,2) . "\n";
        $message .= "\nPay within 3 days to avoid penalties.\nThank you!";

    } else {
        // SHORT MESSAGE (for amounts ≤ ₱1,000)
        $message = "Ben & Sof Dorm\n";
        $message .= "Payment Reminder\n\n";
        $message .= "Hi {$tenant['tenant_name']}!\n";
        $message .= "Room: {$tenant['room_number']}\n";
        $message .= "Due: {$tenant['due_date']}\n";
        $message .= "Amount: PHP " . number_format($total_amount,2) . "\n\n";
        $message .= "Pay within 3 days.\nThank you!";
    }

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
        'message_type' => $use_detailed ? 'detailed' : 'short',
        'total_amount' => $total_amount,
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
