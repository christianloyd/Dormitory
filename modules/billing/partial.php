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

function getBillingStatus($total, $payment) {
    if ($payment >= $total) return "Settled";
    if ($payment > 0 && $payment < $total) return "Partial";
    return "Pending";
}

$partialTenants = [];

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
    // Convert all amounts to float to avoid string addition errors
    $base_rent      = floatval($row['base_rent'] ?? 0);
    $utility_amount = floatval($row['utility_amount'] ?? 0);
    $add_amount     = floatval($row['add_amount'] ?? 0);
    $total_amount   = $base_rent + $utility_amount + $add_amount;

    $payment_amount = floatval($row['payment_amount'] ?? 0);
    $balance        = max($total_amount - $payment_amount, 0);

    $status = getBillingStatus($total_amount, $payment_amount);

    if ($status === "Partial") {
        // store calculated totals and balance for display
        $row['total_amount'] = $total_amount;
        $row['balance'] = $balance;
        $row['payment_amount'] = $payment_amount;
        $partialTenants[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Partial Payment Tenants</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { 
    margin:0; 
    font-family: Arial,sans-serif; 
    display:flex; 
    background-color: #f6f7f6;
}
.main-content { 
    margin-left:225px; 
    padding:30px; 
    background-color: #f6f7f6;
    min-height:100vh; 
    width:calc(100% - 225px); 
    box-sizing: border-box;
}
.partial-table-container {
    overflow-x:auto;
    max-height: 70vh;
}
.profile-pic {
    width: 40px; 
    height: 40px;
    object-fit: cover;
    border-radius: 50%;
    border: 1.5px solid #5A7D7C;
}
table th, table td {
    vertical-align: middle;
    text-align: center;
    font-size: 14px;
    white-space: nowrap;
}
#searchInput { max-width: 250px; }
body, html { width: 100%; height: 100%; overflow: hidden; }
.btn-login {
    background-color: #5A7D7C;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 6px 10px;
    cursor: pointer;
    transition: background 0.3s ease;
    margin-right: 10px;
}
.btn-login:hover { background-color: #496766; }
.total-partial { font-weight: bold; color: #5A7D7C; font-size: 16px; margin-top: 10px; }
</style>
</head>
<body>
<?php include '../../sidebar.php'; ?>
<div class="main-content">

<div class="d-flex justify-content-between align-items-center mt-0">
    <h2>Partial Payment Tenants (<?php echo date("F Y"); ?>)</h2>
</div>
<hr style="width: 100%; margin: 10px auto; border: 1px solid #140d0dff;">

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    <form class="d-flex" onsubmit="return false;">
        <button type="submit" class="btn-login">Search</button>
        <input type="text" id="searchInput" class="form-control me-2" placeholder="Search tenant by full name...">
    </form>
</div>

<p class="total-partial">Total Partial Tenants: <?php echo count($partialTenants); ?></p>

<div class="card">
    <div class="card-body partial-table-container">
        <?php if (count($partialTenants) > 0): ?>
        <table class="table table-bordered table-striped align-middle" id="partialTable">
            <thead class="table-info">
                <tr>
                    <th>Profile</th>
                    <th>Tenant Name</th>
                    <th>Room</th>
                    <th>Due Date</th>
                    <th>Payment Date</th>
                    <th>Balance</th>
                    <th>Total Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($partialTenants as $row): ?>
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
                    <td>₱<?php echo number_format($row['balance'], 2); ?></td>
                    <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                    <td>₱<?php echo number_format($row['payment_amount'], 2); ?></td>
                    <td><span class="badge bg-info">Partial</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="text-muted">No partial payment tenants found for this month.</p>
        <?php endif; ?>
    </div>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script>
const searchInput = document.getElementById('searchInput');
const tableBody = document.getElementById('partialTable')?.getElementsByTagName('tbody')[0];

if (tableBody) {
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
