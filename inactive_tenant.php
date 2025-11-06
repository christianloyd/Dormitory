<?php
include 'db.php';

$tenantQuery = $conn->query("
    SELECT tenant_id, tenant_name
    FROM tenants
    WHERE status='Inactive'
    ORDER BY tenant_name ASC
");
$tenants = $tenantQuery->fetch_all(MYSQLI_ASSOC);

$selectedTenant = null;
$billingData = null;

if(isset($_POST['generate_bill'])){
    $selectedTenant = intval($_POST['tenant']);

    $stmt = $conn->prepare("
        SELECT b.*, r.room_number
        FROM billing b
        JOIN rooms r ON b.room_id = r.room_id
        WHERE b.tenant_id = ?
        ORDER BY b.payment_date ASC
    ");
    $stmt->bind_param("i", $selectedTenant);
    $stmt->execute();
    $billingData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<div class="mb-3">
    <form method="post" class="d-flex align-items-center gap-2">
        <input type="hidden" name="tab" value="inactive_tenant">

        <label for="tenant">Tenant:</label>
        <select name="tenant" id="tenant" required class="form-select">
            <option value="">-- Choose Tenant --</option>
            <?php foreach($tenants as $t): ?>
                <option value="<?= $t['tenant_id'] ?>" <?= ($selectedTenant==$t['tenant_id']?'selected':'') ?>>
                    <?= htmlspecialchars($t['tenant_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" name="generate_bill" class="btn btn-primary">Generate Bill</button>

        <?php if($billingData): ?>
            <button type="button" class="btn btn-success" onclick="window.print();">Print</button>
        <?php endif; ?>
    </form>
</div>

<?php if($billingData): ?>
    <?php foreach($billingData as $row): ?>
        <table class="table table-bordered mb-3" style="width:100%; border-collapse:collapse;">
            <tbody>
                <tr>
                    <td><strong>Room Number:</strong> <?= htmlspecialchars($row['room_number']) ?></td>
                    <td><strong>Due Date:</strong> <?= $row['due_date'] ?> | <strong>Payment Date:</strong> <?= $row['payment_date'] ?></td>
                </tr>
                <tr>
                    <td><strong>Base Rent:</strong> ₱<?= number_format($row['base_rent'],2) ?></td>
                    <td><strong>Interest:</strong> ₱<?= number_format($row['interest'],2) ?></td>
                </tr>
                <tr>
                    <td><strong>Utility Fee:</strong> <?= implode(', ', json_decode($row['utility_fee'],true) ?? []) ?></td>
                    <td><strong>Utility Amount:</strong> ₱<?= number_format(array_sum(json_decode($row['utility_amount'],true) ?? []),2) ?></td>
                </tr>
                <tr>
                    <td><strong>Additional Charges:</strong> <?= implode(', ', json_decode($row['add_charges'],true) ?? []) ?></td>
                    <td><strong>Additional Amount:</strong> ₱<?= number_format(array_sum(json_decode($row['add_amount'],true) ?? []),2) ?></td>
                </tr>
                <tr>
                    <td><strong>Balance:</strong> ₱<?= number_format($row['balance'],2) ?></td>
                    <td><strong>Previous Balance:</strong> ₱<?= number_format($row['previous_balance'],2) ?></td>
                </tr>
                <tr>
                    <td><strong>Credit Balance:</strong> ₱<?= number_format($row['credit_balance'],2) ?></td>
                    <td><strong>Previous Credit Balance:</strong> ₱<?= number_format($row['previous_credit'],2) ?></td>
                </tr>
                <tr>
                    <td><strong>Payment Amount:</strong> ₱<?= number_format($row['payment_amount'],2) ?></td>
                    <td><strong>Payment Method:</strong> <?= htmlspecialchars($row['payment_method']) ?></td>
                </tr>
                <tr>
                    <td><strong>Total Amount:</strong> ₱<?= number_format($row['total_amount'],2) ?></td>
                    <td>
                        <strong>Status:</strong> 
                        <span class="badge <?= $row['status']=='Settled'?'bg-success':'bg-danger' ?>">
                            <?= $row['status'] ?>
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    <?php endforeach; ?>
<?php endif; ?>
