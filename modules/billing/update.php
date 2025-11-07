<?php
/**
 * Billing Module
 * Path: /modules/billing/update.php
 */
require_once "../../includes/auth_check.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Invalid access.");

// --- Retrieve values ---
$bill_id = intval($_POST['bill_id']);
$tenant_id = intval($_POST['tenant_id']);
$room_id = intval($_POST['room_id']);
$base_rent = floatval($_POST['base_rent']);
$due_date = $_POST['due_date'] ?? '';
$payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : null;
$interest = floatval($_POST['interest'] ?? 0);
$payment_amount = floatval($_POST['payment_amount'] ?? 0);
$payment_method = $_POST['payment_method'] ?? '';

// --- Utilities / Additional Charges ---
$utility_fee = $_POST['utility_fee'] ?? [];
$utility_amount = array_map('floatval', $_POST['utility_amount'] ?? []);

$add_charges = $_POST['add_charges'] ?? [];
$add_amount = array_map('floatval', $_POST['add_amount'] ?? []);

// --- JSON encode ---
$utility_fee_json = json_encode($utility_fee);
$utility_amount_json = json_encode($utility_amount);
$add_charges_json = json_encode($add_charges);
$add_amount_json = json_encode($add_amount);

// --- Update billing safely ---
$sql = "UPDATE billing SET 
        tenant_id = ?, 
        room_id = ?, 
        due_date = ?, 
        payment_date = ?, 
        base_rent = ?, 
        utility_fee = ?, 
        utility_amount = ?, 
        add_charges = ?, 
        add_amount = ?, 
        interest = ?, 
        payment_amount = ?, 
        payment_method = ?
    WHERE bill_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "iissdssssddsi",
    $tenant_id,
    $room_id,
    $due_date,
    $payment_date,
    $base_rent,
    $utility_fee_json,
    $utility_amount_json,
    $add_charges_json,
    $add_amount_json,
    $interest,
    $payment_amount,
    $payment_method,
    $bill_id
);

if ($stmt->execute()) {
    header("Location: view.php?tenant_id=".$tenant_id);
    exit();
} else {
    echo "Error updating bill: " . $stmt->error;
}
?>
