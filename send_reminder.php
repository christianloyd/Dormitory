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

    // --- Construct reminder message ---
    $message = "Ben and Sof Dormitory\n";
    $message .= "Purok 1A, Mati, San Miguel, ZDS\n\n";
    $message .= "Good day, {$tenant['tenant_name']}!\n";
    $message .= "This is a friendly reminder regarding your billing for your room.\n\n";
    $message .= "Room Number: {$tenant['room_number']}\n";
    $message .= "Due Date: {$tenant['due_date']}\n";
    $message .= "Payment Date: " . ($tenant['payment_date'] ?: "(Not yet paid)") . "\n\n";
    $message .= "Charges:\n";
    $message .= "- Base Rent: ₱" . number_format($tenant['base_rent'],2) . "\n";
    $message .= "- Interest: ₱" . number_format($tenant['interest'],2) . "\n";

    if (!empty($utilityFees)) {
        foreach ($utilityFees as $i => $fee) {
            $amt = number_format($utilityAmounts[$i] ?? 0,2);
            $message .= "- Utility Fees: {$fee} – ₱{$amt}\n";
        }
    } else {
        $message .= "- Utility Fees: – ₱0.00\n";
    }

    if (!empty($addCharges)) {
        foreach ($addCharges as $i => $charge) {
            $amt = number_format($addAmounts[$i] ?? 0,2);
            $message .= "- Additional Charges: {$charge} – ₱{$amt}\n";
        }
    } else {
        $message .= "- Additional Charges: – ₱0.00\n";
    }

    $message .= "\nPayment Details:\n";
    $message .= "- Payment Amount: ₱" . number_format($tenant['payment_amount'],2) . "\n";
    $message .= "- Payment Method: " . ($tenant['payment_method'] ?: "-") . "\n";
    $message .= "\nPlease settle your payment within 3 days to avoid penalties. Thank you.";

    // --- Simulate sending SMS ---
    $numbers = [$tenant['contact_number'], $tenant['guardian_contact']];
    $sent_numbers = [];
    foreach ($numbers as $number) {
        if (!empty($number)) {
            $sent_numbers[] = $number;
            // Normally here you would send SMS via Twilio
            // $client->messages->create($number, ['from'=>$twilio_number, 'body'=>$message]);
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
        'message' => "Reminder sent successfully.",
        'sent_to' => $sent_numbers,
        'sms_preview' => $message
    ]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
