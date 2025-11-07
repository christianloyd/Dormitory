<?php
/**
 * Secure Tenant Update Handler
 * Fixed SQL injection and added secure file upload
 */
require_once '../../includes/auth_check.php';

try {
    // Verify CSRF token
    CSRF::verifyRequest();

    // Validate tenant_id is provided
    if (!isset($_POST['edit_tenant_id'])) {
        throw new Exception("Tenant ID is required.");
    }

    $tenant_id = intval($_POST['edit_tenant_id']);
    $tenant_name = trim($_POST['tenant_name']);
    $room_id = intval($_POST['room_id']);
    $deck_type = !empty($_POST['deck_type']) ? $_POST['deck_type'] : null;
    $address = trim($_POST['address']);
    $contact_number = trim($_POST['contact_number']);
    $guardian_contact = trim($_POST['guardian_contact']);
    $status = $_POST['status'];
    $date_started = $_POST['date_started'];

    // Validate inputs
    if (!Validator::isNotEmpty($tenant_name)) {
        throw new Exception("Tenant name is required.");
    }

    if (!Validator::isValidPhoneNumber($contact_number)) {
        throw new Exception("Invalid contact number format. Must be 09XXXXXXXXX.");
    }

    if (!Validator::isValidPhoneNumber($guardian_contact)) {
        throw new Exception("Invalid guardian contact format. Must be 09XXXXXXXXX.");
    }

    if (!Validator::isValidDate($date_started)) {
        throw new Exception("Invalid date format.");
    }

    // Fetch existing tenant to preserve old images if not updated (FIXED: SQL Injection)
    $result = $db->select('tenants', ['tenant_id' => $tenant_id], 'profile_pic, proof_pic');
    $existing = $result->fetch_assoc();

    if (!$existing) {
        throw new Exception("Tenant not found.");
    }

    $profile_pic = $existing['profile_pic'];
    $proof_pic = $existing['proof_pic'];

    // Initialize secure file upload handler
    $fileUpload = new FileUpload();

    // Handle profile picture upload securely
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == UPLOAD_ERR_OK) {
        try {
            $new_profile = $fileUpload->upload($_FILES['profile_pic'], 'tenant_profile');
            // Delete old file if exists
            if ($profile_pic && strpos($profile_pic, 'default') === false) {
                $fileUpload->delete($profile_pic);
            }
            $profile_pic = $new_profile;
        } catch (Exception $e) {
            // Log error but continue (optional: throw to stop execution)
            error_log("Profile pic upload failed: " . $e->getMessage());
        }
    }

    // Handle proof picture upload securely
    if (isset($_FILES['proof_pic']) && $_FILES['proof_pic']['error'] == UPLOAD_ERR_OK) {
        try {
            $new_proof = $fileUpload->upload($_FILES['proof_pic'], 'tenant_proof');
            // Delete old file if exists
            if ($proof_pic && strpos($proof_pic, 'default') === false) {
                $fileUpload->delete($proof_pic);
            }
            $proof_pic = $new_proof;
        } catch (Exception $e) {
            // Log error but continue
            error_log("Proof pic upload failed: " . $e->getMessage());
        }
    }

    // Update tenant record using Database helper
    $db->update('tenants',
        [
            'tenant_name' => $tenant_name,
            'room_id' => $room_id,
            'deck_type' => $deck_type,
            'address' => $address,
            'contact_number' => $contact_number,
            'guardian_contact' => $guardian_contact,
            'status' => $status,
            'date_started' => $date_started,
            'profile_pic' => $profile_pic,
            'proof_pic' => $proof_pic
        ],
        ['tenant_id' => $tenant_id]
    );

    Session::setMessage("Tenant updated successfully!", "success");
    header("Location: index.php");
    exit();

} catch (Exception $e) {
    Session::setMessage($e->getMessage(), "danger");
    header("Location: index.php");
    exit();
}
?>
