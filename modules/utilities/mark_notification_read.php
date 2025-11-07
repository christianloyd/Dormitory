<?php
include '../../db.php';
if(isset($_POST['id'])){
    $id = intval($_POST['id']);
    $conn->query("UPDATE notifications SET is_read = 1 WHERE id = $id");
}
?>
