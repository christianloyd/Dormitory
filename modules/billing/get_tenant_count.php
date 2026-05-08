<?php
require_once __DIR__ . '/../../includes/auth_check.php';

header('Content-Type: application/json');

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

if ($room_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid room id provided.',
        'count'   => 0
    ]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS count
     FROM tenant_rooms tr
     INNER JOIN tenants t ON t.tenant_id = tr.tenant_id
     WHERE tr.room_id = ?
       AND tr.released_at IS NULL
       AND t.status = 'Active'"
);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare tenant count query.',
        'count'   => 0
    ]);
    exit;
}

$stmt->bind_param('i', $room_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result ? $result->fetch_assoc() : ['count' => 0];
$stmt->close();

echo json_encode([
    'success' => true,
    'count'   => (int)($data['count'] ?? 0)
]);
exit;
