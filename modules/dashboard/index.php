<?php
/**
 * Dashboard Module - Main Dashboard
 * Path: /modules/dashboard/index.php
 */
require_once '../../includes/auth_check.php';
require_once __DIR__ . '/../../helpers/BillingItems.php';
require_once __DIR__ . '/../../helpers/TenantAssignments.php';

$currentMonth = date('m');
$currentYear  = date('Y');
$currentMonthName = date('F'); // Example: September

$incomeMonthOptions = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December',
];

$selectedIncomeMonth = isset($_GET['income_month']) && $_GET['income_month'] !== ''
    ? max(1, min(12, (int)$_GET['income_month']))
    : null;
$selectedIncomeYear = isset($_GET['income_year']) && $_GET['income_year'] !== ''
    ? (int)$_GET['income_year']
    : null;

$incomeYearOptions = [];
$incomeYearsResult = $conn->query(
    "SELECT DISTINCT YEAR(due_date) AS year_value
     FROM billing
     WHERE payment_amount > 0 AND due_date IS NOT NULL
     ORDER BY year_value DESC"
);

if ($incomeYearsResult) {
    while ($yearRow = $incomeYearsResult->fetch_assoc()) {
        $yearValue = isset($yearRow['year_value']) ? (int)$yearRow['year_value'] : null;
        if ($yearValue) {
            $incomeYearOptions[] = $yearValue;
        }
    }
    $incomeYearsResult->free();
}

if (empty($incomeYearOptions)) {
    $incomeYearOptions[] = (int)$currentYear;
}

$incomeFilterLabel = 'All Time';
if ($selectedIncomeMonth !== null && $selectedIncomeYear !== null) {
    $monthName = $incomeMonthOptions[$selectedIncomeMonth] ?? ('Month ' . $selectedIncomeMonth);
    $incomeFilterLabel = $monthName . ' ' . $selectedIncomeYear;
} elseif ($selectedIncomeMonth !== null) {
    $monthName = $incomeMonthOptions[$selectedIncomeMonth] ?? ('Month ' . $selectedIncomeMonth);
    $incomeFilterLabel = $monthName . ' (All Years)';
} elseif ($selectedIncomeYear !== null) {
    $incomeFilterLabel = 'Year ' . $selectedIncomeYear;
}

$filterClausesNoAlias = [];
if ($selectedIncomeMonth !== null) {
    $filterClausesNoAlias[] = 'MONTH(due_date) = ' . $selectedIncomeMonth;
}
if ($selectedIncomeYear !== null) {
    $filterClausesNoAlias[] = 'YEAR(due_date) = ' . $selectedIncomeYear;
}

$incomeWhere = 'payment_amount > 0';
if (!empty($filterClausesNoAlias)) {
    $incomeWhere .= ' AND ' . implode(' AND ', $filterClausesNoAlias);
}

$totalIncome = 0.0;
$incomeSql = "SELECT SUM(payment_amount) AS total_income FROM billing WHERE $incomeWhere";
$incomeResult = $conn->query($incomeSql);
if ($incomeResult) {
    $incomeRow = $incomeResult->fetch_assoc();
    if ($incomeRow && $incomeRow['total_income'] !== null) {
        $totalIncome = (float)$incomeRow['total_income'];
    }
    $incomeResult->free();
}

$filterClausesWithAlias = [];
if ($selectedIncomeMonth !== null) {
    $filterClausesWithAlias[] = 'MONTH(b.due_date) = ' . $selectedIncomeMonth;
}
if ($selectedIncomeYear !== null) {
    $filterClausesWithAlias[] = 'YEAR(b.due_date) = ' . $selectedIncomeYear;
}
$dueDateFilterSql = '';
if (!empty($filterClausesWithAlias)) {
    $dueDateFilterSql = ' AND ' . implode(' AND ', $filterClausesWithAlias);
}

// ====== Stats ======
$tenantsCount = $conn->query("SELECT COUNT(*) AS count FROM tenants WHERE status='Active'")->fetch_assoc()['count'];
$roomsCount = $conn->query("SELECT COUNT(*) AS count FROM rooms WHERE record_status='Active'")->fetch_assoc()['count'];



