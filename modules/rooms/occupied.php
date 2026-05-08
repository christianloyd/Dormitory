<?php
/**
 * Rooms Module - Occupied Rooms
 * Path: /modules/rooms/occupied.php
 */
require_once '../../includes/auth_check.php';
require_once '../../helpers/TenantAssignments.php';

$roomInventory = TenantAssignments::getRoomInventory($conn);

$occupiedRooms = array_values(array_filter($roomInventory, function (array $room): bool {
    return ($room['available_slots'] ?? 0) <= 0;
}));

$totalOccupied = count($occupiedRooms);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Occupied Rooms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            background-color: #f6f7f6;
        }
        .main-content {
            margin-left: 225px;
            padding: 30px;
            background-color: #f6f7f6;
            min-height: 100vh;
            width: calc(100% - 225px);
            box-sizing: border-box;
        }
        h2 {
            text-align: center;
            color: #5A7D7C;
            margin-bottom: 20px;
        }
        .total-count {
            text-align: center;
            font-weight: bold;
            color: #5A7D7C;
            margin-bottom: 20px;
        }
        .btn-back {
            margin-bottom: 15px;
            background: #5A7D7C;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-back:hover {
            background: #496766;
        }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }
        th {
            background-color: #5A7D7C;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f6f9f8;
        }
    </style>
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>

    <div class="main-content">
        <h2>Occupied Rooms</h2>
        <div class="total-count">
            Total Occupied rooms: <?= $totalOccupied; ?>
        </div>
        <button class="btn-back" onclick="window.history.back()">Go Back</button>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Room Number</th>
                        <th>Room Type</th>
                        <th>Capacity</th>
                        <th>Available</th>
                        <th>Upper Decks</th>
                        <th>Lower Decks</th>
                        <th>Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($occupiedRooms)): ?>
                    <?php foreach ($occupiedRooms as $room):
                        $roomNumber = htmlspecialchars($room['room_number'], ENT_QUOTES, 'UTF-8');
                        $roomType = htmlspecialchars($room['room_type'], ENT_QUOTES, 'UTF-8');

                        $capacity = (int)$room['capacity'];
                        $upperDecks = (int)$room['upper_deck_count'];
                        $lowerDecks = (int)$room['lower_deck_count'];

                        $availableSlots = (int)$room['available_slots'];
                        $upperAvailable = (int)$room['upper_available'];
                        $lowerAvailable = (int)$room['lower_available'];

                        if ($room['room_type'] === 'Whole Room') {
                            $capacity = max(1, $capacity);
                        }

                        $upperCapacity = $upperDecks;
                        $lowerCapacity = $lowerDecks;

                        $upperSummary = $upperDecks > 0
                            ? sprintf('%d occupied / %d', $upperCapacity - $upperAvailable, $upperCapacity)
                            : '—';
                        $lowerSummary = $lowerDecks > 0
                            ? sprintf('%d occupied / %d', $lowerCapacity - $lowerAvailable, $lowerCapacity)
                            : '—';
                    ?>
                        <tr>
                            <td><?= $roomNumber ?></td>
                            <td><?= $roomType ?></td>
                            <td><?= $capacity ?></td>
                            <td><?= $availableSlots ?></td>
                            <td><?= $upperSummary ?></td>
                            <td><?= $lowerSummary ?></td>
                            <td><?= number_format((float)$room['price'], 2) ?></td>
                            <td>Occupied</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8">No occupied rooms found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
