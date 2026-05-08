<?php
/**
 * Reports Module - Monthly Payments
 * Path: /modules/reports/monthly_payments.php
 */
require_once "../../includes/auth_check.php";
require_once __DIR__ . '/../../helpers/BillingItems.php';

// Fetch dorm header image for print
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
<title>Monthly Payments</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background-color: #f0f4f3; }
.main-content { margin-left: 225px; padding: 30px; min-height: 100vh; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.page-header h2 { font-size: 1.6rem; font-weight: 700; color: #2c3e50; margin: 0; }
.page-header h2 i { color: #5A7D7C; margin-right: 8px; }
.card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 20px; }
.card-body { padding: 20px 24px; }
.profile-pic { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 2px solid #5A7D7C; }
.report-table { width: 100%; border-collapse: collapse; }
.report-table thead th {
    background-color: #5A7D7C;
    color: #fff;
    padding: 12px 14px;
    text-align: center;
    font-size: 0.87rem;
    font-weight: 600;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 2;
}
.report-table tbody td { padding: 10px 14px; text-align: center; border-bottom: 1px solid #eee; font-size: 0.9rem; vertical-align: middle; }
.report-table tbody tr:hover { background-color: #f1f8f7; }
.table-wrapper { overflow-x: auto; overflow-y: auto; max-height: 520px; }
.badge-settled { background: #d4edda; color: #155724; border-radius: 20px; padding: 4px 12px; font-size: 0.82rem; font-weight: 600; }
.badge-partial { background: #fff3cd; color: #856404; border-radius: 20px; padding: 4px 12px; font-size: 0.82rem; font-weight: 600; }
.summary-box { background: #fff; border: 1px solid #dee2e6; border-radius: 10px; padding: 14px 20px; display: inline-block; font-weight: 700; font-size: 1rem; color: #2c3e50; }
.btn-teal { background-color: #5A7D7C; color: #fff; border: none; border-radius: 8px; padding: 8px 18px; font-size: 0.9rem; }
.btn-teal:hover { background-color: #4a6c6b; color: #fff; }

</style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>



<div class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fas fa-calendar-check"></i> Monthly Payments &mdash; <?= $monthName . ' ' . $selectedYear ?></h2>
    </div>

    <!-- Filter Card -->
    <div class="card">
        <div class="card-body">
            <form class="d-flex flex-wrap gap-3 align-items-end" method="get">
                <div>
                    <label class="form-label fw-semibold small mb-1">Month</label>
                    <select name="month" class="form-select" style="min-width:140px;">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m == $selectedMonth ? 'selected' : '' ?>>
                                <?= date("F", mktime(0, 0, 0, $m, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label fw-semibold small mb-1">Year</label>
                    <select name="year" class="form-select" style="min-width:110px;">
                        <?php for ($y = 2022; $y <= 2030; $y++): ?>
                            <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label class="d-block mb-1">&nbsp;</label>
                    <button type="submit" class="btn-teal btn"><i class="fas fa-filter me-1"></i> View</button>
                    <a href="print_monthly_payments.php?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>" target="_blank" class="btn btn-secondary ms-2" style="background:#2c3e50; border:none; padding:8px 18px; border-radius:8px;"><i class="fas fa-print me-1"></i> Print Report</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card">
        <div class="card-body">
            <?php if (count($paidTenants) > 0): ?>
            <div class="table-wrapper">
                <table class="report-table">
                    <thead>
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
                    <?php foreach ($paidTenants as $row): ?>
                    <tr>
                        <td>
                            <?php if (!empty($row['profile_pic'])): ?>
                                <img src="<?= BASE_PATH . '/' . htmlspecialchars($row['profile_pic']) ?>" class="profile-pic">
                            <?php else: ?> — <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['tenant_name']) ?></td>
                        <td><?= htmlspecialchars($row['room_number']) ?></td>
                        <td><?= date("M d, Y", strtotime($row['due_date'])) ?></td>
                        <td>₱<?= number_format($row['balance'], 2) ?></td>
                        <td>₱<?= number_format($row['credit'], 2) ?></td>
                        <td><?= !empty($row['payment_date']) ? date("M d, Y", strtotime($row['payment_date'])) : '—' ?></td>
                        <td>₱<?= number_format($row['total'], 2) ?></td>
                        <td>₱<?= number_format($row['payment_amount'], 2) ?></td>
                        <td><span class="<?= $row['status'] == 'Partial' ? 'badge-partial' : 'badge-settled' ?>"><?= $row['status'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <div class="summary-box">
                    <i class="fas fa-coins me-2 text-success"></i> Total Payments: ₱<?= number_format($totalPayments, 2) ?>
                </div>
            </div>
            <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-calendar-times fa-2x mb-3 d-block opacity-50"></i>
                    No payments found for <?= $monthName . ' ' . $selectedYear ?>.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
