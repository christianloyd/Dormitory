<?php
/**
 * Billing Module - Billing Calendar
 * Path: /modules/billing/index.php
 */
require_once '../../includes/auth_check.php';

date_default_timezone_set('Asia/Manila');

// Get current or selected month/year
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year  = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Calendar setup
$first_day     = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day);
$month_name    = date('F', $first_day);

// Adjust day_of_week so Monday=0, Sunday=6
$day_of_week   = (date('w', $first_day) + 6) % 7;

// Prev/Next month
$prev_month = $month - 1; $prev_year  = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
$next_month = $month + 1; $next_year  = $year;
if ($next_month > 12) { $next_month = 1; $next_year++; }

// Fetch tenant assignments for calendar (only Active tenants)
$tenantAssignments = [];

$tenantQuery = $conn->query("
    SELECT 
        t.tenant_id,
        t.tenant_name,
        DATE(t.date_started) AS start_date,
        tr.room_id,
        r.room_number
    FROM tenants t
    LEFT JOIN tenant_rooms tr 
        ON tr.tenant_id = t.tenant_id AND tr.released_at IS NULL
    LEFT JOIN rooms r ON r.room_id = tr.room_id
    WHERE LOWER(t.status) = 'active'
");

if ($tenantQuery) {
    while ($row = $tenantQuery->fetch_assoc()) {
        $tenantId = (int)$row['tenant_id'];
        if (!isset($tenantAssignments[$tenantId])) {
            $tenantAssignments[$tenantId] = [
                'tenant_name' => $row['tenant_name'],
                'start_date' => $row['start_date'],
                'rooms' => [],
            ];
        }

        if (!empty($row['room_number'])) {
            $tenantAssignments[$tenantId]['rooms'][] = $row['room_number'];
        }
    }
}

$due_dates = [];

foreach ($tenantAssignments as $tenantId => $tenantInfo) {
    $start_date = strtotime($tenantInfo['start_date']);
    if (!$start_date) {
        continue;
    }

    $billing_day = date('d', $start_date);
    $first_billing_month = strtotime("+1 month", $start_date);

    $current_month_start = strtotime("$year-$month-01");
    $days_in_current_month = date('t', $current_month_start);

    if ($current_month_start >= strtotime(date('Y-m-01', $first_billing_month))) {
        $due_day = min($billing_day, $days_in_current_month);
        $dateStr = date('Y-m-d', strtotime("$year-$month-$due_day"));

        if (!isset($due_dates[$dateStr])) {
            $due_dates[$dateStr] = [];
        }

        $due_dates[$dateStr][] = [
            'tenant_id' => $tenantId,
            'tenant_name' => $tenantInfo['tenant_name'],
            'rooms' => $tenantInfo['rooms'],
        ];
    }
}



?>
<!DOCTYPE html>
<html>
<head>
    <title>Billing Calendar</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/billing_calendar.css">
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>

<div class="main-content">
    <h2>Billing Calendar</h2>
    <hr style="width: 100%; margin: 10px auto; border: 1px solid #140d0dff;">

   <div class="calendar-wrapper">
    <div class="calendar-header d-flex justify-content-between align-items-center mb-3" style="max-width:1400px; margin:0 auto;">
    
    <!-- Right side: Date Range -->
<div class="search-bar ms-auto">
    <form method="GET" action="due_dates.php" style="display:flex; gap:10px; align-items:center;">
        <label for="range">Range:</label>
        <select id="range" name="range_option" onchange="updateDateRange()">
            <option value="">-- Select --</option>
            <option value="5">5 Days</option>
            <option value="10">10 Days</option>
            <option value="15">15 Days</option>
            <option value="custom">Custom</option>
        </select>
        <label for="from">From:</label>
        <input type="date" id="from" name="start_date" disabled required>
        <label for="to">To:</label>
        <input type="date" id="to" name="end_date" disabled required>
        <button type="submit" class="search-btn">Submit</button>
    </form>
</div>
</div>
 

    <!-- Calendar container -->
    <div class="calendar-container">
        <table>
            <tr>
                <th colspan="7" class="month-header">
                    <div class="month-header-nav">
                        <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>">&laquo;</a>
                        <span><?php echo strtoupper($month_name) . " " . $year; ?></span>
                        <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>">&raquo;</a>
                    </div>
                </th>
            </tr>
            <tr>
                <th>Monday</th>
                <th>Tuesday</th>
                <th>Wednesday</th>
                <th>Thursday</th>
                <th>Friday</th>
                <th>Saturday</th>
                <th>Sunday</th>
            </tr>
            <?php
            $current_day = 1;
            $printed_days = 0;

            // Loop 6 rows (weeks)
            for ($week = 0; $week < 6; $week++) {
                echo "<tr>";
                for ($d = 0; $d < 7; $d++) {
                    $cell_index = $week * 7 + $d;

                    if ($cell_index < $day_of_week || $current_day > $days_in_month) {
                        echo "<td></td>";
                    } else {
                        $date = sprintf("%04d-%02d-%02d", $year, $month, $current_day);
                        $tenantsForDay = $due_dates[$date] ?? [];
                        $tenantsJson = htmlspecialchars(json_encode($tenantsForDay, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                        echo "<td data-date='$date' data-tenants='$tenantsJson'>";
                        echo "<strong>$current_day</strong>";

                        if (!empty($tenantsForDay)) {
                            $count = count($tenantsForDay);
                            $label = $count === 1 ? "1 tenant will be due" : "$count tenants will be due";
                            echo "<span class='due-count'>$label</span>";
                        }

                        echo "</td>";
                        $current_day++;
                    }
                }
                echo "</tr>";
            }
            ?>
        </table>
    </div>
</div>
</div>

<!-- Due Tenants Modal -->
<div class="modal fade" id="dueModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tenants Due on <span data-role="modal-date"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>Total tenants:</strong> <span data-role="modal-total">0</span></p>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th style="width:60px;">Profile</th>
                <th>Tenant</th>
                <th style="width:200px;">Rooms</th>
                <th style="width:140px;">Action</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a href="#" id="viewAllBtn" class="btn btn-primary">View all in list</a>
      </div>
    </div>
  </div>
</div>

<script>
function updateDateRange() {
    const range = document.getElementById("range").value;
    const from = document.getElementById("from");
    const to = document.getElementById("to");

    let today = new Date();
    let dd = String(today.getDate()).padStart(2, '0');
    let mm = String(today.getMonth() + 1).padStart(2, '0');
    let yyyy = today.getFullYear();
    let todayStr = yyyy + '-' + mm + '-' + dd;

    if (range !== "custom" && range !== "") {
        let days = parseInt(range);
        let endDate = new Date(today);
        endDate.setDate(today.getDate() + (days - 1));

        let lastDayMonth = new Date(yyyy, today.getMonth() + 1, 0);
        if (endDate > lastDayMonth) endDate = lastDayMonth;

        let dd2 = String(endDate.getDate()).padStart(2, '0');
        let mm2 = String(endDate.getMonth() + 1).padStart(2, '0');
        let yyyy2 = endDate.getFullYear();
        let endStr = yyyy2 + '-' + mm2 + '-' + dd2;

        from.value = todayStr;
        to.value = endStr;
        from.disabled = true;
        to.disabled = true;
    } else if (range === "custom") {
        from.disabled = false;
        to.disabled = false;
        from.value = todayStr;
        to.value = todayStr;
    } else {
        from.value = "";
        to.value = "";
        from.disabled = true;
        to.disabled = true;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const dayCells = document.querySelectorAll('.calendar-container td[data-date]');
    const modalElement = document.getElementById('dueModal');
    if (!modalElement || dayCells.length === 0) {
        return;
    }

    const modal = new bootstrap.Modal(modalElement);
    const modalDateEl = modalElement.querySelector('[data-role="modal-date"]');
    const modalTotalEl = modalElement.querySelector('[data-role="modal-total"]');
    const modalBodyTable = modalElement.querySelector('tbody');
    const viewAllBtn = document.getElementById('viewAllBtn');

    dayCells.forEach(cell => {
        cell.addEventListener('click', () => {
            const date = cell.dataset.date;
            const payload = cell.getAttribute('data-tenants') || '[]';
            let tenantList = [];
            try {
                tenantList = JSON.parse(payload);
            } catch (err) {
                console.error('Invalid tenant payload', err);
                tenantList = [];
            }

            const displayDate = new Date(date + 'T00:00:00');
            modalDateEl.textContent = displayDate.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });

            const uniqueTenants = new Map();
            tenantList.forEach(entry => {
                const id = entry.tenant_id ?? entry.id ?? entry.tenantId;
                if (!id) {
                    return;
                }
                if (!uniqueTenants.has(id)) {
                    uniqueTenants.set(id, {
                        id,
                        name: entry.tenant_name ?? entry.name ?? 'Tenant',
                        rooms: Array.isArray(entry.rooms) ? entry.rooms : []
                    });
                }
            });

            const tenantsArray = Array.from(uniqueTenants.values());
            modalTotalEl.textContent = tenantsArray.length;
            modalBodyTable.innerHTML = '';

            tenantsArray.forEach(tenant => {
                const row = document.createElement('tr');
                const rooms = tenant.rooms.length ? tenant.rooms.join(', ') : '—';
                row.innerHTML = `
                    <td><i class="fa-solid fa-circle-user"></i></td>
                    <td>${tenant.name}</td>
                    <td>${rooms}</td>
                    <td><a href="due_dates.php?date=${encodeURIComponent(date)}&tenant_id=${tenant.id}" class="btn btn-primary btn-sm">View Bills</a></td>
                `;
                modalBodyTable.appendChild(row);
            });

            if (!tenantsArray.length) {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="4" class="text-center text-muted">No tenants due for this date.</td>';
                modalBodyTable.appendChild(row);
            }

            if (viewAllBtn) {
                viewAllBtn.href = `due_dates.php?date=${encodeURIComponent(date)}`;
            }

            modal.show();
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
