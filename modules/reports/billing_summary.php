<?php
/**
 * Reports Module - Billing Summary
 * Path: /modules/reports/billing_summary.php
 */
require_once "../../includes/auth_check.php";

// Capture filter month/year
$filter_month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$filter_year  = isset($_GET['year']) ? intval($_GET['year']) : 0;

// Base query
$sql = "
    SELECT 
        b.bill_id AS invoice_id,
        t.tenant_name,
        r.room_number,
        b.due_date,
        b.payment_date,
        b.base_rent,
        b.utility_fee,
        b.utility_amount,
        b.add_charges,
        b.add_amount,
        b.interest,
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

// Add WHERE if filtered
$where = [];
if ($filter_month > 0) $where[] = "MONTH(b.due_date) = $filter_month";
if ($filter_year > 0) $where[] = "YEAR(b.due_date) = $filter_year";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);

$sql .= " ORDER BY t.tenant_name ASC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Billing Summary Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background-color: #f0f4f3; font-family: Arial, sans-serif; margin: 0; }
    .main-content { margin-left:225px; padding:30px; min-height:100vh; }
</style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>

<div class="main-content">
<div style="padding: 20px; width: 100%; box-sizing: border-box;">
    <h2>Billing Summary Report</h2>

    <!-- Filter by Month/Year -->
    <form method="get" class="filter-bar" style="margin-bottom: 15px; display: flex; flex-wrap: wrap; gap:10px; align-items:center;">
        <label>Month:</label>
        <select name="month">
            <option value="0">All</option>
            <?php for ($m=1; $m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= ($filter_month==$m?'selected':'') ?>>
                    <?= date("F", mktime(0,0,0,$m,1)) ?>
                </option>
            <?php endfor; ?>
        </select>

        <label>Year:</label>
        <select name="year">
            <option value="0">All</option>
            <?php 
            $currentYear = date("Y");
            for ($y=$currentYear; $y>=2020; $y--): ?>
                <option value="<?= $y ?>" <?= ($filter_year==$y?'selected':'') ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>

        <button type="submit">Filter</button>
    </form>

    <!-- Action Bar -->
    <div class="action-bar" style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; margin-bottom: 15px;">
        <input type="text" id="searchTenant" placeholder="Search Tenant..." style="padding:5px; width:200px;">
        <div>
            <button class="btn btn-print" onclick="window.print()" style="padding:8px 12px; background:#5A7D7C; color:white; border:none; border-radius:5px; margin-left:5px;">🖨 Print</button>
        </div>
    </div>

    <!-- Report Table -->
    <div class="table-container">
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

                        $total_amount = floatval($row['base_rent']) + $utility_total + $add_total + $interest + $previous_balance + $other_amount - $previous_credit;
                        $payment_amount = floatval($row['payment_amount'] ?? 0);
                        $credit_balance = max(0, $payment_amount - $total_amount);
                        $balance = max(0, $total_amount - $payment_amount);

                        // Status
                        if ($payment_amount >= $total_amount && $total_amount > 0) {
                            $status = "<span style='color:green;font-weight:bold;'>Settled</span>";
                        } elseif ($payment_amount > 0 && $payment_amount < $total_amount) {
                            $status = "<span style='color:orange;font-weight:bold;'>Partial</span>";
                        } elseif ($total_amount > 0) {
                            $status = "<span style='color:red;font-weight:bold;'>Pending</span>";
                        } else {
                            $status = "-";
                        }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['tenant_name']) ?></td>
                        <td>₱<?= number_format($credit_balance,2) ?></td>
                        <td>₱<?= number_format($balance,2) ?></td>
                        <td>₱<?= number_format($payment_amount,2) ?></td>
                        <td>₱<?= number_format($total_amount,2) ?></td>
                        <td><?= $status ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">No data found for selected filter.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.table-container {
      max-height: 500px;   /* match sa Billing Summary */
    max-width: 100%;     /* para dili mulapaw */
    overflow-y: auto;
    overflow-x: auto;
    border: 1px solid #ddd;
    border-radius: 6px;
}
/* Table styles */
#reportTable {
    width: 100%;
    border-collapse: collapse;
    background-color: #ffffff;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    min-width: 700px;
}

#reportTable th, #reportTable td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
    vertical-align: middle;
}

#reportTable th {
    background-color: #5A7D7C;
    color: #fff;
    position: sticky;
    top: 0;
    z-index: 2;
}

@media print {
    body * { visibility:hidden; }
    #reportTable, #reportTable * { visibility:visible; }
    #reportTable { position:absolute; left:0; top:0; width:100%; }
}
</style>

<script>
// Tenant search filter
document.getElementById('searchTenant').addEventListener('keyup', function(){
    let filter = this.value.toLowerCase();
    document.querySelectorAll('#reportTable tbody tr').forEach(row=>{
        let tenant = row.cells[0].textContent.toLowerCase();
        row.style.display = tenant.includes(filter) ? '' : 'none';
    });
});
</script>
</div>
</div>
</body>
</html>
