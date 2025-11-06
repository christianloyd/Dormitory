<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// Function to render Outstanding Balance Report
function renderOutstandingReport($conn, $filter_month = 0, $filter_year = 0) {
    // Base query
    $sql = "SELECT 
                t.tenant_id,
                t.tenant_name,
                SUM(b.total_amount - b.payment_amount) AS balance
            FROM tenants t
            LEFT JOIN billing b ON t.tenant_id = b.tenant_id
            WHERE t.status = 'Active'"; // ✅ Only active tenants

    // Dynamic filters for month/year
    $where = [];
    if ($filter_month > 0) $where[] = "MONTH(b.due_date) = $filter_month";
    if ($filter_year > 0)  $where[] = "YEAR(b.due_date) = $filter_year";

    // Add filters dynamically
    if ($where) {
        $sql .= " AND " . implode(" AND ", $where);
    }

    $sql .= " GROUP BY t.tenant_id, t.tenant_name
              ORDER BY balance DESC";

    $result = $conn->query($sql);

    // Search input
    echo '<div style="margin-bottom:10px;">';
    echo '<input type="text" id="searchTenantOutstanding" placeholder="Search Tenant..." style="padding:5px; width:200px;">';
    echo '</div>';

    // Display table
    echo '<div style="overflow-x:auto;">';
    echo '<table id="outstandingTable" style="width:100%; border-collapse:collapse; text-align:center; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,0.2); border-radius:6px;">';
    echo '<thead>';
    echo '<tr style="background:#5A7D7C; color:#fff;">';
    echo '<th style="padding:10px; border:1px solid #ccc;">Tenant</th>';
    echo '<th style="padding:10px; border:1px solid #ccc;">Outstanding Balance</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $balance = $row['balance'] ?? 0;
            echo '<tr>';
            echo '<td style="border:1px solid #ccc; padding:8px;">' . htmlspecialchars($row['tenant_name']) . '</td>';
            echo '<td style="border:1px solid #ccc; padding:8px;">₱' . number_format($balance, 2) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="2" style="border:1px solid #ccc; padding:10px;">No data found for selected filter.</td></tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// Capture filters from GET
$filter_month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$filter_year  = isset($_GET['year']) ? intval($_GET['year']) : 0;

// Call the function to display the report
renderOutstandingReport($conn, $filter_month, $filter_year);
?>

<script>
// Tenant search filter
document.getElementById('searchTenantOutstanding').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#outstandingTable tbody tr');
    rows.forEach(row => {
        let tenant = row.cells[0].textContent.toLowerCase();
        row.style.display = tenant.indexOf(filter) > -1 ? '' : 'none';
    });
});
</script>
