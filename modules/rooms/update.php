<?php
require_once "../../includes/auth_check.php"; // Include database connection

// Make sure POST data exists
if (!isset($_POST['room_number'])) {
    die("Room number is required.");
}

// Sanitize and assign POST data
$room_number = $_POST['room_number'];
$room_type = $_POST['room_type'];
$deck_type = !empty($_POST['deck_type']) ? $_POST['deck_type'] : null;
$price = floatval($_POST['price']);
$capacity = intval($_POST['capacity']);
$available = intval($_POST['available']);
$status = $_POST['status'];
$upper_deck_count = intval($_POST['upper_deck_count']);
$lower_deck_count = intval($_POST['lower_deck_count']);

// Prepare and execute update
$stmt = $conn->prepare("UPDATE rooms SET room_type=?, deck_type=?, price=?, capacity=?, available=?, status=?, upper_deck_count=?, lower_deck_count=? WHERE room_number=?");
$stmt->bind_param(
    "ssdiissii",
    $room_type,
    $deck_type,
    $price,
    $capacity,
    $available,
    $status,
    $upper_deck_count,
    $lower_deck_count,
    $room_number
);

if ($stmt->execute()) {
    $stmt->close();
    // Redirect back to user.php room tab with success message
    header("Location: user.php?tab=room&updated=1");
    exit();
} else {
    die("Failed to update room: " . $stmt->error);
}
?>
