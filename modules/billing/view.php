<?php
// Include database connection and auth
require_once "../../includes/auth_check.php";
require_once __DIR__ . '/../../helpers/BillingLock.php';
require_once __DIR__ . '/../../helpers/BillingItems.php';
require_once __DIR__ . '/../../helpers/TenantAssignments.php';


// Function to get room number by ID
function getRoomNumber($conn, $room_id) {
    $sql = "SELECT room_number FROM rooms WHERE room_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result ? $result['room_number'] : 'N/A';
}

$tenant_id = isset($_GET['tenant_id']) ? intval($_GET['tenant_id']) : 0;
if ($tenant_id <= 0) die("Invalid tenant ID.");

$billMonthParam = isset($_GET['bill_month']) ? trim($_GET['bill_month']) : '';
$bill_month = preg_match('/^\d{4}-\d{2}$/', $billMonthParam) ? $billMonthParam : '';
$bill_month_label = $bill_month ? date('F Y', strtotime($bill_month . '-01')) : '';

// Tenant info
$tenant_sql = "SELECT * FROM tenants WHERE tenant_id = ?";

$stmt = $conn->prepare($tenant_sql);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$tenant = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tenant) die("Tenant not found.");

// Active room assignments for this tenant
$tenantAssignments = TenantAssignments::fetchAssignments($conn, $tenant_id);

// Prepare rooms for dropdown in Add Bill modal (prioritize active assignments)
$rooms = [];
if (!empty($tenantAssignments)) {
    foreach ($tenantAssignments as $assignment) {
        $rooms[] = [
            'room_id' => (int)$assignment['room_id'],
            'room_number' => $assignment['room_number'],
            'room_type' => $assignment['room_type'] ?? 'Room',
            'price' => isset($assignment['price']) ? (float)$assignment['price'] : 0.0,
        ];
    }
}

if (empty($rooms)) {
    $roomStmt = $conn->prepare("SELECT room_id, room_number, room_type, price FROM rooms WHERE record_status = 'Active' ORDER BY room_number ASC");
    if ($roomStmt) {
        $roomStmt->execute();
        $roomResult = $roomStmt->get_result();
        if ($roomResult) {
            while ($roomRow = $roomResult->fetch_assoc()) {
                $rooms[] = [
                    'room_id' => (int)$roomRow['room_id'],
                    'room_number' => $roomRow['room_number'],
                    'room_type' => $roomRow['room_type'] ?? 'Room',
                    'price' => isset($roomRow['price']) ? (float)$roomRow['price'] : 0.0,
                ];
            }
        }
        $roomStmt->close();
    }
}

$default_room_id = $rooms[0]['room_id'] ?? 0;
$default_base_rent = isset($rooms[0]) ? (float)$rooms[0]['price'] : 0.0;
$tenant_room_summary = !empty($tenantAssignments)
    ? implode(', ', array_map(fn($assignment) => $assignment['room_number'], $tenantAssignments))
    : 'Unassigned';

// Billing history (ascending)
$bill_sql = "SELECT b.*, t.tenant_name
             FROM billing b
             JOIN tenants t ON b.tenant_id = t.tenant_id
             WHERE b.tenant_id = ?";

if (!empty($bill_month)) {
    $bill_sql .= " AND DATE_FORMAT(b.due_date, '%Y-%m') = ?";
}

$bill_sql .= " ORDER BY b.due_date ASC";

$stmt = $conn->prepare($bill_sql);

if (!empty($bill_month)) {
    $stmt->bind_param("is", $tenant_id, $bill_month);
} else {
    $stmt->bind_param("i", $tenant_id);
}
$stmt->execute();
$bill_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$billSummaries = [];
$billDetails = [];
$tenant_name = $tenant['tenant_name'] ?? '';
$prev_balance = 0;
$prev_credit = 0;

