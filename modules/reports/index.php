<?php
/**
 * Reports Module - Main Reports Hub
 * Path: /modules/reports/index.php
 */
require_once "../../includes/auth_check.php";

// Determine which tab to show
$activeTab = 'billing_summary'; // default tab

if (isset($_GET['tab'])) {
    $activeTab = $_GET['tab'];
} elseif (isset($_POST['tab'])) {
    $activeTab = $_POST['tab'];
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Billing Reports</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    display: flex;
    background-color: #f6f7f6ff;
}
.main-content {
    margin-left: 225px;
    padding: 30px;
    min-height: 100vh;
    width: calc(100% - 225px);
    background-color: #f6f7f6ff;
}
.report-container {
    display: flex;
    height: 100%;
}
.report-sidebar {
    width: 220px;
    background: #f4f4f4;
    border-right: 1px solid #ddd;
    padding-top: 20px;
}
.report-sidebar ul {
    list-style: none;
    padding: 0;
}
.report-sidebar li {
    margin: 10px 0;
}
.report-sidebar a {
    display: block;
    padding: 12px 20px;
    text-decoration: none;
    color: #333;
}
.report-sidebar a.active, .report-sidebar a:hover {
    background: #007bff;
    color: white;
    border-radius: 8px;
}
.report-content {
    flex: 1;
    padding: 30px;
    background: #fff;
    overflow-y: auto;
}
.report-section {
    display: none;
}
.report-section.active {
    display: block;
}
body, html {
    width: 100%;
    height: 100%;
    overflow: hidden;
}
</style>
</head>
<body>

<?php include '../../sidebar.php'; ?>

<div class="main-content">
    <?php
    $header = $conn->query("SELECT setting_value FROM settings WHERE setting_name='header_image'")->fetch_assoc();
    $header_pic = $header ? $header['setting_value'] : "uploads/default_header.png";
    ?>
    <div class="d-flex justify-content-between align-items-center mt-0 mb-3">
        <h2>Billing Reports</h2>
        <div class="profile-box d-flex align-items-center">
            <img src="<?= $header_pic ?>" alt="Header Picture" class="rounded-circle" width="50" height="50">
            <span class="ms-2">Admin</span>
        </div>
    </div>

    <div class="report-container">
        <!-- Sidebar -->
        <div class="report-sidebar">
            <ul>
                <li><a href="#" class="tab-link <?= ($activeTab=='billing_summary')?'active':'' ?>" data-target="billing_summary">Billing Summary Report</a></li>
                <li><a href="#" class="tab-link <?= ($activeTab=='collection')?'active':'' ?>" data-target="collection">Collection Report</a></li>
                <li><a href="#" class="tab-link <?= ($activeTab=='outstanding')?'active':'' ?>" data-target="outstanding">Outstanding Balance Report</a></li>
                <li><a href="#" class="tab-link <?= ($activeTab=='inactive_tenant')?'active':'' ?>" data-target="inactive_tenant">Inactive Tenant</a></li>
            </ul>
        </div>

        <!-- Content -->
        <div class="report-content">
            <div id="billing_summary" class="report-section <?= ($activeTab=='billing_summary')?'active':'' ?>">
                <?php include "report_billing_summary.php"; ?>
            </div>
            <div id="collection" class="report-section <?= ($activeTab=='collection')?'active':'' ?>">
                <?php include "report_collection.php"; ?>
            </div>
            <div id="outstanding" class="report-section <?= ($activeTab=='outstanding')?'active':'' ?>">
                <?php include "report_outstanding.php"; ?>
            </div>
            <div id="inactive_tenant" class="report-section <?= ($activeTab=='inactive_tenant')?'active':'' ?>">
                <?php include "inactive_tenant.php"; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    function activateTab(target) {
        $('.tab-link').removeClass('active');
        $('.tab-link[data-target="'+target+'"]').addClass('active');

        $('.report-section').removeClass('active');
        $('#' + target).addClass('active');
    }

    $('.tab-link').click(function(e) {
        e.preventDefault();
        const target = $(this).data('target');
        activateTab(target);
    });

    // Auto-activate tab based on PHP variable (GET or POST)
    activateTab('<?= $activeTab ?>');
});
</script>

</body>
</html>
