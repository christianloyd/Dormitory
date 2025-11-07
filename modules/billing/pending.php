<?php
session_start();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'dorm_db';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$currentMonth = date('m');
$currentYear  = date('Y');

// --- Functions ---
function getBillingStatus($total, $payment, $prev_credit) {
    if ($prev_credit >= $total) return "Settled";
    if (($prev_credit + $payment) >= $total) return "Settled";
    if ($payment > 0) return "Partial";
    return "Pending";
}

function computeBalances($total, $payment, $prev_credit) {
    $creditBalance = 0;
    $balance = 0;

    if ($payment > $total) {
        $creditBalance = $payment - $total;
    } elseif (($payment + $prev_credit) > $total) {
        $creditBalance = ($payment + $prev_credit) - $total;
    }

    if (($payment + $prev_credit) < $total) {
        $balance = $total - ($payment + $prev_credit);
    }

    return [
        "credit" => $creditBalance,
        "balance" => $balance
    ];
}

// --- Fetch billing data ---
$prev_credit = 0;
$pendingTenants = [];

$query = "
    SELECT b.*, t.tenant_name, t.profile_pic, r.room_number 
    FROM billing b
    INNER JOIN tenants t ON b.tenant_id = t.tenant_id
    INNER JOIN rooms r ON t.room_id = r.room_id
    WHERE 
        MONTH(b.due_date) = $currentMonth 
        AND YEAR(b.due_date) = $currentYear
        AND t.status = 'Active'
    ORDER BY b.due_date ASC
";


$res = $conn->query($query);

while ($row = $res->fetch_assoc()) {
    // Decode JSON safely
    $utility_amounts = json_decode($row['utility_amount'], true);
    if (!is_array($utility_amounts)) $utility_amounts = [$utility_amounts ?? 0];

    $add_amounts = json_decode($row['add_amount'], true);
    if (!is_array($add_amounts)) $add_amounts = [$add_amounts ?? 0];

    $base_rent        = floatval($row['base_rent'] ?? 0);
    $utility_total    = array_sum(array_map('floatval', $utility_amounts));
    $add_total        = array_sum(array_map('floatval', $add_amounts));
    $interest         = floatval($row['interest'] ?? 0);
    $previous_balance = floatval($row['previous_balance'] ?? 0);
    $other_amount     = floatval($row['other_amount'] ?? 0);

    $total = $base_rent + $utility_total + $add_total + $interest + $previous_balance + $other_amount;
    $payment = floatval($row['payment_amount'] ?? 0);

    // Compute status & balances
    $status = getBillingStatus($total, $payment, $prev_credit);
    $balances = computeBalances($total, $payment, $prev_credit);

   if ($status === "Pending") {
    $row['status'] = $status;
    $row['total'] = $total;
    $row['credit_balance'] = $balances['credit'];
    $row['balance'] = $balances['balance'];
    $pendingTenants[] = $row;
}

    // Update previous credit
    if ($payment > $total) {
        $prev_credit = $payment - $total;
    } elseif ($prev_credit > 0 && ($prev_credit + $payment) > $total) {
        $prev_credit = ($prev_credit + $payment) - $total;
    } else {
        $prev_credit = 0;
    }
}

// --- Compute total pending tenants ---
$totalPending = count($pendingTenants);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pending Payment Tenants</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { margin:0; font-family: Arial,sans-serif; display:flex; background-color:#f6f7f6; }
.main-content { margin-left:225px; padding:30px; background-color:#f6f7f6; min-height:100vh; width:calc(100% - 225px); }
.pending-table-container { overflow-x:auto; max-height:70vh; }
.profile-pic { width:40px; height:40px; object-fit:cover; border-radius:50%; border:1.5px solid #5A7D7C; }
table th, table td { vertical-align: middle; text-align:center; font-size:14px; white-space:nowrap; }
#searchInput { max-width:250px; margin-left:5px; border:1px solid #000; border-radius:4px; padding:5px 10px; }
.btn-login { background-color:#5A7D7C; color:white; border:none; border-radius:6px; padding:6px 10px; cursor:pointer; transition: background 0.3s; margin-right:10px; }
.btn-login:hover { background-color:#496766; }
.total-pending { font-weight:bold; color:#5A7D7C; font-size:16px; margin-top:10px; }
body, html { width:100%; height:100%; overflow:hidden; }
</style>
</head>
<body>
<?php include '../../sidebar.php'; ?>
<div class="main-content">

<?php
$header = $conn->query("SELECT setting_value FROM settings WHERE setting_name='header_image'")->fetch_assoc();
$header_pic = $header ? BASE_PATH . '/' . $header['setting_value'] : BASE_PATH . "/uploads/default_header.png";
?>

<div class="d-flex justify-content-between align-items-center mt-0">
    <h2>Pending Payment Tenants (<?php echo date("F Y"); ?>)</h2>
    <div class="profile-box d-flex align-items-center">
        <img src="<?php echo $header_pic; ?>" alt="Header Picture" class="rounded-circle" width="50" height="50">
        <span class="ms-2">Admin</span>
    </div>
</div>
<hr style="width:100%; margin:10px auto; border:1px solid #140d0dff;">

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    <form class="d-flex" onsubmit="return false;">
        <button type="submit" class="btn-login">Search</button>
        <input type="text" id="searchInput" class="form-control me-2" placeholder="Search tenant by full name...">
    </form>
</div>

<p class="total-pending">Total Pending Tenants: <?php echo $totalPending; ?></p>

<div class="card">
    <div class="card-body pending-table-container">
        <?php if ($totalPending > 0): ?>
        <table class="table table-bordered table-striped align-middle" id="pendingTable">
            <thead class="table-warning">
                <tr>
                    <th>Profile</th>
                    <th>Tenant Name</th>
                    <th>Room</th>
                    <th>Due Date</th>
                    <th>Payment Date</th>
                    <th>Total Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pendingTenants as $row): ?>
                <tr>
                    <td>
                        <?php if (!empty($row['profile_pic'])): ?>
                            <img src="<?php echo BASE_PATH . '/' . htmlspecialchars($row['profile_pic']); ?>" class="profile-pic" alt="Profile">
                        <?php else: ?>
                            <i class="fa-solid fa-circle-user fa-2x"></i>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['tenant_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['room_number']); ?></td>
                    <td><?php echo date("M d, Y", strtotime($row['due_date'])); ?></td>
                    <td><?php echo !empty($row['payment_date']) ? date("M d, Y", strtotime($row['payment_date'])) : "-"; ?></td>
                    <td>₱<?php echo number_format($row['total'], 2); ?></td>
                    <td>₱<?php echo number_format(floatval($row['payment_amount']), 2); ?></td>
                    <td>
                        <span class="badge bg-warning"><?php echo $row['status']; ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="text-muted">No pending payment tenants found for this month.</p>
        <?php endif; ?>
    </div>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script>
const searchInput = document.getElementById('searchInput');
const tableBody = document.getElementById('pendingTable')?.getElementsByTagName('tbody')[0];

if (searchInput && tableBody) {
    searchInput.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        Array.from(tableBody.rows).forEach(row => {
            const tenantName = row.cells[1].textContent.toLowerCase();
            row.style.display = tenantName.includes(filter) ? '' : 'none';
        });
    });
}
</script>
</body>
</html>
