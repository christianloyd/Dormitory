<?php
session_start();
require_once '../../includes/auth_check.php';

// Security check
}

// Only POST request allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid access.");
}

$bill_id = intval($_POST['bill_id']);
$tenant_id = intval($_POST['tenant_id']);
$room_id = intval($_POST['room_id']);
$base_rent = floatval($_POST['base_rent']);
$utility_fee = trim($_POST['utility_fee']);
$utility_amount = floatval($_POST['utility_amount']);
$add_charges = trim($_POST['add_charges']);
$add_amount = floatval($_POST['add_amount']);
$payment_amount = floatval($_POST['payment_amount']);
$payment_method = trim($_POST['payment_method']);
$due_date = $_POST['due_date'];
$interest = isset($_POST['interest']) ? floatval($_POST['interest']) : 0; // ✅ NEW FIELD

// ✅ Step 1: Get existing bill for carry-over values
$sql = "SELECT previous_balance, previous_credit FROM billing WHERE bill_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$previous_balance = $row['previous_balance'];
$previous_credit  = $row['previous_credit'];

// ✅ Step 2: Compute new total (added interest)
$current_total = $base_rent + $utility_amount + $add_amount + $interest + $previous_balance - $previous_credit;

// ✅ Step 3: Compute balance & credit
$balance = max(0, $current_total - $payment_amount);
$credit_balance = max(0, $payment_amount - $current_total);

// ✅ Step 4: Status
if ($payment_amount >= $current_total) {
    $status = "Settled";
} elseif ($payment_amount > 0 && $payment_amount < $current_total) {
    $status = "Partial";
} else {
    $status = "Pending Payment";
}

// ✅ Step 5: Update bill (current record only)
$update_sql = "UPDATE billing SET 
    tenant_id = ?, 
    room_id = ?, 
    base_rent = ?, 
    utility_fee = ?, 
    utility_amount = ?, 
    add_charges = ?, 
    add_amount = ?, 
    interest = ?, 
    payment_amount = ?, 
    payment_method = ?, 
    due_date = ?, 
    status = ?, 
    balance = ?, 
    credit_balance = ? 
    WHERE bill_id = ?";

$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param(
    "iidsdsddddsssdi",
    $tenant_id,
    $room_id,
    $base_rent,
    $utility_fee,
    $utility_amount,
    $add_charges,
    $add_amount,
    $interest,
    $payment_amount,
    $payment_method,
    $due_date,
    $status,
    $balance,
    $credit_balance,
    $bill_id
);

if ($update_stmt->execute()) {
    header("Location: view.php?tenant_id=" . $tenant_id);
    exit();
} else {
    echo "Error updating bill: " . $update_stmt->error;
}
?>
