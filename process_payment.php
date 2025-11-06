<?php
include 'db.php';
session_start();

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
$stmt2 = $conn->prepare("SELECT tenant_name, guardian_contact FROM tenants WHERE tenant_id = ?");
$stmt2->bind_param("i", $tenant_id);
$stmt2->execute();
$tenant = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

// Only trigger prompt if status is Partial or Settled
if ($status === 'Partial' || $status === 'Settled') {
    $_SESSION['showPaymentConfirmPrompt'] = true;
    $_SESSION['tenant_name'] = $tenant['tenant_name'] ?? '';
    $_SESSION['guardian_name'] = $tenant['guardian_contact'] ?? ''; // use guardian_contact
}


        // Redirect back to viewbill
        header("Location: viewbill.php?tenant_id=$tenant_id");
        exit;
    } else {
        echo "<script>alert('Error updating payment.'); window.history.back();</script>";
    }
    $stmt->close();
}
?>
