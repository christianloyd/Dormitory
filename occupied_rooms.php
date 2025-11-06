<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

include("db.php");

// Fetch only Active rooms and Active tenants
$roomQuery = "
    SELECT r.*, 
        COUNT(t.tenant_id) AS total_tenants
    FROM rooms r
    LEFT JOIN tenants t 
        ON r.room_id = t.room_id 
        AND t.status = 'Active'
    WHERE r.record_status = 'Active'
    GROUP BY r.room_id
    ORDER BY r.room_number ASC
";
$roomResult = mysqli_query($conn, $roomQuery);

// Count fully occupied rooms (only Active rooms and Active tenants)
$result = $conn->query("
    SELECT COUNT(*) AS totalOccupied
    FROM (
        SELECT r.room_id, r.capacity, COUNT(t.tenant_id) AS tenants_count
        FROM rooms r
        LEFT JOIN tenants t 
            ON r.room_id = t.room_id 
            AND t.status = 'Active'
        WHERE r.record_status = 'Active'
        GROUP BY r.room_id
        HAVING tenants_count >= r.capacity
    ) AS occupied_rooms
");
$totalOccupied = $result->fetch_assoc()['totalOccupied'];
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
    <?php include 'sidebar.php'; ?>

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
                <?php
                $hasOccupied = false;
                while ($row = mysqli_fetch_assoc($roomResult)) {
                    $available = $row['capacity'] - $row['total_tenants'];
                    if ($available <= 0) {
                        $hasOccupied = true;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['room_number']) ?></td>
                            <td><?= htmlspecialchars($row['room_type']) ?></td>
                            <td><?= $row['capacity'] ?></td>
                            <td><?= $available ?></td>
                            <td><?= $row['upper_deck_count'] ?></td>
                            <td><?= $row['lower_deck_count'] ?></td>
                            <td><?= number_format($row['price'],2) ?></td>
                            <td>Occupied</td>
                        </tr>
                        <?php
                    }
                }
                if (!$hasOccupied) {
                    echo '<tr><td colspan="8">No occupied rooms found.</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
