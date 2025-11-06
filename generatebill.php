<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Fetch tenants with Partial or Settled billing
$tenantQuery = $conn->query("
    SELECT DISTINCT t.tenant_id, t.tenant_name
    FROM tenants t
    JOIN billing b ON t.tenant_id = b.tenant_id
    WHERE b.status IN ('Partial','Settled')
    ORDER BY t.tenant_name ASC
");
$tenants = $tenantQuery->fetch_all(MYSQLI_ASSOC);

$selectedTenant = null;
$selectedMonth = null;
$billData = null;

if(isset($_POST['generate_bill'])){
    $tenant_id = intval($_POST['tenant']);
    $month = intval($_POST['month']);
    $selectedTenant = $tenant_id;
    $selectedMonth = $month;

    $stmt = $conn->prepare("
        SELECT b.*, r.room_number, t.tenant_name
        FROM billing b
        JOIN tenants t ON b.tenant_id = t.tenant_id
        JOIN rooms r ON b.room_id = r.room_id
        WHERE b.tenant_id = ? AND MONTH(b.payment_date) = ? AND b.status IN ('Partial','Settled')
        ORDER BY b.payment_date ASC
    ");
    $stmt->bind_param("ii", $tenant_id, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    $billData = $result->fetch_assoc();
    $stmt->close();

    if($billData){
        $base_rent = floatval($billData['base_rent']);
        $interest = floatval($billData['interest'] ?? 0);

        $utility_amount = json_decode($billData['utility_amount'],true) ?? [];
        $utility_total = array_sum($utility_amount);

        $add_amount = json_decode($billData['add_amount'],true) ?? [];
        $add_total = array_sum($add_amount);

        $previous_balance = floatval($billData['previous_balance'] ?? 0);
        $previous_credit = floatval($billData['previous_credit'] ?? 0);
        $balance = floatval($billData['balance'] ?? 0);
        $credit_balance = floatval($billData['credit_balance'] ?? 0);
        $payment_amount = floatval($billData['payment_amount'] ?? 0);

        $total_amount = $base_rent + $utility_total + $add_total + $interest + $previous_balance - $previous_credit;

        $balance = max(0, $total_amount - $payment_amount);
        $credit_balance = max(0, $payment_amount - $total_amount);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Generate Bill</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
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

        .form-container {
            background: #fff;
            padding: 20px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        select, button {
            padding: 6px 12px;
            margin-right: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        button {
            cursor: pointer;
            background: #5A7D7C;
            color: #fff;
            border: none;
            transition: 0.3s;
        }

        button:hover { background: #4e6b6a; }

        .bill-container {
            background: #fff;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            position: relative;
        }

        .print-btn {
            position: absolute;
            top: 20px;
            right: 25px;
            background: #ff7f50;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }
        .print-btn:hover { background: #e56b3d; }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px; 
            background: #fafafa; 
        }

        td, th { 
            padding: 8px 10px; 
            border: 1px solid #ddd; 
        }

        th {
            background: #f0f0f0;
            text-align: left;
        }

        tr:nth-child(even) { background: #f1f1f1; }

        h3 { margin: 0; margin-bottom: 15px; color: #333; }

        @media print {
            body * { visibility: hidden; }
            #printable_invoice, #printable_invoice * { visibility: visible; }
            #printable_invoice { position: absolute; top: 0; left: 0; width: 100%; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Form for tenant selection -->
        <div class="form-container">
            <form method="post">
                <label>Tenant:</label>
                <select name="tenant" required>
                    <option value="">-- Choose Tenant --</option>
                    <?php foreach($tenants as $t): ?>
                        <option value="<?= $t['tenant_id'] ?>" <?= ($selectedTenant==$t['tenant_id']?'selected':'') ?>>
                            <?= htmlspecialchars($t['tenant_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Month:</label>
                <select name="month" required>
                    <option value="">-- Choose Month --</option>
                    <?php for($m=1;$m<=12;$m++):
                        $num = str_pad($m,2,'0',STR_PAD_LEFT);
                        $selected = ($selectedMonth==$m)?'selected':''; ?>
                        <option value="<?= $num ?>" <?= $selected ?>><?= date("F", mktime(0,0,0,$m,1)) ?></option>
                    <?php endfor; ?>
                </select>

                <button type="submit" name="generate_bill">Generate Bill</button>
            </form>
        </div>

        <!-- Generated Bill -->
        <?php if($billData): ?>
        <div class="bill-container">
            <button class="print-btn" onclick="printInvoice()">🖨 Print Bill</button>
            <div id="printable_invoice">
               <h3>Tenant: <?= htmlspecialchars($billData['tenant_name']) ?></h3>
<table>
    <tr>
        <td>Room Number:</td>
        <td><?= htmlspecialchars($billData['room_number']) ?></td>
        <td>Due Date:</td>
        <td><?= date("Y-m-d", strtotime($billData['due_date'])) ?></td>
    </tr>
    <tr>
        <td>Payment Date:</td>
        <td><?= !empty($billData['payment_date']) ? date("Y-m-d", strtotime($billData['payment_date'])) : '-' ?></td>
        <td>Base Rent:</td>
        <td>₱<?= number_format($base_rent,2) ?></td>
    </tr>
    <tr>
        <td>Interest:</td>
        <td>₱<?= number_format($interest,2) ?></td>
        <td>Utility Fee:</td>
        <td><?= implode(', ', json_decode($billData['utility_fee'],true) ?? []) ?></td>
    </tr>
    <tr>
        <td>Utility Amount:</td>
        <td>₱<?= number_format($utility_total,2) ?></td>
        <td>Additional Charges:</td>
        <td><?= implode(', ', json_decode($billData['add_charges'],true) ?? []) ?></td>
    </tr>
    <tr>
        <td>Additional Charges Amount:</td>
        <td>₱<?= number_format($add_total,2) ?></td>
        <td>Previous Balance:</td>
        <td>₱<?= number_format($previous_balance,2) ?></td>
    </tr>
    <tr>
        <td>Previous Credit Balance:</td>
        <td>₱<?= number_format($previous_credit,2) ?></td>
        <td>Payment Amount:</td>
        <td>₱<?= number_format($payment_amount,2) ?></td>
    </tr>
    <tr>
        <td>Balance:</td>
        <td>₱<?= number_format($balance,2) ?></td>
        <td>Credit Balance:</td>
        <td>₱<?= number_format($credit_balance,2) ?></td>
    </tr>
    <tr>
        <td>Payment Method:</td>
        <td><?= htmlspecialchars($billData['payment_method']) ?></td>
        <td>Total Amount:</td>
        <td>₱<?= number_format($total_amount,2) ?></td>
    </tr>
    <tr>
        <td>Status:</td>
        <td colspan="3"><?= htmlspecialchars($billData['status']) ?></td>
    </tr>
</table>

            </div>
        </div>

        <script>
        function printInvoice() {
            var printContents = document.getElementById("printable_invoice").innerHTML;
            var w = window.open("", "", "width=900,height=700");
            w.document.write("<html><head><title>Print Invoice</title></head><body>");
            w.document.write(printContents);
            w.document.write("</body></html>");
            w.document.close();
            w.print();
        }
        </script>
        <?php endif; ?>
    </div>
</body>
</html>
