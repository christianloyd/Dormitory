-- Disable foreign key checks to allow truncating tables with constraints
SET FOREIGN_KEY_CHECKS = 0;

-- Truncate all transactional and tenant-related tables
TRUNCATE TABLE `billing`;
TRUNCATE TABLE `billing_additional_items`;
TRUNCATE TABLE `billing_sms_status`;
TRUNCATE TABLE `billing_utility_items`;
TRUNCATE TABLE `billing_utility_reservations`;

TRUNCATE TABLE `invoices`;
TRUNCATE TABLE `payments`;

TRUNCATE TABLE `notifications`;
TRUNCATE TABLE `sms_logs`;

TRUNCATE TABLE `tenant_rooms`;
TRUNCATE TABLE `tenants`;

TRUNCATE TABLE `room_additional_descriptions`;

-- Note: We are keeping the actual `rooms`, `settings`, and `admin_account` intact to avoid breaking the core system structure.
-- If you strictly want to delete rooms too, uncomment the line below:
-- TRUNCATE TABLE `rooms`;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
