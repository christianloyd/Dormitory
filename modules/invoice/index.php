<?php
/**
 * Invoice Module - Main Listing Page
 * Path: /modules/invoice/index.php
 */
require_once '../../includes/auth_check.php';
require_once __DIR__ . '/../../helpers/BillingItems.php';

// Capture tenant filter
$tenant_id = isset($_GET['tenant_id']) ? intval($_GET['tenant_id']) : 0;

// Base SQL
$sql = "
    SELECT 
        b.bill_id AS invoice_id,
        t.tenant_name,
        r.room_number,
        b.due_date,
        b.payment_date,
        b.base_rent,
        b.interest,
        b.previous_balance,
        b.previous_credit,
        b.other_amount,
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

// Add tenant filter if selected
if ($tenant_id > 0) {
    $sql .= " AND b.tenant_id = $tenant_id";  // <-- Use AND instead of WHERE
}

// Add ORDER BY
$sql .= " ORDER BY b.due_date DESC";

// Execute query
$result = $conn->query($sql);


?>
<!DOCTYPE html>
<html>
<head>
    <title>Invoice Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
body { 
    margin: 0; 
    font-family: Arial, sans-serif; 
    display: flex; 
    background-color: #f6f7f6;
}

.main-content {
    margin-left: 225px;
    padding: 30px; 
    background-color: #f6f7f6;
    min-height: 100vh; 
    width: calc(100% - 225px); 
    border-left: 2px solid #f0f4f3;
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px;
    margin-top: 15px;
    table-layout: fixed;
    background-color: #fff;
}

th, td { 
    border: 1px solid #ccc;
    padding: 12px; 
    word-wrap: break-word; 
    text-align: center; /* Center all headers and cells */
    vertical-align: middle; /* Vertically center content */
}

th { 
    background: #5A7D7C !important; 
    color: #fff !important; 
    font-weight: bold;
}

tbody tr {
    background: #fdfdfd;
    transition: all 0.3s;
}

tbody tr:hover {
    background: #e9f5f5;
}

.btn-view { 
    padding: 5px 10px; 
    background: #007bff; 
    color: white; 
    border-radius: 4px; 
    text-decoration: none; 
    cursor: pointer; 
}

.btn-view:hover { 
    background: #0056b3; 
}

.status-partial { 
    background-color: #FFF3CD !important; 
    color: #856404 !important; 
    font-weight: bold; 
    border-radius: 4px;
    padding: 5px 10px;
}

.status-settled { 
    background-color: #D4EDDA !important; 
    color: #155724 !important; 
    font-weight: bold; 
    border-radius: 4px;
    padding: 5px 10px;
}
.status-partial { 
    background-color: #FFF3CD !important; /* Light Yellow */
    color: #856404 !important; 
    font-weight: bold; 
    border-radius: 4px;
    padding: 5px 10px;
}

body, html {
    width: 100%;
    height: 100%;
    overflow: hidden; /* Prevent scrolling */
}

    </style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mt-0">
            <h2>📄 Invoice Report</h2>
        </div>
        <hr style="width: 100%; margin: 10px auto; border: 1px solid #140d0dff;">

       <!-- Filter by Tenant -->
<form method="get" style="margin-bottom:15px;">
    <label for="tenant_id">Select Tenant:</label>
    <select name="tenant_id" onchange="this.form.submit()">
        <option value="0">All</option>
        <?php
        // Fetch only active tenants
        $tenants = $conn->query("SELECT tenant_id, tenant_name FROM tenants WHERE status = 'Active' ORDER BY tenant_name ASC");
        while ($t = $tenants->fetch_assoc()):
        ?>
            <option value="<?= $t['tenant_id'] ?>" <?= ($tenant_id==$t['tenant_id']?'selected':'') ?>>
                <?= htmlspecialchars($t['tenant_name']) ?>
            </option>
        <?php endwhile; ?>
    </select>