// ====== Total Income (sum of all payments within current month) ======
// ====== Function to compute balances ======
function computeBalances($total, $payment, $prev_credit) {
    $creditBalance = 0;
    $balance = 0;

    if ($payment > $total) {
        $creditBalance = $payment - $total;
    } elseif (($payment + $prev_credit) > $total) {
        $creditBalance = ($payment + $prev_credit) - $total;
    }

    if (($payment + $prev_credit) < $total) {
        $balance = $total - ($payment + $prev_credit);
    }

    return [
        "credit" => $creditBalance,
        "balance" => $balance
    ];
}

// ====== Fetch current month payments ======
$prev_credit = 0;
$paidTenants = [];

$query = "
    SELECT b.*, t.tenant_name, t.profile_pic, r.room_number, t.status AS tenant_status
    FROM billing b
    INNER JOIN tenants t ON b.tenant_id = t.tenant_id
    INNER JOIN rooms r ON t.room_id = r.room_id
    WHERE 
        b.payment_amount > 0
        AND t.status = 'Active'" . $dueDateFilterSql . "
    ORDER BY b.due_date ASC
";

$res = $conn->query($query);

while ($row = $res->fetch_assoc()) {

    $billId = isset($row['bill_id']) ? (int)$row['bill_id'] : 0;
    $utilityItems = $billId ? getBillingUtilityItems($conn, $billId) : [];
    $additionalItems = $billId ? getBillingAdditionalItems($conn, $billId) : [];

    $base_rent        = floatval($row['base_rent'] ?? 0);
    $utility_total    = sumBillingItems($utilityItems);
    $add_total        = sumBillingItems($additionalItems);
    $interest         = floatval($row['interest'] ?? 0);
    $previous_balance = floatval($row['previous_balance'] ?? 0);
    $other_amount     = floatval($row['other_amount'] ?? 0);

    $total = $base_rent + $utility_total + $add_total + $interest + $previous_balance + $other_amount;
    $payment = floatval($row['payment_amount'] ?? 0);

    $status = getBillingStatus($total, $payment, $prev_credit);
    $balances = computeBalances($total, $payment, $prev_credit);

    $row['status'] = $status;
    $row['credit_balance'] = $balances['credit'];
    $row['balance'] = $balances['balance'];
    $row['total'] = $total;

    $paidTenants[] = $row;

    // Update previous credit
    if ($payment > $total) {
        $prev_credit = $payment - $total;
    } elseif ($prev_credit > 0 && ($prev_credit + $payment) > $total) {
        $prev_credit = ($prev_credit + $payment) - $total;
    } else {
        $prev_credit = 0;
    }
}

// Rooms Occupied / Available snapshot via tenant_rooms inventory
$roomInventory = TenantAssignments::getRoomInventory($conn);
$roomsOccupied = count(array_filter($roomInventory, static function (array $room): bool {
    return ($room['available_slots'] ?? 0) <= 0;
}));
$roomsAvailable = count(array_filter($roomInventory, static function (array $room): bool {
    return ($room['available_slots'] ?? 0) > 0;
}));

// ====== Billing Status Function ======
function getBillingStatus($total, $payment, $prev_credit) {
    if ($prev_credit >= $total) {
        return "Settled";
    }
    if (($prev_credit + $payment) >= $total) {
        return "Settled";
    }
    if ($payment > 0) {
        return "Partial";
    }
    return "Pending";
}

// ====== Compute Settled / Partial / Pending ======
$settledCount = $partialCount = $pendingCount = 0;

