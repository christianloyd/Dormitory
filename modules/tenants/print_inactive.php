<?php
require_once '../../includes/auth_check.php';
require_once __DIR__ . '/../../helpers/BillingItems.php';

$headerRow = $conn->query("SELECT setting_value FROM settings WHERE setting_name='header_image'")->fetch_assoc();
$headerPic = $headerRow ? BASE_PATH . '/' . $headerRow['setting_value'] : BASE_PATH . '/uploads/default_header.png';

$selectedTenant = isset($_GET['tenant']) ? intval($_GET['tenant']) : 0;
$selectedName = '';

$tq = $conn->prepare("SELECT tenant_name FROM tenants WHERE tenant_id = ?");
$tq->bind_param("i", $selectedTenant);
$tq->execute();
$tRes = $tq->get_result();
if ($tRow = $tRes->fetch_assoc()) {
    $selectedName = $tRow['tenant_name'];
}
$tq->close();

$billingData = [];
if ($selectedTenant > 0) {
    $stmt = $conn->prepare("
        SELECT b.*, r.room_number
        FROM billing b
        JOIN rooms r ON b.room_id = r.room_id
        WHERE b.tenant_id = ?
        ORDER BY b.due_date DESC
    ");
    $stmt->bind_param("i", $selectedTenant);
    $stmt->execute();
    $billingData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

function statusText(string $status): string {
    return match(strtolower(trim($status))) {
        'settled' => 'Settled',
        'partial' => 'Partial',
        default   => 'Pending',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Print - Inactive Tenant Billing History</title>
<style>
body { background: #fff; font-family: Arial, sans-serif; font-size: 11pt; color: #000; margin: 0; }
* { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; box-sizing: border-box; }
@page { margin: 18mm 15mm; size: A4 portrait; }

.print-header { display: flex; align-items: center; gap: 20px; padding: 0 0 14px 0; border-bottom: 2.5px solid #2c3e50; margin-bottom: 18px; }
.print-header img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #2c3e50; }
.print-header .print-meta { flex: 1; }
.print-header .print-title { font-size: 1.25rem; font-weight: 700; color: #2c3e50; margin-bottom: 4px; }
.print-header .print-tenant { font-size: 0.95rem; margin-bottom: 2px; color: #333; }
.print-header .print-date   { font-size: 0.82rem; color: #666; }

.tenant-banner { background: #f0f0f0; color: #000; border: 1px solid #ccc; border-radius: 6px; padding: 12px 16px; margin-bottom: 12px; font-weight: 700; }

.bill-card { border: 1px solid #ccc; margin-bottom: 14px; page-break-inside: avoid; }
.bill-card-header { background: #e8e8e8; border-bottom: 1.5px solid #999; padding: 8px 12px; display: flex; justify-content: space-between; font-weight: bold; }
.bill-row { display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1px solid #e0e0e0; }
.bill-row:last-child { border-bottom: none; }
.bill-row.summary-row { background: #f5f5f5; }
.bill-cell { padding: 7px 12px; display: flex; flex-direction: column; }
.bill-cell + .bill-cell { border-left: 1px solid #e0e0e0; }
.cell-label { font-size: 0.75rem; font-weight: 600; color: #555; text-transform: uppercase; margin-bottom: 2px; }
.cell-value { font-size: 0.95rem; color: #000; }
.mono { font-family: 'Courier New', monospace; }

.no-bills { text-align: center; padding: 40px; color: #555; font-style: italic; }
</style>
</head>
<body onload="window.print();">

<div class="print-header">
    <img src="<?= htmlspecialchars($headerPic) ?>" alt="Logo">
    <div class="print-meta">
        <div class="print-title">Billing History — Inactive Tenant</div>
        <?php if ($selectedName): ?>
            <div class="print-tenant">Tenant: <strong><?= htmlspecialchars($selectedName) ?></strong></div>
        <?php endif; ?>
        <div class="print-date">Generated: <?= date('F d, Y \a\t h:i A') ?></div>
    </div>
</div>

<?php if (empty($billingData)): ?>
    <div class="no-bills">No billing records found for this tenant.</div>
<?php else: ?>
    <div class="tenant-banner">
        <?= htmlspecialchars($selectedName) ?> — <?= count($billingData) ?> record(s)
    </div>

    <?php foreach ($billingData as $row):
        $utilityItems     = getBillingUtilityItems($conn, (int)$row['bill_id']);
        $utilityLabels    = array_column($utilityItems, 'label');
        $utilityAmounts   = array_map(fn($i) => (float)($i['amount'] ?? 0), $utilityItems);
        $additionalItems  = getBillingAdditionalItems($conn, (int)$row['bill_id']);
        $additionalLabels = array_column($additionalItems, 'label');
        $additionalAmounts = array_map(fn($i) => (float)($i['amount'] ?? 0), $additionalItems);
        $dueFormatted     = $row['due_date']     ? date('M d, Y', strtotime($row['due_date'])) : '-';
        $paidFormatted    = $row['payment_date'] ? date('M d, Y', strtotime($row['payment_date'])) : '-';
    ?>
    <div class="bill-card">
        <div class="bill-card-header">
            <span>Room <?= htmlspecialchars($row['room_number']) ?></span>
            <span>Due: <?= $dueFormatted ?> &nbsp;|&nbsp; Paid: <?= $paidFormatted ?></span>
        </div>
        <div class="bill-row">
            <div class="bill-cell">
                <div class="cell-label">Base Rent</div>
                <div class="cell-value mono">₱<?= number_format($row['base_rent'], 2) ?></div>
            </div>
            <div class="bill-cell">
                <div class="cell-label">Late Payment Charge</div>
                <div class="cell-value mono">₱<?= number_format($row['interest'], 2) ?></div>
            </div>
        </div>
        <div class="bill-row">
            <div class="bill-cell">
                <div class="cell-label">Utility Fees</div>
                <div class="cell-value"><?= !empty($utilityLabels) ? implode(', ', array_map('htmlspecialchars', $utilityLabels)) : '-' ?></div>
            </div>
            <div class="bill-cell">
                <div class="cell-label">Utility Amount</div>
                <div class="cell-value mono">₱<?= number_format(array_sum($utilityAmounts), 2) ?></div>
            </div>
        </div>
        <div class="bill-row">
            <div class="bill-cell">
                <div class="cell-label">Additional Charges</div>
                <div class="cell-value"><?= !empty($additionalLabels) ? implode(', ', array_map('htmlspecialchars', $additionalLabels)) : '-' ?></div>
            </div>
            <div class="bill-cell">
                <div class="cell-label">Additional Amount</div>
                <div class="cell-value mono">₱<?= number_format(array_sum($additionalAmounts), 2) ?></div>
            </div>
        </div>
        <div class="bill-row">
            <div class="bill-cell">
                <div class="cell-label">Balance</div>
                <div class="cell-value mono">₱<?= number_format($row['balance'], 2) ?></div>
            </div>
            <div class="bill-cell">
                <div class="cell-label">Previous Balance</div>
                <div class="cell-value mono">₱<?= number_format($row['previous_balance'], 2) ?></div>
            </div>
        </div>
        <div class="bill-row">
            <div class="bill-cell">
                <div class="cell-label">Credit Balance</div>
                <div class="cell-value mono">₱<?= number_format($row['credit_balance'], 2) ?></div>
            </div>
            <div class="bill-cell">
                <div class="cell-label">Previous Credit Balance</div>
                <div class="cell-value mono">₱<?= number_format($row['previous_credit'], 2) ?></div>
            </div>
        </div>
        <div class="bill-row">
            <div class="bill-cell">
                <div class="cell-label">Payment Amount</div>
                <div class="cell-value mono">₱<?= number_format($row['payment_amount'], 2) ?></div>
            </div>
            <div class="bill-cell">
                <div class="cell-label">Payment Method</div>
                <div class="cell-value"><?= $row['payment_method'] ? htmlspecialchars($row['payment_method']) : '-' ?></div>
            </div>
        </div>
        <div class="bill-row summary-row">
            <div class="bill-cell">
                <div class="cell-label">Total Amount</div>
                <div class="cell-value mono" style="font-weight:bold;">₱<?= number_format($row['total_amount'], 2) ?></div>
            </div>
            <div class="bill-cell">
                <div class="cell-label">Status</div>
                <div class="cell-value" style="font-weight:bold;"><?= statusText($row['status'] ?? 'Pending') ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
