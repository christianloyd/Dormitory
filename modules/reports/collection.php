<?php
/**
 * Reports Module
 * Path: /modules/reports/collection.php
 */
require_once "../../includes/auth_check.php";

// Fetch dorm header image for print
$hdrRow = $conn->query("SELECT setting_value FROM settings WHERE setting_name='header_image'")->fetch_assoc();
$hdrPic = $hdrRow ? BASE_PATH . '/' . $hdrRow['setting_value'] : BASE_PATH . '/uploads/default_header.png';

// --- Filters ---
$tenant_filter = $_GET['tenant']    ?? '';
$from_date     = $_GET['from_date'] ?? '';
$to_date       = $_GET['to_date']   ?? '';

// --- Base query --- only active tenants
$query = "
    SELECT 
        b.bill_id,
        t.tenant_id,
        t.tenant_name,
        r.room_number,
        b.balance AS total_balance,
        b.due_date,
        b.status
    FROM billing b
    JOIN tenants t ON b.tenant_id = t.tenant_id
    JOIN rooms r ON b.room_id = r.room_id
    WHERE t.status = 'Active'
";

$params = [];
$types = '';

if (!empty($tenant_filter)) {
    $query .= " AND t.tenant_name LIKE ?";
    $params[] = "%$tenant_filter%";
    $types .= 's';
}

if (!empty($from_date)) {
    $query .= " AND b.due_date >= ?";
    $params[] = $from_date;
    $types .= 's';
}

if (!empty($to_date)) {
    $query .= " AND b.due_date <= ?";
    $params[] = $to_date;
    $types .= 's';
}