foreach ($bill_history as $index => $row) {
    $billId = isset($row['bill_id']) ? (int)$row['bill_id'] : 0;
    if ($billId <= 0) {
        continue;
    }

    $utilityItems = getBillingUtilityItems($conn, $billId);
    $addItems = getBillingAdditionalItems($conn, $billId);

    $utilityFees = array_column($utilityItems, 'label');
    $utilityAmounts = array_column($utilityItems, 'amount');

    $addCharges = array_column($addItems, 'label');
    $addAmounts = array_column($addItems, 'amount');

    $roomNumber = getRoomNumber($conn, $row['room_id']);

    $stmt_room = $conn->prepare("SELECT price FROM rooms WHERE room_id = ?");
    $stmt_room->bind_param("i", $row['room_id']);
    $stmt_room->execute();
    $room = $stmt_room->get_result()->fetch_assoc();
    $stmt_room->close();
    $base_rent_for_bill = isset($room['price']) ? (float)$room['price'] : 0.0;

    $total_utility = sumBillingItems($utilityItems);
    $total_add = sumBillingItems($addItems);
    $row_prev_balance = (float)($row['previous_balance'] ?? 0);
    $row_prev_credit = (float)($row['previous_credit'] ?? 0);
    $interestAmount = (float)($row['interest'] ?? 0);
    $otherAmount = (float)($row['other_amount'] ?? 0);

    $storedTotal = (float)($row['total_amount'] ?? 0);
    $payment = (float)($row['payment_amount'] ?? 0);
    $status = $row['status'] ?? 'Pending';

    $grossDue = $base_rent_for_bill + $total_utility + $total_add + $row_prev_balance + $interestAmount + $otherAmount;
    $amountDueBeforePayment = $storedTotal > 0.009
        ? $storedTotal
        : max(0, $grossDue - $row_prev_credit);

    $balance = (float)($row['balance'] ?? 0);
    if ($status === 'Settled') {
        $balance = 0;
    } elseif ($balance <= 0.009) {
        $balance = max(0, $amountDueBeforePayment - $payment);
    }

    $credit = (float)($row['credit_balance'] ?? 0);
    if ($credit <= 0.009 && $payment > $amountDueBeforePayment) {
        $credit = max(0, $payment - $amountDueBeforePayment);
    }

    $total_display = $amountDueBeforePayment;

    $current_prev_balance = $balance;
    $current_prev_credit = $credit;

    $is_bill_locked = isBillingLocked($row['due_date'], $row['status'] ?? null);

    $billSummaries[] = [
        'bill_id' => $billId,
        'due_date' => $row['due_date'] ?? '',
        'month_label' => !empty($row['due_date']) ? date('F Y', strtotime($row['due_date'])) : '',
        'room_number' => $roomNumber,
        'status' => $status,
        'total_display' => $total_display,
        'balance' => $balance,
        'payment' => $payment,
        'is_locked' => $is_bill_locked,
    ];

    $billDetails[$billId] = [
        'row' => $row,
        'room_number' => $roomNumber,
        'utilityFees' => $utilityFees,
        'utilityAmounts' => $utilityAmounts,
        'addCharges' => $addCharges,
        'addAmounts' => $addAmounts,
        'base_rent_for_bill' => $base_rent_for_bill,
        'balance' => $balance,
        'credit' => $credit,
        'previous_balance' => $prev_balance,
        'previous_credit' => $prev_credit,
        'payment' => $payment,
        'total_display' => $total_display,
        'status' => $status,
        'is_locked' => $is_bill_locked,
    ];

    $prev_balance = $current_prev_balance;
    $prev_credit = $current_prev_credit;
}

$selected_bill_id = isset($_GET['bill_id']) ? intval($_GET['bill_id']) : 0;
if (!isset($billDetails[$selected_bill_id])) {
    $selected_bill_id = !empty($billSummaries) ? (int)$billSummaries[0]['bill_id'] : 0;
}

$selectedDetail = ($selected_bill_id && isset($billDetails[$selected_bill_id])) ? $billDetails[$selected_bill_id] : null;

