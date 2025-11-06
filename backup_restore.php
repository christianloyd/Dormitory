<?php
session_start();
include "db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'export') {
        // Export DB
        $db_name = "dorm_db"; // your database name
        $backup_file = "backup_" . date("Y-m-d_H-i-s") . ".sql";

        // Command to export database
        $command = "mysqldump --user=root --password= --host=localhost $db_name > $backup_file";
        system($command, $output);

        // Force download
        if(file_exists($backup_file)){
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($backup_file).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($backup_file));
            readfile($backup_file);
            unlink($backup_file); // delete temporary backup file
            exit;
        } else {
            echo "<script>alert('Failed to create backup.'); window.history.back();</script>";
        }

    } elseif ($action === 'import') {
        // Import DB
        if(isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] === 0){
            $sql_file = $_FILES['sql_file']['tmp_name'];
            $db_name = "dorm_db";
            $command = "mysql --user=root --password= --host=localhost $db_name < $sql_file";
            system($command, $output);
            echo "<script>alert('Database restored successfully!'); window.location.href='user.php?tab=backup';</script>";
            exit;
        } else {
            echo "<script>alert('Please select a valid SQL file.'); window.history.back();</script>";
        }
    }
}

// Fetch existing databases
$databases = [];
$result = $conn->query("SHOW DATABASES");
while($row = $result->fetch_array()) {
    $databases[] = $row[0];
}
?>

<!-- Backup & Restore HTML -->
<h2>Backup & Restore</h2>

<form method="POST">
    <input type="hidden" name="action" value="export">
    <button type="submit" class="btn btn-primary mb-3">Export Database</button>
</form>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="import">
    <div class="mb-2">
        <label for="sql_file" class="form-label">Import Database (.sql)</label>
        <input type="file" name="sql_file" id="sql_file" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-success">Import Database</button>
</form>

<!-- Display Existing Databases -->
<h5 class="mt-4">Existing Databases:</h5>
<ul>
<?php
if(!empty($databases)) {
    foreach($databases as $db) {
        echo "<li>$db</li>";
    }
} else {
    echo "<li>No databases found.</li>";
}
?>
</ul>
