<?php
/**
 * Database Connection & Configuration
 * Now loads credentials from .env file for better security
 */

// Load environment configuration
require_once __DIR__ . '/config.php';

// Database connection using environment variables
$host = env('DB_HOST', 'localhost');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$dbname = env('DB_NAME', 'dorm_db');

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SMS Configuration (IPROG API) - Now from environment variables
if (!defined('SMS_ENABLED')) {
    define('SMS_ENABLED', envBool(env('SMS_ENABLED', 'true')));
}
if (!defined('SMS_API_URL')) {
    define('SMS_API_URL', env('SMS_API_URL', 'https://sms.iprogtech.com/api/v1/sms_messages'));
}
if (!defined('SMS_API_TOKEN')) {
    define('SMS_API_TOKEN', env('SMS_API_TOKEN', ''));
}
if (!defined('SMS_SENDER')) {
    define('SMS_SENDER', env('SMS_SENDER', 'BEN & SOF Dormitory'));
}

// Include SMS Helper
require_once __DIR__ . '/sms_helper.php';
?>
