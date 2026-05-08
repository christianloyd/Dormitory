<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentUri  = $_SERVER['REQUEST_URI'];

// Determine if we're inside the Reports submenu
$reportPages = ['billing_summary.php', 'collection.php', 'outstanding.php', 'inactive.php', 'report_collection.php', 'report_outstanding.php', 'monthly_payments.php', 'pendingpayment_report.php', 'per_tenant.php', 'total_income.php'];
$isInReports  = false;
foreach ($reportPages as $rp) {
    if (strpos($currentUri, $rp) !== false) {
        $isInReports = true;
        break;
    }
}
// Also catch the /reports/ path for index.php
if (strpos($currentUri, '/reports/') !== false) {
    $isInReports = true;
}

// Determine if we're inside the Settings submenu
$isInSettings = strpos($currentUri, '/settings/') !== false;
?>

<!-- Hamburger Toggle Button (Mobile Only) -->
<button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Navigation">
    <i class="fas fa-bars"></i>
</button>

<!-- Dark Overlay Backdrop for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar d-flex flex-column" id="mainSidebar">
    <!-- Close Button (Mobile Only) -->
    <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Close Navigation">
        &times;
    </button>
    <h2>Admin Panel</h2>
    <ul class="flex-grow-1">
        <li><a href="<?= BASE_PATH ?>/modules/dashboard/" class="<?= (strpos($currentUri, '/dashboard/') !== false) ? 'active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="<?= BASE_PATH ?>/modules/tenants/" class="<?= (strpos($currentUri, '/tenants/') !== false) ? 'active' : '' ?>"><i class="fas fa-users"></i> Tenants</a></li>
        <li><a href="<?= BASE_PATH ?>/modules/rooms/" class="<?= (strpos($currentUri, '/rooms/') !== false) ? 'active' : '' ?>"><i class="fas fa-door-open"></i> Rooms</a></li>
        <li><a href="<?= BASE_PATH ?>/modules/billing/" class="<?= (strpos($currentUri, '/billing/') !== false) ? 'active' : '' ?>"><i class="fas fa-clipboard-list"></i> Billing</a></li>
        <li><a href="<?= BASE_PATH ?>/modules/invoice/" class="<?= (strpos($currentUri, '/invoice/') !== false) ? 'active' : '' ?>"><i class="fas fa-file-invoice"></i> Invoice</a></li>
        <li><a href="<?= BASE_PATH ?>/modules/sms/" class="<?= (strpos($currentUri, '/sms/') !== false) ? 'active' : '' ?>"><i class="fas fa-sms"></i> SMS Logs</a></li>

        <!-- ============================= -->
        <!-- Report with Dropdown Submenu  -->
        <!-- ============================= -->
        <li class="has-submenu <?= $isInReports ? 'open' : '' ?>">
            <a href="#" class="dropdown-toggle <?= $isInReports ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i> Report
                <i class="fas fa-chevron-down submenu-arrow"></i>
            </a>
            <ul class="submenu" style="display:<?= $isInReports ? 'block' : 'none' ?>; list-style:none; padding-left:25px;">
                <li><a href="<?= BASE_PATH ?>/modules/reports/billing_summary.php" class="<?= (strpos($currentUri, '/billing_summary.php') !== false) ? 'active' : '' ?>"><i class="fas fa-file-invoice-dollar"></i> Billing Summary</a></li>
                <li><a href="<?= BASE_PATH ?>/modules/reports/collection.php" class="<?= (strpos($currentUri, '/collection.php') !== false) ? 'active' : '' ?>"><i class="fas fa-hand-holding-usd"></i> Collection Report</a></li>
                <li><a href="<?= BASE_PATH ?>/modules/reports/outstanding.php" class="<?= (strpos($currentUri, '/outstanding.php') !== false) ? 'active' : '' ?>"><i class="fas fa-exclamation-circle"></i> Outstanding Balance</a></li>
                <li><a href="<?= BASE_PATH ?>/modules/reports/monthly_payments.php" class="<?= (strpos($currentUri, '/monthly_payments.php') !== false) ? 'active' : '' ?>"><i class="fas fa-calendar-check"></i> Monthly Payments</a></li>
                <li><a href="<?= BASE_PATH ?>/modules/reports/pendingpayment_report.php" class="<?= (strpos($currentUri, '/pendingpayment_report.php') !== false) ? 'active' : '' ?>"><i class="fas fa-clock"></i> Pending Payments</a></li>
                <li><a href="<?= BASE_PATH ?>/modules/tenants/inactive.php" class="<?= (strpos($currentUri, '/inactive.php') !== false) ? 'active' : '' ?>"><i class="fas fa-user-slash"></i> Inactive Tenants</a></li>
            </ul>
        </li>
        <!-- ============================= -->

        <!-- Settings Dropdown -->
        <li class="has-submenu <?= $isInSettings ? 'open' : '' ?>">
            <a href="#" class="dropdown-toggle <?= $isInSettings ? 'active' : '' ?>">
                <i class="fas fa-cog"></i> Settings
                <i class="fas fa-chevron-down submenu-arrow"></i>
            </a>
            <ul class="submenu" style="display:<?= $isInSettings ? 'block' : 'none' ?>; list-style:none; padding-left:25px;">
                <li><a href="<?= BASE_PATH ?>/modules/settings/update_bp.php" class="<?= (strpos($currentUri, '/update_bp.php') !== false) ? 'active' : '' ?>"><i class="fas fa-user-circle"></i> Change Profile</a></li>
                <li><a href="<?= BASE_PATH ?>/modules/settings/update_account.php" class="<?= (strpos($currentUri, '/update_account.php') !== false) ? 'active' : '' ?>"><i class="fas fa-user-lock"></i> Account</a></li>
            </ul>
        </li>
    </ul>

    <!-- Logout button fixed at bottom -->
    <div class="mt-auto p-2">
        <a href="<?= BASE_PATH ?>/auth/logout.php" class="text-danger d-block">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- Styles and Icons -->
