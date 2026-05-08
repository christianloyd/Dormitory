<?php
/**
 * Billing Module
 * Path: /modules/billing/view_all.php
 */
require_once "../../includes/auth_check.php";

if (!isset($_GET['tenant_id'])) {
    echo "Tenant not selected.";
    exit();
}

$tenant_id = intval($_GET['tenant_id']);

// Fetch tenant info
$tenant_sql = "SELECT t.tenant_name, r.room_number, r.price AS base_rent
               FROM tenants t
               JOIN rooms r ON t.room_id = r.room_id
               WHERE t.tenant_id = $tenant_id";
$tenant_result = $conn->query($tenant_sql);

if ($tenant_result->num_rows == 0) {
    echo "Tenant not found.";
    exit();
}

$tenant = $tenant_result->fetch_assoc();

// Sample bills for demonstration
$bills = [
    [
        'bill_date' => '2025-08-08',
        'description' => 'Electricity and Water',
        'other_amount' => 500,
        'base_rent' => $tenant['base_rent'],
        'previous_balance' => 0,
        'status' => 'Unpaid'
    ],
    [
        'bill_date' => '2025-09-08',
        'description' => 'Internet Fee',
        'other_amount' => 300,
        'base_rent' => $tenant['base_rent'],
        'previous_balance' => 500,
        'status' => 'Unpaid'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bills - <?php echo htmlspecialchars($tenant['tenant_name']); ?></title>
    <link rel="stylesheet" href="../../css/view_bills.css">

</head>
<body>
    <h1>Billing Records for <?php echo htmlspecialchars($tenant['tenant_name']); ?></h1>
    <p>Room: <?php echo htmlspecialchars($tenant['room_number']); ?> | Base Rent: ₱<?php echo number_format($tenant['base_rent'],2); ?></p>

<br>
<a class="btn-add" href="add.php?tenant_id=<?php echo $tenant_id; ?>">Add Billing</a>


    <table>
        <thead>
            <tr>
                <th>Bill Date</th>
                <th>Description / Other Charges</th>
                <th>Base Rent</th>
                <th>Other Expenses Amount</th>
                <th>Previous Balance</th>
                <th>Total Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bills as $bill): 
                $total = $bill['base_rent'] + $bill['other_amount'] + $bill['previous_balance'];
            ?>
            <tr>
                <td><?php echo date("F Y", strtotime($bill['bill_date'])); ?></td>
                <td><?php echo htmlspecialchars($bill['description']); ?></td>
                <td>₱<?php echo number_format($bill['base_rent'],2); ?></td>
                <td>₱<?php echo number_format($bill['other_amount'],2); ?></td>
                <td>₱<?php echo number_format($bill['previous_balance'],2); ?></td>
                <td class="total">₱<?php echo number_format($total,2); ?></td>
                <td><?php echo $bill['status']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>



     <a class="btn-back" href="billing.php">&larr; Back to Billing</a>
   

</body>
</html>
