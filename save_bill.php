<?php
// 🔹 Return JSON response always
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php';

try {
    // --- Input handling ---
    $tenant_id = intval($_POST['tenant_id']);
    $room_id = intval($_POST['room_id']);
    $base_rent = floatval($_POST['base_rent']);
    $due_date = $_POST['due_date'];

    // --- Utility fees & amounts ---
    $utility_fees = $_POST['utility_fee'] ?? [];
    $utility_amounts = $_POST['utility_amount'] ?? [];
    // Ensure arrays match in length
    $utility_fees = array_values($utility_fees);
    $utility_amounts = array_map('floatval', array_pad($utility_amounts, count($utility_fees), 0));

    // --- Additional charges & amounts ---
    $add_charges = $_POST['add_charges'] ?? [];
    $add_amounts = $_POST['add_amount'] ?? [];
    $add_charges = array_values($add_charges);
    $add_amounts = array_map('floatval', array_pad($add_amounts, count($add_charges), 0));

    // --- Validate required fields ---
    if (empty($tenant_id) || empty($room_id) || empty($due_date)) {
        throw new Exception("Missing required fields.");
    }

    // --- Prepare insert statement ---
    $stmt = $conn->prepare("
        INSERT INTO billing 
        (tenant_id, room_id, due_date, base_rent, utility_fee, utility_amount, add_charges, add_amount)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    // --- JSON encode arrays ---
    $utility_fees_json = json_encode($utility_fees);
    $utility_amounts_json = json_encode($utility_amounts);
    $add_charges_json = json_encode($add_charges);
    $add_amounts_json = json_encode($add_amounts);

    // --- Bind parameters (corrected types) ---
    // i = int, s = string, d = double
    $stmt->bind_param(
    "iisdssss",
    $tenant_id,            // i
    $room_id,              // i
    $due_date,             // s
    $base_rent,            // d
    $utility_fees_json,    // s
    $utility_amounts_json, // s
    $add_charges_json,     // s
    $add_amounts_json      // s
);

    // --- Execute ---
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'tenant_id' => $tenant_id
        ]);
    } else {
        throw new Exception("Failed to insert billing: " . $stmt->error);
    }

    $stmt->close();
    exit;

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
