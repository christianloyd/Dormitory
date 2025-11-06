<?php
include 'db.php';

if (isset($_GET['id'])) {
    $room_id = intval($_GET['id']);

    $sql = "UPDATE rooms SET record_status = 'Inactive' WHERE room_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $room_id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
}
?>
