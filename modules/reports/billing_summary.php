<?php
/**
 * Reports Module - Billing Summary
 * Path: /modules/reports/billing_summary.php
 */
require_once "../../includes/auth_check.php";
require_once __DIR__ . '/../../helpers/BillingItems.php';

// Fetch dorm header image for print
$hdrRow = $conn->query("SELECT setting_value FROM settings WHERE setting_name='header_image'")->fetch_assoc();
$hdrPic = $hdrRow ? BASE_PATH . '/' . $hdrRow['setting_value'] : BASE_PATH . '/uploads/default_header.png';

// Capture filter month/year
$filter_month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$filter_year  = isset($_GET['year'])  ? intval($_GET['year'])  : 0;
$filterLabel  = '';
if ($filter_month > 0) $filterLabel .= date("F", mktime(0,0,0,$filter_month,1));
if ($filter_year  > 0) $filterLabel .= ($filterLabel ? ' ' : '') . $filter_year;
if (!$filterLabel) $filterLabel = 'All Records';

// Base query
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
<title>Billing Summary Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background-color: #f0f4f3; font-family: 'Segoe UI', Arial, sans-serif; margin: 0; }
.main-content { margin-left: 225px; padding: 30px; min-height: 100vh; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.page-header h2 { font-size: 1.6rem; font-weight: 700; color: #2c3e50; margin: 0; }
.page-header h2 i { color: #5A7D7C; margin-right: 8px; }
.card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 20px; }
.card-body { padding: 20px 24px; }
.filter-row { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
.filter-row label { font-size: 0.85rem; font-weight: 600; color: #555; margin-bottom: 4px; display: block; }
.filter-row .form-select { min-width: 140px; border-radius: 8px; border-color: #ddd; font-size: 0.9rem; }
.btn-primary-teal { background-color: #5A7D7C; color: #fff; border: none; border-radius: 8px; padding: 8px 18px; font-size: 0.9rem; transition: background 0.2s; }
.btn-primary-teal:hover { background-color: #4a6c6b; color: #fff; }
.btn-outline-teal { background-color: #fff; color: #5A7D7C; border: 1.5px solid #5A7D7C; border-radius: 8px; padding: 7px 14px; font-size: 0.88rem; transition: all 0.2s; }
.btn-outline-teal:hover { background-color: #5A7D7C; color: #fff; }
.search-wrap { position: relative; }
.search-wrap input { padding-left: 36px; border-radius: 8px; border-color: #ddd; font-size: 0.9rem; }
.search-wrap .fa-search { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 0.85rem; }
.table-card { border-radius: 12px; overflow: hidden; }
.table-wrapper { max-height: 520px; overflow-y: auto; overflow-x: auto; }
#reportTable { width: 100%; border-collapse: collapse; min-width: 680px; }
#reportTable thead th {
    background-color: #5A7D7C;
    color: #fff;
    padding: 12px 14px;
    text-align: center;
    font-size: 0.87rem;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 2;
    white-space: nowrap;
}
#reportTable tbody td { padding: 10px 14px; text-align: center; border-bottom: 1px solid #eee; font-size: 0.9rem; vertical-align: middle; }
#reportTable tbody tr:hover { background-color: #f1f8f7; }
.badge-settled  { background: #d4edda; color: #155724; border-radius: 20px; padding: 4px 12px; font-size: 0.82rem; font-weight: 600; }
.badge-partial  { background: #fff3cd; color: #856404; border-radius: 20px; padding: 4px 12px; font-size: 0.82rem; font-weight: 600; }
.badge-pending  { background: #f8d7da; color: #721c24; border-radius: 20px; padding: 4px 12px; font-size: 0.82rem; font-weight: 600; }


</style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>



<div class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fas fa-file-invoice-dollar"></i> Billing Summary Report</h2>
        <div class="d-flex gap-2">
            <a href="print_billing_summary.php?month=<?= $filter_month ?>&year=<?= $filter_year ?>" target="_blank" class="btn-outline-teal" style="text-decoration:none;"><i class="fas fa-print me-1"></i> Print Report</a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card">
        <div class="card-body">
            <form method="get" class="filter-row">
                <div>
                    <label>Month</label>
                    <select name="month" class="form-select">
                        <option value="0">All Months</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= ($filter_month == $m ? 'selected' : '') ?>>
                                <?= date("F", mktime(0, 0, 0, $m, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label>Year</label>
                    <select name="year" class="form-select">
                        <option value="0">All Years</option>
                        <?php for ($y = date("Y"); $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= ($filter_year == $y ? 'selected' : '') ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-primary-teal d-block"><i class="fas fa-filter me-1"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card table-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchTenant" class="form-control" placeholder="Search tenant...">
                </div>
            </div>
            <div class="table-wrapper">
                <table id="reportTable">
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
                            <tr><td colspan="6" class="text-center text-muted py-4">No data found for selected filter.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('searchTenant').addEventListener('keyup', function () {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#reportTable tbody tr').forEach(row => {
        const tenant = row.cells[0].textContent.toLowerCase();
        row.style.display = tenant.includes(filter) ? '' : 'none';
    });
});
</script>
</body>
</html>
