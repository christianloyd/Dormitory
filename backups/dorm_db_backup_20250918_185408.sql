DROP TABLE IF EXISTS admin_account;
CREATE TABLE `admin_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO admin_account VALUES('1','gladys','gladys');
INSERT INTO admin_account VALUES('2','admin','$2y$10$ZACqCD/aXgmvKyxfaHcQruHbRHUi8.jdRhK0009JzUTDmpXVagWCS');


DROP TABLE IF EXISTS billing;
CREATE TABLE `billing` (
  `bill_id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `bill_date` date DEFAULT NULL,
  `amount_due` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `base_rent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `utility_fee` varchar(255) DEFAULT '',
  `utility_amount` decimal(10,2) DEFAULT 0.00,
  `add_charges` varchar(255) DEFAULT '',
  `add_amount` decimal(10,2) DEFAULT 0.00,
  `previous_balance` decimal(10,2) DEFAULT 0.00,
  `previous_credit` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) DEFAULT 0.00,
  `credit_balance` decimal(10,2) DEFAULT 0.00,
  `status` enum('Unpaid','Partial','Settled','Paid') DEFAULT 'Unpaid',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `other_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`bill_id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `billing_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`tenant_id`) ON DELETE CASCADE,
  CONSTRAINT `billing_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS payments;
CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` int(11) NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`payment_id`),
  KEY `bill_id` (`bill_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `billing` (`bill_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS rooms;
CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL AUTO_INCREMENT,
  `room_number` varchar(50) NOT NULL,
  `room_type` enum('Bed Spacer','Whole Room') NOT NULL,
  `deck_type` enum('Upper','Lower') DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `capacity` int(11) NOT NULL,
  `available` int(11) NOT NULL DEFAULT 0,
  `status` enum('Available','Full') NOT NULL DEFAULT 'Available',
  `upper_deck_count` int(11) DEFAULT 0,
  `lower_deck_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO rooms VALUES('8','01','Whole Room',NULL,'500.00','1','0','Available','0','0','2025-09-18 08:38:26');
INSERT INTO rooms VALUES('10','02','Bed Spacer',NULL,'500.00','2','0','Available','1','1','2025-09-18 08:47:49');


DROP TABLE IF EXISTS settings;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_name` varchar(100) DEFAULT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_name` (`setting_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO settings VALUES('1','profile_image','uploads/profile_1758213983.jpg');
INSERT INTO settings VALUES('2','header_image','uploads/header_admin.jpg');


DROP TABLE IF EXISTS sms_logs;
CREATE TABLE `sms_logs` (
  `sms_id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) DEFAULT NULL,
  `recipient_type` enum('Tenant','Parent') DEFAULT NULL,
  `contact_number` varchar(15) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `date_sent` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`sms_id`),
  KEY `tenant_id` (`tenant_id`),
  CONSTRAINT `sms_logs_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS tenants;
CREATE TABLE `tenants` (
  `tenant_id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_name` varchar(100) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `proof_pic` varchar(255) DEFAULT NULL,
  `room_id` int(11) NOT NULL,
  `deck_type` enum('Lower Deck','Upper Deck') DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `guardian_contact` varchar(20) NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `date_started` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`tenant_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `tenants_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO tenants VALUES('26','Gladys Tic-Ing','uploads/about.jpg','','8',NULL,'Kalasan Pagadian City','09104884941','09090999999','Active','2025-09-18','2025-09-18 08:48:38');
INSERT INTO tenants VALUES('27','Julien Grafe','uploads/518937024_1014987657168953_4812181174251610177_n.png','','10','Lower Deck','Balangasan','09666666666','09877777777','Active','2025-09-19','2025-09-18 08:49:16');
INSERT INTO tenants VALUES('28','Cherime Pielago Princess','uploads/517091118_747251621356075_4078182769288626963_n.jpg','','10','Upper Deck','Balangasan','09677575755','09464644666','Active','2025-09-19','2025-09-18 08:49:56');


