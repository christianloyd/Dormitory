<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "dorm_db"; // change if different

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SMS Configuration (IPROG API)
if (!defined('SMS_ENABLED')) {
    define('SMS_ENABLED', true); // Set to false to disable SMS sending
}
if (!defined('SMS_API_URL')) {
    define('SMS_API_URL', 'https://sms.iprogtech.com/api/v1/sms_messages');
}
if (!defined('SMS_API_TOKEN')) {
    define('SMS_API_TOKEN', 'b3372e928050d30de930b74a8bf86321b21ccc74');
}
if (!defined('SMS_SENDER')) {
    define('SMS_SENDER', 'BEN & SOF Dormitory');
}

// Include SMS Helper
require_once __DIR__ . '/sms_helper.php';
?>
