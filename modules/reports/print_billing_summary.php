<?php
require_once "../../includes/auth_check.php";
require_once __DIR__ . '/../../helpers/BillingItems.php';

$hdrRow = $conn->query("SELECT setting_value FROM settings WHERE setting_name='header_image'")->fetch_assoc();
$hdrPic = $hdrRow ? BASE_PATH . '/' . $hdrRow['setting_value'] : BASE_PATH . '/uploads/default_header.png';

$filter_month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$filter_year  = isset($_GET['year'])  ? intval($_GET['year'])  : 0;
$filterLabel  = '';
if ($filter_month > 0) $filterLabel .= date("F", mktime(0,0,0,$filter_month,1));
if ($filter_year  > 0) $filterLabel .= ($filterLabel ? ' ' : '') . $filter_year;
if (!$filterLabel) $filterLabel = 'All Records';

$sql = "
    SELECT 
        b.bill_id AS invoice_id,
        t.tenant_name,
        r.room_number,
        b.due_date,
        b.payment_date,
        b.base_rent,
        b.interest,
        b.previous_balance,
        b.previous_credit,
        b.other_amount,
        b.total_amount,
        b.payment_amount,
        b.balance,
        b.credit_balance,
        b.status
    FROM billing b
    LEFT JOIN tenants t ON b.tenant_id = t.tenant_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE t.status = 'Active'
";

$filters = [];
if ($filter_month > 0) $filters[] = "MONTH(b.due_date) = $filter_month";
if ($filter_year > 0)  $filters[] = "YEAR(b.due_date) = $filter_year";
if ($filters) {
    $sql .= " AND " . implode(" AND ", $filters);
}
$sql .= " ORDER BY t.tenant_name ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Print - Billing Summary Report</title>
<style>
body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background: #fff; color: #000; font-size: 10pt; }
* { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
@page { margin: 15mm; size: A4 portrait; }

.print-header { display: flex; align-items: center; gap: 18px; padding-bottom: 12px; border-bottom: 2.5px solid #2c3e50; margin-bottom: 20px; }
.print-header img { width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 2px solid #2c3e50; }
.print-header .ph-title { font-size: 1.4rem; font-weight: 700; color: #2c3e50; margin: 0; }
.print-header .ph-sub   { font-size: 0.9rem; color: #555; margin-top: 3px; }

.report-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.report-table th { background-color: #5A7D7C !important; color: #fff !important; padding: 8px; text-align: center; border: 1px solid #ddd; font-weight: bold; }
.report-table td { padding: 8px; text-align: center; border: 1px solid #ddd; vertical-align: middle; }
.badge-settled { color: #155724; font-weight: bold; }
.badge-partial { color: #856404; font-weight: bold; }
.badge-pending { color: #721c24; font-weight: bold; }
</style>
</head>
<body onload="window.print();">

<div class="print-header">
    <img src="<?= htmlspecialchars($hdrPic) ?>" alt="Logo">
    <div>
        <h1 class="ph-title">Billing Summary Report</h1>
        <div class="ph-sub">Period: <?= htmlspecialchars($filterLabel) ?> &nbsp;|&nbsp; Printed: <?= date('F d, Y \a\t h:i A') ?></div>
    </div>
</div>

<table class="report-table">
    <thead>
        <tr>
            <th>Tenant</th>
            <th>Credit Balance</th>
            <th>Balance</th>
            <th>Payment Amount</th>
            <th>Total Amount</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()):
                $billId = intval($row['invoice_id']);
                $interest = floatval($row['interest'] ?? 0);
                $utilityItems = getBillingUtilityItems($conn, $billId);
                $additionalItems = getBillingAdditionalItems($conn, $billId);
                $utility_total = sumBillingItems($utilityItems);
                $add_total = sumBillingItems($additionalItems);
                $previous_balance = floatval($row['previous_balance'] ?? 0);
                $previous_credit = floatval($row['previous_credit'] ?? 0);
                $other_amount = floatval($row['other_amount'] ?? 0);
                $total_amount = floatval($row['base_rent']) + $utility_total + $add_total + $interest + $previous_balance + $other_amount - $previous_credit;
                $payment_amount = floatval($row['payment_amount'] ?? 0);
                $credit_balance = max(0, $payment_amount - $total_amount);
                $balance = max(0, $total_amount - $payment_amount);
                
                if ($payment_amount >= $total_amount && $total_amount > 0) {
                    $badge = '<span class="badge-settled">Settled</span>';
                } elseif ($payment_amount > 0 && $payment_amount < $total_amount) {
                    $badge = '<span class="badge-partial">Partial</span>';
                } elseif ($total_amount > 0) {
                    $badge = '<span class="badge-pending">Pending</span>';
                } else {
                    $badge = '-';
                }
            ?>
            <tr>
                <td><?= htmlspecialchars($row['tenant_name']) ?></td>
                <td>₱<?= number_format($credit_balance, 2) ?></td>
                <td>₱<?= number_format($balance, 2) ?></td>
                <td>₱<?= number_format($payment_amount, 2) ?></td>
                <td>₱<?= number_format($total_amount, 2) ?></td>
                <td><?= $badge ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center; padding: 20px; color: #555;">No data found for selected filter.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
