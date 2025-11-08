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

// Fetch tenants for calendar (only Active)
$tenants = $conn->query("
    SELECT tenant_id, tenant_name, DATE(date_started) AS start_date 
    FROM tenants 
    WHERE LOWER(status) = 'active'
");


$due_dates = [];
while ($t = $tenants->fetch_assoc()) {
    $start_date = strtotime($t['start_date']);
    $start_date_advance = strtotime("+1 month", $start_date);
    $start_day = date('d', $start_date_advance);
    $due_date_this_month = strtotime(sprintf("%04d-%02d-%02d", $year, $month, $start_day));
    if ($due_date_this_month >= $start_date_advance) {
        $formatted_date = date('Y-m-d', $due_date_this_month);
        if (!isset($due_dates[$formatted_date])) {
            $due_dates[$formatted_date] = 0;
        }
        $due_dates[$formatted_date]++;
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
    <!-- DATE RANGE FILTER aligned to right edge of calendar -->
    <div class="d-flex justify-content-end mb-3" 
     style="max-width:1400px; margin:0 auto; padding-left:90%;">
        <div class="search-bar">
            <form method="GET" action="due_dates.php" style="display:flex; gap:8px; align-items:center;">
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
                        echo "<td onclick=\"window.location='due_dates.php?date=$date'\">";
                        echo "<strong>$current_day</strong>";

                        if (isset($due_dates[$date])) {
                            $count = $due_dates[$date];
                            $label = $count == 1 ? "1 tenants will be due" : "$count tenants will be due";
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
</script>
</body>
</html>
