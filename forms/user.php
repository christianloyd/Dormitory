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
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            background-color: #f6f7f6ff;
        }
        .main-content {
            margin-left: 225px; /* respect main sidebar */
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
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="settings-container">
        <!-- User Settings Sidebar -->
        <div class="settings-sidebar">
            <ul>
                <li><a href="#" class="tab-link active" data-target="profile"><i class="fas fa-user"></i> Change Profile</a></li>
                <li><a href="#" class="tab-link" data-target="header"><i class="fas fa-image"></i> Change Header Picture</a></li>
                <li><a href="#" class="tab-link" data-target="tenant"><i class="fas fa-users"></i> Edit Tenant Information</a></li>
                <li><a href="#" class="tab-link" data-target="room"><i class="fas fa-door-closed"></i> Edit Room Information</a></li>
                <li><a href="#" class="tab-link" data-target="theme"><i class="fas fa-palette"></i> Change Theme</a></li>
                <li><a href="#" class="tab-link" data-target="backup"><i class="fas fa-database"></i> Backup & Restore</a></li>
            </ul>
        </div>

        <!-- Content Area -->
        <div class="settings-content">
            <div id="profile" class="settings-section active">
                <h2>Change Profile</h2>
                <p>Upload or edit your profile picture for logout/login area.</p>
            </div>
            <div id="header" class="settings-section">
                <h2>Change Header Picture</h2>
                <p>Upload a new header logo or image.</p>
            </div>
            <div id="tenant" class="settings-section">
                <h2>Edit Tenant Information</h2>
                <p>Manage and update tenant records here.</p>
            </div>
            <div id="room" class="settings-section">
                <h2>Edit Room Information</h2>
                <p>Manage and update room details here.</p>
            </div>
            <div id="theme" class="settings-section">
                <h2>Change Color Theme</h2>
                <p>Select a new color scheme for the system.</p>
            </div>
            <div id="backup" class="settings-section">
                <h2>Backup & Restore</h2>
                <p>Export or import database backup.</p>
            </div>
        </div>
    </div>
</div>

<script>
    // Tab switching
    document.querySelectorAll('.tab-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            document.querySelectorAll('.settings-section').forEach(sec => sec.classList.remove('active'));

            let target = this.getAttribute('data-target');
            document.getElementById(target).classList.add('active');
        });
    });
</script>
</body>
</html>
