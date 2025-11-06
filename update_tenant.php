<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Make sure tenant_id is provided (fix: match modal field name)
if (!isset($_POST['edit_tenant_id'])) {
    die("Tenant ID is required.");
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

// Fetch existing tenant to preserve old images if not updated
$result = $conn->query("SELECT profile_pic, proof_pic FROM tenants WHERE tenant_id=$tenant_id");
$existing = $result->fetch_assoc();
$profile_pic = $existing['profile_pic'];
$proof_pic = $existing['proof_pic'];

// Handle profile picture upload
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
    $target_profile = "uploads/profile_" . time() . "_" . basename($_FILES['profile_pic']['name']);
    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_profile)) {
        $profile_pic = $target_profile;
    }
}

// Handle proof picture upload
if (isset($_FILES['proof_pic']) && $_FILES['proof_pic']['error'] == 0) {
    $target_proof = "uploads/proof_" . time() . "_" . basename($_FILES['proof_pic']['name']);
    if (move_uploaded_file($_FILES['proof_pic']['tmp_name'], $target_proof)) {
        $proof_pic = $target_proof;
    }
}

// Update tenant record
$stmt = $conn->prepare("UPDATE tenants 
    SET tenant_name=?, room_id=?, deck_type=?, address=?, contact_number=?, guardian_contact=?, status=?, date_started=?, profile_pic=?, proof_pic=? 
    WHERE tenant_id=?");

$stmt->bind_param("sissssssssi", $tenant_name, $room_id, $deck_type, $address, $contact_number, $guardian_contact, $status, $date_started, $profile_pic, $proof_pic, $tenant_id);

if ($stmt->execute()) {
    // Redirect back to user.php and activate tenant tab
    header("Location: user.php?tab=tenant&updated=1");
    exit();
} else {
    die("Failed to update tenant: " . $stmt->error);
}
?>
