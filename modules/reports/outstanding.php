<?php
/**
 * Reports Module - Outstanding Balance
 * Path: /modules/reports/outstanding.php
 */
require_once "../../includes/auth_check.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reports Hub</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background-color: #f0f4f3; font-family: Arial, sans-serif; }
    .main-content { margin-left:225px; padding:30px; min-height:100vh; }
    .report-buttons { display:flex; flex-wrap:wrap; gap:15px; margin-top:20px; }
    .report-buttons a { flex:1 1 200px; text-align:center; padding:15px; border-radius:8px; text-decoration:none; color:white; font-weight:bold; transition: 0.3s; }
    .btn-billing { background-color:#5A7D7C; }
    .btn-billing:hover { background-color:#496766; }
    .btn-collection { background-color:#28a745; }
    .btn-collection:hover { background-color:#218838; }
    .btn-outstanding { background-color:#ffc107; color:#000; }
    .btn-outstanding:hover { background-color:#e0a800; color:#000; }
</style>
</head>
<body>
<?php include '../../sidebar.php'; ?>

<div class="main-content">
    <h2>Reports Hub</h2>
    <p>Select a report to view:</p>

    <div class="report-buttons">
        
        <a href="report_outstanding.php" class="btn-outstanding">Outstanding Balance Repot</a>
        <!-- Add more reports here -->
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
