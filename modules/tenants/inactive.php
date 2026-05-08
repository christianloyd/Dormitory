<?php
/**
 * Tenants Module - Inactive Tenants Billing History
 * Path: /modules/tenants/inactive.php
 */
require_once '../../includes/auth_check.php';
require_once __DIR__ . '/../../helpers/BillingItems.php';

// Fetch dorm header image
$headerRow = $conn->query("SELECT setting_value FROM settings WHERE setting_name='header_image'")->fetch_assoc();
$headerPic = $headerRow ? BASE_PATH . '/' . $headerRow['setting_value'] : BASE_PATH . '/uploads/default_header.png';

$tenantQuery = $conn->query("
    SELECT tenant_id, tenant_name
    FROM tenants
    WHERE status='Inactive'
    ORDER BY tenant_name ASC
");
$tenants = $tenantQuery->fetch_all(MYSQLI_ASSOC);

$selectedTenant = null;
$billingData    = null;
$selectedName   = '';

if (isset($_POST['generate_bill'])) {
    $selectedTenant = intval($_POST['tenant']);

    // Get selected tenant name for display
    foreach ($tenants as $t) {
        if ($t['tenant_id'] == $selectedTenant) {
            $selectedName = $t['tenant_name'];
            break;
        }
    }

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

// Status badge helper
function statusBadge(string $status): string {
    return match(strtolower(trim($status))) {
        'settled' => '<span class="status-badge status-settled"><i class="fas fa-check-circle me-1"></i>Settled</span>',
        'partial' => '<span class="status-badge status-partial"><i class="fas fa-adjust me-1"></i>Partial</span>',
        default   => '<span class="status-badge status-pending"><i class="fas fa-clock me-1"></i>Pending</span>',
    };
}
// Plain text status for print
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
<title>Inactive Tenants – Billing History</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
/* ─── Base ─────────────────────────────────────────────── */
body {
    background-color: #f0f4f3;
    font-family: 'Segoe UI', Arial, sans-serif;
    margin: 0;
}
.main-content {
    margin-left: 225px;
    padding: 30px;
    min-height: 100vh;
}

/* ─── Page Header ──────────────────────────────────────── */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}
.page-header h2 {
    font-size: 1.55rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
}
.page-header h2 i {
    color: #5A7D7C;
    margin-right: 10px;
}

/* ─── Control Card ─────────────────────────────────────── */
.control-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    padding: 20px 24px;
    margin-bottom: 24px;
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    align-items: flex-end;
}
.control-card label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #555;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.control-card label i {
    color: #5A7D7C;
}
.tenant-select {
    min-width: 320px;
    max-width: 480px;
    border-radius: 8px;
    border-color: #dde2e6;
    font-size: 0.92rem;
    flex: 1;
}
.tenant-select:focus {
    border-color: #5A7D7C;
    box-shadow: 0 0 0 3px rgba(90,125,124,0.2);
}
.btn-generate {
    background-color: #5A7D7C;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 9px 20px;
    font-size: 0.9rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 7px;
    transition: background 0.2s;
}
.btn-generate:hover { background-color: #4a6c6b; color: #fff; }
.btn-print {
    background-color: #fff;
    color: #5A7D7C;
    border: 1.5px solid #5A7D7C;
    border-radius: 8px;
    padding: 9px 20px;
    font-size: 0.9rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 7px;
    transition: all 0.2s;
}
.btn-print:hover { background: #5A7D7C; color: #fff; }

/* ─── Tenant Name Banner ───────────────────────────────── */
.tenant-banner {
    background: linear-gradient(135deg, #5A7D7C 0%, #3d5e5d 100%);
    color: #fff;
    border-radius: 12px;
    padding: 16px 24px;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.tenant-banner i { font-size: 1.5rem; opacity: 0.85; }
.tenant-banner .name { font-size: 1.15rem; font-weight: 700; }
.tenant-banner .sub  { font-size: 0.82rem; opacity: 0.8; margin-top: 2px; }

/* ─── Billing Card ─────────────────────────────────────── */
.bill-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    margin-bottom: 20px;
    overflow: hidden;
}
.bill-card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
}
.bill-card-header .room-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    color: #2c3e50;
    font-size: 1rem;
}
.bill-card-header .room-info i { color: #5A7D7C; }
.bill-card-header .bill-dates {
    font-size: 0.82rem;
    color: #6c757d;
    display: flex;
    gap: 14px;
}
.bill-card-header .bill-dates span {
    display: flex;
    align-items: center;
    gap: 5px;
}
.bill-card-body {
    padding: 4px 0;
}

/* ─── Bill Row ─────────────────────────────────────────── */
.bill-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-bottom: 1px solid #f2f2f2;
}
.bill-row:last-child { border-bottom: none; }
.bill-cell {
    padding: 11px 20px;
    font-size: 0.9rem;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.bill-cell + .bill-cell {
    border-left: 1px solid #f2f2f2;
}
.bill-cell .cell-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.85rem;
    margin-top: 1px;
}
.bill-cell .cell-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 2px;
}
.bill-cell .cell-value {
    font-size: 0.95rem;
    color: #2c3e50;
    font-weight: 500;
}
.bill-cell .cell-value.mono { font-family: 'Courier New', monospace; }

