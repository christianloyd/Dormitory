<?php
require_once "../../includes/auth_check.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports Hub</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    /* Reset body */
    body { margin:0; font-family: Arial, sans-serif; background-color:#f0f4f3; }

    /* Sidebar */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 225px;
        height: 100%;
        background-color: #333;
        color: #fff;
        z-index: 0; /* must be lower than main content */
    }

    /* Main content */
    .main-content {
        margin-left: 225px; /* space for sidebar */
        padding: 30px;
        min-height: 100vh;
        position: relative;
        z-index: 1; /* make sure it's above sidebar */
    }

    /* Report buttons container */
    .report-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 20px;
    }

    /* Buttons */
    .report-buttons a {
        flex: 1 1 200px;
        text-align: center;
        padding: 15px;
        border-radius: 8px;
        text-decoration: none;
        color: white;
        font-weight: bold;
        transition: 0.3s;
        display: block; /* ensures full area is clickable */
        z-index: 2;
    }

    .btn-outstanding { background-color:#ffc107; color:#000; }
    .btn-outstanding:hover { background-color:#e0a800; color:#000; }

    .btn-pending { background-color:#17a2b8; }
    .btn-pending:hover { background-color:#138496; }
</style>
</head>
<body>

<?php include '../../includes/sidebar.php'; ?>

<div class="main-content">
    <h2>Reports Hub</h2>
    <p>Select a report to view:</p>

    <div class="report-buttons">
        <a href="./outstanding.php" class="btn-outstanding">Outstanding Balance Report</a>
        <a href="./pendingpayment_report.php" class="btn-pending">Pending Payment Report</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
