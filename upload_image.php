<?php
/**
 * Secure Image Upload Handler
 * Uses FileUpload helper for validation and Database helper for secure queries
 */
require_once 'includes/auth_check.php';

$error = '';
$success = '';

// Handle uploads securely
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        CSRF::verifyRequest();

        $type = $_POST['type'] ?? '';

        // Validate type
        if (!in_array($type, ['profile', 'header'])) {
            throw new Exception("Invalid upload type.");
        }

        // Initialize secure file upload handler
        $fileUpload = new FileUpload();

        // Upload profile image
        if ($type === 'profile' && !empty($_FILES['profile_image']['name'])) {
            $file_field = 'profile_image';
            $setting_name = 'profile_image';
        }
        // Upload header image
        elseif ($type === 'header' && !empty($_FILES['header_image']['name'])) {
            $file_field = 'header_image';
            $setting_name = 'header_image';
        } else {
            throw new Exception("No file selected.");
        }

        // Upload file securely
        $uploaded_path = $fileUpload->upload($_FILES[$file_field], $type);

        // Get old file path to delete later
        $old_result = $db->select('settings', ['setting_name' => $setting_name]);
        $old_row = $old_result->fetch_assoc();
        $old_file = $old_row['setting_value'] ?? null;

        // Update database using secure Database helper
        $db->update('settings',
            ['setting_value' => $uploaded_path],
            ['setting_name' => $setting_name]
        );

        // Delete old file if exists and not a default
        if ($old_file && strpos($old_file, 'default') === false) {
            $fileUpload->delete($old_file);
        }

        $success = "Image updated successfully!";
        Session::setMessage($success, 'success');
        header('Location: user.php?success=1');
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
        Session::setMessage($error, 'danger');
    }
}

// Fetch current images securely
try {
    $profile_result = $db->select('settings', ['setting_name' => 'profile_image']);
    $profile_row = $profile_result->fetch_assoc();
    $profile_pic = $profile_row['setting_value'] ?? 'uploads/default_profile.png';

    $header_result = $db->select('settings', ['setting_name' => 'header_image']);
    $header_row = $header_result->fetch_assoc();
    $header_pic = $header_row['setting_value'] ?? 'uploads/default_header.png';
} catch (Exception $e) {
    $profile_pic = 'uploads/default_profile.png';
    $header_pic = 'uploads/default_header.png';
}

// Get flash messages
$flash_message = Session::getMessage();
?>

<h2 class="mb-4"><i class="fas fa-cog"></i> User Settings</h2>

<?php if ($flash_message): ?>
    <div class="alert alert-<?= htmlspecialchars($flash_message['type']) ?>">
        <?= htmlspecialchars($flash_message['message']) ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Image updated successfully!</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Profile Picture -->
    <div class="col-md-6">
        <div class="card p-3 text-center">
            <h5>Profile Picture (Login Background)</h5>
            <img src="<?= htmlspecialchars($profile_pic) ?>" class="profile-square mb-3" alt="Profile Picture">

            <form action="upload_image.php" method="POST" enctype="multipart/form-data">
                <?php echo CSRF::getTokenField(); ?>
                <input type="hidden" name="type" value="profile">
                <input type="file" name="profile_image" class="form-control mb-2" accept="image/jpeg,image/png,image/gif" required>
                <small class="text-muted">Max 5MB. JPG, PNG, or GIF only.</small>
                <button type="submit" class="btn btn-primary btn-upload">Upload</button>
            </form>
        </div>
    </div>

    <!-- Header Picture -->
    <div class="col-md-6">
        <div class="card p-3 text-center">
            <h5>Header Picture</h5>
            <div class="header-wrapper position-relative d-inline-block">
                <img src="<?= htmlspecialchars($header_pic) ?>" class="header-circle mb-3" alt="Header Picture">
                <span class="header-text position-absolute">ADMIN</span>
            </div>

            <form action="upload_image.php" method="POST" enctype="multipart/form-data">
                <?php echo CSRF::getTokenField(); ?>
                <input type="hidden" name="type" value="header">
                <input type="file" name="header_image" class="form-control mb-2" accept="image/jpeg,image/png,image/gif" required>
                <small class="text-muted">Max 5MB. JPG, PNG, or GIF only.</small>
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