<link href="<?= BASE_PATH ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_PATH ?>/css/sidebar.css">
<link rel="stylesheet" href="<?= BASE_PATH ?>/css/responsive.css">

<!-- Dropdown Toggle Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('click', function(e) {
            e.preventDefault();
            const parentLi = this.closest('.has-submenu');
            const submenu  = this.nextElementSibling;

            // Close all other submenus
            document.querySelectorAll('.has-submenu').forEach(li => {
                if (li !== parentLi) {
                    li.classList.remove('open');
                    const sm = li.querySelector('.submenu');
                    if (sm) sm.style.display = 'none';
                }
            });

            // Toggle this submenu
            const isOpen = parentLi.classList.toggle('open');
            submenu.style.display = isOpen ? 'block' : 'none';
        });
    });

    // --- Mobile Sidebar Toggle Logic ---
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const mainSidebar = document.getElementById('mainSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
        mainSidebar.classList.add('show');
        sidebarOverlay.classList.add('show');
    }

    function closeSidebar() {
        mainSidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', openSidebar);
    }
    
    if (sidebarClose) {
        sidebarClose.addEventListener('click', closeSidebar);
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
});
</script>

<!-- Sidebar Styling -->
<style>
.sidebar ul {
    padding-left: 0;
    list-style: none;
}

.sidebar ul li a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
    transition: background 0.2s ease;
}

.sidebar ul li a:hover {
    background-color: rgba(255,255,255,0.1);
}

.sidebar ul li a.active,
.sidebar ul li.open > a.dropdown-toggle {
    background-color: #5A7D7C;
    font-weight: bold;
    color: #fff;
}

/* Arrow icon rotation */
.dropdown-toggle .submenu-arrow {
    margin-left: auto;
    font-size: 0.75rem;
    transition: transform 0.25s ease;
}
.has-submenu.open > a.dropdown-toggle .submenu-arrow {
    transform: rotate(180deg);
}

/* Submenu items */
.submenu {
    margin-top: 2px;
    margin-bottom: 4px;
    padding-left: 0 !important;
    list-style: none;
}

.submenu li a {
    background: rgba(0,0,0,0.15);
    color: #cfd8dc;
    padding: 8px 15px 8px 20px;
    display: flex;
    align-items: center;
    gap: 8px;
    border-radius: 6px;
    margin-top: 2px;
    font-size: 0.92rem;
    transition: background 0.2s ease;
}

.submenu li a:hover,
.submenu li a.active {
    background-color: #5A7D7C;
    color: #fff;
    font-weight: bold;
}
</style>
