<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Function to get room number
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

// Tenant info
$tenant_sql = "SELECT t.*, r.price AS base_rent, r.room_number 
               FROM tenants t
               JOIN rooms r ON t.room_id = r.room_id
               WHERE t.tenant_id = ?";

$stmt = $conn->prepare($tenant_sql);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$tenant = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tenant) die("Tenant not found.");

// Billing history (ascending)
$bill_sql = "SELECT b.*, t.tenant_name 
             FROM billing b
             JOIN tenants t ON b.tenant_id = t.tenant_id
             WHERE b.tenant_id = ?
             ORDER BY b.due_date ASC";
$stmt = $conn->prepare($bill_sql);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$bill_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// -----------------------------
// Determine if reminder prompt should show
// -----------------------------
$showReminderPrompt = false;
$tenant_name = $tenant['tenant_name'];
$guardian_name = $tenant['guardian_name'] ?? '';

// Check if there are pending bills
$hasPendingBills = false;
$oldestPendingBillDate = null;

foreach ($bill_history as $bill) {
    if ($bill['status'] === 'Pending') {
        $hasPendingBills = true;
        $oldestPendingBillDate = $bill['due_date'];
        break; // Get the oldest pending bill
    }
}

// Only show reminder prompt if:
// 1. There are pending bills
// 2. AND no reminder SMS sent for this tenant for the current pending bill period
if ($hasPendingBills && $oldestPendingBillDate) {
    // Check if reminder SMS already sent for this bill period
    // We check if any reminder (containing "Payment Reminder") was sent after the bill was created
    $checkSmsStmt = $conn->prepare("
        SELECT COUNT(*) as reminder_count
        FROM sms_logs
        WHERE tenant_id = ?
        AND message LIKE '%Payment Reminder%'
        AND status = 'sent'
        AND DATE(date_sent) >= DATE(?)
    ");

    $checkSmsStmt->bind_param("is", $tenant_id, $oldestPendingBillDate);
    $checkSmsStmt->execute();
    $smsCheck = $checkSmsStmt->get_result()->fetch_assoc();
    $checkSmsStmt->close();

    // Show prompt only if no reminder sent yet
    $showReminderPrompt = ($smsCheck['reminder_count'] == 0);
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
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
    #billing-container { display:flex; flex-direction:column; gap:10px; min-height:600px; }
    .billing-box { transition: opacity 0.2s ease; }
    .billing-box.removing { opacity: 0.5; }
    .billing-box table { width:100%; border-collapse:collapse; font-size:14px; }
    .billing-box td { padding:6px 8px; border:1px solid #ccc; vertical-align:top; }
    .billing-box td.label { font-weight:bold; background:#f5f5f5; width:25%; }
    .footer-nav { display:flex; justify-content:space-between; margin-top:20px; }
</style>
</head>
<body class="bg-light">
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h2>Billing for <?php echo htmlspecialchars($tenant_name); ?> (Room <?php echo htmlspecialchars($tenant['room_number']); ?>)</h2>

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
<?php if(empty($bill_history)): ?>
<p>No billing found for this tenant.</p>
<?php endif; ?>

<?php foreach ($bill_history as $index => $row): 

    // Utility Fees
    $utilityFees = json_decode($row['utility_fee'], true);
    if (!is_array($utilityFees)) $utilityFees = [$utilityFees ?? 0];
    $utilityAmounts = json_decode($row['utility_amount'], true);
    if (!is_array($utilityAmounts)) $utilityAmounts = [$utilityAmounts ?? 0];
    $utilityAmounts = array_pad($utilityAmounts, count($utilityFees), 0);

    // Additional Charges
    $addCharges = json_decode($row['add_charges'], true);
    if (!is_array($addCharges)) $addCharges = [$addCharges ?? 0];
    $addAmounts = json_decode($row['add_amount'], true);
    if (!is_array($addAmounts)) $addAmounts = [$addAmounts ?? 0];
    $addAmounts = array_pad($addAmounts, count($addCharges), 0);

    // Base Rent
    $room_id_for_bill = $row['room_id'];
    $stmt_room = $conn->prepare("SELECT price FROM rooms WHERE room_id = ?");
    $stmt_room->bind_param("i", $room_id_for_bill);
    $stmt_room->execute();
    $room = $stmt_room->get_result()->fetch_assoc();
    $stmt_room->close();
    $base_rent_for_bill = floatval($room['price']);

    // Totals
    $total_utility = array_sum($utilityAmounts);
    $total_add = array_sum($addAmounts);
    $total = $base_rent_for_bill + $total_utility + $total_add + floatval($prev_balance) + floatval($row['interest']);
    $payment = floatval($row['payment_amount']);
    $balance = 0; 
    $credit = 0; 
    $status = "";
    $total_display = $total;

    if ($prev_credit >= $total) {
        $credit = $prev_credit - $total;
        $balance = 0;
        $status = "Settled";
        $total_display = 0.00;
    } elseif ($prev_credit > 0) {
        $balance = max($total - $prev_credit - $payment,0);
        $credit = 0;
        $status = ($payment >= ($total - $prev_credit)) ? "Settled" : (($payment > 0) ? "Partial" : "Pending Payment");
        $total_display = $total - $prev_credit;
    } else {
        $balance = max($total - $payment,0);
        $credit = ($payment > $total) ? $payment - $total : 0;
        $status = ($payment >= $total) ? "Settled" : (($payment > 0) ? "Partial" : "Pending Payment");
        $total_display = $total;
    }

    $current_prev_balance = $balance;
    $current_prev_credit = $credit;
?>

<div class="billing-box bill-item" data-index="<?php echo $index; ?>" style="display:none;">
    <table>
        <tr>
            <td class="label">Room Number:</td>
            <td><?php echo htmlspecialchars(getRoomNumber($conn, $row['room_id'])); ?></td>
        </tr>
        <tr>
            <td class="label">Due Date:</td><td><?php echo htmlspecialchars($row['due_date']); ?></td>
            <td class="label">Payment Date:</td><td><?php echo htmlspecialchars($row['payment_date']); ?></td>
        </tr>
        <tr>
            <td class="label">Base Rent:</td><td>₱<?php echo number_format($base_rent_for_bill,2); ?></td>
            <td class="label">Interest:</td><td>₱<?php echo number_format($row['interest'],2); ?></td>
        </tr>
        <!-- Utility Fees -->
        <?php foreach ($utilityFees as $key => $fee): ?>
        <tr>
            <?php if($key==0): ?>
                <td class="label" rowspan="<?php echo count($utilityFees); ?>">Utility Fee:</td>
                <td><?php echo htmlspecialchars($fee); ?></td>
                <td class="label" rowspan="<?php echo count($utilityFees); ?>">Utility Amount:</td>
                <td>₱<?php echo number_format($utilityAmounts[$key],2); ?></td>
            <?php else: ?>
                <td><?php echo htmlspecialchars($fee); ?></td>
                <td>₱<?php echo number_format($utilityAmounts[$key],2); ?></td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <!-- Additional Charges -->
        <?php foreach ($addCharges as $key => $charge): ?>
        <tr>
            <?php if($key==0): ?>
                <td class="label" rowspan="<?php echo count($addCharges); ?>">Additional Charges:</td>
                <td><?php echo htmlspecialchars($charge); ?></td>
                <td class="label" rowspan="<?php echo count($addCharges); ?>">Additional Amount:</td>
                <td>₱<?php echo number_format($addAmounts[$key],2); ?></td>
            <?php else: ?>
                <td><?php echo htmlspecialchars($charge); ?></td>
                <td>₱<?php echo number_format($addAmounts[$key],2); ?></td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td class="label">Balance:</td><td>₱<?php echo number_format($balance,2); ?></td>
            <td class="label">Previous Balance:</td><td>₱<?php echo number_format($prev_balance,2); ?></td>
        </tr>
        <tr>
            <td class="label">Credit Balance:</td><td>₱<?php echo number_format($credit,2); ?></td>
            <td class="label">Previous Credit Balance:</td><td>₱<?php echo number_format($prev_credit,2); ?></td>
        </tr>
        <tr>
            <td class="label">Payment Amount:</td><td>₱<?php echo number_format($payment,2); ?></td>
            <td class="label">Payment Method:</td><td><?php echo htmlspecialchars($row['payment_method']); ?></td>
        </tr>
        <tr>
            <td class="label">Total Amount:</td><td>₱<?php echo number_format($total_display,2); ?></td>
            <td class="label">Status:</td><td><?php echo $status; ?></td>
        </tr>
    </table>

    <div class="actions mt-2 text-end">
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal<?php echo $row['bill_id']; ?>">Payment</button>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editBillModal<?php echo $row['bill_id']; ?>">Edit</button>
        <a href="delete_bill.php?bill_id=<?php echo $row['bill_id']; ?>" class="delete-btn btn btn-sm btn-danger">Delete</a>
    </div>

<?php 
    $prev_balance = $current_prev_balance;
    $prev_credit = $current_prev_credit;
    include 'forms/edit_bill_form.php';
    include 'forms/payment_modal.php';
?>
</div>
<?php endforeach; ?>
</div>

<div class="footer-nav">
    <button id="prevBtn" class="btn btn-secondary">&laquo; Previous</button>
    <button id="nextBtn" class="btn btn-secondary">Next &raquo;</button>
</div>

<div class="back-btn mt-3">
    <?php 
    $backUrl = "duedatelist.php";
    if (!empty($date)) {
        $backUrl .= "?date=" . urlencode($date);
    } elseif (!empty($start_date) && !empty($end_date)) {
        $backUrl .= "?start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date);
    }
    ?>
    <a href="<?php echo $backUrl; ?>" class="btn btn-secondary">Back to List of Billing</a>
</div>

<!-- Add Bill Modal -->
<?php include 'forms/add_bill_form.php'; ?>
<?php include 'forms/reminder_message_modal.php'; ?>
<?php include 'forms/payment_confirmation_modal.php'; ?>


<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

$showPaymentConfirmPrompt = $_SESSION['showPaymentConfirmPrompt'] ?? false;
$paymentTenantName = $_SESSION['tenant_name'] ?? '';
$paymentGuardianName = $_SESSION['guardian_name'] ?? ''; // still works


// Clear session so prompt doesn’t show again on refresh
unset($_SESSION['showPaymentConfirmPrompt'], $_SESSION['tenant_name'], $_SESSION['guardian_name']);
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // --- Pagination ---
    let bills = document.querySelectorAll('.bill-item');
    let currentIndex = 0;
    const perPage = 2;

    function showBills() {
        bills.forEach((bill, index) => {
            bill.style.display = (index >= currentIndex && index < currentIndex + perPage) ? 'block' : 'none';
        });
    }

    document.getElementById('prevBtn').addEventListener('click', () => {
        if (currentIndex - perPage >= 0) {
            currentIndex -= perPage;
            showBills();
        }
    });

    document.getElementById('nextBtn').addEventListener('click', () => {
        if (currentIndex + perPage < bills.length) {
            currentIndex += perPage;
            showBills();
        }
    });

    // --- Search by Month ---
    const searchInput = document.getElementById('monthSearch');
    searchInput.addEventListener('keyup', function() {
        const filter = searchInput.value.toLowerCase();
        bills.forEach(box => {
            const dueDateCell = box.querySelector('td:nth-child(2)');
            if (dueDateCell) {
                const monthName = new Date(dueDateCell.textContent)
                    .toLocaleString('default', { month: 'long' }).toLowerCase();
                box.style.display = (monthName.indexOf(filter) > -1) ? 'block' : 'none';
            }
        });
    });

    // --- Delete bill ---
    <!-- Make sure SweetAlert2 is loaded -->
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();

        const billBox = this.closest('.billing-box');
        const url = this.href;
        const tenantName = this.getAttribute('data-tenant') || 'this tenant'; // optional data attr

        Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to delete ${tenantName}'s billing?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(url)
                    .then(res => res.text())
                    .then(data => {
                        if (data.trim() === "success") {
                            Swal.fire({
                                title: 'Deleted!',
                                text: `${tenantName}'s billing has been successfully removed.`,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });

                            // Remove bill box
                            billBox.remove();

                            // Update pagination/list
                            bills = document.querySelectorAll('.bill-item');
                            if (currentIndex >= bills.length) {
                                currentIndex = Math.max(0, bills.length - perPage);
                            }
                            showBills();
                        } else {
                            Swal.fire('Error', 'Failed to delete the bill.', 'error');
                        }
                    })
                    .catch(() => {
                        Swal.fire('Error', 'Something went wrong while deleting.', 'error');
                    });
            }
        });
    });
});

    // --- Initial display ---
    showBills();

    // --- Payment Reminder Prompt (Pending only) ---
    <?php if($showReminderPrompt): ?>
    setTimeout(function() {
        Swal.fire({
            title: 'Payment Reminder',
            text: 'Do you want to send a payment reminder to Tenant <?php echo addslashes($tenant_name); ?> and Guardian <?php echo addslashes($guardian_name); ?>?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                var reminderModal = new bootstrap.Modal(document.getElementById('reminderMessageModal'));
                reminderModal.show();
            }
        });
    }, 1000);
    <?php endif; ?>

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