/* Icon color themes */
.icon-room    { background: #e8f4f4; color: #5A7D7C; }
.icon-cal     { background: #e8f0fb; color: #4a6fa5; }
.icon-rent    { background: #fff0e6; color: #e67e22; }
.icon-late    { background: #fce8e8; color: #e74c3c; }
.icon-util    { background: #e8fbf0; color: #27ae60; }
.icon-add     { background: #f5e8fb; color: #8e44ad; }
.icon-balance { background: #fff8e6; color: #f39c12; }
.icon-credit  { background: #e8f4f4; color: #16a085; }
.icon-payment { background: #e6eeff; color: #2980b9; }
.icon-method  { background: #f0f0f0; color: #555; }
.icon-total   { background: #2c3e50; color: #fff; }
.icon-status  { background: transparent; }

/* ─── Status Badges ────────────────────────────────────── */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.82rem;
    font-weight: 600;
}
.status-settled { background: #d4edda; color: #155724; }
.status-partial { background: #fff3cd; color: #856404; }
.status-pending { background: #f8d7da; color: #721c24; }

/* ─── Empty/No-Bill State ───────────────────────────────── */
.no-bills {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    padding: 48px 24px;
    text-align: center;
    color: #aaa;
}
.no-bills i { font-size: 3rem; margin-bottom: 14px; display: block; opacity: 0.4; }
.no-bills p { font-size: 1rem; }


</style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>



<div class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fas fa-user-slash"></i> Inactive Tenants — Billing History</h2>
    </div>

    <!-- Control Card -->
    <div class="control-card">
        <form method="post" style="display:contents;">
            <input type="hidden" name="tab" value="inactive_tenant">
            <div style="flex:1; min-width:260px;">
                <label for="tenant"><i class="fas fa-user"></i> Select Inactive Tenant</label>
                <select name="tenant" id="tenant" required class="form-select tenant-select">
                    <option value="">-- Choose Tenant --</option>
                    <?php foreach ($tenants as $t): ?>
                        <option value="<?= $t['tenant_id'] ?>" <?= ($selectedTenant == $t['tenant_id'] ? 'selected' : '') ?>>
                            <?= htmlspecialchars($t['tenant_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; gap:10px; align-items:flex-end;">
                <button type="submit" name="generate_bill" class="btn-generate">
                    <i class="fas fa-file-invoice-dollar"></i> Generate Bill
                </button>
                <?php if ($billingData): ?>
                    <a href="print_inactive.php?tenant=<?= $selectedTenant ?>" target="_blank" class="btn-print" style="text-decoration:none;">
                        <i class="fas fa-print"></i> Print Report
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if ($billingData !== null): ?>
        <!-- Tenant Banner -->
        <div class="tenant-banner">
            <i class="fas fa-user-circle"></i>
            <div>
                <div class="name"><?= htmlspecialchars($selectedName) ?></div>
                <div class="sub"><i class="fas fa-history me-1"></i><?= count($billingData) ?> billing record(s) found</div>
            </div>
        </div>

        <?php if (empty($billingData)): ?>
            <div class="no-bills">
                <i class="fas fa-file-excel"></i>
                <p>No billing records found for this tenant.</p>
            </div>
        <?php else: ?>
            <?php foreach ($billingData as $row):
                $utilityItems     = getBillingUtilityItems($conn, (int)$row['bill_id']);
                $utilityLabels    = array_column($utilityItems, 'label');
                $utilityAmounts   = array_map(fn($i) => (float)($i['amount'] ?? 0), $utilityItems);
                $additionalItems  = getBillingAdditionalItems($conn, (int)$row['bill_id']);
                $additionalLabels = array_column($additionalItems, 'label');
                $additionalAmounts = array_map(fn($i) => (float)($i['amount'] ?? 0), $additionalItems);
                $dueFormatted     = $row['due_date']     ? date('M d, Y', strtotime($row['due_date'])) : '—';
                $paidFormatted    = $row['payment_date'] ? date('M d, Y', strtotime($row['payment_date'])) : '—';
            ?>
            <div class="bill-card">
                <!-- Card Header -->
                <div class="bill-card-header">
                    <div class="room-info">
                        <i class="fas fa-door-open"></i>
                        Room <?= htmlspecialchars($row['room_number']) ?>
                    </div>
                    <div class="bill-dates">
                        <span><i class="fas fa-calendar-alt"></i> Due: <?= $dueFormatted ?></span>
                        <span><i class="fas fa-calendar-check"></i> Paid: <?= $paidFormatted ?></span>
                    </div>
                </div>

                <!-- Card Body Grid -->
                <div class="bill-card-body">
                    <!-- Row 1: Rent + Late Charge -->
                    <div class="bill-row">
                        <div class="bill-cell">
                            <div class="cell-icon icon-rent"><i class="fas fa-home"></i></div>
                            <div>
                                <div class="cell-label">Base Rent</div>
                                <div class="cell-value mono">₱<?= number_format($row['base_rent'], 2) ?></div>
                            </div>
                        </div>
                        <div class="bill-cell">
                            <div class="cell-icon icon-late"><i class="fas fa-exclamation-triangle"></i></div>
                            <div>
                                <div class="cell-label">Late Payment Charge</div>
                                <div class="cell-value mono">₱<?= number_format($row['interest'], 2) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Utilities -->
                    <div class="bill-row">
                        <div class="bill-cell">
                            <div class="cell-icon icon-util"><i class="fas fa-bolt"></i></div>
                            <div>
                                <div class="cell-label">Utility Fees</div>
                                <div class="cell-value">
                                    <?= !empty($utilityLabels) ? implode(', ', array_map('htmlspecialchars', $utilityLabels)) : '<span class="text-muted">—</span>' ?>
                                </div>
                            </div>
                        </div>
                        <div class="bill-cell">
                            <div class="cell-icon icon-util"><i class="fas fa-tachometer-alt"></i></div>
                            <div>
                                <div class="cell-label">Utility Amount</div>
                                <div class="cell-value mono">₱<?= number_format(array_sum($utilityAmounts), 2) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Additional Charges -->
                    <div class="bill-row">
                        <div class="bill-cell">
                            <div class="cell-icon icon-add"><i class="fas fa-plus-circle"></i></div>
                            <div>
                                <div class="cell-label">Additional Charges</div>
                                <div class="cell-value">
                                    <?= !empty($additionalLabels) ? implode(', ', array_map('htmlspecialchars', $additionalLabels)) : '<span class="text-muted">—</span>' ?>
                                </div>
                            </div>
                        </div>
                        <div class="bill-cell">
                            <div class="cell-icon icon-add"><i class="fas fa-receipt"></i></div>
                            <div>
                                <div class="cell-label">Additional Amount</div>
                                <div class="cell-value mono">₱<?= number_format(array_sum($additionalAmounts), 2) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 4: Balance + Previous Balance -->
                    <div class="bill-row">
                        <div class="bill-cell">
                            <div class="cell-icon icon-balance"><i class="fas fa-balance-scale"></i></div>
                            <div>
                                <div class="cell-label">Balance</div>
                                <div class="cell-value mono">₱<?= number_format($row['balance'], 2) ?></div>
                            </div>
                        </div>
                        <div class="bill-cell">
                            <div class="cell-icon icon-balance"><i class="fas fa-history"></i></div>
                            <div>
                                <div class="cell-label">Previous Balance</div>
                                <div class="cell-value mono">₱<?= number_format($row['previous_balance'], 2) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 5: Credit Balance + Previous Credit -->
                    <div class="bill-row">
                        <div class="bill-cell">
                            <div class="cell-icon icon-credit"><i class="fas fa-hand-holding-usd"></i></div>
                            <div>
                                <div class="cell-label">Credit Balance</div>
                                <div class="cell-value mono">₱<?= number_format($row['credit_balance'], 2) ?></div>
                            </div>
                        </div>
                        <div class="bill-cell">
                            <div class="cell-icon icon-credit"><i class="fas fa-undo"></i></div>
                            <div>
                                <div class="cell-label">Previous Credit Balance</div>
                                <div class="cell-value mono">₱<?= number_format($row['previous_credit'], 2) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 6: Payment Amount + Payment Method -->
                    <div class="bill-row">
                        <div class="bill-cell">
                            <div class="cell-icon icon-payment"><i class="fas fa-money-bill-wave"></i></div>
                            <div>
                                <div class="cell-label">Payment Amount</div>
                                <div class="cell-value mono">₱<?= number_format($row['payment_amount'], 2) ?></div>
                            </div>
                        </div>
                        <div class="bill-cell">
                            <div class="cell-icon icon-method"><i class="fas fa-credit-card"></i></div>
                            <div>
                                <div class="cell-label">Payment Method</div>
                                <div class="cell-value">
                                    <?= $row['payment_method'] ? htmlspecialchars($row['payment_method']) : '<span class="text-muted">—</span>' ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 7: Total Amount + Status -->
                    <div class="bill-row" style="background:#fafafa;">
                        <div class="bill-cell">
                            <div class="cell-icon icon-total"><i class="fas fa-file-invoice-dollar"></i></div>
                            <div>
                                <div class="cell-label">Total Amount</div>
                                <div class="cell-value mono" style="font-size:1.05rem;font-weight:700;color:#2c3e50;">
                                    ₱<?= number_format($row['total_amount'], 2) ?>
                                </div>
                            </div>
                        </div>
                        <div class="bill-cell">
                            <div class="cell-icon icon-status"></div>
                            <div>
                                <div class="cell-label">Status</div>
                                <div class="cell-value"><?= statusBadge($row['status'] ?? 'Pending') ?></div>
                            </div>
                        </div>
                    </div>
                </div><!-- /bill-card-body -->
            </div><!-- /bill-card -->
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div><!-- /main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