$queryParams = [
    'tenant_id' => $tenant_id
];
if (!empty($bill_month)) {
    $queryParams['bill_month'] = $bill_month;
}
if (!empty($date)) {
    $queryParams['date'] = $date;
}
if (!empty($start_date)) {
    $queryParams['start_date'] = $start_date;
}
if (!empty($end_date)) {
    $queryParams['end_date'] = $end_date;
}

$clientBillDetails = [];
foreach ($billDetails as $billId => $detail) {
    $row = $detail['row'];
    $utilityItems = [];
    foreach ($detail['utilityFees'] as $idx => $label) {
        $amount = $detail['utilityAmounts'][$idx] ?? 0;
        $utilityItems[] = [
            'label' => $label,
            'amount_display' => '₱' . number_format((float)$amount, 2)
        ];
    }

    $additionalItems = [];
    foreach ($detail['addCharges'] as $idx => $label) {
        $amount = $detail['addAmounts'][$idx] ?? 0;
        $additionalItems[] = [
            'label' => $label,
            'amount_display' => '₱' . number_format((float)$amount, 2)
        ];
    }

    $dueDateRaw = $row['due_date'] ?? '';
    $paymentDateRaw = $row['payment_date'] ?? '';
    $dueDateDisplay = !empty($dueDateRaw) ? date('F d, Y', strtotime($dueDateRaw)) : '—';
    $paymentDateDisplay = !empty($paymentDateRaw) ? date('F d, Y', strtotime($paymentDateRaw)) : '—';

    $clientBillDetails[$billId] = [
        'bill_id' => $billId,
        'bill_label' => 'BILL #' . $billId,
        'room_number' => $detail['room_number'],
        'due_date_display' => $dueDateDisplay,
        'due_date' => $dueDateRaw,
        'payment_date_display' => $paymentDateDisplay,
        'payment_method' => $row['payment_method'] ?? '—',
        'base_rent_display' => '₱' . number_format((float)$detail['base_rent_for_bill'], 2),
        'late_payment_display' => '₱' . number_format((float)($row['interest'] ?? 0), 2),
        'other_amount_display' => '₱' . number_format((float)($row['other_amount'] ?? 0), 2),
        'status' => $detail['status'],
        'utility_items' => $utilityItems,
        'additional_items' => $additionalItems,
        'previous_balance_display' => '₱' . number_format((float)$detail['previous_balance'], 2),
        'previous_credit_display' => '₱' . number_format((float)$detail['previous_credit'], 2),
        'balance_display' => '₱' . number_format((float)$detail['balance'], 2),
        'credit_balance_display' => '₱' . number_format((float)$detail['credit'], 2),
        'payment_amount_display' => '₱' . number_format((float)$detail['payment'], 2),
        'total_due_display' => '₱' . number_format((float)$detail['total_display'], 2),
        'can_modify' => !$detail['is_locked'],
        'payment_modal_id' => 'paymentModal' . $billId,
        'edit_modal_id' => 'editBillModal' . $billId,
        'delete_url' => 'delete.php?bill_id=' . $billId,
        'tenant_name' => $tenant_name,
        'month_label' => !empty($detail['row']['due_date']) ? date('F Y', strtotime($detail['row']['due_date'])) : '',
    ];
}

// Reminder prompt deprecated now that billing notices auto-send
$showReminderPrompt = false;

// Determine if payment confirmation prompt should show
$showPaymentConfirmPrompt = false;
$tenant_name = $tenant['tenant_name'];
$guardian_name = $tenant['guardian_name'] ?? '';

foreach ($bill_history as $bill) {
    // Only show confirmation if status is Partial or Settled
    if ($bill['status'] === 'Partial' || $bill['status'] === 'Settled') {
        $showPaymentConfirmPrompt = true;
        break;
    }
}


