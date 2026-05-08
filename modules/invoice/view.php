<?php
/**
 * Invoice Module - View/Print Individual Invoice
 * Path: /modules/invoice/view.php
 */
require_once '../../includes/auth_check.php';
require_once __DIR__ . '/../../helpers/BillingItems.php';

if (!isset($_GET['id'])) {
    die("Invoice ID is required.");
}

$bill_id = intval($_GET['id']);

// Set timezone to Manila
date_default_timezone_set("Asia/Manila");

// Fetch full billing details
$query = $conn->query("
    SELECT b.*, t.tenant_name, t.address, t.contact_number, r.room_number
    FROM billing b
    JOIN tenants t ON b.tenant_id = t.tenant_id
    JOIN rooms r ON b.room_id = r.room_id
    WHERE b.bill_id = $bill_id
");

$bill = $query->fetch_assoc();
if (!$bill) {
    die("Invoice not found.");
}

// Invoice generated timestamp
$generated_datetime = date("M d, Y h:i A");

// Payment Date display
$payment_date = !empty($bill['payment_date']) ? date("M d, Y", strtotime($bill['payment_date'])) : '-';

// Fetch normalized billing line items
$utilityItems = getBillingUtilityItems($conn, $bill_id);
$additionalItems = getBillingAdditionalItems($conn, $bill_id);

$utility_fee = array_column($utilityItems, 'label');
$utility_amount = array_map(fn($item) => $item['amount'] ?? 0, $utilityItems);

$add_charges = array_column($additionalItems, 'label');
$add_amount = array_map(fn($item) => $item['amount'] ?? 0, $additionalItems);

// Compute totals safely
$utility_total = array_sum(array_map('floatval', $utility_amount));
$add_total = array_sum(array_map('floatval', $add_amount));
$interest = floatval($bill['interest'] ?? 0);
$previous_balance = floatval($bill['previous_balance'] ?? 0);
$previous_credit = floatval($bill['previous_credit'] ?? 0);
$other_amount = floatval($bill['other_amount'] ?? 0);

$total_calculated = floatval($bill['base_rent']) + $utility_total + $add_total + $interest + $previous_balance + $other_amount - $previous_credit;
$payment_amount = floatval($bill['payment_amount'] ?? 0);
$balance = max(0, $total_calculated - $payment_amount);
$credit_balance = max(0, $payment_amount - $total_calculated);

// Compute Status dynamically
if ($payment_amount >= $total_calculated) {
    $status = 'Settled';
} elseif ($payment_amount > 0) {
    $status = 'Partial';
} else {
    $status = 'Pending';
}
?>
<style>
body { font-family: Arial, sans-serif; background:#f6f7f6; padding:20px; }
#invoice_area { width: 320px; margin: auto; border: 1px dashed #333; padding: 15px 10px; background: #fff; }
.header { text-align: center; border-bottom: 1px dashed #333; margin-bottom: 10px; line-height: 1.2; }
.header h2, .header small { padding-left: 5px; padding-right: 5px; }
.section { font-size: 14px; margin-bottom: 10px; line-height: 1.2; }
.table { width: 100%; border-collapse: collapse; }
.table td { padding: 4px; font-size: 13px; }
.total { border-top: 1px dashed #333; font-weight: bold; margin-top: 10px; padding-top: 5px; text-align: right; }
.footer { text-align: center; border-top: 1px dashed #333; margin-top: 10px; font-size: 12px; }
.status-settled { color: #2F855A; font-weight:bold; }
.status-partial { color: #D69E2E; font-weight:bold; }
.status-pending { color: #C53030; font-weight:bold; }
</style>

<div id="invoice_area">
    <div class="header">
        <h2>🏠 BEN AND SOF DORMITORY</h2>
        <small>Purok1A Mati San Miguel ZDS</small>
    </div>

    <div class="section">
        <p><strong>Tenant:</strong> <?= htmlspecialchars($bill['tenant_name']); ?> (Room <?= htmlspecialchars($bill['room_number']); ?>)</p>
        <p><strong>Invoice Generated:</strong> <?= $generated_datetime; ?></p>
        <p><strong>Due Date:</strong> <?= date("M d, Y", strtotime($bill['due_date'])); ?></p>
        <p><strong>Payment Date:</strong> <?= $payment_date; ?></p>
        <p><strong>Status:</strong> <span class="status-<?= strtolower(str_replace(' ','',$status)); ?>"><?= $status; ?></span></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($bill['address']); ?></p>
        <p><strong>Contact:</strong> <?= htmlspecialchars($bill['contact_number']); ?></p>
    </div>

    <div class="section">
        <table class="table">
            <tr><td>Base Rent</td><td align="right">₱<?= number_format($bill['base_rent'],2); ?></td></tr>

            <?php foreach($utility_fee as $k => $fee): ?>
                <tr>
                    <td><?= htmlspecialchars($fee); ?></td>
                    <td align="right">₱<?= number_format($utility_amount[$k] ?? 0,2); ?></td>
                </tr>
            <?php endforeach; ?>

            <?php foreach($add_charges as $k => $charge): ?>
                <tr>
                    <td><?= htmlspecialchars($charge); ?></td>
                    <td align="right">₱<?= number_format($add_amount[$k] ?? 0,2); ?></td>
                </tr>
            <?php endforeach; ?>

            <tr><td>Late Payment Charge</td><td align="right">₱<?= number_format($interest,2); ?></td></tr>
            <tr><td>Previous Balance</td><td align="right">₱<?= number_format($previous_balance,2); ?></td></tr>
            <tr><td>Previous Credit</td><td align="right">₱<?= number_format($previous_credit,2); ?></td></tr>
            <tr><td>Other Amount</td><td align="right">₱<?= number_format($other_amount,2); ?></td></tr>

            <tr style="font-weight:bold; border-top:1px dashed #333;">
                <td>Total Amount</td>
                <td align="right">₱<?= number_format($total_calculated,2); ?></td>
            </tr>
        </table>
    </div>

    <div class="total">
        Payment: ₱<?= number_format($payment_amount,2); ?><br>
        Balance: ₱<?= number_format($balance,2); ?><br>
        Credit: ₱<?= number_format($credit_balance,2); ?><br>
        Payment Method: <?= htmlspecialchars($bill['payment_method']); ?>
    </div>

    <div class="footer">
        <p>Thank you for staying with us!</p>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
function exportInvoice() {
    html2canvas(document.getElementById("invoice_area")).then(function(canvas) {
        var link = document.createElement('a');
        link.download = 'invoice_<?= $bill['bill_id']; ?>.png';
        link.href = canvas.toDataURL();
        link.click();
    });
}
function printInvoice() {
    var printContents = document.getElementById("invoice_area").innerHTML;
    var w = window.open("", "", "width=600,height=700");
    w.document.write("<html><head><title>Print Invoice</title></head><body>");
    w.document.write(printContents);
    w.document.write("</body></html>");
    w.document.close();
    w.print();
}
</script>
