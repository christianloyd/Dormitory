<?php
require_once __DIR__ . '/../../includes/auth_check.php';

header('Content-Type: application/json');

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

if ($room_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid room id provided.',
        'descriptions' => []
    ]);
    exit;
}

$stmt = $conn->prepare("SELECT description FROM room_additional_descriptions WHERE room_id = ? ORDER BY description ASC");

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare room charges query.',
        'descriptions' => []
    ]);
    exit;
}

$stmt->bind_param('i', $room_id);
$stmt->execute();
$result = $stmt->get_result();
$descriptions = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $descriptions[] = $row['description'];
    }
}

$stmt->close();

echo json_encode([
    'success' => true,
    'descriptions' => $descriptions
]);
exit;