</form>


        <!-- Invoice Table -->
        <div class="container bg-white p-3 rounded shadow-sm" style="max-width: 100%; overflow-x:auto;">
            <div style="max-height: 600px; overflow-y: auto;">
                <table class="table table-bordered table-striped align-middle" style="min-width: 1300px;">
                    <thead class="sticky-top">
                        <tr>
                            <th>Tenant Name</th>
                            <th>Room</th>
                            <th>Due Date</th>
                            <th>Payment Date</th>
                            <th>Base Rent</th>
                            <th>Utility Fee</th>
                            <th>Additional Charges</th>
                            <th>Interest</th>
                            <th>Total Amount</th>
                            <th>Payment Amount</th>
                            <th>Credit Balance</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                 <tbody>
<?php if ($result && $result->num_rows > 0): ?>
   <?php while ($row = $result->fetch_assoc()): ?>
    <?php
        $billId = intval($row['invoice_id']);
        $interest = floatval($row['interest'] ?? 0);

        $utilityItems = getBillingUtilityItems($conn, $billId);
        $additionalItems = getBillingAdditionalItems($conn, $billId);

        $utility_total = sumBillingItems($utilityItems);
        $add_total = sumBillingItems($additionalItems);

        $previous_balance = floatval($row['previous_balance'] ?? 0);
        $previous_credit = floatval($row['previous_credit'] ?? 0);
        $other_amount = floatval($row['other_amount'] ?? 0);

        $total_amount = floatval($row['base_rent']) + $utility_total + $add_total + $interest + $previous_balance + $other_amount - $previous_credit;

        $payment_amount = floatval($row['payment_amount'] ?? 0);
        $credit_balance = max(0, $payment_amount - $total_amount);

        if ($payment_amount >= $total_amount) {
            $visible_status = 'Settled';
        } elseif ($payment_amount > 0) {
            $visible_status = 'Partial';
        } else {
            // Skip Pending
            continue;
        }
    ?>
    <tr>
        <td><?= htmlspecialchars($row['tenant_name']) ?></td>
        <td><?= htmlspecialchars($row['room_number']) ?></td>
        <td><?= date("M d, Y", strtotime($row['due_date'])) ?></td>
        <td><?= !empty($row['payment_date']) ? date("M d, Y", strtotime($row['payment_date'])) : '-' ?></td>
        <td>₱<?= number_format($row['base_rent'], 2) ?></td>
        <td>₱<?= number_format($utility_total, 2) ?></td>
        <td>₱<?= number_format($add_total, 2) ?></td>
        <td>₱<?= number_format($interest, 2) ?></td>
        <td>₱<?= number_format($total_amount, 2) ?></td>
        <td>₱<?= number_format($payment_amount, 2) ?></td>
        <td style="color:green;">₱<?= number_format($credit_balance, 2) ?></td>
        <td class="<?= $visible_status=='Partial'?'status-partial':'status-settled' ?>">
            <?= $visible_status ?>
        </td>
        <td>
            <button class="btn-view" onclick="openInvoice(<?= $row['invoice_id'] ?>)">🖨 View / Print</button>
        </td>
    </tr>
<?php endwhile; ?>

<?php else: ?>
    <tr><td colspan="13">No invoices found.</td></tr>
<?php endif; ?>
</tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="invoiceModal" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body" id="invoiceContent">
                Loading...
              </div>
              <div class="modal-footer justify-content-center">
                  <button class="btn btn-success" onclick="printInvoice()">🖨 Print</button>
                  <button class="btn btn-primary" onclick="exportInvoice()">💾 Export as Image</button>
              </div>
            </div>
          </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
function openInvoice(id) {
    fetch("view.php?id=" + id)
    .then(res => res.text())
    .then(html => {
        document.getElementById("invoiceContent").innerHTML = html;
        let modal = new bootstrap.Modal(document.getElementById("invoiceModal"));
        modal.show();
    })
    .catch(err => {
        document.getElementById("invoiceContent").innerHTML = "<p class='text-danger'>Failed to load invoice.</p>";
    });
}

function printInvoice() {
    let content = document.getElementById("invoiceContent").innerHTML;
    let printWindow = window.open('', '', 'width=900,height=650');
    printWindow.document.write('<html><head><title>Print Invoice</title></head><body>');
    printWindow.document.write(content);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

function exportInvoice() {
    let invoice = document.getElementById("invoiceContent");
    html2canvas(invoice).then(canvas => {
        let link = document.createElement("a");
        link.download = "invoice.png";
        link.href = canvas.toDataURL();
        link.click();
    });
}
</script>
</body>
</html>