$query .= " ORDER BY b.due_date DESC";

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Pre-fetch all billing info for modal
$billing_details = [];
$rows = [];
while ($row = $result->fetch_assoc()) {
    $tenant_id = $row['tenant_id'];

    // Get full billing details including room number
    $bill_stmt = $conn->prepare("
        SELECT b.*, r.room_number 
        FROM billing b 
        JOIN rooms r ON b.room_id = r.room_id 
        WHERE b.tenant_id = ? AND b.bill_id = ?
    ");
    $bill_stmt->bind_param("ii", $tenant_id, $row['bill_id']);
    $bill_stmt->execute();
    $bill_details = $bill_stmt->get_result()->fetch_assoc();
    $bill_stmt->close();

    $billing_details[$row['bill_id']] = $bill_details;
    $rows[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Collection Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f4f3; margin: 0; }
.main-content { margin-left: 225px; padding: 30px; min-height: 100vh; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.page-header h2 { font-size: 1.6rem; font-weight: 700; color: #2c3e50; margin: 0; }
.page-header h2 i { color: #5A7D7C; margin-right: 8px; }
.card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 20px; }
.card-body { padding: 20px 24px; }
.btn-teal { background-color: #5A7D7C; color: #fff; border: none; border-radius: 8px; }
.btn-teal:hover { background-color: #4a6c6b; color: #fff; }
.report-table { width: 100%; border-collapse: collapse; min-width: 700px; }
.report-table thead th { background: #5A7D7C; color: #fff; padding: 12px 14px; text-align: center; font-size: 0.87rem; font-weight: 600; position: sticky; top: 0; z-index: 2; white-space: nowrap; }
.report-table tbody td { padding: 10px 14px; text-align: center; border-bottom: 1px solid #eee; font-size: 0.9rem; vertical-align: middle; }
.report-table tbody tr:hover { background-color: #f1f8f7; }
.table-wrapper { overflow-x: auto; overflow-y: auto; max-height: 520px; }
.badge-settled { background: #d4edda; color: #155724; border-radius: 20px; padding: 4px 12px; font-size: 0.82rem; font-weight: 600; }
.badge-partial  { background: #fff3cd; color: #856404; border-radius: 20px; padding: 4px 12px; font-size: 0.82rem; font-weight: 600; }
.badge-pending  { background: #f8d7da; color: #721c24; border-radius: 20px; padding: 4px 12px; font-size: 0.82rem; font-weight: 600; }
.summary-box { background: #fff; border: 1px solid #dee2e6; border-radius: 10px; padding: 14px 20px; font-weight: 700; font-size: 1rem; color: #2c3e50; }

</style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>



<div class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fas fa-hand-holding-usd"></i> Collection Report</h2>
    </div>

    <!-- Filter Card -->
    <div class="card">
        <div class="card-body">
            <form method="get" class="d-flex flex-wrap gap-3 align-items-end">
                <div>
                    <label class="form-label fw-semibold small mb-1">Tenant Name</label>
                    <input type="text" name="tenant" class="form-control" placeholder="Search tenant..." value="<?php echo htmlspecialchars($tenant_filter); ?>" style="min-width:180px;">
                </div>
                <div>
                    <label class="form-label fw-semibold small mb-1">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($from_date); ?>">
                </div>
                <div>
                    <label class="form-label fw-semibold small mb-1">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($to_date); ?>">
                </div>
                <div>
                    <label class="d-block mb-1">&nbsp;</label>
                    <label class="d-block mb-1">&nbsp;</label>
                    <button type="submit" class="btn btn-teal"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="print_collection.php?tenant=<?= urlencode($tenant_filter) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>" target="_blank" class="btn btn-secondary ms-2" style="background:#2c3e50; border:none; padding:8px 18px; border-radius:8px;"><i class="fas fa-print me-1"></i> Print Report</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card">
        <div class="card-body">
            <div class="table-wrapper">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Tenant Name</th>
                        <th>Room</th>
                        <th>Total Balance</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
            <?php
            $total_collection = 0;
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $status = $row['status'] ?? '';
                    $badgeClass = match(strtolower($status)) {
                        'settled' => 'badge-settled',
                        'partial' => 'badge-partial',
                        default   => 'badge-pending',
                    };
                    echo "<tr>
                        <td>{$row['tenant_name']}</td>
                        <td>{$row['room_number']}</td>
                        <td>₱" . number_format((float)$row['total_balance'], 2) . "</td>
                        <td>{$row['due_date']}</td>
                        <td><span class='{$badgeClass}'>{$status}</span></td>
                        <td>
                            <button class='btn btn-sm view-btn' style='background:#5A7D7C;color:#fff;border-radius:6px;' data-bill='{$row['bill_id']}'>View</button>
                        </td>
                    </tr>";
                    $total_collection += (float)$row['total_balance'];
                }
            } else {
                echo "<tr><td colspan='6' class='text-center text-muted py-4'>No records found.</td></tr>";
            }
            ?>
                </tbody>
            </table>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <div class="summary-box"><i class="fas fa-coins me-2 text-success"></i> Total Collection: ₱<?php echo number_format($total_collection,2); ?></div>
            </div>
        </div>
    </div>

<!-- View Modal -->
<div class="modal fade" id="viewBillingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Billing Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="viewBillingContent">
        Loading...
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const billingDetails = <?php echo json_encode($billing_details); ?>;

document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const billId = this.getAttribute('data-bill');
        const bill = billingDetails[billId];

        if (bill) {
            // --- Safely parse utilities ---
            let utilities = [];
            let utilityAmounts = [];
            try {
                if (bill.utility_fee && bill.utility_fee.trim().startsWith("[")) {
                    utilities = JSON.parse(bill.utility_fee);
                } else if (bill.utility_fee) {
                    utilities = bill.utility_fee.split(","); // handle comma-separated string
                }

                if (bill.utility_amount && bill.utility_amount.trim().startsWith("[")) {
                    utilityAmounts = JSON.parse(bill.utility_amount);
                } else if (bill.utility_amount) {
                    utilityAmounts = bill.utility_amount.split(",").map(Number); // convert to numbers
                }

                if (!Array.isArray(utilities)) utilities = [utilities];
                if (!Array.isArray(utilityAmounts)) utilityAmounts = [utilityAmounts];
            } catch(e) {
                utilities = [bill.utility_fee || '-'];
                utilityAmounts = [bill.utility_amount || 0];
            }

            // --- Safely parse additional charges ---
            let addCharges = [];
            let addAmounts = [];
            try {
                if (bill.add_charges && bill.add_charges.trim().startsWith("[")) {
                    addCharges = JSON.parse(bill.add_charges);
                } else if (bill.add_charges) {
                    addCharges = bill.add_charges.split(",");
                }

                if (bill.add_amount && bill.add_amount.trim().startsWith("[")) {
                    addAmounts = JSON.parse(bill.add_amount);
                } else if (bill.add_amount) {
                    addAmounts = bill.add_amount.split(",").map(Number);
                }

                if (!Array.isArray(addCharges)) addCharges = [addCharges];
                if (!Array.isArray(addAmounts)) addAmounts = [addAmounts];
            } catch(e) {
                addCharges = [bill.add_charges || '-'];
                addAmounts = [bill.add_amount || 0];
            }

            // --- Build utility table rows ---
            let utilityRows = '';
            for(let i=0; i<utilities.length; i++){
                utilityRows += `<tr>
                    ${i===0 ? '<td class="fw-bold" rowspan="'+utilities.length+'">Utility Fee:</td>' : ''}
                    <td>${utilities[i] || '-'}</td>
                    ${i===0 ? '<td class="fw-bold" rowspan="'+utilities.length+'">Utility Amount:</td>' : ''}
                    <td>₱${parseFloat(utilityAmounts[i] || 0).toFixed(2)}</td>
                </tr>`;
            }

            // --- Build additional charges table rows ---
            let addRows = '';
            for(let i=0; i<addCharges.length; i++){
                addRows += `<tr>
                    ${i===0 ? '<td class="fw-bold" rowspan="'+addCharges.length+'">Additional Charges:</td>' : ''}
                    <td>${addCharges[i] || '-'}</td>
                    ${i===0 ? '<td class="fw-bold" rowspan="'+addCharges.length+'">Additional Amount:</td>' : ''}
                    <td>₱${parseFloat(addAmounts[i] || 0).toFixed(2)}</td>
                </tr>`;
            }

            // --- Build full modal content ---
            const content = `
            <table class="table table-bordered">
                <tr>
                    <td class="fw-bold">Room Number:</td><td>${bill.room_number}</td>
                    <td class="fw-bold">Due Date:</td><td>${bill.due_date}</td>
                    <td class="fw-bold">Payment Date:</td><td>${bill.payment_date || '-'}</td>
                </tr>
                <tr>
                    <td class="fw-bold">Base Rent:</td><td>₱${parseFloat(bill.base_rent || 0).toFixed(2)}</td>
                    <td class="fw-bold">Late Payment Charge:</td><td>₱${parseFloat(bill.interest || 0).toFixed(2)}</td>
                </tr>
                ${utilityRows}
                ${addRows}
                <tr>
                    <td class="fw-bold">Balance:</td><td>₱${parseFloat(bill.balance || 0).toFixed(2)}</td>
                    <td class="fw-bold">Previous Balance:</td><td>₱${parseFloat(bill.previous_balance || 0).toFixed(2)}</td>
                </tr>
                <tr>
                    <td class="fw-bold">Credit Balance:</td><td>₱${parseFloat(bill.credit_balance || 0).toFixed(2)}</td>
                    <td class="fw-bold">Previous Credit Balance:</td><td>₱${parseFloat(bill.previous_credit || 0).toFixed(2)}</td>
                </tr>
                <tr>
                    <td class="fw-bold">Payment Amount:</td><td>₱${parseFloat(bill.payment_amount || 0).toFixed(2)}</td>
                    <td class="fw-bold">Payment Method:</td><td>${bill.payment_method || '-'}</td>
                </tr>
                <tr>
                    <td class="fw-bold">Total Amount:</td><td>₱${parseFloat(bill.total_amount || 0).toFixed(2)}</td>
                    <td class="fw-bold">Status:</td><td>${bill.status || '-'}</td>
                </tr>
            </table>
            `;
            document.getElementById('viewBillingContent').innerHTML = content;
            new bootstrap.Modal(document.getElementById('viewBillingModal')).show();
        }
    });
});
</script>
</div>
</div>
</body>
</html>
