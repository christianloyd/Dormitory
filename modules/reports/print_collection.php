<?php
require_once "../../includes/auth_check.php";

$hdrRow = $conn->query("SELECT setting_value FROM settings WHERE setting_name='header_image'")->fetch_assoc();
$hdrPic = $hdrRow ? BASE_PATH . '/' . $hdrRow['setting_value'] : BASE_PATH . '/uploads/default_header.png';

$tenant_filter = $_GET['tenant']    ?? '';
$from_date     = $_GET['from_date'] ?? '';
$to_date       = $_GET['to_date']   ?? '';

$query = "
    SELECT 
        b.bill_id,
        t.tenant_name,
        r.room_number,
        b.balance AS total_balance,
        b.due_date,
        b.status
    FROM billing b
    JOIN tenants t ON b.tenant_id = t.tenant_id
    JOIN rooms r ON b.room_id = r.room_id
    WHERE t.status = 'Active'
";

$params = [];
$types = '';

if (!empty($tenant_filter)) {
    $query .= " AND t.tenant_name LIKE ?";
    $params[] = "%$tenant_filter%";
    $types .= 's';
}
if (!empty($from_date)) {
    $query .= " AND b.due_date >= ?";
    $params[] = $from_date;
    $types .= 's';
}
if (!empty($to_date)) {
    $query .= " AND b.due_date <= ?";
    $params[] = $to_date;
    $types .= 's';
}

$query .= " ORDER BY b.due_date DESC";

$stmt = $conn->prepare($query);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Print - Collection Report</title>
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
.summary-box { padding: 10px; border: 1px solid #2c3e50; font-weight: 700; font-size: 1rem; float: right; margin-top: 10px; }
</style>
</head>
<body onload="window.print();">

<div class="print-header">
    <img src="<?= htmlspecialchars($hdrPic) ?>" alt="Logo">
    <div>
        <h1 class="ph-title">Collection Report</h1>
        <div class="ph-sub">
            <?= $from_date ? 'From: ' . htmlspecialchars($from_date) . ' ' : '' ?>
            <?= $to_date ? 'To: ' . htmlspecialchars($to_date) : '' ?>
            <?= (!$from_date && !$to_date) ? 'All Records' : '' ?> 
            &nbsp;|&nbsp; Printed: <?= date('F d, Y \a\t h:i A') ?>
        </div>
    </div>
</div>

<table class="report-table">
    <thead>
        <tr>
            <th>Bill ID</th>
            <th>Name</th>
            <th>Room</th>
            <th>Total Balance</th>
            <th>Due Date</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
<?php
$total_collection = 0;
if (!empty($rows)) {
    foreach ($rows as $row) {
        $status = htmlspecialchars($row['status'] ?? '');
        echo "<tr>
            <td>{$row['bill_id']}</td>
            <td>" . htmlspecialchars($row['tenant_name']) . "</td>
            <td>" . htmlspecialchars($row['room_number']) . "</td>
            <td>₱" . number_format((float)$row['total_balance'], 2) . "</td>
            <td>{$row['due_date']}</td>
            <td><strong>{$status}</strong></td>
        </tr>";
        $total_collection += (float)$row['total_balance'];
    }
} else {
    echo "<tr><td colspan='6' style='text-align:center;color:#555;'>No records found.</td></tr>";
}
?>
    </tbody>
</table>

<div class="summary-box">
    Total Collection: ₱<?= number_format($total_collection, 2) ?>
</div>

</body>
</html>