$billingRes = $conn->query(" 
    SELECT b.*, 
           IFNULL(bu.utility_total, 0) AS utility_total,
           IFNULL(ba.add_total, 0) AS add_total
    FROM billing b
    INNER JOIN tenants t ON b.tenant_id = t.tenant_id
    LEFT JOIN (
        SELECT bill_id, SUM(amount) AS utility_total
        FROM billing_utility_items
        GROUP BY bill_id
    ) bu ON bu.bill_id = b.bill_id
    LEFT JOIN (
        SELECT bill_id, SUM(amount) AS add_total
        FROM billing_additional_items
        GROUP BY bill_id
    ) ba ON ba.bill_id = b.bill_id
    WHERE 
        t.status = 'Active'" . $dueDateFilterSql . "
");

while ($bill = $billingRes->fetch_assoc()) {



    // ====== Fetch Notifications ======
$notifications = $conn->query("
    SELECT n.id, n.tenant_id, t.tenant_name, n.type, n.message, n.is_read, n.created_at
    FROM notifications n
    JOIN tenants t ON n.tenant_id = t.tenant_id
    ORDER BY n.created_at DESC
");


    // Compute totals
    $base_rent        = floatval($bill['base_rent'] ?? 0);
    $utility_total    = floatval($bill['utility_total'] ?? 0);
    $add_total        = floatval($bill['add_total'] ?? 0);
    $interest         = floatval($bill['interest'] ?? 0);
    $previous_balance = floatval($bill['previous_balance'] ?? 0);
    $other_amount     = floatval($bill['other_amount'] ?? 0);

    $total_amount = $base_rent + $utility_total + $add_total + $interest + $previous_balance + $other_amount;
    $payment      = floatval($bill['payment_amount'] ?? 0);

    // Determine billing status per bill
    if ($payment >= $total_amount) {
        $settledCount++;
    } elseif ($payment > 0 && $payment < $total_amount) {
        $partialCount++;
    } else {
        $pendingCount++;
    }
}

// ====== Monthly Income for Chart ======
$monthlyIncome = array_fill(1, 12, 0);

$sqlIncome = "
    SELECT SUM(b.payment_amount) AS total
    FROM billing b
    INNER JOIN tenants t ON b.tenant_id = t.tenant_id
    WHERE 
        MONTH(b.due_date) = ? 
        AND YEAR(b.due_date) = ? 
        AND t.status = 'Active'
";
$stmtIncome = $conn->prepare($sqlIncome);
$stmtIncome->bind_param("ii", $currentMonth, $currentYear);
$stmtIncome->execute();
$resIncome = $stmtIncome->get_result();
$row = $resIncome->fetch_assoc();

$monthlyIncome[$currentMonth] = (float)$row['total'];



// ====== Monthly Total Tenants Due ======
$monthlyDue = array_fill(1, 12, 0);

$sqlDue = "
    SELECT COUNT(*) AS total_due
    FROM billing
    WHERE MONTH(due_date) = ? AND YEAR(due_date) = ?
";
$stmtDue = $conn->prepare($sqlDue);
$stmtDue->bind_param("ii", $currentMonth, $currentYear);
$stmtDue->execute();
$resDue = $stmtDue->get_result();
$row = $resDue->fetch_assoc();

$monthlyDue[$currentMonth] = (int)$row['total_due'];


// ====== Monthly Settled / Partial / Pending ======
$monthlySettled = array_fill(1, 12, 0);
$monthlyPartial = array_fill(1, 12, 0);
$monthlyPending = array_fill(1, 12, 0);

$sqlStatus = "
    SELECT 
        MONTH(b.due_date) AS month,
        SUM(CASE 
                WHEN b.payment_amount >= (b.base_rent + COALESCE(b.previous_balance,0) + COALESCE(b.other_amount,0) + COALESCE(b.interest,0) + IFNULL(bu.utility_total,0) + IFNULL(ba.add_total,0)) 
                THEN 1 ELSE 0 END
            ) AS settled,
        SUM(CASE 
                WHEN b.payment_amount > 0 AND b.payment_amount < (b.base_rent + COALESCE(b.previous_balance,0) + COALESCE(b.other_amount,0) + COALESCE(b.interest,0) + IFNULL(bu.utility_total,0) + IFNULL(ba.add_total,0)) 
                THEN 1 ELSE 0 END
            ) AS partial,
        SUM(CASE WHEN b.payment_amount = 0 THEN 1 ELSE 0 END) AS pending
    FROM billing b
    INNER JOIN tenants t ON b.tenant_id = t.tenant_id
    LEFT JOIN (
        SELECT bill_id, SUM(amount) AS utility_total
        FROM billing_utility_items
        GROUP BY bill_id
    ) bu ON bu.bill_id = b.bill_id
    LEFT JOIN (
        SELECT bill_id, SUM(amount) AS add_total
        FROM billing_additional_items
        GROUP BY bill_id
    ) ba ON ba.bill_id = b.bill_id
    WHERE YEAR(b.due_date) = ? AND t.status = 'Active'
    GROUP BY MONTH(b.due_date)
";
$stmtStatus = $conn->prepare($sqlStatus);
$stmtStatus->bind_param("i", $currentYear);
$stmtStatus->execute();
$resStatus = $stmtStatus->get_result();

while ($row = $resStatus->fetch_assoc()) {
    $month = (int)$row['month'];

    if ($monthlyDue[$month] > 0) {
        // Percentages
        $monthlySettled[$month] = round(($row['settled'] / $monthlyDue[$month]) * 100, 2);
        $monthlyPartial[$month] = round(($row['partial'] / $monthlyDue[$month]) * 100, 2);
        $monthlyPending[$month] = round(($row['pending'] / $monthlyDue[$month]) * 100, 2);
    } else {
        $monthlySettled[$month] = 0;
        $monthlyPartial[$month] = 0;
        $monthlyPending[$month] = 0;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Ben & Sof Dormitory</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../css/new_dashboard.css">
<style>
    /* Notification styles */
    .notif-unread {
        background-color: #e7f3ff;
        border-left: 4px solid #0d6efd;
    }
    .notif-read {
        background-color: #f8f9fa;
        opacity: 0.7;
    }
    .mark-read-btn {
        flex-shrink: 0;
        margin-left: 10px;
    }
    .notif-item {
        transition: all 0.3s ease;
    }
    .notif-item:hover {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
</style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
<div class="main-content">
<div class="container-fluid">

  <?php
// ====== Fetch Header Image ======
$header = $conn->query("SELECT setting_value FROM settings WHERE setting_name='header_image'")->fetch_assoc();
$header_pic = $header ? BASE_PATH . '/' . $header['setting_value'] : BASE_PATH . "/uploads/default_header.png";

// ====== Fetch Notifications ======
$notifications = $conn->query("
    SELECT n.id, n.tenant_id, t.tenant_name, n.type, n.message, n.is_read, n.created_at
    FROM notifications n
    JOIN tenants t ON n.tenant_id = t.tenant_id
    ORDER BY n.created_at DESC
    LIMIT 50
");
// Count only unread notifications
$unreadCount = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0")->fetch_assoc()['count'];
?>

<!-- Header with Notification & Profile -->
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mt-0 gap-3">
    <h2 class="mb-0">Dashboard</h2>
    <form method="get" class="d-flex flex-wrap align-items-center gap-2">
        <div class="form-floating">
            <select name="income_month" id="incomeMonth" class="form-select">
                <option value="">All Months</option>
                <?php foreach ($incomeMonthOptions as $monthNumber => $monthLabel): ?>
                    <option value="<?php echo $monthNumber; ?>" <?php echo ($selectedIncomeMonth === $monthNumber) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($monthLabel); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="incomeMonth">Income Month</label>
        </div>
        <div class="form-floating">
            <select name="income_year" id="incomeYear" class="form-select">
                <option value="">All Years</option>
                <?php foreach ($incomeYearOptions as $yearOption): ?>
                    <option value="<?php echo $yearOption; ?>" <?php echo ($selectedIncomeYear === (int)$yearOption) ? 'selected' : ''; ?>>
                        <?php echo $yearOption; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="incomeYear">Income Year</label>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="submit" class="btn btn-primary">Apply</button>
            <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>
    <div class="d-flex align-items-center">
        <!-- Notification Icon -->
        <div class="position-relative me-3">
            <i class="fas fa-bell fa-lg" id="notifIcon" style="cursor:pointer;"></i>
            <?php if($unreadCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notifBadge">
                    <?php echo $unreadCount; ?>
                    <span class="visually-hidden">unread notifications</span>
                </span>
            <?php endif; ?>
        </div>

        <!-- Profile Box -->
        <div class="profile-box d-flex align-items-center">
            <img src="<?php echo $header_pic; ?>" class="rounded-circle" width="50" height="50" alt="Admin Profile">
            <span class="ms-2">Admin</span>
        </div>
    </div>
</div>
<hr>

<?php include 'stats_widget.php'; ?>

<!-- Notification Modal -->
<div class="modal fade" id="notifModal" tabindex="-1" aria-labelledby="notifModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="notifModalLabel">Notifications</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php if($notifications->num_rows > 0): ?>
            <ul class="list-group" id="notificationList">
            <?php while($notif = $notifications->fetch_assoc()): ?>
                <li class="list-group-item notif-item <?= $notif['is_read'] ? 'notif-read' : 'notif-unread' ?>" data-notif-id="<?= $notif['id'] ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <strong><?php echo htmlspecialchars($notif['tenant_name']); ?>:</strong>
                            <?php echo htmlspecialchars($notif['message']); ?>
                            <br>
                            <small class="text-muted"><?php echo $notif['created_at']; ?></small>
                        </div>
                        <?php if(!$notif['is_read']): ?>
                            <button class="btn btn-sm btn-outline-primary mark-read-btn"
                                    onclick="markAsRead(<?= $notif['id'] ?>)"
                                    title="Mark as read">
                                <i class="fas fa-check"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>No notifications yet.</p>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="markAllAsRead()">Mark All as Read</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const ctx = document.getElementById('billingChart').getContext('2d');

// PHP arrays to JS
let incomeData = <?php echo json_encode(array_values($monthlyIncome)); ?>;
let settledPercent = <?php echo json_encode(array_values($monthlySettled)); ?>;
let partialPercent = <?php echo json_encode(array_values($monthlyPartial)); ?>;
let pendingPercent = <?php echo json_encode(array_values($monthlyPending)); ?>;

// Heartbeat effect function
function heartbeat(data, amplitude = 0.05, speed = 200) {
    return data.map((val, i) => val * (1 + amplitude * Math.sin(Date.now()/speed + i)));
}

// Chart instance
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [
            {
                label: 'Total Income (₱)',
                data: incomeData,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40,167,69,0.2)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#28a745',
                pointBorderColor: '#fff',
                pointRadius: 4,
                yAxisID: 'y'
            },
            {
                label: 'Settled (%)',
                data: settledPercent,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0,123,255,0.05)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                yAxisID: 'y1'
            },
            {
                label: 'Partial (%)',
                data: partialPercent,
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255,193,7,0.05)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                yAxisID: 'y1'
            },
            {
                label: 'Pending (%)',
                data: pendingPercent,
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220,53,69,0.05)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 0 },
        plugins: { legend: { display: true } },
        scales: {
            y: { // Total Income axis
                beginAtZero: true,
                suggestedMax: Math.max(...incomeData) * 1.2,
                title: { display: true, text: 'Total Income (₱)' }
            },
            y1: { // Percentage axis
                beginAtZero: true,
                max: 100,
                position: 'right',
                grid: { drawOnChartArea: false },
                title: { display: true, text: 'Percentage (%)' }
            }
        }
    }
});

// Animate heartbeat for all datasets
function animateHeartbeat() {
    // Total Income heartbeat
    chart.data.datasets[0].data = heartbeat(incomeData, 0.05, 200);

    // Optional: small heartbeat for Settled/Partial/Pending
    chart.data.datasets[1].data = heartbeat(settledPercent, 0.02, 300);
    chart.data.datasets[2].data = heartbeat(partialPercent, 0.02, 300);
    chart.data.datasets[3].data = heartbeat(pendingPercent, 0.02, 300);

    chart.update('none'); // fast update without full animation
    requestAnimationFrame(animateHeartbeat);
}
animateHeartbeat();


document.getElementById('notifIcon').addEventListener('click', function() {
    var notifModal = new bootstrap.Modal(document.getElementById('notifModal'));
    notifModal.show();
});

// Mark single notification as read
function markAsRead(notifId) {
    fetch('<?= BASE_PATH ?>/modules/utilities/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + notifId
    })
    .then(response => {
        if (response.ok) {
            // Update UI
            const notifItem = document.querySelector(`[data-notif-id="${notifId}"]`);
            if (notifItem) {
                notifItem.classList.remove('notif-unread');
                notifItem.classList.add('notif-read');
                notifItem.querySelector('.mark-read-btn')?.remove();
            }
            // Update badge count
            updateNotifBadge();
        }
    })
    .catch(error => console.error('Error:', error));
}

// Mark all notifications as read
function markAllAsRead() {
    const unreadNotifs = document.querySelectorAll('.notif-unread');
    const promises = [];

    unreadNotifs.forEach(notif => {
        const notifId = notif.getAttribute('data-notif-id');
        promises.push(
            fetch('<?= BASE_PATH ?>/modules/utilities/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + notifId
            })
        );
    });

    Promise.all(promises).then(() => {
        // Update all UI elements
        unreadNotifs.forEach(notif => {
            notif.classList.remove('notif-unread');
            notif.classList.add('notif-read');
            notif.querySelector('.mark-read-btn')?.remove();
        });
        // Update badge
        updateNotifBadge();
    }).catch(error => console.error('Error:', error));
}

// Update notification badge count
function updateNotifBadge() {
    const unreadCount = document.querySelectorAll('.notif-unread').length;
    const badge = document.getElementById('notifBadge');

    if (badge) {
        if (unreadCount > 0) {
            badge.textContent = unreadCount;
        } else {
            badge.remove();
        }
    }
}
</script>
</body>
</html>
