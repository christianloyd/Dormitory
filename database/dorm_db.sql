-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 11, 2025 at 04:41 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dorm_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_account`
--

CREATE TABLE `admin_account` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_account`
--

INSERT INTO `admin_account` (`id`, `username`, `password`) VALUES
(1, 'ADMIN', 'ADMIN'),
(2, 'BENandSOF', '$2y$10$AFUrn75E.hzl8WQKTwKVb.LKQlFUmGMRkbI8l8tDgKAzi7TmdEmii');

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `bill_id` int(11) NOT NULL,
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
  `interest` decimal(10,2) DEFAULT 0.00,
  `previous_balance` decimal(10,2) DEFAULT 0.00,
  `previous_credit` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) DEFAULT 0.00,
  `credit_balance` decimal(10,2) DEFAULT 0.00,
  `status` enum('Pending','Unpaid','Partial','Settled','Paid') DEFAULT 'Pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT 0.00,
  `payment_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_with_interest` decimal(10,2) DEFAULT 0.00,
  `other_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing`
--

INSERT INTO `billing` (`bill_id`, `tenant_id`, `room_id`, `bill_date`, `amount_due`, `description`, `due_date`, `base_rent`, `utility_fee`, `utility_amount`, `add_charges`, `add_amount`, `interest`, `previous_balance`, `previous_credit`, `balance`, `credit_balance`, `status`, `payment_method`, `payment_amount`, `payment_date`, `total_amount`, `total_with_interest`, `other_amount`, `created_at`) VALUES
(466, 172, 51, NULL, 0.00, NULL, '2026-01-10', 8000.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 1750.00, 'Settled', 'Cash', 10000.00, '2025-12-10', 8250.00, 0.00, 0.00, '2025-12-10 05:16:53'),
(467, 172, 51, NULL, 0.00, NULL, '2026-02-10', 8000.00, '', 0.00, '', 0.00, 0.00, 0.00, 1750.00, 0.00, 3750.00, 'Settled', 'Cash', 10000.00, '2025-12-10', 6250.00, 0.00, 0.00, '2025-12-10 05:28:37'),
(468, 172, 51, NULL, 0.00, NULL, '2026-03-10', 8000.00, '', 0.00, '', 0.00, 0.00, 0.00, 3750.00, 0.00, 0.00, 'Pending', '', 0.00, NULL, 0.00, 0.00, 0.00, '2025-12-10 05:37:14'),
(492, 174, 44, NULL, 0.00, NULL, '2026-01-14', 1500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '', 0.00, NULL, 0.00, 0.00, 0.00, '2025-12-10 17:54:20'),
(493, 175, 44, NULL, 0.00, NULL, '2026-01-14', 1500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '', 0.00, NULL, 0.00, 0.00, 0.00, '2025-12-10 17:54:20'),
(494, 178, 27, NULL, 0.00, NULL, '2026-01-12', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 500.00, 'Settled', 'Cash', 1500.00, '2025-12-10', 1000.00, 0.00, 0.00, '2025-12-10 18:03:23'),
(495, 179, 43, NULL, 0.00, NULL, '2026-01-12', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 4000.00, 'Settled', 'Cash', 5000.00, '2025-12-10', 1000.00, 0.00, 0.00, '2025-12-10 18:05:17'),
(496, 179, 43, NULL, 0.00, NULL, '2026-02-12', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 4000.00, NULL, NULL, NULL, '0', 0.00, NULL, 0.00, 0.00, 0.00, '2025-12-10 18:06:42'),
(497, 178, 27, NULL, 0.00, NULL, '2026-02-12', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 500.00, 0.00, 100.00, 'Settled', 'Cash', 600.00, '2025-12-10', 500.00, 0.00, 0.00, '2025-12-10 18:12:46'),
(498, 178, 27, NULL, 0.00, NULL, '2026-03-12', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 100.00, 0.00, 0.00, 'Pending', '', 0.00, NULL, 0.00, 0.00, 0.00, '2025-12-10 18:25:24'),
(502, 182, 51, NULL, 0.00, NULL, '2026-01-13', 8000.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 833.34, 'Settled', 'GCash', 9000.00, '2025-12-10', 8166.66, 0.00, 0.00, '2025-12-10 18:52:13'),
(503, 180, 51, NULL, 0.00, NULL, '2026-01-13', 8000.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 1833.33, 'Settled', 'Cash', 10000.00, '2025-12-10', 8166.67, 0.00, 0.00, '2025-12-10 18:52:13'),
(504, 181, 51, NULL, 0.00, NULL, '2026-01-13', 8000.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 2833.33, 'Settled', 'Cash', 11000.00, '2025-12-10', 8166.67, 0.00, 0.00, '2025-12-10 18:52:13'),
(508, 180, 51, NULL, 0.00, NULL, '2026-02-13', 8000.00, '', 0.00, '', 0.00, 0.00, 0.00, 1833.33, 6353.34, 0.00, 'Pending', '', 0.00, NULL, 6353.34, 0.00, 0.00, '2025-12-10 20:04:05'),
(509, 181, 51, NULL, 0.00, NULL, '2026-02-13', 8000.00, '', 0.00, '', 0.00, 0.00, 0.00, 2833.33, 5353.34, 0.00, 'Pending', '', 0.00, NULL, 5353.34, 0.00, 0.00, '2025-12-10 20:04:05'),
(510, 182, 51, NULL, 0.00, NULL, '2026-02-13', 8000.00, '', 0.00, '', 0.00, 0.00, 0.00, 833.34, 7353.32, 0.00, 'Pending', '', 0.00, NULL, 7353.32, 0.00, 0.00, '2025-12-10 20:04:05'),
(511, 183, 27, NULL, 0.00, NULL, '2026-01-14', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 550.00, 0.00, 'Pending', '', 0.00, NULL, 550.00, 0.00, 0.00, '2025-12-11 09:18:06'),
(512, 178, 27, NULL, 0.00, NULL, '2026-01-14', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 500.00, 50.00, 0.00, 'Pending', '', 0.00, NULL, 50.00, 0.00, 0.00, '2025-12-11 09:18:06'),
(514, 183, 51, NULL, 0.00, NULL, '2026-01-14', 8000.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 8300.00, 0.00, 'Pending', '', 0.00, NULL, 8300.00, 0.00, 0.00, '2025-12-11 13:36:14'),
(516, 181, 51, NULL, 0.00, NULL, '2026-01-14', 8000.00, '', 0.00, '', 0.00, 0.00, 0.00, 2833.33, 5466.67, 0.00, 'Pending', '', 0.00, NULL, 5466.67, 0.00, 0.00, '2025-12-11 13:36:14'),
(517, 182, 51, NULL, 0.00, NULL, '2026-01-14', 8000.00, '', 0.00, '', 0.00, 0.00, 0.00, 833.34, 7466.66, 0.00, 'Pending', '', 0.00, NULL, 7466.66, 0.00, 0.00, '2025-12-11 13:36:14'),
(518, 184, 51, NULL, 0.00, NULL, '2026-01-13', 8000.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 8033.33, 0.00, 'Pending', '', 0.00, NULL, 8033.33, 0.00, 0.00, '2025-12-11 14:14:11'),
(519, 185, 51, NULL, 0.00, NULL, '2026-01-14', 8000.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 8000.00, 0.00, 'Pending', '', 0.00, NULL, 8000.00, 0.00, 0.00, '2025-12-11 14:14:43'),
(520, 185, 53, NULL, 0.00, NULL, '2026-01-14', 5000.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 5500.00, 0.00, 'Pending', '', 0.00, NULL, 5500.00, 0.00, 0.00, '2025-12-11 15:21:28');

