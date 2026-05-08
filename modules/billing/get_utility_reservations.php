<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../helpers/BillingReservations.php';

header('Content-Type: application/json');

$tenantId = isset($_GET['tenant_id']) ? intval($_GET['tenant_id']) : 0;
$roomId = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$dueDate = isset($_GET['due_date']) ? trim($_GET['due_date']) : '';

if ($tenantId <= 0 || $roomId <= 0 || $dueDate === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Missing or invalid parameters.',
        'reservations' => [],
    ]);
    exit;
}

try {
    $reservations = getPendingUtilityReservations($conn, $tenantId, $roomId, $dueDate);

    $totals = [];
    foreach ($reservations as $reservation) {
        $label = (string)($reservation['label'] ?? '');
        $amount = (float)($reservation['amount'] ?? 0);
        if ($label === '' || $amount <= 0) {
            continue;
        }
        if (!isset($totals[$label])) {
            $totals[$label] = 0.0;
        }
        $totals[$label] += $amount;
    }

    echo json_encode([
        'success' => true,
        'reservations' => $reservations,
        'totals' => $totals,
    ]);
    exit;
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch reservations: ' . $e->getMessage(),
        'reservations' => [],
    ]);
    exit;
}
