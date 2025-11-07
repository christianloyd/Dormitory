<?php
include '../../db.php';

// Kuhaa ang admin account
$result = $conn->query("SELECT id, password FROM admin_account");

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $plainPassword = $row['password'];

    // Hash the password
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

    // Update sa DB
    $stmt = $conn->prepare("UPDATE admin_account SET password=? WHERE id=?");
    $stmt->bind_param("si", $hashedPassword, $id);
    $stmt->execute();

    echo "Password for admin ID {$id} hashed successfully.<br>";
}

echo "All passwords hashed!";
?>
