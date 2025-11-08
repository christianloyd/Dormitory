<?php
/**
 * Settings Module - Update Account
 * Path: /modules/settings/update_account.php
 */
require_once '../../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = trim($_POST['current_password']);
    $new_username = trim($_POST['new_username']);
    $new_password = trim($_POST['new_password']);

    // Get current admin info (case-sensitive username)
    $stmt = $conn->prepare("SELECT id, username, password FROM admin_account WHERE BINARY username=?");
    $stmt->bind_param("s", $_SESSION['admin_username']);
    $stmt->execute();
    $res = $stmt->get_result();
    $admin = $res->fetch_assoc();

    if (!$admin) {
        die("Admin account not found.");
    }

    // Verify current password
    if (!password_verify($current_password, $admin['password'])) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            Swal.fire({
                title: 'Error',
                text: 'Current password is incorrect.',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#d33'
            }).then(() => {
                window.history.back();
            });
        </script>";
        exit();
    }

    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update database with new username & hashed password
    $stmt = $conn->prepare("UPDATE admin_account SET username=?, password=? WHERE id=?");
    $stmt->bind_param("ssi", $new_username, $hashed_password, $admin['id']);
    $stmt->execute();

    // Update session with new username
    $_SESSION['admin_username'] = $new_username;

    // Ensure header image is not removed and display "admin"
    $fixed_header = "uploads/header_admin.jpg"; // keep your header image path
    $conn->query("UPDATE settings SET setting_value='$fixed_header' WHERE setting_name='header_image'");

    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "<script>
        Swal.fire({
            title: 'Success!',
            text: 'Credentials updated successfully! Header image is preserved. Please restart the Dormitory System and use the new username & password.',
            icon: 'success',
            confirmButtonText: 'OK',
            confirmButtonColor: '#198754'
        }).then(() => {
            window.location.href='logout.php';
        });
    </script>";
    exit();
}
?>
