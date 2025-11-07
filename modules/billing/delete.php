<?php
require_once '../../includes/auth_check.php';

if (isset($_GET['bill_id'])) {
    $bill_id = intval($_GET['bill_id']);

    // Delete the bill record
    $sql = "DELETE FROM billing WHERE bill_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bill_id);

    if ($stmt->execute()) {
        echo "success"; // para ma-detect sa fetch() nga successful
    } else {
        echo "error";
    }

    $stmt->close();
} else {
    echo "invalid";
}

$conn->close();
?>
