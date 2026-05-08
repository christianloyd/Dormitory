<?php
$conn = new mysqli('localhost', 'root', '', 'dorm_db');
$res = $conn->query('DESCRIBE billing');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
