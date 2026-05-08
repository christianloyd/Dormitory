<?php
/**
 * Billing Module
 * Path: /modules/billing/update.php
 */
require_once "../../includes/auth_check.php";
require_once __DIR__ . '/../../helpers/BillingLock.php';
require_once __DIR__ . '/../../helpers/BillingItems.php';
require_once __DIR__ . '/../../helpers/BillingCalculator.php';

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
$utility_fees_raw = $_POST['utility_fee'] ?? [];
$utility_amounts_raw = $_POST['utility_amount'] ?? [];
$utilityItems = [];

foreach ($utility_fees_raw as $idx => $fee) {
    $fee = trim((string)$fee);
    $amount = isset($utility_amounts_raw[$idx]) ? floatval($utility_amounts_raw[$idx]) : 0;
    if ($fee === '' && $amount == 0) {
        continue;
    }
    $utilityItems[] = [
        'label' => $fee,
        'amount' => $amount
    ];
}

$add_charges_raw = $_POST['add_charges'] ?? [];
$add_amounts_raw = $_POST['add_amount'] ?? [];
$additionalItems = [];

foreach ($add_charges_raw as $idx => $charge) {
    $charge = trim((string)$charge);
    $amount = isset($add_amounts_raw[$idx]) ? floatval($add_amounts_raw[$idx]) : 0;
    if ($charge === '' && $amount == 0) {
        continue;
    }
    $additionalItems[] = [
        'label' => $charge,
        'amount' => $amount
    ];
}

// --- Update billing safely ---
$utilityTotal = sumBillingItems($utilityItems);
$additionalTotal = sumBillingItems($additionalItems);

// Fetch existing payment info to prevent overwriting payments
$stmt = $conn->prepare("SELECT payment_amount, payment_method, payment_date, previous_balance, previous_credit, other_amount FROM billing WHERE bill_id = ?");
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$existingBill = $stmt->get_result()->fetch_assoc();
$stmt->close();

$payment_amount = (float)($existingBill['payment_amount'] ?? 0);
$payment_method_db = $existingBill['payment_method'] ?? '';
$payment_date_db = $existingBill['payment_date'] ?? null;
$previous_balance = (float)($existingBill['previous_balance'] ?? 0);
$previous_credit = (float)($existingBill['previous_credit'] ?? 0);
$other_amount = (float)($existingBill['other_amount'] ?? 0);

$grossTotal = $base_rent + $interest + $utilityTotal + $additionalTotal + $previous_balance + $other_amount;
$appliedCredit = min($previous_credit, $grossTotal);
$amountDueBeforePayment = round($grossTotal - $appliedCredit, 2);
if ($amountDueBeforePayment < 0) $amountDueBeforePayment = 0.0;

$carriedPreviousCredit = round(max(0.0, $previous_credit - $grossTotal), 2);
$remainingBalance = max(0.0, $amountDueBeforePayment - $payment_amount);
$newCreditFromPayment = max(0.0, $payment_amount - $amountDueBeforePayment);
$creditBalance = $carriedPreviousCredit + $newCreditFromPayment;

$remainingBalance = round($remainingBalance, 2);
$creditBalance = round($creditBalance, 2);

if ($remainingBalance <= 0.009) {
    $status = 'Settled';
    $remainingBalance = 0.0;
} elseif ($payment_amount > 0) {
    $status = 'Partial';
} else {
    $status = 'Pending';
}

$conn->begin_transaction();

$sql = "UPDATE billing SET 
        tenant_id = ?, 
        room_id = ?, 
        due_date = ?, 
        base_rent = ?, 
        interest = ?, 
        total_amount = ?,
        balance = ?,
        credit_balance = ?,
        status = ?
    WHERE bill_id = ?";

if (isBillingRecordLocked($conn, $bill_id)) {
    Session::setMessage('This billing record is locked and cannot be edited.', 'danger');
    header("Location: view.php?tenant_id=" . $tenant_id);
    exit();
}

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "iisdddddsi",
    $tenant_id,
    $room_id,
    $due_date,
    $base_rent,
    $interest,
    $amountDueBeforePayment,
    $remainingBalance,
    $creditBalance,
    $status,
    $bill_id
);

if ($stmt->execute()) {
    $stmt->close();
    replaceBillingUtilityItems($conn, $bill_id, $utilityItems);
    replaceBillingAdditionalItems($conn, $bill_id, $additionalItems);
    
    // Cascade carry over to subsequent bills
    BillingCalculator::syncNextBillCarryOver($conn, $tenant_id, $due_date, $remainingBalance, $creditBalance);
    
    $conn->commit();
    header("Location: view.php?tenant_id=".$tenant_id);
    exit();
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->rollback();
    echo "Error updating bill: " . $error;
}
?>
