<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    die('unauthorized');
}

if (isset($_GET['id'])) {
    $room_id = intval($_GET['id']);

    // Update record_status to 'Active'
    $stmt = $conn->prepare("UPDATE rooms SET record_status = 'Active' WHERE room_id = ?");
    $stmt->bind_param("i", $room_id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
} else {
    echo "error";
}

$conn->close();
?>
