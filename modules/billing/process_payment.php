<?php
/**
 * Billing Module - Process Payment
 * Path: /modules/billing/process_payment.php
 */
require_once '../../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bill_id = intval($_POST['bill_id']);
    $tenant_id = intval($_POST['tenant_id']);
    $payment_amount = floatval($_POST['payment_amount']);
    $payment_method = $_POST['payment_method'];
    $payment_date = date('Y-m-d H:i:s');
    $total_amount = floatval(str_replace(',', '', $_POST['total_amount']));

    // Determine status
    if ($payment_amount >= $total_amount) {
        $status = "Settled";
    } elseif ($payment_amount > 0) {
        $status = "Partial";
    } else {
        $status = "Pending Payment";
    }

    $sql = "UPDATE billing 
            SET payment_amount = ?, payment_method = ?, payment_date = ?, status = ?
            WHERE bill_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dsssi", $payment_amount, $payment_method, $payment_date, $status, $bill_id);

    if ($stmt->execute()) {
        // Fetch tenant info for prompt
        $stmt2 = $conn->prepare("SELECT tenant_name, contact_number, guardian_contact FROM tenants WHERE tenant_id = ?");
        $stmt2->bind_param("i", $tenant_id);
        $stmt2->execute();
        $tenant = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        // Only trigger prompt if status is Partial or Settled
        if ($status === 'Partial' || $status === 'Settled') {
            $_SESSION['showPaymentConfirmPrompt'] = true;
            $_SESSION['tenant_name'] = $tenant['tenant_name'] ?? '';
            $_SESSION['guardian_name'] = $tenant['guardian_contact'] ?? ''; // use guardian_contact

            // Auto-send payment confirmation SMS
            $smsHelper = new SMSHelper($conn);
            $smsHelper->setSMSEnabled(SMS_ENABLED);

            // Fetch payment details for SMS
            $stmt3 = $conn->prepare("
                SELECT b.payment_amount, b.payment_method, b.payment_date, b.total_amount, r.room_number
                FROM billing b
                INNER JOIN rooms r ON b.room_id = r.room_id
                WHERE b.bill_id = ?
            ");
            $stmt3->bind_param("i", $bill_id);
            $stmt3->execute();
            $paymentInfo = $stmt3->get_result()->fetch_assoc();
            $stmt3->close();

            if ($paymentInfo) {
                $remaining_balance = $total_amount - $payment_amount;

                // --- HYBRID MESSAGE: Choose format based on amount ---
                $amount_threshold = 1000;
                $use_detailed = ($payment_amount > $amount_threshold || $remaining_balance > $amount_threshold);

                if ($use_detailed) {
                    // DETAILED MESSAGE (for amounts > ₱1,000)
                    $message = "Ben and Sof Dormitory\n";
                    $message .= "Purok 1A, Mati, San Miguel, ZDS\n\n";
                    $message .= "Dear {$tenant['tenant_name']},\n\n";
                    $message .= "PAYMENT CONFIRMATION\n";
                    $message .= str_repeat("-", 30) . "\n";
                    $message .= "Room: {$paymentInfo['room_number']}\n";
                    $message .= "Date: " . date('M d, Y', strtotime($payment_date)) . "\n";
                    $message .= "Paid: PHP " . number_format($payment_amount, 2) . "\n";
                    $message .= "Method: {$payment_method}\n";
                    $message .= "Status: {$status}\n";

                    if ($status === 'Partial') {
                        $message .= "\nBalance: PHP " . number_format($remaining_balance, 2) . "\n";
                    } else {
                        $message .= "\nFully settled!\n";
                    }

                    $message .= "\nThank you for your payment!";

                } else {
                    // SHORT MESSAGE (for amounts ≤ ₱1,000)
                    $message = "Ben & Sof Dorm\n";
                    $message .= "Payment Received!\n\n";
                    $message .= "Room: {$paymentInfo['room_number']}\n";
                    $message .= "Paid: PHP " . number_format($payment_amount, 2) . "\n";
                    $message .= "Method: {$payment_method}\n";

                    if ($status === 'Partial') {
                        $message .= "Balance: PHP " . number_format($remaining_balance, 2) . "\n";
                    } else {
                        $message .= "Status: Settled\n";
                    }

                    $message .= "\nThank you!";
                }

                // Send to tenant
                if (!empty($tenant['contact_number'])) {
                    $smsHelper->sendSMS($tenant['contact_number'], $message, $tenant_id, 'Tenant');
                }

                // Send to guardian
                if (!empty($tenant['guardian_contact'])) {
                    $smsHelper->sendSMS($tenant['guardian_contact'], $message, $tenant_id, 'Guardian');
                }
            }
        }

        // Redirect back to viewbill
        header("Location: view.php?tenant_id=$tenant_id");
        exit;
    } else {
        echo "<script>alert('Error updating payment.'); window.history.back();</script>";
    }
    $stmt->close();
}
?>
