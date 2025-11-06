<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// --- Filters ---
$tenant_filter = $_GET['tenant'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

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

    // Get full billing details
    $bill_stmt = $conn->prepare("SELECT * FROM billing WHERE tenant_id = ? AND bill_id = ?");
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
<title>Collection Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: Arial,sans-serif; background:#f0f4f3; padding:20px; }
.container { background:#fff; padding:20px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1); max-width:1200px; margin:auto; }
h2 { text-align:center; margin-bottom:20px; }
.table th, .table td { text-align:center; vertical-align:middle; }
.total { font-weight:bold; text-align:right; margin-top:10px; font-size:16px; }
.filter-form { margin-bottom:20px; display:flex; gap:10px; flex-wrap:wrap; }
.filter-form input, .filter-form button { padding:5px; }
</style>
</head>
<body>

<div class="container">
    <h2>📊 Collection Report</h2>

    <!-- Filters -->
    <form method="get" class="filter-form">
        <input type="text" name="tenant" placeholder="Search Tenant" value="<?php echo htmlspecialchars($tenant_filter); ?>">
        <label>From: <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>"></label>
        <label>To: <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>"></label>
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>

    <!-- Table -->
    <table class="table table-bordered">
        <thead class="table-secondary">
            <tr>
                <th>Bill ID</th>
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
                    echo "<tr>
                        <td>{$row['bill_id']}</td>
                        <td>{$row['tenant_name']}</td>
                        <td>{$row['room_number']}</td>
                        <td>₱" . number_format($row['total_balance'],2) . "</td>
                        <td>{$row['due_date']}</td>
                        <td>{$row['status']}</td>
                        <td>
                            <button class='btn btn-sm btn-info view-btn' data-bill='{$row['bill_id']}'>View</button>
                        </td>
                    </tr>";
                    $total_collection += $row['total_balance'];
                }
            } else {
                echo "<tr><td colspan='7'>No records found.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="total">TOTAL COLLECTION: ₱<?php echo number_format($total_collection,2); ?></div>
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
                    <td class="fw-bold">Room Number:</td><td>${bill.room_id}</td>
                    <td class="fw-bold">Due Date:</td><td>${bill.due_date}</td>
                    <td class="fw-bold">Payment Date:</td><td>${bill.payment_date || '-'}</td>
                </tr>
                <tr>
                    <td class="fw-bold">Base Rent:</td><td>₱${parseFloat(bill.base_rent || 0).toFixed(2)}</td>
                    <td class="fw-bold">Interest:</td><td>₱${parseFloat(bill.interest || 0).toFixed(2)}</td>
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

