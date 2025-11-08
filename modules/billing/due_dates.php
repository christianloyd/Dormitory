<?php
/**
 * Billing Module - Due Dates List
 * Path: /modules/billing/due_dates.php
 */
require_once '../../includes/auth_check.php';

$due_tenants = [];
$date = '';
$start_date = '';
$end_date = '';

if (isset($_GET['date'])) {
    // Single date
    $date = $_GET['date'];

    $tenants = $conn->query("SELECT t.tenant_id, t.tenant_name, t.profile_pic, r.room_number, t.date_started
                         FROM tenants t
                         JOIN rooms r ON t.room_id = r.room_id
                         WHERE t.status = 'Active'");

    while ($t = $tenants->fetch_assoc()) {
        $start_day = date('d', strtotime($t['date_started']));
        $tenant_month = date('m', strtotime($date));
        $tenant_year = date('Y', strtotime($date));
        $due_date = sprintf("%04d-%02d-%02d", $tenant_year, $tenant_month, $start_day);

        if ($due_date == $date && strtotime($due_date) >= strtotime($t['date_started'])) {
            $due_tenants[] = $t;
        }
    }

} elseif (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    // Range
    $start_date = $_GET['start_date'];
    $end_date   = $_GET['end_date'];

    $stmt = $conn->prepare("SELECT t.tenant_id, t.tenant_name, t.profile_pic, r.room_number, t.date_started
                        FROM tenants t
                        JOIN rooms r ON t.room_id = r.room_id
                        WHERE t.status = 'Active'");

    $stmt->execute();
    $result = $stmt->get_result();

    while ($t = $result->fetch_assoc()) {
        $day = date('d', strtotime($t['date_started']));
        $month = date('m', strtotime($start_date));
        $year  = date('Y', strtotime($start_date));
        $due_date = sprintf("%04d-%02d-%02d", $year, $month, $day);

        if (strtotime($due_date) >= strtotime($start_date) && strtotime($due_date) <= strtotime($end_date)) {
            $due_tenants[] = $t;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tenants Due</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { margin:0; font-family: Arial, sans-serif; display:flex; background-color: #f0f4f3; }
        .main-content { margin-left:225px; padding:30px; background-color:#fff; min-height:100vh; width:100%; border-left:2px solid #5A7D7C; }
        h2 { margin-bottom:25px; font-weight:bold; color:#5A7D7C; }
        table { border-radius:8px; background:#fff; width:100%; border-collapse:collapse; }
        thead th { background-color: #5A7D7C; color:#fff; text-align:center; font-size:15px; font-weight:bold; }
        tbody td { text-align:center; vertical-align:middle; font-size:14px; color:#333; }
        tbody tr:nth-child(even) { background-color:#f9f9f9; }
        tbody tr:hover { background-color:#e6f2f1; transition:0.3s; }
        .profile-pic { width:50px; height:50px; object-fit:cover; border-radius:50%; border:2px solid #5A7D7C; }
        .fa-circle-user { font-size:40px; color:#5A7D7C; }
        .btn-primary { background-color:#5A7D7C; border:none; font-size:13px; padding:6px 12px; transition:0.3s; }
        .btn-primary:hover { background-color:#3d5a59; }
        .btn-secondary { background-color:#ccc; border:none; font-size:13px; padding:6px 12px; transition:0.3s; }
        .btn-secondary:hover { background-color:#aaa; }
        .back-btn { margin-top:20px; }
        body, html { width: 100%; height: 100%; overflow: hidden; }
        .back-btn {
    margin-top: 15px;
    text-align: left; /* optional: para right-aligned */
    position: sticky;
    bottom: 0;
    background-color: #fff; /* para dili overlay sa table */
    padding: 10px 0;
}
.back-btn {
    position: fixed;
    bottom: 20px;
    left: 260px; /* optional alignment */
}


        /* Scrollable table wrapper */
        .table-wrapper {
            max-height: 500px; /* fixed height for vertical scroll */
            overflow-y: auto;
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }

        /* Custom vertical scroll bar */
        .table-wrapper::-webkit-scrollbar {
            width: 8px;
        }
        .table-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        .table-wrapper::-webkit-scrollbar-thumb {
            background: #5A7D7C;
            border-radius: 4px;
        }
        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #3d5a59;
        }

       .table-wrapper {
    max-height: 600px; /* fixed height for vertical scroll */
    overflow-y: auto;
    width: 100%;
    border-radius: 8px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
    margin-top: 30px; /* ipa-down ang table 20px gikan sa taas */
}
        /* Responsive adjustments */
        @media(max-width:768px) {
            .main-content { padding:15px; }
            table { font-size:12px; }
            .btn-primary, .btn-secondary { font-size:11px; padding:4px 10px; }
        }
        .table-wrapper table thead th {
    background-color: #5A7D7C !important;
    color: #fff !important;
    text-align: center;
    font-size: 15px;
    font-weight: bold;
    padding: 15px;
}
.search-container {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 10px;
}

.search-container input {
    padding: 6px 12px;
    font-size: 14px;
    border-radius: 4px;
    border: 1px solid #ccc;
    width: 250px;
}
.search-container {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 5px; /* spacing between input and button */
    margin-bottom: 10px;
}

.search-container input {
    padding: 6px 12px;
    font-size: 14px;
    border-radius: 4px;
    border: 1px solid #5A7D7C;
    outline: none;
    width: 250px;
    color: #fff;
    background-color: #f6f6f6ff;
    transition: 0.3s;
}

.search-container input::placeholder {
    color: #e0e0e0;
}

.search-container input:focus {
    background-color: #3d5a59;
    border-color: #3d5a59;
}

.search-container button {
    padding: 6px 12px;
    font-size: 14px;
    border: none;
    border-radius: 4px;
    background-color: #5A7D7C;
    color: #fff;
    cursor: pointer;
    transition: 0.3s;
}

.search-container button:hover {
    background-color: #3d5a59;
}

    </style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>

<div class="main-content">
    <?php
    if (!empty($date)) {
        $formattedDate = date("F d, Y", strtotime($date));
        echo "<h2>Tenants Due on $formattedDate</h2>";
    } elseif (!empty($start_date) && !empty($end_date)) {
        $formattedStart = date("F d, Y", strtotime($start_date));
        $formattedEnd   = date("F d, Y", strtotime($end_date));
        echo "<h2>Tenants Due from $formattedStart to $formattedEnd</h2>";
    } else {
        echo "<h2>Tenants Due</h2>";
    }
    ?>

    <?php if (empty($due_tenants)) : ?>
        <div class="alert alert-info">No tenants have billing due for this selection.</div>
    <?php else : ?>

        <div class="search-container">
             <button type="submit" class="btn-search">Search</button>
    <input type="text" id="tenantSearch" placeholder="Search tenant name...">
   
</div>


        <!-- Scrollable table wrapper -->
        <div class="table-wrapper">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Profile</th>
                        <th>Full Name</th>
                        <th>Room Number</th>
                        <th>Date Started</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($due_tenants as $tenant) : ?>
                        <tr>
                            <td>
                                <?php if (!empty($tenant['profile_pic'])): ?>
                                    <img src="<?php echo BASE_PATH . '/' . htmlspecialchars($tenant['profile_pic']); ?>" class="profile-pic" alt="Profile">
                                <?php else: ?>
                                    <i class="fa-solid fa-circle-user"></i>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($tenant['tenant_name']); ?></td>
                            <td><?php echo htmlspecialchars($tenant['room_number']); ?></td>
                            <td><?php echo htmlspecialchars(date("F d, Y", strtotime($tenant['date_started']))); ?></td>
                            <td>
                                <?php 
                                $viewBillUrl = "view.php?tenant_id=" . $tenant['tenant_id'];
                                if (!empty($date)) {
                                    $viewBillUrl .= "&date=" . urlencode($date);
                                } elseif (!empty($start_date) && !empty($end_date)) {
                                    $viewBillUrl .= "&start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date);
                                }
                                ?>
                                <a href="<?php echo $viewBillUrl; ?>" class="btn btn-primary btn-sm">View Bills</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Back Button -->
    <div class="back-btn">
        <a href="billing.php" class="btn btn-secondary">Back to Calendar</a>
    </div>
</div>
<script>
    const searchInput = document.getElementById('tenantSearch');
    searchInput.addEventListener('keyup', function() {
        const filter = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll('.table-wrapper tbody tr');

        rows.forEach(row => {
            const tenantName = row.cells[1].textContent.toLowerCase();
            if (tenantName.indexOf(filter) > -1) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>

</body>
</html>
