<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar d-flex flex-column">
    <h2>Admin Panel</h2>
    <ul class="flex-grow-1">
        <li><a href="dashboard.php" class="<?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="tenants.php" class="<?= ($currentPage == 'tenants.php') ? 'active' : '' ?>"><i class="fas fa-users"></i> Tenants</a></li>
        <li><a href="rooms.php" class="<?= ($currentPage == 'rooms.php') ? 'active' : '' ?>"><i class="fas fa-door-open"></i> Rooms</a></li>
        <li><a href="billing.php" class="<?= ($currentPage == 'billing.php') ? 'active' : '' ?>"><i class="fas fa-clipboard-list"></i> Billing</a></li>
        <li><a href="monthly_payments.php" class="<?= ($currentPage == 'monthly_payments.php') ? 'active' : '' ?>"><i class="fas fa-calendar-check"></i> Monthly Payments</a></li>
        <li><a href="invoice.php" class="<?= ($currentPage == 'invoice.php') ? 'active' : '' ?>"><i class="fas fa-file-invoice"></i> Invoice</a></li>
        <li><a href="sms_logs.php" class="<?= ($currentPage == 'sms_logs.php') ? 'active' : '' ?>"><i class="fas fa-sms"></i> SMS Logs</a></li>

        <!-- ============================= -->
        <!-- Report with Dropdown Submenu -->
        <!-- ============================= -->
        <li>
            <a href="#" class="dropdown-toggle <?= ($currentPage == 'report.php') ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i> Report
            </a>
            <ul class="submenu" style="display:none; list-style:none; padding-left:25px;">
                <li><a href="billing_summary.php" class="<?= ($currentPage == 'billing_summary.php') ? 'active' : '' ?>">Billing Summary Report</a></li>
                <li><a href="collection_report.php" class="<?= ($currentPage == 'collection_report.php') ? 'active' : '' ?>">Collection Report</a></li>
                <li><a href="outstanding_balance.php" class="<?= ($currentPage == 'outstanding_balance.php') ? 'active' : '' ?>">Outstanding Balance Report</a></li>
                <li><a href="inactive_tenant.php" class="<?= ($currentPage == 'inactive_tenant.php') ? 'active' : '' ?>">Inactive Tenant</a></li>
            </ul>
        </li>
        <!-- ============================= -->

        <li><a href="user.php" class="<?= ($currentPage == 'user.php') ? 'active' : '' ?>"><i class="fas fa-cog"></i> Settings</a></li>
    </ul>

    <!-- Logout button fixed at bottom -->
    <div class="mt-auto p-2">
        <a href="logout.php" class="text-danger d-block">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- Styles and Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/sidebar.css">

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
