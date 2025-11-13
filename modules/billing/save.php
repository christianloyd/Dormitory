<?php
// 🔹 Return JSON response always
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/auth_check.php';
require_once __DIR__ . '/../../helpers/BillingLock.php';
require_once __DIR__ . '/../../helpers/BillingItems.php';

try {
    // --- Input handling ---
    $tenant_id = intval($_POST['tenant_id']);
    $room_id = intval($_POST['room_id']);
    $base_rent = floatval($_POST['base_rent']);
    $due_date = $_POST['due_date'];

    if (isBillingLockedByDate($due_date)) {
        throw new Exception("This billing month is locked and cannot accept new records.");
    }

    // --- Utility fees & amounts ---
    $utility_fees_raw = $_POST['utility_fee'] ?? [];
    $utility_amounts_raw = $_POST['utility_amount'] ?? [];
    $utility_fees = [];
    $utility_amounts = [];

    foreach ($utility_fees_raw as $idx => $fee) {
        $fee = trim((string)$fee);
        $amount = isset($utility_amounts_raw[$idx]) ? floatval($utility_amounts_raw[$idx]) : 0;

        if ($fee === '' && $amount == 0) {
            continue; // skip empty pairs
        }

        $utility_fees[] = $fee;
        $utility_amounts[] = $amount;
    }

    // --- Additional charges & amounts ---
    $add_charges_raw = $_POST['add_charges'] ?? [];
    $add_amounts_raw = $_POST['add_amount'] ?? [];
    $add_charges = [];
    $add_amounts = [];

    foreach ($add_charges_raw as $idx => $charge) {
        $charge = trim((string)$charge);
        $amount = isset($add_amounts_raw[$idx]) ? floatval($add_amounts_raw[$idx]) : 0;

        if ($charge === '' && $amount == 0) {
            continue;
        }

        $add_charges[] = $charge;
        $add_amounts[] = $amount;
    }

    // --- Validate required fields ---
    if (empty($tenant_id) || empty($room_id) || empty($due_date)) {
        throw new Exception("Missing required fields.");
    }

    $transactionStarted = false;
    $conn->begin_transaction();
    $transactionStarted = true;

    $stmt = $conn->prepare("
        INSERT INTO billing 
        (tenant_id, room_id, due_date, base_rent)
        VALUES (?, ?, ?, ?)
    ");

    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param(
        "iisd",
        $tenant_id,
        $room_id,
        $due_date,
        $base_rent
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to insert billing: " . $stmt->error);
    }

    $bill_id = $stmt->insert_id;
    $stmt->close();

    $utilityItems = [];
    foreach ($utility_fees as $idx => $fee) {
        $utilityItems[] = [
            'label' => $fee,
            'amount' => $utility_amounts[$idx] ?? 0
        ];
    }

    $additionalItems = [];
    foreach ($add_charges as $idx => $charge) {
        $additionalItems[] = [
            'label' => $charge,
            'amount' => $add_amounts[$idx] ?? 0
        ];
    }

    replaceBillingUtilityItems($conn, $bill_id, $utilityItems);
    replaceBillingAdditionalItems($conn, $bill_id, $additionalItems);

    $conn->commit();
    $transactionStarted = false;

    echo json_encode([
        'success' => true,
        'tenant_id' => $tenant_id
    ]);
    exit;

} catch (Exception $e) {
    if (isset($transactionStarted) && $transactionStarted) {
        $conn->rollback();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
