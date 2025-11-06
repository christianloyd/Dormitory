<?php
session_start();
include 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Manila');

// --- Get filter values ---
$selected_month = isset($_GET['month']) ? $_GET['month'] : '';
$selected_year = isset($_GET['year']) ? $_GET['year'] : '';
$selected_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';

// --- Build base query ---
$query = "
SELECT 
    t.tenant_name,
    r.room_number,
    b.base_rent,
    b.utility_fee,
    b.utility_amount,
    b.add_charges,
    b.add_amount,
    b.interest,
    b.previous_balance,
    b.previous_credit,
    b.other_amount,
    b.balance,
    b.credit_balance,
    b.status,
    b.payment_method,
    b.payment_amount,
    DATE_FORMAT(b.created_at, '%M %Y') AS billing_period
FROM billing b
JOIN tenants t ON b.tenant_id = t.tenant_id
JOIN rooms r ON b.room_id = r.room_id
WHERE t.status = 'Active'
";

// --- Apply filters ---
if (!empty($selected_month) && !empty($selected_year)) {
    $query .= " AND MONTH(b.created_at) = '$selected_month' AND YEAR(b.created_at) = '$selected_year'";
} elseif (!empty($selected_year)) {
    $query .= " AND YEAR(b.created_at) = '$selected_year'";
}

if (!empty($selected_method)) {
    $query .= " AND b.payment_method = '$selected_method'";
}

$query .= " ORDER BY b.created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Billing Summary Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { 
    margin:0; 
    font-family: Arial,sans-serif; 
    display:flex; 
    background-color:#f2f6f5; 
}
.main-content { 
    margin-left:225px; 
    padding:30px; 
    background-color: #f0f4f3;
    min-height:100vh; 
    width: calc(100% - 225px);
    overflow-x: auto;
}
.table thead th {
    background-color: #5A7D7C !important;
    color: white !important;
    text-align: center;
}
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <h2 class="mb-4" style="font-weight: 600;">Billing Summary Report</h2>

        <!-- Filter Form -->
        <form method="GET" class="row g-3 mb-4 justify-content-center">
            <div class="col-md-3">
                <label for="month" class="form-label">Select Month</label>
                <select name="month" id="month" class="form-select">
                    <option value="">All</option>
                    <?php
                    for ($m = 1; $m <= 12; $m++) {
                        $monthName = date("F", mktime(0, 0, 0, $m, 10));
                        $selected = ($selected_month == $m) ? 'selected' : '';
                        echo "<option value='$m' $selected>$monthName</option>";
                    }
                    ?>
                </select>
            </div>

           <div class="col-md-3">
    <label for="year" class="form-label">Select Year</label>
    <select name="year" id="year" class="form-select" style="max-height: 150px; overflow-y: auto;">
        <option value="">All</option>
        <?php
        for ($y = 2030; $y >= 2014; $y--) {
            $selected = ($selected_year == $y) ? 'selected' : '';
            echo "<option value='$y' $selected>$y</option>";
        }
        ?>
    </select>
</div>

            <div class="col-md-3">
                <label for="payment_method" class="form-label">Payment Method</label>
                <select name="payment_method" id="payment_method" class="form-select">
                    <option value="">All</option>
                    <option value="Cash" <?= ($selected_method == 'Cash') ? 'selected' : '' ?>>Cash</option>
                    <option value="GCash" <?= ($selected_method == 'GCash') ? 'selected' : '' ?>>GCash</option>
                    <option value="Bank Transfer" <?= ($selected_method == 'Bank Transfer') ? 'selected' : '' ?>>Bank Transfer</option>
                </select>
            </div>

            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn w-100" 
                    style="background-color: #5A7D7C; border-color: #5A7D7C; color: white;">
                    Search
                </button>
            </div>
        </form>

        <!-- Billing Summary Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover text-center align-middle">
                <thead>
                    <tr>
                        <th>Tenant Name</th>
                        <th>Room Number</th>
                        <th>Previous Balance</th>
                        <th>Previous Credit</th>
                        <th>Current Balance</th>
                        <th>Credit Balance</th>
                        <th>Billing Period</th>
                        <th>Payment Method</th>
                        <th>Payment Amount</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {

                        // 🔹 Decode JSON and compute totals (same logic as invoice.php)
                        $utility_total = 0;
                        $add_total = 0;
                        $interest = floatval($row['interest'] ?? 0);

                        $utility_amounts = json_decode($row['utility_amount'], true);
                        if (is_array($utility_amounts)) $utility_total = array_sum($utility_amounts);

                        $add_amounts = json_decode($row['add_amount'], true);
                        if (is_array($add_amounts)) $add_total = array_sum($add_amounts);

                        $previous_balance = floatval($row['previous_balance'] ?? 0);
                        $previous_credit = floatval($row['previous_credit'] ?? 0);
                        $other_amount = floatval($row['other_amount'] ?? 0);

                        // Compute total manually
                        $total_amount = floatval($row['base_rent']) 
                                      + $utility_total 
                                      + $add_total 
                                      + $interest 
                                      + $previous_balance 
                                      + $other_amount 
                                      - $previous_credit;

                        echo "<tr>
                            <td>{$row['tenant_name']}</td>
                            <td>{$row['room_number']}</td>
                            <td>₱" . number_format($row['previous_balance'], 2) . "</td>
                            <td>₱" . number_format($row['previous_credit'], 2) . "</td>
                            <td>₱" . number_format($row['balance'], 2) . "</td>
                            <td>₱" . number_format($row['credit_balance'], 2) . "</td>
                            <td>{$row['billing_period']}</td>
                            <td>{$row['payment_method']}</td>
                            <td>₱" . number_format($row['payment_amount'], 2) . "</td>
                            <td>₱" . number_format($total_amount, 2) . "</td>
                            <td>{$row['status']}</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='11' class='text-center text-muted'>No billing records found.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
s