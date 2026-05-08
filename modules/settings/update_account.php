<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['admin_username'])) {
    header("Location: ../../auth/login.php");
    exit();
}

$message = '';
$success_update = false; // flag para modal

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_username = $_POST['current_username'];
    $current_password = $_POST['current_password'];
    $new_username = $_POST['new_username'];
    $new_password = $_POST['new_password'];

    // Get current admin data
    $sql = "SELECT * FROM admin_account WHERE BINARY username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if (!$admin) {
        $message = 'Admin account not found.';
    } else {
        if (password_verify($current_password, $admin['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE admin_account SET username = ?, password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $new_username, $hashed_password, $admin['id']);
            if ($update_stmt->execute()) {
                $success_update = true; // success flag
                session_destroy(); // destroy session after update
            } else {
                $message = 'Failed to update credentials. Try again.';
            }
        } else {
            $message = 'Current password is incorrect.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Account - Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php include '../../includes/sidebar.php'; ?>
    <style>
        .main-wrapper {
            display: flex;
            min-height: 100vh;
            margin-left: 250px;
            padding: 30px;
            background-color: #f5f5f5;
            transition: filter 0.3s ease;
        }
        .main-wrapper.blurred {
            filter: blur(4px);
        }
       .form-container {
    background: #fff;
    padding: 40px 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    width: 2000px; /* increase from 400px to 600px */
    max-width: 99%; /* para responsive sa smaller screens */
}
        .form-container h2 {
            margin-bottom: 30px;
            color: #333;
            text-align: left;
        }
        .btn-warning {
            background-color: #5A7D7C;
            border-color: #5A7D7C;
            font-weight: bold;
        }
        .btn-warning:hover {
            background-color: #496766;
            border-color: #496766;
        }
        .alert {
            font-size: 0.95rem;
        }
        body, html {
        width: 100%;
        height: 100%;
        overflow: hidden; /* aron dili mo-scroll */
        }
    </style>
</head>
<body>
<div class="main-wrapper" id="mainWrapper">
    <div class="form-container">
        <h2>Change Username & Password</h2>
        <?php if($message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Current Username</label>
                <input type="text" name="current_username" class="form-control" required>
            </div>
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
</div>

<?php if($success_update): ?>
<script>
document.getElementById('mainWrapper').classList.add('blurred'); // blur background
Swal.fire({
    title: "🔒 Credentials Updated",
    html: "<p>Your account credentials have been successfully changed.</p><p>Please restart the system to apply the changes.</p>",
    icon: "info",
    showCancelButton: true,
    confirmButtonText: "Restart Now",
    cancelButtonText: "Later",
    allowOutsideClick: false,
    allowEscapeKey: false,
    customClass: {
        popup: "swal2-popup-custom",
        title: "swal2-title-custom",
        confirmButton: "swal2-confirm-custom",
        cancelButton: "swal2-cancel-custom"
    }
}).then((result) => {
    // redirect to login page after closing modal
    window.location.href = "../../auth/login.php";
});
</script>
<style>
.swal2-popup-custom {
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    border-radius: 8px;
    padding: 25px 20px;
    width: 420px;
}
.swal2-title-custom {
    font-size: 20px;
    font-weight: 600;
    color: #1a1a1a;
}
.swal2-confirm-custom {
    background-color: #0078D7;
    color: white;
    font-weight: 500;
    border-radius: 4px;
    padding: 8px 18px;
}
.swal2-cancel-custom {
    background-color: #f3f3f3;
    color: #333;
    font-weight: 500;
    border-radius: 4px;
    padding: 8px 18px;
}
</style>
<?php endif; ?>

</body>
</html>