-- --------------------------------------------------------

--
-- Table structure for table `billing_additional_items`
--

CREATE TABLE `billing_additional_items` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `billing_sms_status`
--

CREATE TABLE `billing_sms_status` (
  `bill_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `last_status` varchar(20) NOT NULL,
  `last_message` varchar(255) NOT NULL,
  `success_count` int(11) NOT NULL DEFAULT 0,
  `failure_count` int(11) NOT NULL DEFAULT 0,
  `last_attempt_at` datetime NOT NULL,
  `last_error` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing_sms_status`
--

INSERT INTO `billing_sms_status` (`bill_id`, `tenant_id`, `last_status`, `last_message`, `success_count`, `failure_count`, `last_attempt_at`, `last_error`, `updated_at`) VALUES
(492, 174, 'skipped', 'Error sending billing notice: Unknown column \'t.guardian_name\' in \'field list\'', 0, 0, '2025-12-11 01:54:20', NULL, '2025-12-11 01:54:20'),
(494, 178, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 02:03:25', NULL, '2025-12-11 02:03:25'),
(495, 179, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 02:05:19', NULL, '2025-12-11 02:05:19'),
(499, 180, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 02:41:26', NULL, '2025-12-11 02:41:26'),
(502, 182, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 02:52:18', NULL, '2025-12-11 02:52:18'),
(503, 180, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 02:52:16', NULL, '2025-12-11 02:52:16'),
(504, 181, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 02:52:18', NULL, '2025-12-11 02:52:18'),
(505, 181, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 03:35:09', NULL, '2025-12-11 03:35:09'),
(506, 180, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 03:35:06', NULL, '2025-12-11 03:35:06'),
(507, 182, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 03:35:09', NULL, '2025-12-11 03:35:09'),
(508, 180, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 04:04:10', NULL, '2025-12-11 04:04:10'),
(509, 181, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 04:04:08', NULL, '2025-12-11 04:04:08'),
(510, 182, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 04:04:10', NULL, '2025-12-11 04:04:10'),
(511, 183, 'failed', 'Billing notice attempt completed but no messages were sent.', 0, 2, '2025-12-11 17:18:51', 'Tenant (09497428155): Connection error: Could not resolve host: sms.iprogtech.com; Guardian (09497428155): Connection error: Could not resolve host: sms.iprogtech.com', '2025-12-11 17:18:51'),
(512, 178, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 17:18:51', NULL, '2025-12-11 17:18:51'),
(513, 183, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 17:19:42', NULL, '2025-12-11 17:19:42'),
(514, 183, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 21:36:39', NULL, '2025-12-11 21:36:39'),
(515, 180, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 21:36:26', NULL, '2025-12-11 21:36:26'),
(516, 181, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 21:36:32', NULL, '2025-12-11 21:36:32'),
(517, 182, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 21:36:39', NULL, '2025-12-11 21:36:39'),
(518, 184, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 22:14:16', NULL, '2025-12-11 22:14:16'),
(519, 185, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 22:14:47', NULL, '2025-12-11 22:14:47'),
(520, 185, 'sent', 'Billing notice sent to 2 recipient(s).', 2, 0, '2025-12-11 23:21:31', NULL, '2025-12-11 23:21:31');

-- --------------------------------------------------------

--
-- Table structure for table `billing_utility_items`
--

CREATE TABLE `billing_utility_items` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing_utility_items`
--

INSERT INTO `billing_utility_items` (`id`, `bill_id`, `label`, `amount`, `created_at`) VALUES
(14, 466, 'Electricity', 250.00, '2025-12-10 05:16:53'),
(42, 492, 'Electricity', 250.00, '2025-12-10 17:54:20'),
(43, 493, 'Electricity', 250.00, '2025-12-10 17:54:20'),
(44, 494, 'Electricity', 500.00, '2025-12-10 18:03:23'),
(45, 495, 'Electricity', 500.00, '2025-12-10 18:05:17'),
(46, 497, 'Electricity', 500.00, '2025-12-10 18:16:22'),
(47, 496, 'Electricity', 500.00, '2025-12-10 18:36:24'),
(53, 504, 'Electricity', 166.67, '2025-12-10 18:52:13'),
(57, 508, 'Electricity', 186.67, '2025-12-10 20:04:05'),
(58, 509, 'Electricity', 186.67, '2025-12-10 20:04:05'),
(59, 510, 'Electricity', 186.66, '2025-12-10 20:04:05'),
(60, 511, 'Electricity', 50.00, '2025-12-11 09:18:06'),
(61, 512, 'Electricity', 50.00, '2025-12-11 09:18:06'),
(63, 514, 'Electricity', 300.00, '2025-12-11 13:36:14'),
(65, 516, 'Electricity', 300.00, '2025-12-11 13:36:14'),
(66, 517, 'Electricity', 300.00, '2025-12-11 13:36:14'),
(67, 518, 'Electricity', 33.33, '2025-12-11 14:14:11'),
(68, 503, 'Electricity', 33.34, '2025-12-11 14:14:11'),
(69, 502, 'Electricity', 33.33, '2025-12-11 14:14:11'),
(70, 519, 'Electricity', 0.00, '2025-12-11 14:14:43'),
(71, 520, 'Electricity', 500.00, '2025-12-11 15:21:28');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `invoice_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `base_rent` decimal(10,2) NOT NULL,
  `utility_fee` decimal(10,2) DEFAULT 0.00,
  `additional_charges` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `type` enum('reminder','confirmation') NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `tenant_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(15, 178, '', 'Billing notice sent for Room 01 due on 2026-01-12.', 1, '2025-12-10 18:03:25'),
(16, 179, '', 'Billing notice sent for Room 04 due on 2026-01-12.', 1, '2025-12-10 18:05:19'),
(17, 178, 'confirmation', 'Payment confirmation sent for Room 01. Paid: ₱600.00, Total Bill: ₱500.00', 1, '2025-12-10 18:25:25'),
(18, 180, '', 'Billing notice sent for Room 08 due on 2026-01-13.', 1, '2025-12-10 18:41:26'),
(19, 182, '', 'Billing notice sent for Room 08 due on 2026-01-13.', 1, '2025-12-10 18:52:15'),
(20, 180, '', 'Billing notice sent for Room 08 due on 2026-01-13.', 1, '2025-12-10 18:52:16'),
(21, 181, '', 'Billing notice sent for Room 08 due on 2026-01-13.', 1, '2025-12-10 18:52:18'),
(22, 180, 'confirmation', 'Payment confirmation sent for Room 08. Paid: ₱10,000.00, Total Bill: ₱8,166.67', 1, '2025-12-10 19:33:31'),
(23, 182, 'confirmation', 'Payment confirmation sent for Room 08. Paid: ₱9,000.00, Total Bill: ₱8,166.66', 1, '2025-12-10 19:34:01'),
(24, 181, 'confirmation', 'Payment confirmation sent for Room 08. Paid: ₱11,000.00, Total Bill: ₱8,166.67', 1, '2025-12-10 19:34:46'),
(25, 181, '', 'Billing notice sent for Room 08 due on 2026-02-13.', 1, '2025-12-10 19:35:04'),
(26, 180, '', 'Billing notice sent for Room 08 due on 2026-02-13.', 1, '2025-12-10 19:35:06'),
(27, 182, '', 'Billing notice sent for Room 08 due on 2026-02-13.', 1, '2025-12-10 19:35:09'),
(28, 180, '', 'Billing notice sent for Room 08 due on 2026-02-13.', 1, '2025-12-10 20:04:07'),
(29, 181, '', 'Billing notice sent for Room 08 due on 2026-02-13.', 1, '2025-12-10 20:04:08'),
(30, 182, '', 'Billing notice sent for Room 08 due on 2026-02-13.', 1, '2025-12-10 20:04:10'),
(31, 183, '', 'Billing notice sent for Room 01 due on 2026-01-14.', 0, '2025-12-11 09:18:29'),
(32, 178, '', 'Billing notice sent for Room 01 due on 2026-01-14.', 0, '2025-12-11 09:18:51'),
(33, 183, '', 'Billing notice sent for Room 02 due on 2026-02-14.', 0, '2025-12-11 09:19:42'),
(34, 183, '', 'Billing notice sent for Room 08 due on 2026-01-14.', 0, '2025-12-11 13:36:21'),
(35, 180, '', 'Billing notice sent for Room 08 due on 2026-01-14.', 0, '2025-12-11 13:36:26'),
(36, 181, '', 'Billing notice sent for Room 08 due on 2026-01-14.', 0, '2025-12-11 13:36:32'),
(37, 182, '', 'Billing notice sent for Room 08 due on 2026-01-14.', 0, '2025-12-11 13:36:39'),
(38, 184, '', 'Billing notice sent for Room 08 due on 2026-01-13.', 0, '2025-12-11 14:14:16'),
(39, 185, '', 'Billing notice sent for Room 08 due on 2026-01-14.', 0, '2025-12-11 14:14:47'),
(40, 185, '', 'Billing notice sent for Room 09 due on 2026-01-14.', 0, '2025-12-11 15:21:31');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_number` varchar(50) NOT NULL,
  `room_type` enum('Bed Spacer','Whole Room') NOT NULL,
  `deck_type` enum('Upper','Lower') DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `capacity` int(11) NOT NULL,
  `available` int(11) NOT NULL DEFAULT 0,
  `status` enum('Available','Occupied') NOT NULL DEFAULT 'Available',
  `upper_deck_count` int(11) DEFAULT 0,
  `lower_deck_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `record_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_number`, `room_type`, `deck_type`, `price`, `capacity`, `available`, `status`, `upper_deck_count`, `lower_deck_count`, `created_at`, `record_status`) VALUES
(27, '01', 'Bed Spacer', NULL, 500.00, 2, 2, '', 1, 1, '2025-10-05 09:02:24', 'Active'),
(28, '02', 'Whole Room', NULL, 5000.00, 1, 1, '', 0, 0, '2025-10-05 09:02:35', 'Active'),
(35, '03', 'Bed Spacer', NULL, 500.00, 4, 1, 'Available', 2, 2, '2025-10-08 12:21:18', 'Active'),
(43, '04', 'Bed Spacer', NULL, 500.00, 2, 2, '', 1, 1, '2025-11-27 15:14:07', 'Active'),
(44, '05', 'Bed Spacer', NULL, 1500.00, 2, 2, '', 1, 1, '2025-11-29 10:27:45', 'Active'),
(48, '07', 'Whole Room', NULL, 900.00, 1, 0, 'Available', 0, 0, '2025-12-06 14:47:32', 'Inactive'),
(51, '08', 'Bed Spacer', NULL, 8000.00, 10, 6, 'Available', 5, 5, '2025-12-06 16:35:47', 'Active'),
(53, '09', 'Bed Spacer', NULL, 5000.00, 6, 1, 'Available', 3, 3, '2025-12-11 21:40:14', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `room_additional_descriptions`
--

CREATE TABLE `room_additional_descriptions` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(100) DEFAULT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_name`, `setting_value`) VALUES
(1, 'profile_image', 'uploads/profile_1758637901.jpg'),
(2, 'header_image', 'assets/bg/header_1764222281.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `sms_id` int(11) NOT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `recipient_type` enum('Tenant','Parent','Guardian') DEFAULT 'Tenant',
  `contact_number` varchar(15) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('sent','failed','pending','disabled') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `message_id` varchar(100) DEFAULT NULL,
  `http_code` int(11) DEFAULT NULL,
  `date_sent` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sms_logs`
--

INSERT INTO `sms_logs` (`sms_id`, `tenant_id`, `recipient_type`, `contact_number`, `message`, `status`, `error_message`, `message_id`, `http_code`, `date_sent`) VALUES
(29, 178, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Charity Subiza,\nRoom: 01\nDue Date: Jan 12, 2026\n\nCharges:\n- Base Rent: PHP 500.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 500.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 1,000.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', 'iSms-l5s0MI', 200, '2025-12-11 02:03:24'),
(30, 178, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Charity Subiza,\nRoom: 01\nDue Date: Jan 12, 2026\n\nCharges:\n- Base Rent: PHP 500.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 500.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 1,000.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', 'iSms-eS62we', 200, '2025-12-11 02:03:25'),
(31, 179, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Christian Loyd Bacalso Lamina,\nRoom: 04\nDue Date: Jan 12, 2026\n\nCharges:\n- Base Rent: PHP 500.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 500.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 1,000.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', 'iSms-tdgCY5', 200, '2025-12-11 02:05:18'),
(32, 179, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Christian Loyd Bacalso Lamina,\nRoom: 04\nDue Date: Jan 12, 2026\n\nCharges:\n- Base Rent: PHP 500.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 500.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 1,000.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', 'iSms-Ma4wBl', 200, '2025-12-11 02:05:19'),
(33, 178, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Confirmation\n\nHi Charity Subiza,\nPayment received for Room 01.\nPayment Date: Dec 10, 2025\nAmount Paid: PHP 600.00\nMethod: Cash\nStatus: Settled\n\nBreakdown:\n- Base Rent: PHP 500.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 500.00\n- Additional Charges: None\n\nTotal Bill: PHP 500.00\nRemaining Balance: PHP 0.00\nThank you for your payment!', 'sent', 'Success', 'iSms-mkz9Us', 200, '2025-12-11 02:25:25'),
(34, 178, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Confirmation\n\nHi Charity Subiza,\nPayment received for Room 01.\nPayment Date: Dec 10, 2025\nAmount Paid: PHP 600.00\nMethod: Cash\nStatus: Settled\n\nBreakdown:\n- Base Rent: PHP 500.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 500.00\n- Additional Charges: None\n\nTotal Bill: PHP 500.00\nRemaining Balance: PHP 0.00\nThank you for your payment!', 'sent', 'Success', 'iSms-Twhegq', 200, '2025-12-11 02:25:25'),
(35, 180, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Slade Black,\nRoom: 08\nDue Date: Jan 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 400.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,400.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', 'iSms-TtiHQ4', 200, '2025-12-11 02:41:25'),
(36, 180, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Slade Black,\nRoom: 08\nDue Date: Jan 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 400.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,400.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', 'iSms-bK4l2n', 200, '2025-12-11 02:41:26'),
(37, 182, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Chancellor Aguilar,\nRoom: 08\nDue Date: Jan 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 166.66\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,166.66\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 02:52:14'),
(38, 182, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Chancellor Aguilar,\nRoom: 08\nDue Date: Jan 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 166.66\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,166.66\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 02:52:15'),
(39, 180, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Slade Black,\nRoom: 08\nDue Date: Jan 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 166.67\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,166.67\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 02:52:16'),
(40, 180, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Slade Black,\nRoom: 08\nDue Date: Jan 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 166.67\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,166.67\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 02:52:16'),
(41, 181, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Willow Hubbard,\nRoom: 08\nDue Date: Jan 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 166.67\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,166.67\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 02:52:17'),
(42, 181, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Willow Hubbard,\nRoom: 08\nDue Date: Jan 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 166.67\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,166.67\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 02:52:18'),
(43, 180, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Confirmation\n\nHi Slade Black,\nPayment received for Room 08.\nPayment Date: Dec 10, 2025\nAmount Paid: PHP 10,000.00\nMethod: Cash\nStatus: Settled\n\nBreakdown:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 166.67\n- Additional Charges: None\n\nTotal Bill: PHP 8,166.67\nRemaining Balance: PHP 0.00\nThank you for your payment!', 'sent', 'Success', NULL, 200, '2025-12-11 03:33:30'),
(44, 180, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Confirmation\n\nHi Slade Black,\nPayment received for Room 08.\nPayment Date: Dec 10, 2025\nAmount Paid: PHP 10,000.00\nMethod: Cash\nStatus: Settled\n\nBreakdown:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 166.67\n- Additional Charges: None\n\nTotal Bill: PHP 8,166.67\nRemaining Balance: PHP 0.00\nThank you for your payment!', 'sent', 'Success', NULL, 200, '2025-12-11 03:33:31'),
(45, 182, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Confirmation\n\nHi Chancellor Aguilar,\nPayment received for Room 08.\nPayment Date: Dec 10, 2025\nAmount Paid: PHP 9,000.00\nMethod: GCash\nStatus: Settled\n\nBreakdown:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 166.66\n- Additional Charges: None\n\nTotal Bill: PHP 8,166.66\nRemaining Balance: PHP 0.00\nThank you for your payment!', 'sent', 'Success', NULL, 200, '2025-12-11 03:34:00'),
(46, 182, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Confirmation\n\nHi Chancellor Aguilar,\nPayment received for Room 08.\nPayment Date: Dec 10, 2025\nAmount Paid: PHP 9,000.00\nMethod: GCash\nStatus: Settled\n\nBreakdown:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 166.66\n- Additional Charges: None\n\nTotal Bill: PHP 8,166.66\nRemaining Balance: PHP 0.00\nThank you for your payment!', 'sent', 'Success', NULL, 200, '2025-12-11 03:34:01'),
(47, 181, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Confirmation\n\nHi Willow Hubbard,\nPayment received for Room 08.\nPayment Date: Dec 10, 2025\nAmount Paid: PHP 11,000.00\nMethod: Cash\nStatus: Settled\n\nBreakdown:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 166.67\n- Additional Charges: None\n\nTotal Bill: PHP 8,166.67\nRemaining Balance: PHP 0.00\nThank you for your payment!', 'sent', 'Success', NULL, 200, '2025-12-11 03:34:45'),
(48, 181, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Confirmation\n\nHi Willow Hubbard,\nPayment received for Room 08.\nPayment Date: Dec 10, 2025\nAmount Paid: PHP 11,000.00\nMethod: Cash\nStatus: Settled\n\nBreakdown:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 166.67\n- Additional Charges: None\n\nTotal Bill: PHP 8,166.67\nRemaining Balance: PHP 0.00\nThank you for your payment!', 'sent', 'Success', NULL, 200, '2025-12-11 03:34:46'),
(49, 181, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Willow Hubbard,\nRoom: 08\nDue Date: Feb 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 333.33\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,333.33\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 03:35:03'),
(50, 181, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Willow Hubbard,\nRoom: 08\nDue Date: Feb 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 333.33\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,333.33\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 03:35:04'),
(51, 180, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Slade Black,\nRoom: 08\nDue Date: Feb 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 333.34\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,333.34\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 03:35:05'),
(52, 180, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Slade Black,\nRoom: 08\nDue Date: Feb 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 333.34\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,333.34\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 03:35:06'),
(53, 182, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Chancellor Aguilar,\nRoom: 08\nDue Date: Feb 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 333.33\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,333.33\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 03:35:08'),
(54, 182, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Chancellor Aguilar,\nRoom: 08\nDue Date: Feb 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 333.33\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,333.33\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 03:35:09'),
(55, 180, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Slade Black,\nRoom: 08\nDue Date: Feb 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 186.67\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,186.67\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 04:04:06'),
(56, 180, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Slade Black,\nRoom: 08\nDue Date: Feb 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 186.67\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,186.67\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 04:04:07'),
(57, 181, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Willow Hubbard,\nRoom: 08\nDue Date: Feb 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 186.67\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,186.67\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 04:04:07'),
(58, 181, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Willow Hubbard,\nRoom: 08\nDue Date: Feb 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 186.67\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,186.67\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 04:04:08'),
(59, 182, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Chancellor Aguilar,\nRoom: 08\nDue Date: Feb 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 186.66\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,186.66\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 04:04:09'),
(60, 182, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Chancellor Aguilar,\nRoom: 08\nDue Date: Feb 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Interest: PHP 0.00\n- Utility (Electricity): PHP 186.66\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,186.66\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 04:04:10'),
(61, 183, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Slade Conrad,\nRoom: 01\nDue Date: Jan 14, 2026\n\nCharges:\n- Base Rent: PHP 500.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 50.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 550.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'failed', 'Could not resolve host: sms.iprogtech.com', NULL, NULL, '2025-12-11 17:18:18'),
(62, 183, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Slade Conrad,\nRoom: 01\nDue Date: Jan 14, 2026\n\nCharges:\n- Base Rent: PHP 500.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 50.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 550.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'failed', 'Could not resolve host: sms.iprogtech.com', NULL, NULL, '2025-12-11 17:18:29'),
(63, 178, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Charity Subiza,\nRoom: 01\nDue Date: Jan 14, 2026\n\nCharges:\n- Base Rent: PHP 500.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 50.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 550.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 17:18:46'),
(64, 178, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Charity Subiza,\nRoom: 01\nDue Date: Jan 14, 2026\n\nCharges:\n- Base Rent: PHP 500.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 50.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 550.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 17:18:51'),
(65, 183, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Slade Conrad,\nRoom: 02\nDue Date: Feb 14, 2026\n\nCharges:\n- Base Rent: PHP 5,000.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 120.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 5,120.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 17:19:41'),
(66, 183, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Slade Conrad,\nRoom: 02\nDue Date: Feb 14, 2026\n\nCharges:\n- Base Rent: PHP 5,000.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 120.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 5,120.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 17:19:42'),
(67, 183, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Slade Conrad,\nRoom: 08\nDue Date: Jan 14, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 300.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,300.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 21:36:19'),
(68, 183, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Slade Conrad,\nRoom: 08\nDue Date: Jan 14, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 300.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,300.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 21:36:21'),
(69, 180, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Alex Black,\nRoom: 08\nDue Date: Jan 14, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 300.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,300.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 21:36:24'),
(70, 180, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Alex Black,\nRoom: 08\nDue Date: Jan 14, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 300.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,300.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 21:36:26'),
(71, 181, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Willow Hubbard,\nRoom: 08\nDue Date: Jan 14, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 300.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,300.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 21:36:30'),
(72, 181, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Willow Hubbard,\nRoom: 08\nDue Date: Jan 14, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 300.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,300.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 21:36:32'),
(73, 182, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Chancellor Aguilar,\nRoom: 08\nDue Date: Jan 14, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 300.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,300.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 21:36:34'),
(74, 182, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Chancellor Aguilar,\nRoom: 08\nDue Date: Jan 14, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 300.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,300.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 21:36:39'),
(75, 184, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Analyn Sala,\nRoom: 08\nDue Date: Jan 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 33.33\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,033.33\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 22:14:14'),
(76, 184, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Analyn Sala,\nRoom: 08\nDue Date: Jan 13, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 33.33\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,033.33\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 22:14:16'),
(77, 185, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Gladys Smith,\nRoom: 08\nDue Date: Jan 14, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 0.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,000.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 22:14:45'),
(78, 185, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Gladys Smith,\nRoom: 08\nDue Date: Jan 14, 2026\n\nCharges:\n- Base Rent: PHP 8,000.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 0.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 8,000.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 22:14:47'),
(79, 185, 'Tenant', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Gladys Smith,\nRoom: 09\nDue Date: Jan 14, 2026\n\nCharges:\n- Base Rent: PHP 5,000.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 500.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 5,500.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 23:21:30'),
(80, 185, 'Guardian', '639497428155', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nBilling Notice\n\nHi Gladys Smith,\nRoom: 09\nDue Date: Jan 14, 2026\n\nCharges:\n- Base Rent: PHP 5,000.00\n- Late Payment Charge: PHP 0.00\n- Utility (Electricity): PHP 500.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 5,500.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', NULL, 200, '2025-12-11 23:21:31');

-- --------------------------------------------------------

--
-- Table structure for table `tenants`
--

CREATE TABLE `tenants` (
  `tenant_id` int(11) NOT NULL,
  `tenant_name` varchar(100) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `proof_pic` varchar(255) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `deck_type` enum('Lower Deck','Upper Deck') DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `guardian_contact` varchar(20) NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `date_started` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tenants`
--

INSERT INTO `tenants` (`tenant_id`, `tenant_name`, `profile_pic`, `proof_pic`, `room_id`, `deck_type`, `address`, `contact_number`, `guardian_contact`, `status`, `date_started`, `created_at`) VALUES
(131, 'Gladys Diano', '', '', 27, 'Lower Deck', 'Kalasan', '09499559595', '09885858588', '', '2025-09-06', '2025-10-06 22:27:13'),
(172, 'Catherine English', '', '', 51, 'Upper Deck', 'Officia Enim Alias O', '09497428155', '09497428155', 'Inactive', '2025-12-10', '2025-12-09 14:51:03'),
(174, 'Christian Loyd Bacalso Lamina', 'uploads/bg.jpg', 'uploads/Blue White Simple Annual Report Cover Page A4 Document (A4).png', 44, 'Lower Deck', 'Brgy. Mecolong, Dumalinao, Zamboanga Del Sur', '09497428155', '09497428155', 'Inactive', '2025-12-14', '2025-12-11 01:06:42'),
(175, 'Aether Abno Baliw', '', '', 44, 'Lower Deck', 'Brgy. Mecolong, Dumalinao, Zamboanga Del Sur', '09497428155', '09497428155', 'Inactive', '2025-12-14', '2025-12-11 01:26:34'),
(176, 'Charity Subiza', '', '', 27, 'Lower Deck', 'Brgy. Mecolong, Dumalinao, Zamboanga Del Sur', '09497428155', '09497428155', 'Inactive', '2026-12-12', '2025-12-11 02:02:17'),
(177, 'Charity Subiza', '', '', 27, 'Lower Deck', 'Brgy. Mecolong, Dumalinao, Zamboanga Del Sur', '09497428155', '09497428155', 'Inactive', '2026-12-12', '2025-12-11 02:02:55'),
(178, 'Charity Subiza', '', '', 27, 'Lower Deck', 'Brgy. Mecolong, Dumalinao, Zamboanga Del Sur', '09497428155', '09497428155', 'Active', '2025-12-12', '2025-12-11 02:03:10'),
(179, 'Christian Loyd Bacalso Lamina', '', '', 43, 'Lower Deck', 'Brgy. Mecolong, Dumalinao, Zamboanga Del Sur', '09497428155', '09497428155', 'Active', '2025-12-12', '2025-12-11 02:04:57'),
(180, 'Alex Black', '', '', 51, 'Lower Deck', 'Quod Ipsum Rem Nequ', '09497428155', '09497428155', 'Active', '2025-12-13', '2025-12-11 02:38:32'),
(181, 'Willow Hubbard', '', '', 51, 'Lower Deck', 'Necessitatibus Maxim', '09497428155', '09497428155', 'Active', '2025-12-14', '2025-12-11 02:39:10'),
(182, 'Chancellor Aguilar', '', '', 51, 'Upper Deck', 'Aspernatur Illum Ut', '09497428155', '09497428155', 'Active', '2025-12-13', '2025-12-11 02:39:47'),
(183, 'Slade Conrad', NULL, NULL, NULL, NULL, 'Aspernatur Sequi Omn', '09497428155', '09497428155', 'Active', '2025-12-14', '2025-12-11 16:52:57'),
(184, 'Analyn Sala', NULL, NULL, NULL, NULL, 'Aspernatur Sequi Omn', '09497428155', '09497428155', 'Active', '2025-12-13', '2025-12-11 22:12:36'),
(185, 'Gladys Smith', NULL, NULL, NULL, NULL, 'Tulawas Pagadian', '09497428155', '09497428155', 'Active', '2025-12-14', '2025-12-11 22:13:18');

-- --------------------------------------------------------

--
-- Table structure for table `tenant_rooms`
--

CREATE TABLE `tenant_rooms` (
  `tenant_room_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `deck_type` enum('Lower Deck','Upper Deck') DEFAULT NULL,
  `assigned_at` datetime DEFAULT current_timestamp(),
  `released_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tenant_rooms`
--

INSERT INTO `tenant_rooms` (`tenant_room_id`, `tenant_id`, `room_id`, `deck_type`, `assigned_at`, `released_at`) VALUES
(1, 131, 27, 'Lower Deck', '2025-10-06 22:27:13', NULL),
(2, 172, 51, 'Upper Deck', '2025-12-09 14:51:03', NULL),
(3, 174, 44, 'Lower Deck', '2025-12-11 01:06:42', NULL),
(4, 175, 44, 'Lower Deck', '2025-12-11 01:26:34', NULL),
(5, 176, 27, 'Lower Deck', '2025-12-11 02:02:17', NULL),
(6, 177, 27, 'Lower Deck', '2025-12-11 02:02:55', NULL),
(7, 178, 27, 'Lower Deck', '2025-12-11 02:03:10', NULL),
(8, 179, 43, 'Lower Deck', '2025-12-11 02:04:57', NULL),
(9, 180, 51, 'Lower Deck', '2025-12-11 02:38:32', NULL),
(10, 181, 51, 'Lower Deck', '2025-12-11 02:39:10', NULL),
(11, 182, 51, 'Upper Deck', '2025-12-11 02:39:47', NULL),
(16, 183, 28, NULL, '2025-12-11 16:52:57', NULL),
(17, 183, 27, 'Upper Deck', '2025-12-11 16:52:57', NULL),
(18, 183, 43, 'Upper Deck', '2025-12-11 16:52:57', NULL),
(19, 183, 51, 'Lower Deck', '2025-12-11 16:52:57', NULL),
(21, 184, 35, 'Upper Deck', '2025-12-11 22:12:36', NULL),
(22, 184, 51, 'Upper Deck', '2025-12-11 22:12:36', NULL),
(23, 185, 51, 'Upper Deck', '2025-12-11 22:13:18', NULL),
(24, 185, 53, 'Lower Deck', '2025-12-11 22:13:18', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_account`
--
ALTER TABLE `admin_account`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`bill_id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `billing_additional_items`
--
ALTER TABLE `billing_additional_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`);

--
-- Indexes for table `billing_sms_status`
--
ALTER TABLE `billing_sms_status`
  ADD PRIMARY KEY (`bill_id`);

--
-- Indexes for table `billing_utility_items`
--
ALTER TABLE `billing_utility_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`invoice_id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `bill_id` (`bill_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`);

--
-- Indexes for table `room_additional_descriptions`
--
ALTER TABLE `room_additional_descriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`sms_id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date_sent` (`date_sent`),
  ADD KEY `idx_tenant_id` (`tenant_id`);

--
-- Indexes for table `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`tenant_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `tenant_rooms`
--
ALTER TABLE `tenant_rooms`
  ADD PRIMARY KEY (`tenant_room_id`),
  ADD UNIQUE KEY `uniq_tenant_room` (`tenant_id`,`room_id`),
  ADD KEY `fk_tenant_rooms_room` (`room_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_account`
--
ALTER TABLE `admin_account`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `bill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=521;

--
-- AUTO_INCREMENT for table `billing_additional_items`
--
ALTER TABLE `billing_additional_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `billing_utility_items`
--
ALTER TABLE `billing_utility_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `room_additional_descriptions`
--
ALTER TABLE `room_additional_descriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `sms_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `tenants`
--
ALTER TABLE `tenants`
  MODIFY `tenant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=186;

--
-- AUTO_INCREMENT for table `tenant_rooms`
--
ALTER TABLE `tenant_rooms`
  MODIFY `tenant_room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `billing`
--
ALTER TABLE `billing`
  ADD CONSTRAINT `billing_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`tenant_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `billing_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE;

--
-- Constraints for table `billing_additional_items`
--
ALTER TABLE `billing_additional_items`
  ADD CONSTRAINT `billing_additional_items_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `billing` (`bill_id`) ON DELETE CASCADE;

--
-- Constraints for table `billing_utility_items`
--
ALTER TABLE `billing_utility_items`
  ADD CONSTRAINT `billing_utility_items_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `billing` (`bill_id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`tenant_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`tenant_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `billing` (`bill_id`) ON DELETE CASCADE;

--
-- Constraints for table `room_additional_descriptions`
--
ALTER TABLE `room_additional_descriptions`
  ADD CONSTRAINT `room_additional_descriptions_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`);

--
-- Constraints for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD CONSTRAINT `sms_logs_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`tenant_id`);

--
-- Constraints for table `tenants`
--
ALTER TABLE `tenants`
  ADD CONSTRAINT `tenants_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE;

--
-- Constraints for table `tenant_rooms`
--
ALTER TABLE `tenant_rooms`
  ADD CONSTRAINT `fk_tenant_rooms_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tenant_rooms_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`tenant_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
