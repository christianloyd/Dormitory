<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['admin_username'])) {
    header("Location: ../../auth/login.php");
    exit();
}

$message_bg = '';
$message_header = '';
$current_bg = 'assets/login-bg.jpg'; // default
$current_header = 'assets/header.jpg'; // default

// Fetch current images from database
$result = $conn->query("SELECT setting_name, setting_value FROM settings WHERE setting_name IN ('profile_image','header_image')");
while ($row = $result->fetch_assoc()) {
    if ($row['setting_name'] === 'profile_image') $current_bg = $row['setting_value'];
    if ($row['setting_name'] === 'header_image') $current_header = $row['setting_value'];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Login background upload
    if (isset($_FILES['bg_image'])) {
        $file = $_FILES['bg_image'];
        $allowed_types = ['image/jpeg','image/jpg','image/png','image/gif'];
        if ($file['error'] === 0 && in_array($file['type'], $allowed_types)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_name = 'login_bg_'.time().'.'.$ext;
            $upload_dir = __DIR__ . '/../../assets/bg/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $upload_path = $upload_dir . $new_name;
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_name = 'profile_image'");
                $path_for_db = 'assets/bg/' . $new_name;
                $stmt->bind_param("s", $path_for_db);
                if ($stmt->execute()) {
                    $message_bg = 'Login background updated successfully.';
                    $current_bg = $path_for_db;
                } else {
                    $message_bg = 'Failed to update login background in database.';
                }
            } else {
                $message_bg = 'Failed to move uploaded file for login background.';
            }
        } elseif ($file['error'] !== 4) { // 4 = no file
            $message_bg = 'Invalid file type for login background.';
        }
    }

    // Header image upload
    if (isset($_FILES['header_image'])) {
        $file = $_FILES['header_image'];
        $allowed_types = ['image/jpeg','image/jpg','image/png','image/gif'];
        if ($file['error'] === 0 && in_array($file['type'], $allowed_types)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_name = 'header_'.time().'.'.$ext;
            $upload_dir = __DIR__ . '/../../assets/bg/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $upload_path = $upload_dir . $new_name;
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_name = 'header_image'");
                $path_for_db = 'assets/bg/' . $new_name;
                $stmt->bind_param("s", $path_for_db);
                if ($stmt->execute()) {
                    $message_header = 'Header image updated successfully.';
                    $current_header = $path_for_db;
                } else {
                    $message_header = 'Failed to update header image in database.';
                }
            } else {
                $message_header = 'Failed to move uploaded file for header image.';
            }
        } elseif ($file['error'] !== 4) {
            $message_header = 'Invalid file type for header image.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Background & Header - Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include '../../includes/sidebar.php'; ?>
   <style>
    /* Wrapper to center content sa available screen space */
    .content-wrapper {
        display: flex;
        justify-content: center; /* center horizontally */
        align-items: flex-start; /* top-aligned, pwede i-center vertically if gusto */
        gap: 30px; /* space between two forms */
        margin-top: 50px;
        margin-left: 220px; /* sidebar width */
        flex-wrap: wrap; /* responsive if screen gamay */
    }

    .form-container {
        width: 100%;
        max-width: 450px;
    }

    .preview-img {
        width: 100%;
        margin-bottom: 10px;
        border-radius: 8px;
        height: auto;
        object-fit: cover;
    }
     body, html {
    width: 100%;
    height: 100%;
    overflow: hidden; /* aron dili mo-scroll */
}
</style>
</head>
<body>
<div class="content-wrapper">

    <!-- Login Background Form -->
    <div class="form-container">
        <h4>Login Background</h4>
        <?php if($message_bg): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message_bg) ?></div>
        <?php endif; ?>
        <img src="<?= BASE_PATH . '/' . $current_bg ?>" class="preview-img" alt="Current Background">
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="bg_image" class="form-control mb-2" accept="image/*" required>
            <button type="submit" class="btn btn-warning w-100">Upload</button>
        </form>
    </div>

    <!-- Header Image Form -->
    <div class="form-container">
        <h4>Header Image</h4>
        <?php if($message_header): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message_header) ?></div>
        <?php endif; ?>
        <img src="<?= BASE_PATH . '/' . $current_header ?>" class="preview-img" alt="Current Header">
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="header_image" class="form-control mb-2" accept="image/*" required>
            <button type="submit" class="btn btn-warning w-100">Upload</button>
        </form>
    </div>

</div>
</body>
</html>
