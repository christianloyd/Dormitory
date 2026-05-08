<?php
require_once "../../includes/auth_check.php";
require_once "../../config/db.php";
require_once __DIR__ . '/../../helpers/TenantAssignments.php';

// Fetch dorm header image for print
$hdrRow = $conn->query("SELECT setting_value FROM settings WHERE setting_name='header_image'")->fetch_assoc();
$hdrPic = $hdrRow ? BASE_PATH . '/' . $hdrRow['setting_value'] : BASE_PATH . '/uploads/default_header.png';

// Capture filters from GET
$filter_month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$filter_year  = isset($_GET['year']) ? intval($_GET['year']) : 0;

$where = ["TRIM(b.status) IN ('Pending','Unpaid')"];
if ($filter_month > 0) $where[] = "MONTH(b.due_date) = $filter_month";
if ($filter_year > 0)  $where[] = "YEAR(b.due_date) = $filter_year";

$where_sql = count($where) ? " AND " . implode(" AND ", $where) : "";

$sql = "
SELECT 
    t.tenant_id,
    t.tenant_name,
    GROUP_CONCAT(DISTINCT r.room_number ORDER BY r.room_number SEPARATOR ', ') AS billed_rooms,
    SUM(b.base_rent + b.interest + b.previous_balance - b.previous_credit
        + IFNULL(ai.total,0) + IFNULL(ui.total,0)) AS total_amount,
    SUM(b.payment_amount) AS payment_amount,
    SUM((b.base_rent + b.interest + b.previous_balance - b.previous_credit
        + IFNULL(ai.total,0) + IFNULL(ui.total,0)) - b.payment_amount) AS balance,
    MIN(b.due_date) AS due_date,
    MAX(b.payment_date) AS payment_date,
    GROUP_CONCAT(DISTINCT b.status SEPARATOR ', ') AS status
FROM billing b
INNER JOIN tenants t ON t.tenant_id = b.tenant_id
INNER JOIN rooms r ON r.room_id = b.room_id
LEFT JOIN (
    SELECT bill_id, SUM(amount) AS total 
    FROM billing_additional_items 
    GROUP BY bill_id
) ai ON ai.bill_id = b.bill_id
LEFT JOIN (
    SELECT bill_id, SUM(amount) AS total 
    FROM billing_utility_items 
    GROUP BY bill_id
) ui ON ui.bill_id = b.bill_id
WHERE t.status='Active' $where_sql
GROUP BY t.tenant_id, t.tenant_name
ORDER BY balance DESC
";

$result = $conn->query($sql);
$rows = [];
$tenantIds = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['tenant_id'] = (int)$row['tenant_id'];
        $rows[] = $row;
        $tenantIds[] = $row['tenant_id'];
    }
}

$assignmentsByTenant = TenantAssignments::getAssignmentsForTenants($conn, $tenantIds);

$monthName = $filter_month > 0 ? date("F", mktime(0, 0, 0, $filter_month, 10)) : 'All Months';
$yearName = $filter_year > 0 ? $filter_year : 'All Years';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Print - Pending Payment Report</title>
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
</style>
</head>
<body onload="window.print();">

<div class="print-header">
    <img src="<?= htmlspecialchars($hdrPic) ?>" alt="Logo">
    <div>
        <h1 class="ph-title">Pending Payment Report</h1>
        <div class="ph-sub"><?= $monthName ?> <?= $yearName ?> &nbsp;|&nbsp; Printed: <?= date('F d, Y \a\t h:i A') ?></div>
    </div>
</div>

<table class="report-table">
    <thead>
        <tr>
            <th>Room Number</th>
            <th>Tenant</th>
            <th>Total Amount</th>
            <th>Payment Amount</th>
            <th>Balance</th>
            <th>Due Date</th>
            <th>Payment Date</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
<?php
$total_pending = 0;
if (!empty($rows)) {
    foreach ($rows as $row) {
        $tenantId = $row['tenant_id'];
        $assignments = $assignmentsByTenant[$tenantId] ?? [];
        if (!empty($assignments)) {
            $roomParts = [];
            foreach ($assignments as $assignment) {
                $deckLabel = $assignment['deck_type'] ? ' — ' . $assignment['deck_type'] : '';
                $roomParts[] = htmlspecialchars($assignment['room_number'] . $deckLabel, ENT_QUOTES, 'UTF-8');
            }
            $roomSummary = implode('<br>', $roomParts);
        } else {
            $roomSummary = htmlspecialchars($row['billed_rooms'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        }

        echo '<tr>';
        echo '<td>'.$roomSummary.'</td>';
        echo '<td>'.htmlspecialchars($row['tenant_name']).'</td>';
        echo '<td>₱'.number_format($row['total_amount'],2).'</td>';
        echo '<td>₱'.number_format($row['payment_amount'],2).'</td>';
        echo '<td>₱'.number_format($row['balance'],2).'</td>';
        echo '<td>'.($row['due_date'] ?? '-').'</td>';
        echo '<td>'.($row['payment_date'] ?? '-').'</td>';
        echo '<td>'.htmlspecialchars($row['status']).'</td>';
        echo '</tr>';

        $total_pending += $row['balance'];
    }
} else {
    echo '<tr><td colspan="8" style="text-align:center;color:#555;">No tenants with pending payments found.</td></tr>';
}
?>
    </tbody>
</table>

<div style="font-weight:700;font-size:1rem;float:right;padding:10px;border:1px solid #2c3e50;margin-top:10px;">
    Total Pending Balance: ₱<?= number_format($total_pending, 2) ?>
</div>

</body>
</html>
