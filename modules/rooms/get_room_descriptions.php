<?php
require_once '../../includes/auth_check.php';

header('Content-Type: application/json');

$room_id = intval($_GET['room_id']);
$stmt = $conn->prepare("SELECT description FROM room_additional_descriptions WHERE room_id=? ORDER BY created_at ASC");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

$descriptions = [];
while($row = $result->fetch_assoc()){
    $descriptions[] = $row;
}

echo json_encode($descriptions);
