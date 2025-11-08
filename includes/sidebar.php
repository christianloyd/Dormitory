<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar d-flex flex-column">
    <h2>Admin Panel</h2>
    <ul class="flex-grow-1">
        <li><a href="<?= BASE_PATH ?>/modules/dashboard/" class="<?= (strpos($_SERVER['REQUEST_URI'], '/dashboard/') !== false) ? 'active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="<?= BASE_PATH ?>/modules/tenants/" class="<?= (strpos($_SERVER['REQUEST_URI'], '/tenants/') !== false) ? 'active' : '' ?>"><i class="fas fa-users"></i> Tenants</a></li>
        <li><a href="<?= BASE_PATH ?>/modules/rooms/" class="<?= (strpos($_SERVER['REQUEST_URI'], '/rooms/') !== false) ? 'active' : '' ?>"><i class="fas fa-door-open"></i> Rooms</a></li>
        <li><a href="<?= BASE_PATH ?>/modules/billing/" class="<?= (strpos($_SERVER['REQUEST_URI'], '/billing/') !== false) ? 'active' : '' ?>"><i class="fas fa-clipboard-list"></i> Billing</a></li>
        <li><a href="<?= BASE_PATH ?>/modules/reports/monthly_payments.php" class="<?= (strpos($_SERVER['REQUEST_URI'], '/monthly_payments.php') !== false) ? 'active' : '' ?>"><i class="fas fa-calendar-check"></i> Monthly Payments</a></li>
        <li><a href="<?= BASE_PATH ?>/modules/invoice/" class="<?= (strpos($_SERVER['REQUEST_URI'], '/invoice/') !== false) ? 'active' : '' ?>"><i class="fas fa-file-invoice"></i> Invoice</a></li>
        <li><a href="<?= BASE_PATH ?>/modules/sms/" class="<?= (strpos($_SERVER['REQUEST_URI'], '/sms/') !== false) ? 'active' : '' ?>"><i class="fas fa-sms"></i> SMS Logs</a></li>

        <!-- ============================= -->
        <!-- Report with Dropdown Submenu -->
        <!-- ============================= -->
        <li>
            <a href="#" class="dropdown-toggle <?= ($currentPage == 'report.php') ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i> Report
            </a>
            <ul class="submenu" style="display:none; list-style:none; padding-left:25px;">
                <li><a href="<?= BASE_PATH ?>/modules/reports/billing_summary.php" class="<?= (strpos($_SERVER['REQUEST_URI'], '/billing_summary.php') !== false) ? 'active' : '' ?>">Billing Summary Report</a></li>
                <li><a href="<?= BASE_PATH ?>/modules/reports/collection.php" class="<?= (strpos($_SERVER['REQUEST_URI'], '/collection.php') !== false) ? 'active' : '' ?>">Collection Report</a></li>
                <li><a href="<?= BASE_PATH ?>/modules/reports/outstanding.php" class="<?= (strpos($_SERVER['REQUEST_URI'], '/outstanding.php') !== false) ? 'active' : '' ?>">Outstanding Balance Report</a></li>
                <li><a href="<?= BASE_PATH ?>/modules/tenants/inactive.php" class="<?= (strpos($_SERVER['REQUEST_URI'], '/inactive.php') !== false) ? 'active' : '' ?>">Inactive Tenant</a></li>
            </ul>
        </li>
        <!-- ============================= -->

        <li><a href="<?= BASE_PATH ?>/modules/settings/" class="<?= (strpos($_SERVER['REQUEST_URI'], '/settings/') !== false) ? 'active' : '' ?>"><i class="fas fa-cog"></i> Settings</a></li>
    </ul>

    <!-- Logout button fixed at bottom -->
    <div class="mt-auto p-2">
        <a href="<?= BASE_PATH ?>/auth/logout.php" class="text-danger d-block">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- Styles and Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_PATH ?>/css/sidebar.css">

<!-- Dropdown Toggle Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const dropdowns = document.querySelectorAll('.dropdown-toggle');

    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('click', function(e) {
            e.preventDefault();
            const submenu = this.nextElementSibling;

            // Close other open submenus (optional)
            document.querySelectorAll('.submenu').forEach(menu => {
                if (menu !== submenu) menu.style.display = 'none';
            });

            // Toggle current submenu
            submenu.style.display = (submenu.style.display === 'block') ? 'none' : 'block';
        });
    });
});
</script>

<!-- Optional Styling for Submenu -->
<style>
.sidebar ul {
    padding-left: 0;
    list-style: none;
}

.sidebar ul li a {
    display: block;
    padding: 10px 15px;
    color: #fff;
    text-decoration: none;
}

.sidebar ul li a.active {
    background-color: #5A7D7C; /* theme color */
    font-weight: bold;
    color: #fff;
}

.submenu li a {
    background: #1f2d3d;
    color: #ccc;
    padding: 8px 25px;
    display: block;
    border-radius: 4px;
    margin-top: 3px;
}

.submenu li a:hover, .submenu li a.active {
   background-color: #5A7D7C; /* theme color */
    font-weight: bold;
    color: #fff;
}
</style>
