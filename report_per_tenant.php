<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';

$selectedTenant = isset($_GET['tenant_id']) ? intval($_GET['tenant_id']) : 0;

// Fetch tenants for dropdown
$tenants = $conn->query("SELECT tenant_id, tenant_name FROM tenants ORDER BY tenant_name ASC");

// Fetch selected tenant name
$tenantName = '';
if ($selectedTenant) {
    $tRes = $conn->query("SELECT tenant_name FROM tenants WHERE tenant_id = $selectedTenant");
    if ($tRes && $tRes->num_rows > 0) {
        $tenantName = $tRes->fetch_assoc()['tenant_name'];
    }
}
?>

<form method="get" action="report.php" class="mb-3">
    <input type="hidden" name="tab" value="per_tenant">
    <label>Select Tenant:</label>
    <select name="tenant_id" onchange="this.form.submit()">
        <option value="">-- Select Tenant --</option>
        <?php while($t = $tenants->fetch_assoc()): ?>
            <option value="<?= $t['tenant_id'] ?>" <?= ($selectedTenant == $t['tenant_id'] ? 'selected' : '') ?>>
                <?= htmlspecialchars($t['tenant_name']) ?>
            </option>
        <?php endwhile; ?>
    </select>
</form>

<?php
if ($selectedTenant):
    if ($tenantName) echo "<h4>Tenant: " . htmlspecialchars($tenantName) . "</h4>";

    $sql = "SELECT * FROM billing WHERE tenant_id = $selectedTenant ORDER BY due_date ASC";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0): ?>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; text-align:center; border:1px solid #ccc;">
                <thead>
                    <tr style="background:#5A7D7C; color:white;">
                        <th style="padding:10px; border:1px solid #ccc;">Due Date</th>
                        <th style="padding:10px; border:1px solid #ccc;">Total Amount</th>
                        <th style="padding:10px; border:1px solid #ccc;">Payment Amount</th>
                        <th style="padding:10px; border:1px solid #ccc;">Balance</th>
                        <th style="padding:10px; border:1px solid #ccc;">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $res->fetch_assoc()):
                    $balance = $row['total_amount'] - $row['payment_amount'];
                    $status = $balance <= 0 ? '<span style="color:green;font-weight:bold;">Settled</span>' 
                             : ($row['payment_amount'] > 0 ? '<span style="color:orange;font-weight:bold;">Partial</span>'
                             : '<span style="color:red;font-weight:bold;">Pending</span>');
                ?>
                    <tr>
                        <td style="padding:8px; border:1px solid #ccc;"><?= date('Y-m-d', strtotime($row['due_date'])) ?></td>
                        <td style="padding:8px; border:1px solid #ccc;">₱<?= number_format($row['total_amount'],2) ?></td>
                        <td style="padding:8px; border:1px solid #ccc;">₱<?= number_format($row['payment_amount'],2) ?></td>
                        <td style="padding:8px; border:1px solid #ccc;">₱<?= number_format($balance,2) ?></td>
                        <td style="padding:8px; border:1px solid #ccc;"><?= $status ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>No billing records found for this tenant.</p>
    <?php endif; 
endif;
?>
