<?php
require_once '../../includes/auth_check.php';

date_default_timezone_set('Asia/Manila');

$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$monthFilter = isset($_GET['month']) ? trim($_GET['month']) : '';
$validMonthFilter = preg_match('/^\d{4}-\d{2}$/', $monthFilter) ? $monthFilter : '';

$sql = "
    SELECT 
        b.tenant_id,
        t.tenant_name,
        DATE_FORMAT(b.due_date, '%Y-%m') AS bill_month,
        MIN(b.due_date) AS first_due_date,
        MAX(b.due_date) AS last_due_date,
        GROUP_CONCAT(DISTINCT r.room_number ORDER BY r.room_number SEPARATOR ', ') AS room_numbers,
        COUNT(*) AS bill_count,
        SUM(COALESCE(b.total_amount, 0)) AS total_due,
        SUM(COALESCE(b.payment_amount, 0)) AS total_paid,
        SUM(COALESCE(b.balance, 0)) AS total_balance,
        GROUP_CONCAT(DISTINCT COALESCE(b.status, 'Pending') ORDER BY b.status SEPARATOR ',') AS raw_statuses
    FROM billing b
    INNER JOIN tenants t ON t.tenant_id = b.tenant_id
    LEFT JOIN rooms r ON r.room_id = b.room_id
";

$conditions = [];
$params = [];
$types = '';

if ($validMonthFilter !== '') {
    $conditions[] = "DATE_FORMAT(b.due_date, '%Y-%m') = ?";
    $types .= 's';
    $params[] = $validMonthFilter;
}

if ($searchTerm !== '') {
    $conditions[] = "t.tenant_name LIKE ?";
    $types .= 's';
    $params[] = '%' . $searchTerm . '%';
}

if (!empty($conditions)) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= "
    GROUP BY b.tenant_id, bill_month
    ORDER BY first_due_date DESC, t.tenant_name ASC
";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Failed to prepare billing summary query.');
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function summariseStatus(string $rawStatuses): string
{
    $statuses = array_filter(array_map('trim', explode(',', $rawStatuses)));
    if (empty($statuses)) {
        return 'Pending';
    }

    $statuses = array_unique(array_map('strtolower', $statuses));

    if (array_intersect($statuses, ['pending', 'pending payment'])) {
        return 'Pending';
    }
    if (in_array('partial', $statuses, true)) {
        return 'Partial';
    }
    if (in_array('settled', $statuses, true)) {
        return 'Settled';
    }

    return ucfirst($statuses[0]);
}

function formatPeso(float $amount): string
{
    return '₱' . number_format($amount, 2);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing List</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { margin:0; font-family: 'Arial', sans-serif; display:flex; background-color:#f0f4f3; }
        .main-content { margin-left:225px; padding:30px; background-color:#fff; min-height:100vh; width:100%; border-left:2px solid #5A7D7C; }
        h2 { font-weight:bold; color:#5A7D7C; }
        .filters { display:flex; gap:12px; flex-wrap:wrap; justify-content:space-between; align-items:center; margin:20px 0; }
        .filters .form-control { min-width:220px; }
        .table-wrapper { border-radius:10px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.1); background:#fff; }
        table { margin:0; }
        thead th { background-color:#5A7D7C; color:#fff; font-size:14px; text-transform:uppercase; letter-spacing:0.5px; }
        tbody td { vertical-align:middle; font-size:14px; }
        tbody tr:nth-child(even) { background-color:#f7fbfb; }
        tbody tr:hover { background-color:#e4f0ef; }
        .status-badge { font-size:12px; padding:5px 10px; border-radius:999px; }
        .status-badge.pending { background-color:#fce8e6; color:#b3261e; }
        .status-badge.partial { background-color:#fff4cc; color:#8a6d1c; }
        .status-badge.settled { background-color:#d9f2dd; color:#1e7a2e; }
        .status-badge.other { background-color:#dbe1f2; color:#2b4c7e; }
        .empty-state { border:2px dashed #5A7D7C33; border-radius:12px; padding:60px 20px; text-align:center; color:#5A7D7C; margin-top:40px; background:#f9fcfb; }
        .empty-state i { font-size:48px; margin-bottom:12px; color:#5A7D7C; }
        .btn-primary { background-color:#5A7D7C; border:none; }
        .btn-primary:hover { background-color:#3d5a59; }
        .btn-outline-secondary { border-color:#5A7D7C; color:#5A7D7C; }
        .btn-outline-secondary:hover { background-color:#5A7D7C; color:#fff; }
    </style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2>Billing Overview</h2>
            <p class="text-muted mb-0">Review monthly bill batches before drilling into details.</p>
        </div>
        <a href="<?= BASE_PATH ?>/modules/billing/" class="btn btn-outline-secondary"><i class="fa-solid fa-calendar-days me-2"></i>Billing Calendar</a>
    </div>

    <form method="get" class="filters">
        <div class="d-flex gap-2 flex-wrap">
            <input type="text" name="search" class="form-control" placeholder="Search tenant name" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($validMonthFilter, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter me-2"></i>Filter</button>
            <a href="list.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <?php if (empty($results)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-file-circle-exclamation"></i>
            <h4 class="mt-3">No billing records found</h4>
            <p class="mb-0">Try adjusting your filters or create a new bill from a tenant page.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:22%">Tenant</th>
                        <th style="width:12%">Billing Month</th>
                        <th style="width:14%">Rooms</th>
                        <th style="width:10%" class="text-center">Bills</th>
                        <th style="width:14%" class="text-end">Total Due</th>
                        <th style="width:14%" class="text-end">Amount Paid</th>
                        <th style="width:12%" class="text-center">Status</th>
                        <th style="width:10%" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): 
                        $status = summariseStatus($row['raw_statuses'] ?? '');
                        $statusClass = 'other';
                        switch (strtolower($status)) {
                            case 'pending':
                                $statusClass = 'pending';
                                break;
                            case 'partial':
                                $statusClass = 'partial';
                                break;
                            case 'settled':
                                $statusClass = 'settled';
                                break;
                        }
                        $billMonthLabel = date('F Y', strtotime($row['first_due_date'] ?? ($row['bill_month'] . '-01')));
                        $viewUrl = sprintf(
                            'view.php?tenant_id=%d&bill_month=%s',
                            (int)$row['tenant_id'],
                            urlencode($row['bill_month'])
                        );
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($row['tenant_name']); ?></strong><br>
                            <span class="text-muted small">ID: <?= (int)$row['tenant_id']; ?></span>
                        </td>
                        <td><?= htmlspecialchars($billMonthLabel); ?></td>
                        <td><?= htmlspecialchars($row['room_numbers'] ?: '—'); ?></td>
                        <td class="text-center"><?= (int)$row['bill_count']; ?></td>
                        <td class="text-end"><?= formatPeso((float)$row['total_due']); ?></td>
                        <td class="text-end"><?= formatPeso((float)$row['total_paid']); ?></td>
                        <td class="text-center">
                            <span class="status-badge <?= $statusClass; ?>"><?= htmlspecialchars($status); ?></span>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-primary" href="<?= $viewUrl; ?>">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
