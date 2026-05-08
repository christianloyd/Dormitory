<?php
require_once "../../includes/auth_check.php";
require_once __DIR__ . '/../../helpers/BillingItems.php';

$hdrRow = $conn->query("SELECT setting_value FROM settings WHERE setting_name='header_image'")->fetch_assoc();
$hdrPic = $hdrRow ? BASE_PATH . '/' . $hdrRow['setting_value'] : BASE_PATH . '/uploads/default_header.png';

$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$selectedYear  = isset($_GET['year'])  ? intval($_GET['year'])  : date('Y');
$monthName = date("F", mktime(0, 0, 0, $selectedMonth, 10));
$paidTenants = [];

$query = "
    SELECT b.*, t.tenant_name, t.profile_pic, r.room_number
    FROM billing b
    LEFT JOIN tenants t ON b.tenant_id = t.tenant_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE MONTH(b.due_date) = $selectedMonth 
      AND YEAR(b.due_date) = $selectedYear
      AND t.status = 'Active'
    ORDER BY b.due_date ASC
";

$res = $conn->query($query);
if (!$res) die("Query error: " . $conn->error);

foreach ($res as $row) {
    $billId = intval($row['bill_id']);
    $interest = floatval($row['interest'] ?? 0);
    $utilityItems    = getBillingUtilityItems($conn, $billId);
    $additionalItems = getBillingAdditionalItems($conn, $billId);
    $utility_total = sumBillingItems($utilityItems);
    $add_total     = sumBillingItems($additionalItems);
    $previous_balance = floatval($row['previous_balance'] ?? 0);
    $previous_credit  = floatval($row['previous_credit']  ?? 0);
    $other_amount     = floatval($row['other_amount']     ?? 0);
    $total_amount = floatval($row['total_amount'] ?? (floatval($row['base_rent']) + $utility_total + $add_total + $interest + $previous_balance + $other_amount - $previous_credit));
    $payment_amount = floatval($row['payment_amount'] ?? 0);
    $credit_balance = floatval($row['credit_balance'] ?? max(0, $payment_amount - $total_amount));
    $balance        = floatval($row['balance'] ?? max(0, $total_amount - $payment_amount));
    if ($payment_amount >= $total_amount) {
        $visible_status = 'Settled';
    } elseif ($payment_amount > 0) {
        $visible_status = 'Partial';
    } else {
        continue;
    }
    $row['total']   = $total_amount;
    $row['balance'] = $balance;
    $row['credit']  = $credit_balance;
    $row['status']  = $visible_status;
    $paidTenants[] = $row;
}

$totalPayments = 0;
foreach ($paidTenants as $p) $totalPayments += floatval($p['payment_amount']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Print - Monthly Payments</title>
<style>
body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background: #fff; color: #000; font-size: 10pt; }
* { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
@page { margin: 15mm; size: A4 landscape; }

.print-header { display: flex; align-items: center; gap: 18px; padding-bottom: 12px; border-bottom: 2.5px solid #2c3e50; margin-bottom: 20px; }
.print-header img { width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 2px solid #2c3e50; }
.print-header .ph-title { font-size: 1.4rem; font-weight: 700; color: #2c3e50; margin: 0; }
.print-header .ph-sub   { font-size: 0.9rem; color: #555; margin-top: 3px; }

.report-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.report-table th { background-color: #5A7D7C !important; color: #fff !important; padding: 8px; text-align: center; border: 1px solid #ddd; font-weight: bold; }
.report-table td { padding: 8px; text-align: center; border: 1px solid #ddd; vertical-align: middle; }
.badge-settled { color: #155724; font-weight: bold; }
.badge-partial { color: #856404; font-weight: bold; }
.summary-box { padding: 10px; border: 1px solid #2c3e50; font-weight: 700; font-size: 1rem; float: right; margin-top: 10px; }
</style>
</head>
<body onload="window.print();">

<div class="print-header">
    <img src="<?= htmlspecialchars($hdrPic) ?>" alt="Logo">
    <div>
        <h1 class="ph-title">Monthly Payments Report</h1>
        <div class="ph-sub"><?= $monthName . ' ' . $selectedYear ?> &nbsp;|&nbsp; Printed: <?= date('F d, Y \a\t h:i A') ?></div>
    </div>
</div>

<?php if (count($paidTenants) > 0): ?>
<table class="report-table">
    <thead>
        <tr>
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
    <?php foreach ($paidTenants as $row): ?>
    <tr>
        <td><?= htmlspecialchars($row['tenant_name']) ?></td>
        <td><?= htmlspecialchars($row['room_number']) ?></td>
        <td><?= date("M d, Y", strtotime($row['due_date'])) ?></td>
        <td><?= number_format($row['balance'], 2) ?></td>
        <td><?= number_format($row['credit'], 2) ?></td>
        <td><?= !empty($row['payment_date']) ? date("M d, Y", strtotime($row['payment_date'])) : '-' ?></td>
        <td><?= number_format($row['total'], 2) ?></td>
        <td><?= number_format($row['payment_amount'], 2) ?></td>
        <td><span class="<?= $row['status'] == 'Partial' ? 'badge-partial' : 'badge-settled' ?>"><?= $row['status'] ?></span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="summary-box">
    Total Payments: ₱<?= number_format($totalPayments, 2) ?>
</div>
<?php else: ?>
    <div style="text-align:center; padding: 30px; color: #555;">No payments found for <?= $monthName . ' ' . $selectedYear ?>.</div>
<?php endif; ?>

</body>
</html>
