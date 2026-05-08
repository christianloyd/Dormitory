-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 07, 2025 at 07:01 AM
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
(247, 129, 29, '2025-10-07', 0.00, NULL, '2025-10-01', 100.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Settled', 'Cash', 200.00, '2025-10-07', 0.00, 0.00, 0.00, '2025-10-07 02:39:02'),
(278, 135, 28, '2025-10-07', 0.00, NULL, '2025-10-02', 1000.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Settled', 'GCash', 1000.00, '2025-10-07', 0.00, 0.00, 0.00, '2025-10-07 11:43:03'),
(423, 136, 34, '2025-10-12', 0.00, NULL, '2025-11-02', 150.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Pending', NULL, 0.00, NULL, 0.00, 0.00, 0.00, '2025-10-12 06:17:33'),
(424, 138, 35, '2025-10-12', 0.00, NULL, '2025-10-14', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Partial', 'Cash', 200.00, '2025-10-12', 0.00, 0.00, 0.00, '2025-10-12 06:26:22'),
(427, 140, 28, '2025-10-23', 0.00, NULL, '2025-10-02', 1000.00, '', 0.00, '', 0.00, 5.00, 0.00, 0.00, 0.00, 0.00, 'Partial', '', 0.00, NULL, 0.00, 0.00, 0.00, '2025-10-23 10:38:09'),
(428, 142, 35, '2025-10-31', 0.00, NULL, '2025-10-01', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Settled', 'Bank Transfer', 900.00, '2025-10-31', 0.00, 0.00, 0.00, '2025-10-31 07:39:27'),
(429, 141, 35, '2025-10-31', 0.00, NULL, '2025-10-02', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Partial', 'GCash', 100.00, '2025-10-31', 0.00, 0.00, 0.00, '2025-10-31 07:42:48'),
(430, 143, 35, NULL, 0.00, NULL, '2025-10-01', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Settled', 'GCash', 1500.00, '2025-10-31', 0.00, 0.00, 0.00, '2025-10-31 08:08:11'),
(432, 140, 28, NULL, 0.00, NULL, '2025-11-02', 1000.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Pending', NULL, 0.00, NULL, 0.00, 0.00, 0.00, '2025-11-06 03:57:15'),
(433, 144, 35, NULL, 0.00, NULL, '2025-11-06', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Partial', 'Cash', 300.00, '2025-11-06', 0.00, 0.00, 0.00, '2025-11-06 04:17:22'),
(434, 145, 37, NULL, 0.00, NULL, '2025-11-06', 100.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Settled', 'Cash', 100.00, '2025-11-08', 0.00, 0.00, 0.00, '2025-11-06 04:34:20'),
(435, 139, 34, NULL, 0.00, NULL, '2025-11-17', 150.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Pending', NULL, 0.00, NULL, 0.00, 0.00, 0.00, '2025-11-06 05:12:04'),
(436, 145, 37, NULL, 0.00, NULL, '2025-12-06', 100.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Pending', NULL, 0.00, NULL, 0.00, 0.00, 0.00, '2025-11-08 18:57:13'),
(437, 144, 35, NULL, 0.00, NULL, '2025-12-06', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Pending', NULL, 0.00, NULL, 0.00, 0.00, 0.00, '2025-11-12 10:00:30'),
(440, 147, 27, NULL, 0.00, NULL, '2025-11-14', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Pending', NULL, 0.00, NULL, 0.00, 0.00, 0.00, '2025-11-13 13:25:37'),
(441, 147, 27, NULL, 0.00, NULL, '2025-12-14', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Pending', NULL, 0.00, NULL, 0.00, 0.00, 0.00, '2025-11-13 13:31:54'),
(442, 128, 33, NULL, 0.00, NULL, '2025-11-05', 200.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Settled', 'GCash', 400.00, '2025-11-25', 0.00, 0.00, 0.00, '2025-11-25 10:41:42'),
(443, 159, 42, NULL, 0.00, NULL, '2025-12-27', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Pending', NULL, 0.00, NULL, 0.00, 0.00, 0.00, '2025-11-27 07:00:16'),
(444, 160, 43, NULL, 0.00, NULL, '2025-12-27', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Settled', 'GCash', 800.00, '2025-11-27', 0.00, 0.00, 0.00, '2025-11-27 07:18:02'),
(447, 142, 35, NULL, 0.00, NULL, '2025-11-01', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Partial', 'Cash', 60.00, '2025-11-27', 0.00, 0.00, 0.00, '2025-11-27 08:06:00'),
(450, 161, 29, NULL, 0.00, NULL, '2025-11-27', 100.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Settled', 'GCash', 100.00, '2025-11-27', 0.00, 0.00, 0.00, '2025-11-27 14:54:36'),
(451, 143, 35, NULL, 0.00, NULL, '2025-11-01', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Pending', NULL, 0.00, NULL, 0.00, 0.00, 0.00, '2025-11-28 06:43:29'),
(452, 160, 43, NULL, 0.00, NULL, '2026-01-27', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Partial', 'Bank Transfer', 100.00, '2025-11-28', 0.00, 0.00, 0.00, '2025-11-28 06:44:06'),
(453, 162, 27, NULL, 0.00, NULL, '2025-11-28', 500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Settled', 'Cash', 1500.00, '2025-11-28', 0.00, 0.00, 0.00, '2025-11-28 13:59:06'),
(454, 164, 44, NULL, 0.00, NULL, '2025-12-29', 1500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Partial', 'Cash', 1000.00, '2025-11-29', 0.00, 0.00, 0.00, '2025-11-29 02:41:44'),
(455, 165, 44, NULL, 0.00, NULL, '2025-11-29', 1500.00, '', 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Settled', 'Cash', 1700.00, '2025-11-29', 0.00, 0.00, 0.00, '2025-11-29 06:09:37');

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

--
-- Dumping data for table `billing_additional_items`
--

INSERT INTO `billing_additional_items` (`id`, `bill_id`, `label`, `amount`, `created_at`) VALUES
(1, 443, 'Window Damage', 600.00, '2025-11-27 07:00:16'),
(2, 444, 'Window Damage', 100.00, '2025-11-27 07:18:02');

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
(1, 443, 'Water', 100.00, '2025-11-27 07:00:16'),
(2, 443, 'Electrictiy', 100.00, '2025-11-27 07:00:16'),
(3, 444, 'Water', 100.00, '2025-11-27 07:18:02'),
(4, 444, 'Electricity', 100.00, '2025-11-27 07:18:02'),
(5, 454, 'Water', 500.00, '2025-11-29 02:41:44'),
(6, 455, 'Water', 200.00, '2025-11-29 06:09:37');

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
(1, 138, 'reminder', 'Reminder sent regarding billing for Room 03 due on 2025-10-14.', 1, '2025-10-12 10:03:47'),
(2, 136, 'reminder', 'Reminder sent regarding billing for Room 04 due on 2025-11-02.', 1, '2025-10-12 10:04:33'),
(3, 144, 'reminder', 'Reminder sent regarding billing for Room 03 due on 2025-11-06.', 1, '2025-11-06 04:20:03'),
(4, 145, 'reminder', 'Reminder sent regarding billing for Room 05 due on 2025-11-06.', 1, '2025-11-06 04:34:29'),
(5, 145, 'reminder', 'Reminder sent regarding billing for Room 05 due on 2025-12-06.', 1, '2025-11-08 18:59:24'),
(6, 144, 'reminder', 'Reminder sent regarding billing for Room 03 due on 2025-12-06.', 1, '2025-11-12 10:05:40'),
(7, 160, 'reminder', 'Reminder sent regarding billing for Room 04 due on 2025-12-27.', 1, '2025-11-27 07:18:30'),
(8, 160, 'confirmation', 'Payment confirmation sent for Room 04. Paid: ₱800.00, Total Bill: ₱0.00', 1, '2025-11-27 07:34:59'),
(9, 164, 'reminder', 'Reminder sent regarding billing for Room 05 due on 2025-12-29.', 1, '2025-11-29 02:42:23'),
(10, 164, 'confirmation', 'Payment confirmation sent for Room 05. Paid: ₱1,000.00, Total Bill: ₱0.00', 1, '2025-11-29 02:46:12'),
(11, 165, 'reminder', 'Reminder sent regarding billing for Room 05 due on 2025-11-29.', 0, '2025-11-29 06:09:52'),
(12, 165, 'reminder', 'Reminder sent regarding billing for Room 05 due on 2025-11-29.', 0, '2025-11-29 06:12:00'),
(13, 165, 'reminder', 'Reminder sent regarding billing for Room 05 due on 2025-11-29.', 0, '2025-11-29 06:13:10'),
(14, 165, 'confirmation', 'Payment confirmation sent for Room 05. Paid: ₱1,700.00, Total Bill: ₱0.00', 0, '2025-11-29 06:14:28');

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
(27, '01', 'Bed Spacer', NULL, 500.00, 2, 9, '', 1, 1, '2025-10-05 09:02:24', 'Active'),
(28, '02', 'Whole Room', NULL, 5000.00, 1, 6, '', 0, 0, '2025-10-05 09:02:35', 'Active'),
(29, '03', 'Whole Room', NULL, 100.00, 1, 2, '', 0, 0, '2025-10-05 09:55:20', 'Inactive'),
(32, '04', 'Whole Room', NULL, 150.00, 1, 1, '', 0, 0, '2025-10-06 22:40:01', 'Inactive'),
(33, '01', 'Whole Room', NULL, 200.00, 1, 0, 'Available', 0, 0, '2025-10-07 00:11:01', 'Inactive'),
(34, '04', 'Whole Room', NULL, 150.00, 1, 2, '', 0, 0, '2025-10-07 00:14:08', 'Inactive'),
(35, '03', 'Bed Spacer', NULL, 500.00, 4, 6, '', 2, 2, '2025-10-08 12:21:18', 'Active'),
(36, '05', 'Whole Room', NULL, 400.00, 1, 0, 'Available', 0, 0, '2025-10-09 08:31:08', 'Inactive'),
(37, '05', 'Bed Spacer', NULL, 100.00, 4, 1, 'Available', 2, 2, '2025-10-16 14:41:36', 'Inactive'),
(38, '06', 'Whole Room', NULL, 120.00, 1, 0, 'Available', 0, 0, '2025-10-16 14:41:53', 'Inactive'),
(39, '07', 'Whole Room', NULL, 100.00, 1, 0, 'Available', 0, 0, '2025-10-16 14:42:06', 'Inactive'),
(40, '08', 'Whole Room', NULL, 100.00, 1, 0, 'Available', 0, 0, '2025-10-16 14:42:20', 'Inactive'),
(41, '09', 'Whole Room', NULL, 100.00, 1, 0, 'Available', 0, 0, '2025-10-16 14:42:34', 'Inactive'),
(42, '04', 'Bed Spacer', NULL, 500.00, 2, 1, 'Available', 1, 1, '2025-11-27 14:58:32', 'Inactive'),
(43, '04', 'Bed Spacer', NULL, 500.00, 2, 2, '', 1, 1, '2025-11-27 15:14:07', 'Active'),
(44, '05', 'Bed Spacer', NULL, 1500.00, 2, 3, '', 1, 1, '2025-11-29 10:27:45', 'Active'),
(45, '06', 'Bed Spacer', NULL, 500.00, 10, 0, 'Available', 10, 0, '2025-11-29 14:06:26', 'Inactive'),
(46, '11', 'Bed Spacer', NULL, 300.00, 2, 0, 'Available', 1, 1, '2025-12-06 14:07:15', 'Inactive'),
(47, '11', 'Bed Spacer', NULL, 400.00, 2, 0, 'Available', 1, 1, '2025-12-06 14:16:34', 'Inactive'),
(48, '07', 'Whole Room', NULL, 900.00, 1, 0, 'Available', 0, 0, '2025-12-06 14:47:32', 'Active'),
(49, '08', 'Whole Room', NULL, 900.00, 1, 0, 'Available', 0, 0, '2025-12-06 16:25:32', 'Inactive'),
(50, '08', 'Whole Room', NULL, 900.00, 1, 0, 'Available', 0, 0, '2025-12-06 16:33:01', 'Inactive'),
(51, '08', 'Bed Spacer', NULL, 8000.00, 10, 0, 'Available', 5, 5, '2025-12-06 16:35:47', 'Active');

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

--
-- Dumping data for table `room_additional_descriptions`
--

INSERT INTO `room_additional_descriptions` (`id`, `room_id`, `description`, `created_at`) VALUES
(1, 46, 'door\r\nwindow\r\nfan\r\ntv\r\nradyo', '2025-12-06 14:07:15'),
(2, 47, 'TV', '2025-12-06 14:16:34'),
(3, 47, 'Window', '2025-12-06 14:16:34'),
(4, 47, 'Fan', '2025-12-06 14:16:34'),
(5, 47, 'Glass', '2025-12-06 14:16:34'),
(18, 48, 'Glass', '2025-12-06 15:23:55'),
(19, 48, 'Window', '2025-12-06 15:23:55'),
(20, 48, 'Door', '2025-12-06 15:23:55'),
(22, 27, 'TV', '2025-12-06 15:35:21'),
(23, 27, 'REMOTE', '2025-12-06 15:35:21'),
(24, 27, 'REF', '2025-12-06 15:35:21'),
(25, 27, 'RICE COOKER', '2025-12-06 15:35:21'),
(26, 27, 'WASHING', '2025-12-06 15:35:21'),
(27, 49, 'gladts', '2025-12-06 16:25:32'),
(28, 50, 'GLADYS', '2025-12-06 16:33:01'),
(29, 50, 'GWAPA', '2025-12-06 16:33:01');

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
(1, 144, 'Tenant', '639497428155', 'Ben & Sof Dorm\nPayment Reminder\n\nHi Christian Loyd Bacalso Lamina!\nRoom: 03\nDue: 2025-11-06\nAmount: ₱500.00\n\nPay within 3 days.\nThank you!', 'sent', 'Success', NULL, 200, '2025-11-06 12:20:02'),
(2, 144, 'Guardian', '639104884941', 'Ben & Sof Dorm\nPayment Reminder\n\nHi Christian Loyd Bacalso Lamina!\nRoom: 03\nDue: 2025-11-06\nAmount: ₱500.00\n\nPay within 3 days.\nThank you!', 'sent', 'Success', NULL, 200, '2025-11-06 12:20:03'),
(3, 145, 'Tenant', '639497428155', 'Ben & Sof Dorm\nPayment Reminder\n\nHi Chrizel Bacalso Lamina!\nRoom: 05\nDue: 2025-11-06\nAmount: ₱100.00\n\nPay within 3 days.\nThank you!', 'sent', 'Success', 'iSms-T6qRJK', 200, '2025-11-06 12:34:28'),
(4, 145, 'Guardian', '639104884941', 'Ben & Sof Dorm\nPayment Reminder\n\nHi Chrizel Bacalso Lamina!\nRoom: 05\nDue: 2025-11-06\nAmount: ₱100.00\n\nPay within 3 days.\nThank you!', 'sent', 'Success', 'iSms-JelIX1', 200, '2025-11-06 12:34:29'),
(5, 144, 'Tenant', '639497428155', 'Ben & Sof Dorm\nPayment Received!\n\nRoom: 03\nPaid: ₱300.00\nMethod: Cash\nBalance: ₱200.00\n\nThank you!', 'sent', 'Success', 'iSms-96wgNl', 200, '2025-11-06 21:29:03'),
(6, 144, 'Guardian', '639104884941', 'Ben & Sof Dorm\nPayment Received!\n\nRoom: 03\nPaid: ₱300.00\nMethod: Cash\nBalance: ₱200.00\n\nThank you!', 'sent', 'Success', 'iSms-ZUSUgE', 200, '2025-11-06 21:29:04'),
(7, 145, 'Tenant', '639497428155', 'Ben & Sof Dorm\nPayment Received!\n\nRoom: 05\nPaid: PHP 100.00\nMethod: Cash\nStatus: Settled\n\nThank you!', 'sent', 'Success', 'iSms-6eoN4I', 200, '2025-11-08 18:17:25'),
(8, 145, 'Guardian', '639104884941', 'Ben & Sof Dorm\nPayment Received!\n\nRoom: 05\nPaid: PHP 100.00\nMethod: Cash\nStatus: Settled\n\nThank you!', 'sent', 'Success', 'iSms-hauzan', 200, '2025-11-08 18:17:27'),
(9, 145, 'Tenant', '639497428155', 'Ben & Sof Dorm\nPayment Reminder\n\nHi Chrizel Bacalso Lamina!\nRoom: 05\nDue: 2025-12-06\nAmount: PHP 100.00\n\nPay within 3 days.\nThank you!', 'sent', 'Success', 'iSms-a18mGv', 200, '2025-11-09 02:59:22'),
(10, 145, 'Guardian', '639497428155', 'Ben & Sof Dorm\nPayment Reminder\n\nHi Chrizel Bacalso Lamina!\nRoom: 05\nDue: 2025-12-06\nAmount: PHP 100.00\n\nPay within 3 days.\nThank you!', 'sent', 'Success', 'iSms-HFarcK', 200, '2025-11-09 02:59:24'),
(11, 144, 'Tenant', '639497428155', 'Ben & Sof Dorm\nPayment Reminder\n\nHi Christian Loyd Bacalso Lamina!\nRoom: 03\nDue: 2025-12-06\nBase Rent: PHP 500.00\nAmount Due: PHP 500.00\n\nPay within 3 days.\nThank you!', 'sent', 'Success', 'iSms-L97iVj', 200, '2025-11-12 18:05:39'),
(12, 144, 'Guardian', '639104884941', 'Ben & Sof Dorm\nPayment Reminder\n\nHi Christian Loyd Bacalso Lamina!\nRoom: 03\nDue: 2025-12-06\nBase Rent: PHP 500.00\nAmount Due: PHP 500.00\n\nPay within 3 days.\nThank you!', 'sent', 'Success', 'iSms-lzbKfX', 200, '2025-11-12 18:05:40'),
(13, 160, 'Tenant', '639101612799', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Reminder\n\nHi Cherime Pielago,\nRoom: 04\nDue Date: Dec 27, 2025\n\nCharges:\n- Base Rent: PHP 500.00\n- Interest: PHP 0.00\n- Utility (Water): PHP 100.00\n- Utility (Electricity): PHP 100.00\n- Additional (Window Damage): PHP 100.00\n\nTotal Amount Due: PHP 800.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', 'iSms-uDQJxw', 200, '2025-11-27 15:18:29'),
(14, 160, 'Guardian', '639352523816', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Reminder\n\nHi Cherime Pielago,\nRoom: 04\nDue Date: Dec 27, 2025\n\nCharges:\n- Base Rent: PHP 500.00\n- Interest: PHP 0.00\n- Utility (Water): PHP 100.00\n- Utility (Electricity): PHP 100.00\n- Additional (Window Damage): PHP 100.00\n\nTotal Amount Due: PHP 800.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', 'iSms-F2oLoj', 200, '2025-11-27 15:18:29'),
(15, 160, 'Tenant', '639101612799', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Confirmation\n\nHi Cherime Pielago,\nPayment received for Room 04.\nPayment Date: Nov 27, 2025\nAmount Paid: PHP 800.00\nMethod: GCash\nStatus: Settled\n\nBreakdown:\n- Base Rent: PHP 500.00\n- Interest: PHP 0.00\n- Utility (Water): PHP 100.00\n- Utility (Electricity): PHP 100.00\n- Additional (Window Damage): PHP 100.00\n\nTotal Bill: PHP 0.00\nRemaining Balance: PHP 0.00\nThank you for your payment!', 'sent', 'Success', NULL, 200, '2025-11-27 15:34:58'),
(16, 160, 'Guardian', '639352523816', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Confirmation\n\nHi Cherime Pielago,\nPayment received for Room 04.\nPayment Date: Nov 27, 2025\nAmount Paid: PHP 800.00\nMethod: GCash\nStatus: Settled\n\nBreakdown:\n- Base Rent: PHP 500.00\n- Interest: PHP 0.00\n- Utility (Water): PHP 100.00\n- Utility (Electricity): PHP 100.00\n- Additional (Window Damage): PHP 100.00\n\nTotal Bill: PHP 0.00\nRemaining Balance: PHP 0.00\nThank you for your payment!', 'sent', 'Success', NULL, 200, '2025-11-27 15:34:59'),
(17, 164, 'Tenant', '639350320721', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Reminder\n\nHi Che Magno,\nRoom: 05\nDue Date: Dec 29, 2025\n\nCharges:\n- Base Rent: PHP 1,500.00\n- Interest: PHP 0.00\n- Utility (Water): PHP 500.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 2,000.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', 'iSms-44zM4l', 200, '2025-11-29 10:42:21'),
(18, 164, 'Guardian', '639352523816', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Reminder\n\nHi Che Magno,\nRoom: 05\nDue Date: Dec 29, 2025\n\nCharges:\n- Base Rent: PHP 1,500.00\n- Interest: PHP 0.00\n- Utility (Water): PHP 500.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 2,000.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', 'iSms-SJiKHR', 200, '2025-11-29 10:42:23'),
(19, 164, 'Tenant', '639350320721', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Confirmation\n\nHi Che Magno,\nPayment received for Room 05.\nPayment Date: Nov 29, 2025\nAmount Paid: PHP 1,000.00\nMethod: Cash\nStatus: Partial\n\nBreakdown:\n- Base Rent: PHP 1,500.00\n- Interest: PHP 0.00\n- Utility (Water): PHP 500.00\n- Additional Charges: None\n\nTotal Bill: PHP 0.00\nRemaining Balance: PHP 0.00\nThank you for your payment!', 'sent', 'Success', 'iSms-0vccxl', 200, '2025-11-29 10:46:11'),
(20, 164, 'Guardian', '639352523816', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Confirmation\n\nHi Che Magno,\nPayment received for Room 05.\nPayment Date: Nov 29, 2025\nAmount Paid: PHP 1,000.00\nMethod: Cash\nStatus: Partial\n\nBreakdown:\n- Base Rent: PHP 1,500.00\n- Interest: PHP 0.00\n- Utility (Water): PHP 500.00\n- Additional Charges: None\n\nTotal Bill: PHP 0.00\nRemaining Balance: PHP 0.00\nThank you for your payment!', 'sent', 'Success', 'iSms-ZxP1Rc', 200, '2025-11-29 10:46:12'),
(21, 165, 'Tenant', '639979018660', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Reminder\n\nHi Jullien Grafe,\nRoom: 05\nDue Date: Nov 29, 2025\n\nCharges:\n- Base Rent: PHP 1,500.00\n- Interest: PHP 0.00\n- Utility (Water): PHP 200.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 1,700.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'failed', 'OpenSSL SSL_connect: SSL_ERROR_SYSCALL in connection to sms.iprogtech.com:443 ', NULL, NULL, '2025-11-29 14:09:52'),
(22, 165, 'Guardian', '639171628305', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Reminder\n\nHi Jullien Grafe,\nRoom: 05\nDue Date: Nov 29, 2025\n\nCharges:\n- Base Rent: PHP 1,500.00\n- Interest: PHP 0.00\n- Utility (Water): PHP 200.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 1,700.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'failed', 'OpenSSL SSL_connect: SSL_ERROR_SYSCALL in connection to sms.iprogtech.com:443 ', NULL, NULL, '2025-11-29 14:09:52'),
(23, 165, 'Tenant', '639979018660', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Reminder\n\nHi Jullien Grafe,\nRoom: 05\nDue Date: Nov 29, 2025\n\nCharges:\n- Base Rent: PHP 1,500.00\n- Interest: PHP 0.00\n- Utility (Water): PHP 200.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 1,700.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'failed', 'Failed to connect to sms.iprogtech.com port 443 after 21048 ms: Couldn\'t connect to server', NULL, NULL, '2025-11-29 14:11:57'),
(24, 165, 'Guardian', '639171628305', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Reminder\n\nHi Jullien Grafe,\nRoom: 05\nDue Date: Nov 29, 2025\n\nCharges:\n- Base Rent: PHP 1,500.00\n- Interest: PHP 0.00\n- Utility (Water): PHP 200.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 1,700.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'failed', 'Failed to connect to sms.iprogtech.com port 443 after 3194 ms: Couldn\'t connect to server', NULL, NULL, '2025-11-29 14:12:00'),
(25, 165, 'Tenant', '639979018660', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Reminder\n\nHi Jullien Grafe,\nRoom: 05\nDue Date: Nov 29, 2025\n\nCharges:\n- Base Rent: PHP 1,500.00\n- Interest: PHP 0.00\n- Utility (Water): PHP 200.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 1,700.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', 'iSms-Xgr2df', 200, '2025-11-29 14:13:09'),
(26, 165, 'Guardian', '639171628305', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Reminder\n\nHi Jullien Grafe,\nRoom: 05\nDue Date: Nov 29, 2025\n\nCharges:\n- Base Rent: PHP 1,500.00\n- Interest: PHP 0.00\n- Utility (Water): PHP 200.00\n- Additional Charges: None\n\nTotal Amount Due: PHP 1,700.00\nPlease settle within 3 days to avoid penalties.\nThank you!', 'sent', 'Success', 'iSms-x1ExIr', 200, '2025-11-29 14:13:10'),
(27, 165, 'Tenant', '639979018660', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Confirmation\n\nHi Jullien Grafe,\nPayment received for Room 05.\nPayment Date: Nov 29, 2025\nAmount Paid: PHP 1,700.00\nMethod: Cash\nStatus: Settled\n\nBreakdown:\n- Base Rent: PHP 1,500.00\n- Interest: PHP 0.00\n- Utility (Water): PHP 200.00\n- Additional Charges: None\n\nTotal Bill: PHP 0.00\nRemaining Balance: PHP 0.00\nThank you for your payment!', 'sent', 'Success', 'iSms-3YDBcb', 200, '2025-11-29 14:14:27'),
(28, 165, 'Guardian', '639171628305', 'Ben and Sof Dormitory\nPurok 1A, Mati, San Miguel, ZDS\n\nPayment Confirmation\n\nHi Jullien Grafe,\nPayment received for Room 05.\nPayment Date: Nov 29, 2025\nAmount Paid: PHP 1,700.00\nMethod: Cash\nStatus: Settled\n\nBreakdown:\n- Base Rent: PHP 1,500.00\n- Interest: PHP 0.00\n- Utility (Water): PHP 200.00\n- Additional Charges: None\n\nTotal Bill: PHP 0.00\nRemaining Balance: PHP 0.00\nThank you for your payment!', 'sent', 'Success', 'iSms-wl265x', 200, '2025-11-29 14:14:28');

-- --------------------------------------------------------

--
-- Table structure for table `tenants`
--

CREATE TABLE `tenants` (
  `tenant_id` int(11) NOT NULL,
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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tenants`
--

INSERT INTO `tenants` (`tenant_id`, `tenant_name`, `profile_pic`, `proof_pic`, `room_id`, `deck_type`, `address`, `contact_number`, `guardian_contact`, `status`, `date_started`, `created_at`) VALUES
(128, 'Gladys Tic-ing Gwapa', 'uploads/about.jpg', '', 33, NULL, 'Kalasan Pagadian City', '09488585858', '09108484883', 'Inactive', '2025-10-05', '2025-10-05 09:04:27'),
(129, 'Julien Grafe', 'uploads/518937024_1014987657168953_4812181174251610177_n.png', '', 29, 'Lower Deck', 'Balangasan Pagadian City', '09299435309', '09294934995', 'Inactive', '2025-09-01', '2025-10-05 09:05:12'),
(130, 'Cherime Pielago', 'uploads/517091118_747251621356075_4078182769288626963_n.jpg', '', 27, 'Upper Deck', 'Balangasan Pagadian City', '09084955949', '09293848488', 'Inactive', '2025-09-01', '2025-10-05 09:05:54'),
(131, 'Gladys Diano', '', '', 27, 'Lower Deck', 'Kalasan', '09499559595', '09885858588', '', '2025-09-06', '2025-10-06 22:27:13'),
(134, 'Ovovov', '', '', 32, NULL, 'Bh', '09099999999', '09373737373', 'Inactive', '2025-10-02', '2025-10-06 22:40:28'),
(135, 'Aj Bulacan', '', '', 28, NULL, 'Kpth', '09677666777', '09677889965', 'Inactive', '2025-09-02', '2025-10-07 19:42:44'),
(136, 'Aj Bulacan', '', '', 34, NULL, 'Kalasan', '09966959599', '09998959595', 'Inactive', '2025-09-02', '2025-10-08 11:33:05'),
(137, 'Jullien Grafe', 'uploads/518937024_1014987657168953_4812181174251610177_n.png', '', 28, NULL, 'Kalasan', '09465677777', '09456666666', 'Inactive', '2025-09-02', '2025-10-08 12:16:03'),
(138, 'Gladys Mae', '', '', 35, 'Lower Deck', 'Kalasan', '09104884941', '09104884941', 'Inactive', '2025-09-14', '2025-10-12 14:26:04'),
(139, 'Glsdu', '', '', 34, NULL, 'Mllvkl', '09090808090', '09998989999', 'Inactive', '2025-10-17', '2025-10-16 15:44:15'),
(140, 'Kim', '', '', 28, NULL, 'Kpc', '09259488955', '09888999999', 'Inactive', '2025-10-02', '2025-10-16 15:46:43'),
(141, 'Joen', '', '', 35, 'Upper Deck', 'Kpc', '09899899999', '09299394949', 'Inactive', '2025-09-02', '2025-10-16 16:42:51'),
(142, 'Hana', '', '', 35, 'Lower Deck', 'Klsan', '09050803903', '09000808080', 'Active', '2025-09-01', '2025-10-16 16:57:12'),
(143, 'Dada', '', '', 35, 'Lower Deck', 'Ggg', '09575577777', '09785677565', 'Active', '2025-09-01', '2025-10-31 16:07:47'),
(144, 'Ivan Bustamante', '', '', 35, 'Upper Deck', 'Pagadian', '09497428155', '09104884941', 'Inactive', '2025-11-06', '2025-11-06 12:10:18'),
(145, 'Gladys Smith', '', '', 27, 'Upper Deck', 'Tulawas Pagadian', '09497428155', '09497428155', 'Inactive', '2025-11-06', '2025-11-06 12:33:58'),
(146, 'Christian Loyd Bacalso Lamina', '', '', 27, 'Lower Deck', 'Brgy. Mecolong, Dumalinao, Zamboanga Del Sur', '09497428155', '09497428155', 'Inactive', '2025-11-14', '2025-11-13 21:17:21'),
(147, 'Christian Loyd Bacalso Lamina', '', '', 27, 'Upper Deck', 'Brgy. Mecolong, Dumalinao, Zamboanga Del Sur', '09497428155', '09497428155', 'Inactive', '2025-11-14', '2025-11-13 21:25:10'),
(155, 'Cherime', 'uploads/518937024_1014987657168953_4812181174251610177_n.png', '', 27, 'Upper Deck', 'Clllc', '09545050505', '09044400404', 'Inactive', '2025-11-04', '2025-11-27 14:48:57'),
(156, 'Gladys Tic-ing', 'uploads/about.jpg', 'uploads/admin.png', 27, 'Lower Deck', 'Balangasan', '09776666666', '09676666666', 'Active', '2025-11-11', '2025-11-27 14:50:31'),
(157, 'Cherime Pielago', 'uploads/517091118_747251621356075_4078182769288626963_n.jpg', '', 27, 'Upper Deck', 'Jfififo', '09355050050', '09000000000', 'Inactive', '2025-11-27', '2025-11-27 14:52:56'),
(158, 'Cherime Pielago', 'uploads/517091118_747251621356075_4078182769288626963_n.jpg', '', 28, NULL, 'Balangasan', '09144402000', '09965505050', 'Inactive', '2025-11-27', '2025-11-27 14:56:32'),
(159, 'Cherime Pielago', 'uploads/517091118_747251621356075_4078182769288626963_n.jpg', '', 42, 'Lower Deck', 'Balangasan', '09667746646', '09999999999', 'Inactive', '2025-11-27', '2025-11-27 14:59:14'),
(160, 'Cherime Pielago', 'uploads/517091118_747251621356075_4078182769288626963_n.jpg', '', 43, 'Lower Deck', 'Balangasan', '09101612799', '09352523816', 'Active', '2025-11-27', '2025-11-27 15:16:59'),
(161, 'Kim', '', '', 29, NULL, 'Vkkvkv', '09395585855', '09284848484', 'Active', '2025-10-27', '2025-11-27 16:14:34'),
(162, 'Anna Rose', '', '', 27, 'Upper Deck', 'Kalasan', '09999949494', '09395995959', 'Active', '2025-10-28', '2025-11-28 21:47:57'),
(163, 'Che Pielago', '', '', 44, 'Upper Deck', 'Bayog', '09350320721', '09352523816', 'Inactive', '2008-10-21', '2025-11-29 10:32:58'),
(164, 'Che Magno', '', '', 44, 'Upper Deck', 'Bayog 12', '09350320721', '09352523816', 'Active', '2025-11-29', '2025-11-29 10:36:55'),
(165, 'Jullien Grafe', '', '', 44, 'Lower Deck', 'Balangasan', '09979018660', '09171628305', 'Active', '2025-11-29', '2025-11-29 14:09:05'),
(166, 'Dongking Maot', 'uploads/518937024_1014987657168953_4812181174251610177_n.png', '', 28, NULL, 'Kalasan', '09706060606', '09204440400', 'Inactive', '2025-12-07', '2025-12-07 12:33:42'),
(167, 'Dongking Maot', 'uploads/518937024_1014987657168953_4812181174251610177_n.png', '', 35, '', 'Kalasan', '09706060606', '09204440400', 'Active', '2025-12-07', '2025-12-07 12:33:42'),
(168, 'Dongking Maot', 'uploads/download.jpg', '', 28, NULL, 'Kalasan', '09099999999', '09788888888', 'Active', '2025-12-07', '2025-12-07 13:06:20'),
(169, 'Dongking Maot', 'uploads/download.jpg', '', 43, '', 'Kalasan', '09099999999', '09788888888', 'Active', '2025-12-07', '2025-12-07 13:06:20');

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
  MODIFY `bill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=458;

--
-- AUTO_INCREMENT for table `billing_additional_items`
--
ALTER TABLE `billing_additional_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `billing_utility_items`
--
ALTER TABLE `billing_utility_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `room_additional_descriptions`
--
ALTER TABLE `room_additional_descriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `sms_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `tenants`
--
ALTER TABLE `tenants`
  MODIFY `tenant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