// Capture params for back button
$date = isset($_GET['date']) ? $_GET['date'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Prepare running balances
$prev_balance = 0;
$prev_credit = 0;
$reminderContext = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Bill</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { margin:0; font-family:'Arial',sans-serif; display:flex; background-color:#f0f4f3; }
    .main-content {
        margin-left:225px;
        padding:20px;
        background-color:#fff;
        min-height:100vh;
        width:calc(100% - 225px);
        border-left:2px solid #5A7D7C;
        display:flex;
        flex-direction:column;
    }
    #billing-container { display:flex; flex-direction:column; gap:24px; min-height:400px; }
    .bill-summary-card .table thead th { text-transform:uppercase; font-size:12px; letter-spacing:0.6px; }
    .bill-summary-card .table tbody td { vertical-align:middle; font-size:14px; }
    .bill-summary-card .table tbody tr.table-success { --bs-table-bg: rgba(90,125,124,0.12); }
    .bill-detail-card table { width:100%; border-collapse:collapse; font-size:14px; }
    .bill-detail-card td { padding:6px 8px; border:1px solid #ccc; vertical-align:top; }
    .bill-detail-card td.label { font-weight:bold; background:#f5f5f5; width:26%; }
    .bill-detail-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:12px; }
</style>
</head>
<body class="bg-light">
<?php include '../../includes/sidebar.php'; ?>

<div class="main-content">
    <h2>Billing for <?= htmlspecialchars($tenant_name); ?> (Rooms <?= htmlspecialchars($tenant_room_summary); ?>)</h2>
    <?php if (!empty($bill_month_label)): ?>
        <p class="text-muted">Showing bills due in <?= htmlspecialchars($bill_month_label); ?>.</p>
    <?php endif; ?>

    <!-- Add Bill + Search Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBillModal">+ Add Bill</button>
        </div>
        <div>
            <label for="monthSearch" class="form-label mb-0">Search by Month:</label>
            <input list="months" id="monthSearch" class="form-control d-inline-block" style="width:200px;" placeholder="e.g., September">
            <datalist id="months">
                <option value="January"><option value="February"><option value="March">
                <option value="April"><option value="May"><option value="June">
                <option value="July"><option value="August"><option value="September">
                <option value="October"><option value="November"><option value="December">
            </datalist>
        </div>
    </div>

<div id="billing-container">
<?php if(empty($billSummaries)): ?>
<p>
    <?php if (!empty($bill_month_label)): ?>
        No billing found for <?= htmlspecialchars($bill_month_label); ?>.
    <?php else: ?>
        No billing found for this tenant.
    <?php endif; ?>
</p>
<?php else: ?>
    <div class="card shadow-sm bill-summary-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Bill History</h5>
            <div class="d-flex align-items-center gap-2">
                <?php if (!empty($bill_month_label)): ?>
                    <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($bill_month_label); ?></span>
                <?php endif; ?>
                <span class="text-muted small">Showing <?= count($billSummaries); ?> bill<?= count($billSummaries) !== 1 ? 's' : ''; ?></span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Due Date</th>
                        <th scope="col">Room</th>
                        <th scope="col" class="text-end">Total Due</th>
                        <th scope="col" class="text-end">Paid</th>
                        <th scope="col" class="text-end">Balance</th>
                        <th scope="col" class="text-center">Status</th>
                        <th scope="col" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($billSummaries as $summary):
                    $rowClass = '';
                    $statusClass = 'secondary';
                    switch (strtolower($summary['status'])) {
                        case 'pending':
                        case 'pending payment':
                            $statusClass = 'warning';
                            break;
                        case 'partial':
                            $statusClass = 'info';
                            break;
                        case 'settled':
                            $statusClass = 'success';
                            break;
                        default:
                            $statusClass = 'secondary';
                    }
                ?>
                    <tr class="bill-summary-row <?= $rowClass; ?>" data-month="<?= strtolower(str_replace(' ', '-', $summary['month_label'])); ?>">
                        <td>
                            <strong><?= htmlspecialchars(date('M d, Y', strtotime($summary['due_date']))); ?></strong><br>
                            <small class="text-muted text-uppercase"><?= htmlspecialchars($summary['month_label']); ?></small>
                        </td>
                        <td><?= htmlspecialchars($summary['room_number']); ?></td>
                        <td class="text-end">₱<?= number_format($summary['total_display'], 2); ?></td>
                        <td class="text-end">₱<?= number_format($summary['payment'], 2); ?></td>
                        <td class="text-end">₱<?= number_format($summary['balance'], 2); ?></td>
                        <td class="text-center"><span class="badge bg-<?= $statusClass; ?>"><?= htmlspecialchars(ucfirst($summary['status'])); ?></span></td>
                        <td class="text-center">
                            <button type="button"
                                    class="btn btn-sm btn-outline-primary view-bill-btn"
                                    data-bill-id="<?= (int)$summary['bill_id']; ?>">
                                View
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php foreach ($billDetails as $billId => $detail):
        $row = $detail['row'];
        if (!$detail['is_locked']) {
            include '../../forms/edit_bill_form.php';
            include '../../forms/payment_modal.php';
        }
    endforeach; ?>

    <div class="modal fade" id="billDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" data-role="modal-title">Bill Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <div class="fw-semibold" data-role="modal-subtitle"></div>
                            <div class="text-muted small" data-role="modal-room"></div>
                        </div>
                        <span class="badge bg-secondary" data-role="modal-bill-label"></span>
                    </div>
                    <div data-role="detail-body"></div>
                </div>
                <div class="modal-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="me-auto">
                        <span class="badge text-bg-secondary" data-role="status-badge"></span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success" data-role="payment-btn">Payment</button>
                        <button type="button" class="btn btn-primary" data-role="edit-btn">Edit</button>
                        <button type="button" class="btn btn-danger" data-role="delete-btn">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>
</div>

<div class="back-btn mt-3">
    <?php 
    if (!empty($bill_month)) {
        $backUrl = "list.php?month=" . urlencode($bill_month);
    } elseif (!empty($date)) {
        $backUrl = "due_dates.php?date=" . urlencode($date);
    } elseif (!empty($start_date) && !empty($end_date)) {
        $backUrl = "due_dates.php?start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date);
    } else {
        $backUrl = "list.php";
    }
    ?>
    <a href="<?php echo $backUrl; ?>" class="btn btn-secondary">Back to List of Billing</a>
</div>

<!-- Add Bill Modal -->
<?php include '../../forms/add_bill_form.php'; ?>
<?php include '../../forms/reminder_message_modal.php'; ?>
<?php include '../../forms/payment_confirmation_modal.php'; ?>


<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../includes/auth_check.php';

$showPaymentConfirmPrompt = $_SESSION['showPaymentConfirmPrompt'] ?? false;
$paymentTenantName = $_SESSION['tenant_name'] ?? '';
$paymentGuardianName = $_SESSION['guardian_name'] ?? ''; // still works


// Clear session so prompt doesn’t show again on refresh
unset($_SESSION['showPaymentConfirmPrompt'], $_SESSION['tenant_name'], $_SESSION['guardian_name']);
?>
<script>
window.billDetails = <?= json_encode($clientBillDetails, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const billModalEl = document.getElementById('billDetailModal');
    const modal = billModalEl ? new bootstrap.Modal(billModalEl) : null;
    const modalTitle = billModalEl ? billModalEl.querySelector('[data-role="modal-title"]') : null;
    const modalSubtitle = billModalEl ? billModalEl.querySelector('[data-role="modal-subtitle"]') : null;
    const modalRoom = billModalEl ? billModalEl.querySelector('[data-role="modal-room"]') : null;
    const modalBillLabel = billModalEl ? billModalEl.querySelector('[data-role="modal-bill-label"]') : null;
    const modalDetailBody = billModalEl ? billModalEl.querySelector('[data-role="detail-body"]') : null;
    const statusBadge = billModalEl ? billModalEl.querySelector('[data-role="status-badge"]') : null;
    const paymentBtn = billModalEl ? billModalEl.querySelector('[data-role="payment-btn"]') : null;
    const editBtn = billModalEl ? billModalEl.querySelector('[data-role="edit-btn"]') : null;
    const deleteBtn = billModalEl ? billModalEl.querySelector('[data-role="delete-btn"]') : null;

    const detailData = window.billDetails || {};

    const searchInput = document.getElementById('monthSearch');
    const tableRows = Array.from(document.querySelectorAll('.bill-summary-row'));

    if (searchInput && tableRows.length) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim().toLowerCase();
            tableRows.forEach(row => {
                const monthSlug = row.dataset.month || '';
                if (!query) {
                    row.style.display = '';
                    return;
                }
                row.style.display = monthSlug.indexOf(query.replace(/\s+/g, '-')) > -1 ? '' : 'none';
            });
        });
    }

    function buildDetailTable(data) {
        if (!data) {
            return '<div class="alert alert-info">No details available.</div>';
        }

        const utilityRows = (data.utility_items || []).map(item => `
            <tr>
                <td>${item.label}</td>
                <td>${item.amount_display}</td>
            </tr>
        `).join('') || '<tr><td colspan="2" class="text-muted">None</td></tr>';

        const additionalRows = (data.additional_items || []).map(item => `
            <tr>
                <td>${item.label}</td>
                <td>${item.amount_display}</td>
            </tr>
        `).join('') || '<tr><td colspan="2" class="text-muted">None</td></tr>';

        return `
            <div class="table-responsive">
            <table class="table table-bordered align-middle small">
                <tbody>
                    <tr>
                        <th style="width:22%">Due Date</th><td>${data.due_date_display}</td>
                        <th style="width:22%">Payment Date</th><td>${data.payment_date_display}</td>
                    </tr>
                    <tr>
                        <th>Room</th><td>${data.room_number}</td>
                        <th>Payment Method</th><td>${data.payment_method}</td>
                    </tr>
                    <tr>
                        <th>Base Rent</th><td>${data.base_rent_display}</td>
                        <th>Late Payment</th><td>${data.late_payment_display}</td>
                    </tr>
                    <tr>
                        <th>Other Amount</th><td>${data.other_amount_display}</td>
                        <th>Total Due</th><td>${data.total_due_display}</td>
                    </tr>
                    <tr>
                        <th>Previous Balance</th><td>${data.previous_balance_display}</td>
                        <th>Previous Credit</th><td>${data.previous_credit_display}</td>
                    </tr>
                    <tr>
                        <th>Current Balance</th><td>${data.balance_display}</td>
                        <th>Credit Balance</th><td>${data.credit_balance_display}</td>
                    </tr>
                    <tr>
                        <th>Payment Amount</th><td>${data.payment_amount_display}</td>
                        <th>Status</th><td>${data.status}</td>
                    </tr>
                </tbody>
            </table>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="fw-semibold">Utility Charges</h6>
                    <table class="table table-sm table-striped">
                        <tbody>${utilityRows}</tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-semibold">Additional Charges</h6>
                    <table class="table table-sm table-striped">
                        <tbody>${additionalRows}</tbody>
                    </table>
                </div>
            </div>
        `;
    }

    function attachActionHandlers(data) {
        if (!data) {
            paymentBtn?.setAttribute('disabled', 'disabled');
            editBtn?.setAttribute('disabled', 'disabled');
            deleteBtn?.setAttribute('disabled', 'disabled');
            return;
        }

        const canModify = !!data.can_modify;

        if (paymentBtn) {
            if (canModify) {
                paymentBtn.removeAttribute('disabled');
                paymentBtn.onclick = () => {
                    const modalTarget = document.getElementById(data.payment_modal_id);
                    if (modalTarget) {
                        const paymentModal = bootstrap.Modal.getOrCreateInstance(modalTarget);
                        modal.hide();
                        paymentModal.show();
                    }
                };
            } else {
                paymentBtn.setAttribute('disabled', 'disabled');
                paymentBtn.onclick = null;
            }
        }

        if (editBtn) {
            if (canModify) {
                editBtn.removeAttribute('disabled');
                editBtn.onclick = () => {
                    const modalTarget = document.getElementById(data.edit_modal_id);
                    if (modalTarget) {
                        const editModal = bootstrap.Modal.getOrCreateInstance(modalTarget);
                        modal.hide();
                        editModal.show();
                    }
                };
            } else {
                editBtn.setAttribute('disabled', 'disabled');
                editBtn.onclick = null;
            }
        }

        if (deleteBtn) {
            if (canModify && data.delete_url) {
                deleteBtn.removeAttribute('disabled');
                deleteBtn.onclick = () => {
                    Swal.fire({
                        title: 'Delete bill?',
                        text: 'This action cannot be undone.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Delete'
                    }).then(result => {
                        if (!result.isConfirmed) {
                            return;
                        }
                        fetch(data.delete_url)
                            .then(res => res.text())
                            .then(body => {
                                const outcome = body.trim();
                                if (outcome === 'locked') {
                                    Swal.fire('Locked', 'This billing record is locked and cannot be deleted.', 'info');
                                    return;
                                }
                                if (outcome === 'success') {
                                    Swal.fire('Deleted!', 'Bill removed successfully.', 'success').then(() => window.location.reload());
                                } else {
                                    Swal.fire('Error', 'Failed to delete bill.', 'error');
                                }
                            })
                            .catch(() => Swal.fire('Error', 'Something went wrong.', 'error'));
                    });
                };
            } else {
                deleteBtn.setAttribute('disabled', 'disabled');
                deleteBtn.onclick = null;
            }
        }
    }

    document.querySelectorAll('.view-bill-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!modal) {
                return;
            }
            const billId = parseInt(btn.getAttribute('data-bill-id'), 10);
            const data = detailData[billId];

            if (!data) {
                Swal.fire('Missing data', 'Unable to load bill details.', 'warning');
                return;
            }

            if (modalTitle) {
                modalTitle.textContent = 'Bill Details';
            }
            if (modalSubtitle) {
                modalSubtitle.textContent = `Due ${data.due_date_display}`;
            }
            if (modalRoom) {
                modalRoom.textContent = `Room ${data.room_number}`;
            }
            if (modalBillLabel) {
                modalBillLabel.textContent = data.bill_label;
            }
            if (statusBadge) {
                statusBadge.textContent = data.status;
                statusBadge.className = 'badge text-bg-secondary';
                const statusLower = (data.status || '').toLowerCase();
                if (statusLower === 'settled') {
                    statusBadge.classList.replace('text-bg-secondary', 'text-bg-success');
                } else if (statusLower === 'partial') {
                    statusBadge.classList.replace('text-bg-secondary', 'text-bg-info');
                } else if (statusLower.startsWith('pending')) {
                    statusBadge.classList.replace('text-bg-secondary', 'text-bg-warning');
                }
            }

            if (modalDetailBody) {
                modalDetailBody.innerHTML = buildDetailTable(data);
            }

            attachActionHandlers(data);
            modal.show();
        });
    });


    // --- Payment Confirmation Prompt (Partial/Settled only) ---
    <?php if($showPaymentConfirmPrompt): ?>
    setTimeout(function() {
        Swal.fire({
            title: 'Payment Confirmation',
            text: 'Do you want to send a payment confirmation to Tenant <?php echo addslashes($paymentTenantName); ?> and Guardian <?php echo addslashes($paymentGuardianName); ?>?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                var confirmationModal = new bootstrap.Modal(document.getElementById('paymentConfirmationModal'));
                confirmationModal.show();
            }
        });
    }, 1000);
    <?php endif; ?>
});
</script>

