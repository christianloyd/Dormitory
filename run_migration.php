<?php
/**
 * Run SQL Migration for SMS Logs Enhancement
 * Execute this file once in your browser: http://localhost/dorm_system/run_migration.php
 */

include 'db.php';

echo "<h2>Running SMS Logs Migration...</h2>";
echo "<pre>";

$migrations = [
    // Add new columns
    "ALTER TABLE `sms_logs` ADD COLUMN `status` ENUM('sent', 'failed', 'pending', 'disabled') DEFAULT 'pending' AFTER `message`",
    "ALTER TABLE `sms_logs` ADD COLUMN `error_message` TEXT NULL AFTER `status`",
    "ALTER TABLE `sms_logs` ADD COLUMN `message_id` VARCHAR(100) NULL AFTER `error_message`",
    "ALTER TABLE `sms_logs` ADD COLUMN `http_code` INT NULL AFTER `message_id`",

    // Add indexes
    "ALTER TABLE `sms_logs` ADD INDEX `idx_status` (`status`)",
    "ALTER TABLE `sms_logs` ADD INDEX `idx_date_sent` (`date_sent`)",
    "ALTER TABLE `sms_logs` ADD INDEX `idx_tenant_id` (`tenant_id`)",

    // Update recipient_type
    "ALTER TABLE `sms_logs` MODIFY COLUMN `recipient_type` ENUM('Tenant', 'Parent', 'Guardian') DEFAULT 'Tenant'",

    // Create settings table
    "CREATE TABLE IF NOT EXISTS `settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(100) NOT NULL UNIQUE,
        `setting_value` TEXT,
        `description` VARCHAR(255),
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    // Insert default SMS setting
    "INSERT INTO `settings` (`setting_key`, `setting_value`, `description`)
     VALUES ('sms_enabled', '1', 'Enable or disable SMS notifications (1=enabled, 0=disabled)')
     ON DUPLICATE KEY UPDATE setting_value = setting_value"
];

$success_count = 0;
$error_count = 0;

foreach ($migrations as $index => $sql) {
    echo "\n[" . ($index + 1) . "] Running: " . substr($sql, 0, 80) . "...\n";

    if ($conn->query($sql)) {
        echo "✓ Success\n";
        $success_count++;
    } else {
        // Check if error is because column/index already exists
        if (strpos($conn->error, 'Duplicate column name') !== false ||
            strpos($conn->error, 'Duplicate key name') !== false ||
            strpos($conn->error, 'Duplicate entry') !== false) {
            echo "⚠ Already exists (skipped)\n";
            $success_count++;
        } else {
            echo "✗ Error: " . $conn->error . "\n";
            $error_count++;
        }
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Migration Complete!\n";
echo "Success: $success_count\n";
echo "Errors: $error_count\n";
echo "\n</pre>";

if ($error_count == 0) {
    echo "<p style='color: green; font-weight: bold;'>All migrations completed successfully!</p>";
    echo "<p>You can now delete this file (run_migration.php) for security.</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>Some migrations failed. Please check the errors above.</p>";
}

echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
?>
