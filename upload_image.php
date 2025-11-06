<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Handle uploads safely
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = isset($_POST['type']) ? $_POST['type'] : '';

    // Upload profile
    if ($type === 'profile' && isset($_FILES['profile_image'])) {
        $target = "uploads/profile_" . time() . ".jpg";
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target)) {
            $conn->query("UPDATE settings SET setting_value='$target' WHERE setting_name='profile_image'");
        }
    }

    // Upload header
    if ($type === 'header' && isset($_FILES['header_image'])) {
        $target = "uploads/header_" . time() . ".jpg";
        if (move_uploaded_file($_FILES['header_image']['tmp_name'], $target)) {
            $conn->query("UPDATE settings SET setting_value='$target' WHERE setting_name='header_image'");
        }
    }

    // Redirect safely with alert
    echo "<script>
        alert('Image updated successfully!');
        window.location.href='user.php?success=1';
    </script>";
    exit();
}

// Fetch current images
$profile = $conn->query("SELECT setting_value FROM settings WHERE setting_name='profile_image'")->fetch_assoc();
$profile_pic = $profile ? $profile['setting_value'] : "uploads/default_profile.png";

$header = $conn->query("SELECT setting_value FROM settings WHERE setting_name='header_image'")->fetch_assoc();
$header_pic = $header ? $header['setting_value'] : "uploads/default_header.png";
?>

<h2 class="mb-4"><i class="fas fa-cog"></i> User Settings</h2>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Image updated successfully!</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Profile Picture -->
    <div class="col-md-6">
        <div class="card p-3 text-center">
            <h5>Profile Picture</h5>
            <img src="<?= $profile_pic ?>" class="profile-square mb-3" alt="Profile Picture">

            <form action="upload_image.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="type" value="profile">
                <input type="file" name="profile_image" class="form-control mb-2">
                <button type="submit" class="btn btn-primary btn-upload">Upload</button>
            </form>
        </div>
    </div>

    <!-- Header Picture -->
    <div class="col-md-6">
        <div class="card p-3 text-center">
            <h5>Header Picture</h5>
            <div class="header-wrapper position-relative d-inline-block">
                <img src="<?= $header_pic ?>" class="header-circle mb-3" alt="Header Picture">
                <span class="header-text position-absolute">ADMIN</span>
            </div>

            <form action="upload_image.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="type" value="header">
                <input type="file" name="header_image" class="form-control mb-2">
                <button type="submit" class="btn btn-success btn-upload">Upload</button>
            </form>
        </div>
    </div>
</div>

<style>
body { margin: 0; font-family: Arial, sans-serif; display: flex; background-color: #f6f7f6ff; }
.main-content { margin-left: 225px; padding: 30px; min-height: 100vh; width: calc(100% - 225px); background-color: #f6f7f6ff; }
.card { border-radius: 12px; box-shadow: 0px 4px 12px rgba(0,0,0,0.1); }

.profile-square {
    width: 200px;
    height: 200px;
    object-fit: cover;
    border-radius: 12px;
    border: 3px solid #0d6efd;
    display: block;
    margin: 0 auto;
}

.header-wrapper { width: 150px; height: 150px; position: relative; }

.header-circle {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #28a745;
}

.header-text {
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-weight: bold;
    font-size: 1rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
    pointer-events: none;
    position: absolute;
}

.btn-upload { margin-top: 10px; }
</style>
