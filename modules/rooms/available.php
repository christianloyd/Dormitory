<?php
/**
 * Rooms Module - Available Rooms
 * Path: /modules/rooms/available.php
 */
require_once '../../includes/auth_check.php';

// Fetch all available rooms
$availableQuery = "
    SELECT r.*, 
           (r.capacity - COUNT(t.room_id)) AS available
    FROM rooms r
    LEFT JOIN tenants t ON r.room_id = t.room_id
    GROUP BY r.room_id
    HAVING available > 0
    ORDER BY r.room_number ASC
";
$availableResult = mysqli_query($conn, $availableQuery);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Available Rooms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    display: flex;
    background-color: #f6f7f6;
}
.main-content {
    margin-left: 225px; /* sidebar width */
    padding: 30px;
    background-color: #f6f7f6;
    min-height: 100vh;
    width: calc(100% - 225px);
    box-sizing: border-box;
}
.search-container {
    margin-bottom: 15px;
}
.search-input {
    padding: 8px;
    width: 250px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

/* Table container */
.table-responsive {
    width: 100%;
    overflow-x: auto; /* horizontal scroll if needed */
}

/* Table styles */
table {
    width: 100%;
    max-width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    table-layout: fixed; /* ensures even columns */
}

th, td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;      /* horizontal center */
    vertical-align: middle;  /* vertical center */
    word-wrap: break-word;   /* prevent overflow text */
}

th {
    background-color: #5A7D7C;
    color: white;
}

tr:nth-child(even) {
    background-color: #f6f9f8;
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
</style>
<body>
    <?php include '../../sidebar.php'; ?>


<div class="main-content">
    <button class="btn-back" onclick="window.history.back()">Go Back</button>

    <!-- Search bar -->
    <div class="search-container">
        <input type="text" id="roomSearch" class="search-input" placeholder="Search by Room Number...">
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table id="roomsTable">
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
                $hasAvailable = false;
                while($row = mysqli_fetch_assoc($availableResult)) {
                    $hasAvailable = true;
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['room_number']) ?></td>
                    <td><?= htmlspecialchars($row['room_type']) ?></td>
                    <td><?= $row['capacity'] ?></td>
                    <td><?= $row['available'] ?></td>
                    <td><?= $row['upper_deck_count'] ?></td>
                    <td><?= $row['lower_deck_count'] ?></td>
                    <td><?= number_format($row['price'],2) ?></td>
                    <td>Available</td>
                </tr>
                <?php
                }
                if(!$hasAvailable) {
                    echo '<tr><td colspan="8">No available rooms found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Filter table by room number
    const searchInput = document.getElementById('roomSearch');
    searchInput.addEventListener('keyup', function() {
        const filter = this.value.toUpperCase();
        const rows = document.querySelectorAll('#roomsTable tbody tr');
        rows.forEach(row => {
            const roomNumber = row.cells[0].textContent.toUpperCase();
            row.style.display = roomNumber.indexOf(filter) > -1 ? '' : 'none';
        });
    });
</script>

</body>
</html>
