<?php

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

require_once "../../includes/auth_check.php";

// Get selected month & year
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$selectedYear  = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

$monthName = date("F", mktime(0,0,0,$selectedMonth,10));
$paidTenants = [];


// Query billing for selected month/year
$query = "
    SELECT b.*, t.tenant_name, t.profile_pic, r.room_number
    FROM billing b
    LEFT JOIN tenants t ON b.tenant_id = t.tenant_id
    LEFT JOIN rooms r ON t.room_id = r.room_id
    WHERE MONTH(b.due_date) = $selectedMonth 
      AND YEAR(b.due_date) = $selectedYear
      AND t.status = 'Active'
    ORDER BY b.due_date ASC
";

$res = $conn->query($query);
if(!$res) die("Query error: ".$conn->error);

// Process each tenant
foreach($res as $row) {
    $utility_total = 0;
    $add_total = 0;
    $interest = floatval($row['interest'] ?? 0);

    $utility_amounts = json_decode($row['utility_amount'], true);
    if(is_array($utility_amounts)) $utility_total = array_sum($utility_amounts);

    $add_amounts = json_decode($row['add_amount'], true);
    if(is_array($add_amounts)) $add_total = array_sum($add_amounts);

    $previous_balance = floatval($row['previous_balance'] ?? 0);
    $previous_credit  = floatval($row['credit_balance'] ?? 0);
    $other_amount     = floatval($row['other_amount'] ?? 0);

    $total_amount = floatval($row['base_rent']) + $utility_total + $add_total + $interest + $previous_balance + $other_amount - $previous_credit;
    $payment_amount = floatval($row['payment_amount'] ?? 0);
    $credit_balance = max(0, $payment_amount - $total_amount);
    $balance = max(0, $total_amount - $payment_amount);

    // Determine status
    if($payment_amount >= $total_amount) {
        $visible_status = 'Settled';
    } elseif($payment_amount > 0) {
        $visible_status = 'Partial';
    } else {
        continue; // Skip pending
    }

    $row['total'] = $total_amount;
    $row['balance'] = $balance;
    $row['credit'] = $credit_balance;
    $row['status'] = $visible_status;
    $paidTenants[] = $row;
}

// Compute total payments
$totalPayments = 0;
foreach($paidTenants as $p) $totalPayments += floatval($p['payment_amount']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Monthly Payments</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { margin:0; font-family: Arial,sans-serif; display:flex; background-color: #f6f7f6; }
.main-content { margin-left:225px; padding:30px; background-color:#f6f7f6; min-height:100vh; width:calc(100% - 225px); }
.profile-pic { width:40px; height:40px; object-fit:cover; border-radius:50%; border:1.5px solid #5A7D7C; }
table th, table td { vertical-align: middle; text-align:center; font-size:14px; white-space:nowrap; }
.btn-login { background-color:#5A7D7C; color:white; border:none; border-radius:6px; padding:6px 10px; cursor:pointer; transition: background 0.3s; margin-right:10px; }
.btn-login:hover { background-color:#496766; }
.status-partial { background-color: #FFF3CD !important; color: #856404 !important; font-weight:bold; border-radius:4px; padding:5px 10px; }
.status-settled { background-color: #D4EDDA !important; color: #155724 !important; font-weight:bold; border-radius:4px; padding:5px 10px; }
.total-payments-summary { margin-top:15px; font-weight:bold; font-size:16px; text-align:right; padding:10px 20px; border:1px solid #ccc; border-radius:6px; background-color:#f8f9fa; width:fit-content; margin-left:auto; margin-right:0; }
</style>
</head>
<body>
<?php include '../../sidebar.php'; ?>

<div class="main-content">
    <!-- Header with Month/Year selector -->
    <div class="d-flex justify-content-between align-items-center mt-0">
        <h2>Monthly Payments - <?= $monthName . " " . $selectedYear; ?></h2>
        <form class="d-flex" method="get">
            <select name="month" class="form-select me-2">
                <?php for($m=1;$m<=12;$m++): ?>
                    <option value="<?= $m ?>" <?= $m==$selectedMonth?'selected':'' ?>><?= date("F", mktime(0,0,0,$m,1)) ?></option>
                <?php endfor; ?>
            </select>
            <select name="year" class="form-select me-2">
                <?php for($y=2025;$y<=2050;$y++): ?>
                    <option value="<?= $y ?>" <?= $y==$selectedYear?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn btn-success">View</button>
        </form>
    </div>
    <hr>

    <!-- Payments Table -->
    <?php if(count($paidTenants) > 0): ?>
    <table class="table table-bordered table-striped">
        <thead class="table-info">
            <tr>
                <th>Profile</th>
                <th>Name</th>
                <th>Room</th>
                <th>Due Date</th>
                <th>Balance</th>
                <th>Credit</th>
                <th>Payment Date</th>
                <th>Total Amount</th>
                <th>Payment Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($paidTenants as $row): ?>
        <tr>
            <td><?php if(!empty($row['profile_pic'])): ?><img src="<?= htmlspecialchars($row['profile_pic']) ?>" class="profile-pic"><?php else: ?>-<?php endif; ?></td>
            <td><?= htmlspecialchars($row['tenant_name']) ?></td>
            <td><?= htmlspecialchars($row['room_number']) ?></td>
            <td><?= date("M d, Y", strtotime($row['due_date'])) ?></td>
            <td>₱<?= number_format($row['balance'],2) ?></td>
            <td>₱<?= number_format($row['credit'],2) ?></td>
            <td><?= !empty($row['payment_date']) ? date("M d, Y", strtotime($row['payment_date'])) : '-' ?></td>
            <td>₱<?= number_format($row['total'],2) ?></td>
            <td>₱<?= number_format($row['payment_amount'],2) ?></td>
            <td class="<?= $row['status']=='Partial'?'status-partial':'status-settled' ?>"><?= $row['status'] ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Total Payments at bottom-right -->
    <div class="total-payments-summary">
        Total Payments: ₱<?= number_format($totalPayments,2) ?>
    </div>
    <?php else: ?>
    <p>No payments found for this month.</p>
    <?php endif; ?>
</div>
</body>
</html>
