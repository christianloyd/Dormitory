-- SQL Migration: Enhance sms_logs table for IPROG API integration
-- Run this in phpMyAdmin or MySQL client

-- Add new columns to track SMS delivery status and API responses
ALTER TABLE `sms_logs`
ADD COLUMN `status` ENUM('sent', 'failed', 'pending', 'disabled') DEFAULT 'pending' AFTER `message`,
ADD COLUMN `error_message` TEXT NULL AFTER `status`,
ADD COLUMN `message_id` VARCHAR(100) NULL AFTER `error_message`,
ADD COLUMN `http_code` INT NULL AFTER `message_id`;

-- Add index for better query performance
ALTER TABLE `sms_logs`
ADD INDEX `idx_status` (`status`),
ADD INDEX `idx_date_sent` (`date_sent`),
ADD INDEX `idx_tenant_id` (`tenant_id`);

-- Update recipient_type enum to include 'Guardian' (if needed)
ALTER TABLE `sms_logs`
MODIFY COLUMN `recipient_type` ENUM('Tenant', 'Parent', 'Guardian') DEFAULT 'Tenant';

-- Create settings table for SMS configuration (optional, for admin panel)
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `description` VARCHAR(255),
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default SMS settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`)
VALUES
('sms_enabled', '1', 'Enable or disable SMS notifications (1=enabled, 0=disabled)')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
