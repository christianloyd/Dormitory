<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Settings</title>
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
        .settings-container {
            display: flex;
            height: 100%;
        }
        .settings-sidebar {
            width: 220px;
            background: #f4f4f4;
            border-right: 1px solid #ddd;
            padding-top: 20px;
        }
        .settings-sidebar ul {
            list-style: none;
            padding: 0;
        }
        .settings-sidebar li {
            margin: 10px 0;
        }
        .settings-sidebar a {
            display: block;
            padding: 12px 20px;
            text-decoration: none;
            color: #333;
        }
        .settings-sidebar a.active, .settings-sidebar a:hover {
            background: #007bff;
            color: white;
            border-radius: 8px;
        }
        .settings-content {
            flex: 1;
            padding: 30px;
        }
        .settings-section {
            display: none;
        }
        .settings-section.active {
            display: block;
        }
        body, html {
    width: 100%;
    height: 100%;
    overflow: hidden; /* aron dili mo-scroll */
}
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
<div class="settings-container">

        <!-- Sidebar -->
        <div class="settings-sidebar">
            <ul>
                <li><a href="#" class="tab-link active" data-target="profile"><i class="fas fa-user"></i> Change Profile</a></li>
                <li><a href="#" class="tab-link" data-target="tenant"><i class="fas fa-users"></i> Edit Tenant Information</a></li>
                <li><a href="#" class="tab-link" data-target="room"><i class="fas fa-door-closed"></i> Edit Room Information</a></li>
                <li><a href="#" class="tab-link" data-target="account"><i class="fas fa-key"></i> Account</a></li>
            </ul>
        </div>

        <!-- Content -->
        <div class="settings-content">
            <!-- Profile -->
            <div id="profile" class="settings-section active">
                <?php include "upload_image.php"; ?>
            </div>

            <!-- Tenant -->
            <div id="tenant" class="settings-section" data-loaded="false">
                <p>Loading tenant information...</p>
            </div>

            <!-- Room -->
            <div id="room" class="settings-section">
                <?php include 'room_customization.php'; ?>
            </div>

           <!-- Backup -->
            <div id="backup" class="settings-section">
                 <h2>Backup & Restore</h2>

            <!-- Export (Backup) -->
            <div class="mb-4">
            <h5>Export Database</h5>
        <form method="POST" action="backup_restore.php">
            <input type="hidden" name="action" value="export">
            <button type="submit" class="btn btn-primary">Download Backup</button>
        </form>
    </div>

    <!-- Import (Restore) -->
    <div>
        <h5>Import Database</h5>
        <form method="POST" action="backup_restore.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import">
            <input type="file" name="sql_file" class="form-control mb-2" required>
            <button type="submit" class="btn btn-success">Restore Backup</button>
        </form>
    </div>
</div>

<!-- Account Tab -->
<div id="account" class="settings-section">
    <h2>Change Username & Password</h2>
    <form id="update-account-form" method="POST" action="update_account.php">
        <div class="mb-3">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">New Username</label>
            <input type="text" name="new_username" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-warning">Update Credentials</button>
    </form>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    function activateTab(target) {
        $('.tab-link').removeClass('active');
        $('.tab-link[data-target="'+target+'"]').addClass('active');

        $('.settings-section').removeClass('active');
        $('#' + target).addClass('active');

        // Lazy load tenant only once
        if(target === 'tenant' && $('#tenant').data('loaded') === false) {
            $('#tenant').load('tenant_customization.php', function() {
                $('#tenant').data('loaded', true);
            });
        }
    }

    // Tab click
    $('.tab-link').click(function(e) {
        e.preventDefault();
        const target = $(this).data('target');
        activateTab(target);
    });

    // Auto-activate tab from URL
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if(tabParam) activateTab(tabParam);

    // AJAX: Update account credentials
    $('#updateAccountForm').submit(function(e){
        e.preventDefault();
        $.ajax({
            url: 'update_account.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response){
                $('#accountMessage').html(response);
            },
            error: function(){
                $('#accountMessage').html('<div class="alert alert-danger">An error occurred. Try again.</div>');
            }
        });
    });
});
</script>
</body>
</html>
