<?php
/**
 * Mark Notification as Read
 * Path: /modules/utilities/mark_notification_read.php
 */
require_once '../../includes/auth_check.php';

if(isset($_POST['id'])){
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Return success response
    http_response_code(200);
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No ID provided']);
}
?>
