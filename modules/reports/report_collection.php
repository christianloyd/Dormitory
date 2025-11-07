<?php
if (session_status() === PHP_SESSION_NONE) 
require_once "../../includes/auth_check.php";

$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : 0;
$selectedYear  = isset($_GET['year']) ? intval($_GET['year']) : 0;

$query = "
    SELECT b.*, t.tenant_name, r.room_number
    FROM billing b
    LEFT JOIN tenants t ON b.tenant_id = t.tenant_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE t.status = 'Active'
    ORDER BY b.due_date ASC
";

$res = $conn->query($query);
if(!$res) die("Query error: ".$conn->error);

$monthlyData = [];
foreach($res as $row) {
    if(empty($row['due_date'])) continue;

    $monthNum = date("n", strtotime($row['due_date']));
    $month    = date("F", strtotime($row['due_date']));
    $year     = date("Y", strtotime($row['due_date']));
    $key      = $month." ".$year;

    if($selectedMonth && $selectedYear && ($monthNum != $selectedMonth || $year != $selectedYear)) continue;

    $utility_total = array_sum(json_decode($row['utility_amount'], true) ?? []);
    $add_total     = array_sum(json_decode($row['add_amount'], true) ?? []);
    $interest      = floatval($row['interest'] ?? 0);
    $previous_balance = floatval($row['previous_balance'] ?? 0);
    $previous_credit  = floatval($row['previous_credit'] ?? 0);
    $other_amount     = floatval($row['other_amount'] ?? 0);
    $total_amount     = floatval($row['base_rent']) + $utility_total + $add_total + $interest + $previous_balance + $other_amount - $previous_credit;

    $payment_amount = floatval($row['payment_amount'] ?? 0);
    $balance        = max(0, $total_amount - $payment_amount);
    $credit_balance = max(0, $payment_amount - $total_amount);

    if($payment_amount <= 0) continue;

    if(!isset($monthlyData[$key])) $monthlyData[$key] = [
        'base_rent'=>0,'utility'=>0,'additional'=>0,
        'balance'=>0,'credit'=>0,'collected'=>0
    ];

    $monthlyData[$key]['base_rent']  += floatval($row['base_rent']);
    $monthlyData[$key]['utility']    += $utility_total;
    $monthlyData[$key]['additional'] += $add_total;
    $monthlyData[$key]['balance']    += $balance;
    $monthlyData[$key]['credit']     += $credit_balance;
    $monthlyData[$key]['collected']  += $payment_amount;
}
?>

<!-- ✅ Do not include sidebar or main-content here -->
<div style="padding: 10px 20px;">
    <h4 class="mb-3">Collection Report</h4>

    <form method="get" action="index.php" class="d-flex align-items-center mb-3" style="gap:10px;">
        <input type="hidden" name="tab" value="collection">
        <select name="month" class="form-select" style="width:150px;">
            <option value="">Select Month</option>
            <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= ($selectedMonth==$m?'selected':'') ?>>
                    <?= date("F", mktime(0,0,0,$m,1)) ?>
                </option>
            <?php endfor; ?>
        </select>

        <select name="year" class="form-select" style="width:120px;">
            <option value="">Select Year</option>
            <?php for($y=date("Y");$y>=2020;$y--): ?>
                <option value="<?= $y ?>" <?= ($selectedYear==$y?'selected':'') ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>

        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    </form>

    <?php if(count($monthlyData) > 0): ?>
        <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Month</th>
                        <th class="text-end">Base Rent</th>
                        <th class="text-end">Utility Amount</th>
                        <th class="text-end">Additional Amount</th>
                        <th class="text-end">Balance</th>
                        <th class="text-end">Credit Balance</th>
                        <th class="text-end">Monthly Collected</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($monthlyData as $monthYear => $data): ?>
                    <tr>
                        <td><?= htmlspecialchars($monthYear) ?></td>
                        <td class="text-end">₱<?= number_format($data['base_rent'],2) ?></td>
                        <td class="text-end">₱<?= number_format($data['utility'],2) ?></td>
                        <td class="text-end">₱<?= number_format($data['additional'],2) ?></td>
                        <td class="text-end">₱<?= number_format($data['balance'],2) ?></td>
                        <td class="text-end">₱<?= number_format($data['credit'],2) ?></td>
                        <td class="text-end fw-bold">₱<?= number_format($data['collected'],2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-warning mt-3">
            No collection records found for the selected month/year.
        </div>
    <?php endif; ?>
</div>
