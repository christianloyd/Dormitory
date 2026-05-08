<?php
require_once "../../includes/auth_check.php";
require_once "../../config/db.php";
require_once __DIR__ . '/../../helpers/TenantAssignments.php';
include '../../includes/sidebar.php';

$hdrRow = $conn->query("SELECT setting_value FROM settings WHERE setting_name='header_image'")->fetch_assoc();
$hdrPic = $hdrRow ? BASE_PATH . '/' . $hdrRow['setting_value'] : BASE_PATH . '/uploads/default_header.png';

// Capture filters from GET
$filter_month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$filter_year  = isset($_GET['year']) ? intval($_GET['year']) : 0;

function renderPendingPaymentReport($conn, $filter_month = 0, $filter_year = 0) {
    // Show both Pending and Unpaid statuses
    $where = ["TRIM(b.status) IN ('Pending','Unpaid')"];

    if ($filter_month > 0) $where[] = "MONTH(b.due_date) = $filter_month";
    if ($filter_year > 0)  $where[] = "YEAR(b.due_date) = $filter_year";

    $where_sql = count($where) ? " AND " . implode(" AND ", $where) : "";

    $sql = "
    SELECT 
        t.tenant_id,
        t.tenant_name,
        GROUP_CONCAT(DISTINCT r.room_number ORDER BY r.room_number SEPARATOR ', ') AS billed_rooms,
        SUM(b.base_rent + b.interest + b.previous_balance - b.previous_credit
            + IFNULL(ai.total,0) + IFNULL(ui.total,0)) AS total_amount,
        SUM(b.payment_amount) AS payment_amount,
        SUM((b.base_rent + b.interest + b.previous_balance - b.previous_credit
            + IFNULL(ai.total,0) + IFNULL(ui.total,0)) - b.payment_amount) AS balance,
        MIN(b.due_date) AS due_date,
        MAX(b.payment_date) AS payment_date,
        GROUP_CONCAT(DISTINCT b.status SEPARATOR ', ') AS status
    FROM billing b
    INNER JOIN tenants t ON t.tenant_id = b.tenant_id
    INNER JOIN rooms r ON r.room_id = b.room_id
    LEFT JOIN (
        SELECT bill_id, SUM(amount) AS total 
        FROM billing_additional_items 
        GROUP BY bill_id
    ) ai ON ai.bill_id = b.bill_id
    LEFT JOIN (
        SELECT bill_id, SUM(amount) AS total 
        FROM billing_utility_items 
        GROUP BY bill_id
    ) ui ON ui.bill_id = b.bill_id
    WHERE t.status='Active' $where_sql
    GROUP BY t.tenant_id, t.tenant_name
    ORDER BY balance DESC
    ";

    $result = $conn->query($sql);
    $rows = [];
    $tenantIds = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['tenant_id'] = (int)$row['tenant_id'];
            $rows[] = $row;
            $tenantIds[] = $row['tenant_id'];
        }
    }

    $assignmentsByTenant = TenantAssignments::getAssignmentsForTenants($conn, $tenantIds);

    // Filters UI
    echo '<form method="GET" class="d-flex flex-wrap gap-3 align-items-end mb-3">';
    echo '<div>';
    echo '<label class="form-label fw-semibold small mb-1">Month</label>';
    echo '<select name="month" class="form-select" style="min-width:140px;">';
    echo '<option value="0">All Months</option>';
    for($m=1;$m<=12;$m++){
        $selected = ($m==$filter_month)?'selected':'';
        echo '<option value="'.$m.'" '.$selected.'>'.date('F', mktime(0,0,0,$m,1)).'</option>';
    }
    echo '</select></div>';
    echo '<div>';
    echo '<label class="form-label fw-semibold small mb-1">Year</label>';
    echo '<select name="year" class="form-select" style="min-width:110px;">';
    echo '<option value="0">All Years</option>';
    for($y=2022; $y<=2030; $y++){
        $selected = ($y==$filter_year)?'selected':'';
        echo '<option value="'.$y.'" '.$selected.'>'.$y.'</option>';
    }
    echo '</select></div>';
    echo '<div><label class="d-block mb-1">&nbsp;</label>';
    echo '<button type="submit" class="btn btn-teal"><i class="fas fa-filter me-1"></i> Filter</button>';
    echo '<a href="print_pendingpayment_report.php?month='.$filter_month.'&year='.$filter_year.'" target="_blank" class="btn btn-secondary ms-2" style="background:#2c3e50; border:none; padding:8px 18px; border-radius:8px;"><i class="fas fa-print me-1"></i> Print Report</a>';
    echo '</div></form>';

    // Search input
    echo '<div class="mb-3">';
    echo '<div class="input-group" style="width:280px;">';
    echo '<span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>';
    echo '<input type="text" id="searchTenantPending" class="form-control border-start-0 ps-0" placeholder="Search Tenant...">';
    echo '</div></div>';

    // Table display
    echo '<div class="table-wrapper">';
    echo '<table id="pendingTable" class="report-table">';
    echo '<thead><tr>';
    echo '<th>Room Number</th>';
    echo '<th>Tenant</th>';
    echo '<th>Total Amount</th>';
    echo '<th>Payment Amount</th>';
    echo '<th>Balance</th>';
    echo '<th>Due Date</th>';
    echo '<th>Payment Date</th>';
    echo '<th>Status</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    if (!empty($rows)) {
        foreach ($rows as $row) {
            $tenantId = $row['tenant_id'];
            $assignments = $assignmentsByTenant[$tenantId] ?? [];

            if (!empty($assignments)) {
                $roomParts = [];
                foreach ($assignments as $assignment) {
                    $deckLabel = $assignment['deck_type'] ? ' — ' . $assignment['deck_type'] : '';
                    $roomParts[] = htmlspecialchars($assignment['room_number'] . $deckLabel, ENT_QUOTES, 'UTF-8');
                }
                $roomSummary = implode('<br>', $roomParts);
            } else {
                $roomSummary = htmlspecialchars($row['billed_rooms'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
            }

            echo '<tr>';
            echo '<td>'.$roomSummary.'</td>';
            echo '<td>'.htmlspecialchars($row['tenant_name']).'</td>';
            echo '<td>₱'.number_format($row['total_amount'],2).'</td>';
            echo '<td>₱'.number_format($row['payment_amount'],2).'</td>';
            echo '<td>₱'.number_format($row['balance'],2).'</td>';
            echo '<td>'.($row['due_date'] ?? '-').'</td>';
            echo '<td>'.($row['payment_date'] ?? '-').'</td>';
            echo '<td>'.htmlspecialchars($row['status']).'</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="8" class="text-center text-muted py-3">No tenants with pending payments found.</td></tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}
?>

<div class="main-content" style="margin-left:225px; padding:30px; min-height:100vh; background:#f0f4f3;">
    <div style="margin-bottom:24px;">
        <h2 style="font-size:1.6rem;font-weight:700;color:#2c3e50;margin:0;"><i class="fas fa-clock" style="color:#5A7D7C;margin-right:8px;"></i> Pending Payment Report</h2>
    </div>
    <div style="background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.08);padding:24px;margin-bottom:20px;">
        <?php renderPendingPaymentReport($conn, $filter_month, $filter_year); ?>
    </div>
</div>

<script>
document.getElementById('searchTenantPending').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#pendingTable tbody tr');
    rows.forEach(row => {
        let tenant = row.cells[1].textContent.toLowerCase();
        row.style.display = tenant.indexOf(filter) > -1 ? '' : 'none';
    });
});
</script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.report-table { width:100%; border-collapse:collapse; min-width:700px; }
.report-table thead th { background:#5A7D7C; color:#fff; padding:12px 14px; text-align:center; font-size:0.87rem; font-weight:600; position:sticky; top:0; z-index:2; white-space:nowrap; }
.report-table tbody td { padding:10px 14px; text-align:center; border-bottom:1px solid #eee; font-size:0.9rem; vertical-align:middle; }
.report-table tbody tr:hover { background-color:#f1f8f7; }
.table-wrapper { overflow-x:auto; overflow-y:auto; max-height:520px; }
.btn-teal { background-color:#5A7D7C; color:#fff; border:none; border-radius:8px; }
.btn-teal:hover { background-color:#4a6c6b; color:#fff; }
</style>
