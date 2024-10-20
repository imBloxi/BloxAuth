-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql203.byetcluster.com
-- Generation Time: Oct 16, 2024 at 01:00 PM
-- Server version: 10.6.19-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_37454137_1_licence`
--

-- --------------------------------------------------------

--
-- Table structure for table `anomaly_logs`
--

CREATE TABLE `anomaly_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `api_keys`
--

INSERT INTO `api_keys` (`id`, `user_id`, `api_key`, `created_at`) VALUES
(1, 16, 'b21b0822c1a7730f8d02aed0565f8ca5d6a07c54c01f178409386d3ad75fafef', '2024-10-06 07:56:20'),
(2, 1, 'b8f6a5bb0bf027ec273e5013890fe465c0005eec9751bd32d4227688777c532d', '2024-10-14 16:56:48');

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `application_text` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `credits_used` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `billing_settings`
--

CREATE TABLE `billing_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `use_robux` tinyint(1) DEFAULT 0,
  `use_crypto` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `custom_tiers`
--

CREATE TABLE `custom_tiers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tier_name` varchar(255) NOT NULL,
  `tier_benefits` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `custom_tiers`
--

INSERT INTO `custom_tiers` (`id`, `user_id`, `tier_name`, `tier_benefits`, `created_at`) VALUES
(1, 1, 'test', 'test', '2024-10-14 16:43:46');

-- --------------------------------------------------------

--
-- Table structure for table `licenses`
--

CREATE TABLE `licenses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `roblox_id` varchar(255) NOT NULL,
  `place_id` varchar(255) DEFAULT NULL,
  `key` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `whitelist_type` enum('user','group','place') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `licenses`
--

INSERT INTO `licenses` (`id`, `user_id`, `roblox_id`, `place_id`, `key`, `created_at`, `whitelist_type`) VALUES
(4, 4, '1747293', '429473', 'a631d7109c9d6f7750a8c7ebbc4bb1d0', '2024-07-07 08:40:41', 'user'),
(5, 1, '2629862208', '', '2c4d5129260194433ce58f1bcf094710', '2024-07-07 11:20:28', 'user');

-- --------------------------------------------------------

--
-- Table structure for table `licenses_new`
--

CREATE TABLE `licenses_new` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `key` varchar(255) NOT NULL,
  `whitelist_id` varchar(255) DEFAULT NULL,
  `whitelist_type` enum('user','group','place') NOT NULL DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `description` text DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `roblox_user_id` bigint(20) DEFAULT NULL,
  `max_uses` int(11) DEFAULT NULL,
  `current_uses` int(11) DEFAULT 0,
  `last_used` datetime DEFAULT NULL,
  `expiry_notification_sent` tinyint(1) DEFAULT 0,
  `transferable` tinyint(1) DEFAULT 0,
  `renewal_date` date DEFAULT NULL,
  `is_banned` tinyint(1) DEFAULT 0,
  `ban_reason` text DEFAULT NULL,
  `custom_tier` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `licenses_new`
--

INSERT INTO `licenses_new` (`id`, `user_id`, `key`, `whitelist_id`, `whitelist_type`, `created_at`, `description`, `valid_until`, `roblox_user_id`, `max_uses`, `current_uses`, `last_used`, `expiry_notification_sent`, `transferable`, `renewal_date`, `is_banned`, `ban_reason`, `custom_tier`) VALUES
(1, 1, '3766ae791d69982825f1f0ba14f1065c', '123', 'user', '2024-07-08 06:15:37', NULL, NULL, NULL, NULL, 0, NULL, 0, 0, NULL, 0, NULL, NULL),
(2, 1, 'b86bf7d888cae19011080ebaa116a942', '123', 'user', '2024-07-08 06:19:28', NULL, NULL, NULL, NULL, 0, NULL, 0, 0, NULL, 0, NULL, NULL),
(3, 1, '3072d18b4296d2b495e19b1e839e133c', '123', 'user', '2024-07-08 06:21:36', NULL, NULL, NULL, NULL, 0, NULL, 0, 0, NULL, 0, NULL, NULL),
(4, 1, '21a71db5fb4cc102af082a44ac2dec47', '55314214', 'place', '2024-07-08 06:37:13', NULL, NULL, NULL, NULL, 0, NULL, 0, 0, NULL, 0, NULL, NULL),
(5, 1, '75d7b9693fa0f4333d67ccc9573b9736', '55314214', 'group', '2024-07-08 06:40:04', NULL, NULL, NULL, NULL, 0, NULL, 0, 0, NULL, 0, NULL, NULL),
(6, 1, 'a2d10776659ec5732bdd10de281de07b', '55314214', 'group', '2024-07-08 06:41:20', NULL, NULL, NULL, NULL, 0, NULL, 0, 0, NULL, 0, NULL, NULL),
(7, 1, '42523127524d092c030122b5e49a44da', '72124623', 'user', '2024-07-08 06:41:24', NULL, NULL, NULL, NULL, 0, NULL, 0, 0, NULL, 0, NULL, NULL),
(8, 1, 'b33f6da9a6e95278eef41363249d1188', '123', 'user', '2024-07-08 06:47:18', NULL, NULL, NULL, NULL, 0, NULL, 0, 0, NULL, 0, NULL, NULL),
(9, 1, '760cbe54af1aad3dded060b79a2131f4', '124124214214', 'user', '2024-07-08 06:47:26', NULL, NULL, NULL, NULL, 0, NULL, 0, 0, NULL, 0, NULL, NULL),
(10, 1, '7cb04af21c8c7d40ee7790ff1f5eba08', '123', 'place', '2024-07-08 06:51:26', NULL, NULL, NULL, NULL, 0, NULL, 0, 0, NULL, 0, NULL, NULL),
(11, 5, '538891e32efcad23b9b572c813103779', '', 'group', '2024-07-08 14:26:05', NULL, NULL, NULL, NULL, 0, NULL, 0, 0, NULL, 0, NULL, NULL),
(12, 4, 'd9f86838fa0a9893927941c11530539e', '88837428', 'user', '2024-07-09 11:54:20', NULL, NULL, NULL, NULL, 0, NULL, 0, 0, NULL, 0, NULL, NULL),
(13, 1, '9187d13b321aaf8a5ddf57e5fdcf1fbf', '2629862208', 'user', '2024-07-09 18:25:02', NULL, NULL, NULL, NULL, 0, NULL, 0, 0, NULL, 0, NULL, NULL),
(14, 16, '2c7795022389222febcf94efd8f0e8b7', '2', 'user', '2024-10-06 07:56:15', '123123', '2025-02-20 14:50:00', 4811094431, NULL, 0, NULL, 0, 0, NULL, 0, NULL, NULL),
(16, 16, '4e87cd881470ddf1377cfd21dd556c63', NULL, 'group', '2024-10-06 08:22:00', '2ywtrtt', '2024-11-01 11:24:00', 12421512133, 2147483647, 0, NULL, 0, 0, NULL, 0, NULL, NULL),
(17, 1, '04c595d72d066c9b77d1028351c9762c', '', 'user', '2024-10-14 16:46:58', 'ewgsdg', '2024-10-12 21:43:00', 12412451252154, 0, 0, NULL, 0, 1, NULL, 0, NULL, 'test'),
(18, 1, 'c3cfabee9f033e50792c4a26f937317b', '', 'user', '2024-10-14 16:47:02', 'ewgsdg', '2024-10-12 21:43:00', 12412451252154, 0, 0, NULL, 0, 1, NULL, 0, NULL, 'test'),
(19, 1, '9088b0d286018ccd5f0bd8a5f0d9dadc', '', 'user', '2024-10-14 16:50:42', 'ewgsdg', '2024-10-12 21:43:00', 12412451252154, 0, 0, NULL, 0, 1, NULL, 0, NULL, 'test'),
(20, 1, 'd14d3249f7a2729ea16ff8d35f06fe26', '', 'user', '2024-10-14 16:50:59', '123', '2024-10-11 19:50:00', 1243, 0, 0, NULL, 0, 0, NULL, 1, 'hh', 'test'),
(21, 1, '08675214657979d96bacba2c28d9cd1e', '', 'user', '2024-10-14 16:54:30', '215521215215', '2024-10-11 19:54:00', 21512215215215, 0, 0, NULL, 0, 1, NULL, 0, NULL, 'test');

-- --------------------------------------------------------

--
-- Table structure for table `license_custom_fields`
--

CREATE TABLE `license_custom_fields` (
  `id` int(11) NOT NULL,
  `license_id` int(11) NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `field_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `license_history`
--

CREATE TABLE `license_history` (
  `id` int(11) NOT NULL,
  `license_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `license_keys`
--

CREATE TABLE `license_keys` (
  `id` int(11) NOT NULL,
  `license_key` varchar(255) NOT NULL,
  `issued_to` int(11) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'inactive',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `license_keys`
--

INSERT INTO `license_keys` (`id`, `license_key`, `issued_to`, `status`, `created_at`) VALUES
(1, 'a3-0234-a1-93c520-c5-2742', NULL, 'inactive', '2024-10-05 17:40:17'),
(2, 'd0-bc94-0c-7c2a79-b6-536b', NULL, 'inactive', '2024-10-05 17:47:47'),
(3, '00-aea6-a9-f608b3-b7-eabf', NULL, 'inactive', '2024-10-05 17:47:48'),
(4, '27-4759-46-7065ba-53-352a', NULL, 'inactive', '2024-10-05 17:47:48'),
(5, 'e0-de73-d0-5fdd38-a3-bfe3', NULL, 'inactive', '2024-10-05 17:47:49'),
(6, 'a7-5d35-03-981d95-b1-2a44', NULL, 'inactive', '2024-10-05 17:47:49'),
(7, 'd1-25ab-ab-78924a-33-f4de', NULL, 'inactive', '2024-10-05 17:47:49'),
(8, '7c-f6fd-b0-22abc3-28-637a', NULL, 'inactive', '2024-10-05 17:47:50'),
(9, 'b7-0cde-6e-e6b481-92-17e3', NULL, 'inactive', '2024-10-05 17:47:50'),
(10, '19-c45d-7f-0df69f-7b-4f62', NULL, 'inactive', '2024-10-05 17:47:50'),
(11, 'ba-f0a9-e0-809a06-30-e22b', NULL, 'inactive', '2024-10-05 17:47:50'),
(12, '88-a73f-7c-77e3d2-57-c031', NULL, 'inactive', '2024-10-05 18:25:25'),
(13, '29-494d-df-c6d3cb-4c-56ce', NULL, 'inactive', '2024-10-05 18:25:27');

-- --------------------------------------------------------

--
-- Table structure for table `license_logs`
--

CREATE TABLE `license_logs` (
  `id` int(11) NOT NULL,
  `license_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `action` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `license_script_versions`
--

CREATE TABLE `license_script_versions` (
  `license_id` int(11) NOT NULL,
  `script_version_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `license_transfers`
--

CREATE TABLE `license_transfers` (
  `id` int(11) NOT NULL,
  `license_id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL,
  `to_user_id` int(11) NOT NULL,
  `transfer_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_id`, `ip_address`, `created_at`) VALUES
(1, 1, '78.84.76.155', '2024-06-16 19:00:13'),
(2, 1, '78.84.76.155', '2024-06-16 19:12:17'),
(3, 1, '78.84.76.155', '2024-06-16 19:20:25'),
(4, 1, '77.219.8.15', '2024-06-17 07:44:04'),
(5, 4, '77.219.1.233', '2024-07-07 08:38:37'),
(6, 4, '77.219.1.233', '2024-07-07 09:08:42'),
(7, 1, '95.68.121.247', '2024-07-07 11:07:50'),
(8, 1, '95.68.121.247', '2024-07-07 11:19:44'),
(9, 1, '95.68.121.247', '2024-07-07 11:41:34'),
(10, 1, '95.68.121.247', '2024-07-07 18:44:11'),
(11, 1, '95.68.121.247', '2024-07-07 18:48:21'),
(12, 1, '95.68.121.247', '2024-07-07 18:57:33'),
(13, 1, '95.68.121.247', '2024-07-08 06:06:28'),
(14, 1, '95.68.121.247', '2024-07-08 07:53:44'),
(15, 1, '95.68.121.247', '2024-07-08 09:06:52'),
(16, 1, '95.68.121.247', '2024-07-08 10:45:57'),
(17, 5, '72.243.55.125', '2024-07-08 14:24:43'),
(18, 1, '95.68.121.247', '2024-07-08 14:25:32'),
(19, 5, '72.243.55.125', '2024-07-08 14:29:27'),
(20, 1, '95.68.121.247', '2024-07-08 14:30:35'),
(21, 4, '77.219.3.12', '2024-07-08 15:17:52'),
(22, 4, '95.68.121.247', '2024-07-08 16:19:09'),
(23, 1, '95.68.121.247', '2024-07-09 07:31:38'),
(24, 4, '77.219.3.12', '2024-07-09 11:53:37'),
(25, 12, '170.247.238.188', '2024-07-09 12:00:12'),
(26, 12, '170.247.238.188', '2024-07-09 12:38:21'),
(27, 12, '170.247.238.188', '2024-07-09 12:38:22'),
(28, 1, '95.68.121.247', '2024-07-09 12:41:17'),
(29, 13, '95.68.121.247', '2024-07-09 12:53:54'),
(30, 1, '95.68.121.247', '2024-07-09 13:00:24'),
(31, 14, '170.247.238.188', '2024-07-09 13:03:31'),
(32, 14, '170.247.238.188', '2024-07-09 13:07:05'),
(33, 1, '95.68.121.247', '2024-07-09 13:10:15'),
(34, 1, '95.68.121.247', '2024-07-09 16:10:18'),
(35, 1, '95.68.121.247', '2024-07-09 16:10:42'),
(36, 14, '170.247.238.188', '2024-07-09 16:15:59'),
(37, 1, '95.68.121.247', '2024-07-09 18:02:35'),
(38, 5, '72.243.55.125', '2024-07-10 09:45:51'),
(39, 4, '149.34.244.181', '2024-07-10 09:56:05'),
(40, 4, '149.34.244.181', '2024-07-10 09:56:05'),
(41, 4, '77.219.4.191', '2024-07-10 10:12:16'),
(42, 15, '72.243.55.125', '2024-07-10 10:14:50'),
(43, 15, '72.243.55.125', '2024-07-10 10:20:03'),
(44, 1, '::1', '2024-10-05 17:39:56'),
(45, 16, '::1', '2024-10-06 07:21:52'),
(46, 16, '::1', '2024-10-06 07:22:11'),
(47, 16, '::1', '2024-10-06 09:00:47'),
(48, 16, '::1', '2024-10-06 09:04:39'),
(49, 1, '::1', '2024-10-06 09:17:06'),
(50, 17, '::1', '2024-10-06 10:15:47'),
(51, 16, '::1', '2024-10-08 17:54:25'),
(52, 1, '109.236.81.173', '2024-10-14 12:59:16'),
(53, 1, '109.236.81.173', '2024-10-14 13:05:00'),
(54, 18, '78.62.45.164', '2024-10-14 16:23:56'),
(55, 1, '93.190.138.195', '2024-10-16 14:44:52');

-- --------------------------------------------------------

--
-- Table structure for table `moderation_logs`
--

CREATE TABLE `moderation_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `moderator_id` int(11) NOT NULL,
  `action` enum('suspend','ban','unban','warn') NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `created_at`) VALUES
(1, 1, 'New license key created: a2d10776659ec5732bdd10de281de07b', '2024-07-08 06:41:20'),
(2, 1, 'New license key created: 42523127524d092c030122b5e49a44da', '2024-07-08 06:41:24'),
(3, 1, 'New license key created: b33f6da9a6e95278eef41363249d1188', '2024-07-08 06:47:18'),
(4, 1, 'New license key created: 760cbe54af1aad3dded060b79a2131f4', '2024-07-08 06:47:26'),
(5, 1, 'New license key created: 7cb04af21c8c7d40ee7790ff1f5eba08', '2024-07-08 06:51:26'),
(6, 5, 'New license key created: 538891e32efcad23b9b572c813103779', '2024-07-08 14:26:05'),
(7, 4, 'New license key created: d9f86838fa0a9893927941c11530539e', '2024-07-09 11:54:20'),
(8, 1, 'New license key created: 9187d13b321aaf8a5ddf57e5fdcf1fbf', '2024-07-09 18:25:02');

-- --------------------------------------------------------

--
-- Table structure for table `notifications_3`
--

CREATE TABLE `notifications_3` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` enum('info','warning','error') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `obfuscations`
--

CREATE TABLE `obfuscations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `script` text NOT NULL,
  `obfuscated_script` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `obfuscations`
--

INSERT INTO `obfuscations` (`id`, `user_id`, `script`, `obfuscated_script`, `created_at`) VALUES
(1, 1, 'local HttpService = game:GetService(\"HttpService\")\r\nlocal Players = game:GetService(\"Players\")\r\nlocal User_License_Key = \'YOUR_LICENSE_KEY_HERE\'\r\n\r\n-- Function to validate the license key\r\nlocal function validate_license()\r\n    local player = Players.LocalPlayer\r\n    local roblox_id = player.UserId\r\n    local place_id = game.PlaceId\r\n    local is_https = string.sub(game:GetService(\"HttpService\").Url, 1, 5) == \"https\"\r\n\r\n    if not is_https then\r\n        player:Kick(\"This place does not have HTTPS enabled.\")\r\n        return\r\n    end\r\n\r\n    local url = \"https://your-domain.com/api/validate_key.php\"\r\n    local data = {\r\n        license_key = User_License_Key,\r\n        roblox_id = tostring(roblox_id),\r\n        place_id = tostring(place_id)\r\n    }\r\n    \r\n    local jsonData = HttpService:JSONEncode(data)\r\n    local response = HttpService:PostAsync(url, jsonData, Enum.HttpContentType.ApplicationJson)\r\n    \r\n    local result = HttpService:JSONDecode(response)\r\n    \r\n    if result.status == \"success\" then\r\n        print(\"License validated successfully.\")\r\n    else\r\n        player:Kick(\"License validation failed: \" .. result.message)\r\n    end\r\nend\r\n\r\nvalidate_license()\r\n', 'local User_License_Key = \'123\'\n\n\r)(esnecil_etadilav\n\r\n\rdne\n\rdne    \n\r)egassem.tluser .. \" :deliaf noitadilav esneciL\"(kciK:reyalp        \n\resle    \n\r)\".yllufsseccus detadilav esneciL\"(tnirp        \n\rneht \"sseccus\" == sutats.tluser fi    \n\r    \n\r)esnopser(edoceDNOSJ:ecivreSpttH = tluser lacol    \n\r    \n\r)nosJnoitacilppA.epyTtnetnoCpttH.munE ,ataDnosj ,lru(cnysAtsoP:ecivreSpttH = esnopser lacol    \n\r)atad(edocnENOSJ:ecivreSpttH = ataDnosj lacol    \n\r    \n\r}    \n\r)di_ecalp(gnirtsot = di_ecalp        \n\r,)di_xolbor(gnirtsot = di_xolbor        \n\r,yeK_esneciL_resU = yek_esnecil        \n\r{ = atad lacol    \n\r\"php.yek_etadilav/ipa/moc.niamod-ruoy//:sptth\" = lru lacol    \n\r\n\rdne    \n\rnruter        \n\r)\".delbane SPTTH evah ton seod ecalp sihT\"(kciK:reyalp        \n\rneht sptth_si ton fi    \n\r\n\r\"sptth\" == )5 ,1 ,lrU.)\"ecivreSpttH\"(ecivreSteG:emag(bus.gnirts = sptth_si lacol    \n\rdIecalP.emag = di_ecalp lacol    \n\rdIresU.reyalp = di_xolbor lacol    \n\rreyalPlacoL.sreyalP = reyalp lacol    \n\r)(esnecil_etadilav noitcnuf lacol\n\ryek esnecil eht etadilav ot noitcnuF --\n\r\n\r\'EREH_YEK_ESNECIL_RUOY\' = yeK_esneciL_resU lacol\n\r)\"sreyalP\"(ecivreSteG:emag = sreyalP lacol\n\r)\"ecivreSpttH\"(ecivreSteG:emag = ecivreSpttH lacol', '2024-06-16 08:02:24'),
(2, 3, '\r\nlocal HttpService = game:GetService(\"HttpService\")\r\nlocal Players = game:GetService(\"Players\")\r\nlocal User_License_Key = \'YOUR_LICENSE_KEY_HERE\'\r\n\r\n-- Function to validate the license key\r\nlocal function validate_license()\r\n    local player = Players.LocalPlayer\r\n    local roblox_id = player.UserId\r\n    local place_id = game.PlaceId\r\n    local is_https = string.sub(game:GetService(\"HttpService\").Url, 1, 5) == \"https\"\r\n\r\n    if not is_https then\r\n        player:Kick(\"This place does not have HTTPS enabled.\")\r\n        return\r\n    end\r\n\r\n    local url = \"https://your-domain.com/api/validate_key.php\"\r\n    local data = {\r\n        license_key = User_License_Key,\r\n        roblox_id = tostring(roblox_id),\r\n        place_id = tostring(place_id)\r\n    }\r\n    \r\n    local jsonData = HttpService:JSONEncode(data)\r\n    local response = HttpService:PostAsync(url, jsonData, Enum.HttpContentType.ApplicationJson)\r\n    \r\n    local result = HttpService:JSONDecode(response)\r\n    \r\n    if result.status == \"success\" then\r\n        print(\"License validated successfully.\")\r\n    else\r\n        player:Kick(\"License validation failed: \" .. result.message)\r\n    end\r\nend\r\n\r\nvalidate_license()\r\n\r\n', 'local User_License_Key = \'8b3c70eadb52b8a471cd46c55ac48af1\'\n\n\r\n\r)(esnecil_etadilav\n\r\n\rdne\n\rdne    \n\r)egassem.tluser .. \" :deliaf noitadilav esneciL\"(kciK:reyalp        \n\resle    \n\r)\".yllufsseccus detadilav esneciL\"(tnirp        \n\rneht \"sseccus\" == sutats.tluser fi    \n\r    \n\r)esnopser(edoceDNOSJ:ecivreSpttH = tluser lacol    \n\r    \n\r)nosJnoitacilppA.epyTtnetnoCpttH.munE ,ataDnosj ,lru(cnysAtsoP:ecivreSpttH = esnopser lacol    \n\r)atad(edocnENOSJ:ecivreSpttH = ataDnosj lacol    \n\r    \n\r}    \n\r)di_ecalp(gnirtsot = di_ecalp        \n\r,)di_xolbor(gnirtsot = di_xolbor        \n\r,yeK_esneciL_resU = yek_esnecil        \n\r{ = atad lacol    \n\r\"php.yek_etadilav/ipa/moc.niamod-ruoy//:sptth\" = lru lacol    \n\r\n\rdne    \n\rnruter        \n\r)\".delbane SPTTH evah ton seod ecalp sihT\"(kciK:reyalp        \n\rneht sptth_si ton fi    \n\r\n\r\"sptth\" == )5 ,1 ,lrU.)\"ecivreSpttH\"(ecivreSteG:emag(bus.gnirts = sptth_si lacol    \n\rdIecalP.emag = di_ecalp lacol    \n\rdIresU.reyalp = di_xolbor lacol    \n\rreyalPlacoL.sreyalP = reyalp lacol    \n\r)(esnecil_etadilav noitcnuf lacol\n\ryek esnecil eht etadilav ot noitcnuF --\n\r\n\r\'EREH_YEK_ESNECIL_RUOY\' = yeK_esneciL_resU lacol\n\r)\"sreyalP\"(ecivreSteG:emag = sreyalP lacol\n\r)\"ecivreSpttH\"(ecivreSteG:emag = ecivreSpttH lacol\n\r', '2024-06-16 09:31:27');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL,
  `status` varchar(20) NOT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_logs`
--

CREATE TABLE `payment_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `roblox_username` varchar(255) NOT NULL,
  `payment_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registration_attempts`
--

CREATE TABLE `registration_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registration_attempts`
--

INSERT INTO `registration_attempts` (`id`, `ip_address`, `attempt_time`) VALUES
(1, '72.243.55.125', '2024-07-10 10:14:25');

-- --------------------------------------------------------

--
-- Table structure for table `roblox_accounts`
--

CREATE TABLE `roblox_accounts` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `roblox_username` varchar(255) NOT NULL,
  `linked_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roblox_accounts`
--

INSERT INTO `roblox_accounts` (`id`, `user_id`, `roblox_username`, `linked_at`) VALUES
(2629862208, 1, 'BloxiSkid\r\n', '2024-07-09 16:17:58');

-- --------------------------------------------------------

--
-- Table structure for table `scripts`
--

CREATE TABLE `scripts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `active_version_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `script_versions`
--

CREATE TABLE `script_versions` (
  `id` int(11) NOT NULL,
  `script_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sellix_orders`
--

CREATE TABLE `sellix_orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sellix_order_id` varchar(255) NOT NULL,
  `sellix_product_id` varchar(255) NOT NULL,
  `license_id` int(11) DEFAULT NULL,
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sellix_products`
--

CREATE TABLE `sellix_products` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sellix_product_id` varchar(255) NOT NULL,
  `license_type` varchar(255) NOT NULL,
  `custom_tier_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_levels`
--

CREATE TABLE `subscription_levels` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `api_rate_limit` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_tiers`
--

CREATE TABLE `subscription_tiers` (
  `id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `credits` decimal(10,2) NOT NULL,
  `benefits` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_tiers`
--

INSERT INTO `subscription_tiers` (`id`, `name`, `price`, `credits`, `benefits`) VALUES
(1, 'Basic', '5.00', '10.00', 'Basic support'),
(2, 'Pro', '10.00', '25.00', 'Priority support, 1 free license'),
(3, 'Enterprise', '20.00', '60.00', '24/7 support, 3 free licenses, custom obfuscation');

-- --------------------------------------------------------

--
-- Table structure for table `tos_agreements`
--

CREATE TABLE `tos_agreements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `agreed_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `usage_logs`
--

CREATE TABLE `usage_logs` (
  `id` int(11) NOT NULL,
  `license_id` int(11) DEFAULT NULL,
  `roblox_id` varchar(255) DEFAULT NULL,
  `place_id` varchar(255) DEFAULT NULL,
  `success` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `passkey` varchar(255) DEFAULT NULL,
  `app_name` varchar(255) DEFAULT NULL,
  `seller_id` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `credits` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_staff` tinyint(1) DEFAULT 0,
  `two_fa_enabled` tinyint(1) DEFAULT 0,
  `discord_username` varchar(255) DEFAULT NULL,
  `privacy_settings` varchar(255) DEFAULT 'public',
  `is_banned` tinyint(1) DEFAULT 0,
  `ban_reason` varchar(255) DEFAULT NULL,
  `gamepass_claimed` tinyint(1) DEFAULT 0,
  `license_key` varchar(255) DEFAULT NULL,
  `is_suspended` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `delete_reason` text DEFAULT NULL,
  `trial_credits` decimal(10,2) DEFAULT 20.00,
  `trial_start_date` date DEFAULT NULL,
  `subscription_tier` varchar(20) DEFAULT NULL,
  `subscription_end_date` date DEFAULT NULL,
  `login_alerts` tinyint(1) DEFAULT 0,
  `ip_whitelist` text DEFAULT NULL,
  `two_fa_secret` varchar(32) DEFAULT NULL,
  `default_license_duration` int(11) DEFAULT 30,
  `auto_renew_licenses` tinyint(1) DEFAULT 0,
  `discord_id` varchar(255) DEFAULT NULL,
  `email_notifications` tinyint(1) DEFAULT 0,
  `suspend_reason` text DEFAULT NULL,
  `discord_webhook_url` varchar(255) DEFAULT NULL,
  `sellix_webhook_secret` varchar(255) DEFAULT NULL,
  `profile_visibility` enum('public','private','friends_only') DEFAULT 'public',
  `allow_friend_requests` tinyint(1) DEFAULT 1,
  `show_online_status` tinyint(1) DEFAULT 1,
  `show_activity_status` tinyint(1) DEFAULT 1,
  `allow_email_notifications` tinyint(1) DEFAULT 1,
  `allow_push_notifications` tinyint(1) DEFAULT 1,
  `two_factor_auth_enabled` tinyint(1) DEFAULT 0,
  `sellix_api_key` varchar(255) DEFAULT NULL,
  `sellix_store_id` varchar(255) DEFAULT NULL,
  `api_rate_limit` int(11) DEFAULT 60,
  `api_logging_enabled` tinyint(1) DEFAULT 0,
  `sellix_auto_fulfill` tinyint(1) DEFAULT 0,
  `notify_on_new_license` tinyint(1) DEFAULT 1,
  `notify_on_license_expiry` tinyint(1) DEFAULT 1,
  `log_login_activity` tinyint(1) DEFAULT 1,
  `log_license_usage` tinyint(1) DEFAULT 1,
  `activity_retention` int(11) DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `passkey`, `app_name`, `seller_id`, `email`, `credits`, `created_at`, `is_staff`, `two_fa_enabled`, `discord_username`, `privacy_settings`, `is_banned`, `ban_reason`, `gamepass_claimed`, `license_key`, `is_suspended`, `is_deleted`, `delete_reason`, `trial_credits`, `trial_start_date`, `subscription_tier`, `subscription_end_date`, `login_alerts`, `ip_whitelist`, `two_fa_secret`, `default_license_duration`, `auto_renew_licenses`, `discord_id`, `email_notifications`, `suspend_reason`, `discord_webhook_url`, `sellix_webhook_secret`, `profile_visibility`, `allow_friend_requests`, `show_online_status`, `show_activity_status`, `allow_email_notifications`, `allow_push_notifications`, `two_factor_auth_enabled`, `sellix_api_key`, `sellix_store_id`, `api_rate_limit`, `api_logging_enabled`, `sellix_auto_fulfill`, `notify_on_new_license`, `notify_on_license_expiry`, `log_login_activity`, `log_license_usage`, `activity_retention`) VALUES
(1, 'te', '$2y$10$VBUwJhz6jA/lQWdbBdVqnOCal3Fr2pGewzvdAzhu73OXMI7hfGOsm', 'rr', 'test', 'a3d6773b4a015a97', 'te@gmail.copm', 1212, '2024-06-16 07:59:57', 1, 0, '<!DOCTYPE html>', 'private', 0, 'Account suspended due to unpaid debt', 1, NULL, 0, 0, NULL, '20.00', NULL, NULL, NULL, 0, NULL, NULL, 30, 0, NULL, 0, NULL, NULL, NULL, 'public', 1, 1, 1, 1, 1, 0, NULL, NULL, 60, 0, 0, 1, 1, 1, 1, 30),
(2, 'bigfart69', '$2y$10$ZPkLVLxlOlslJwkUKQAysu8nNq.ewrn.qxbS6zGTolLUx8QrchIiK', NULL, NULL, NULL, 'nothing@gmail.com', 138, '2024-06-16 08:00:48', 0, 0, NULL, 'public', 0, NULL, 0, NULL, 0, 0, NULL, '20.00', NULL, NULL, NULL, 0, NULL, NULL, 30, 0, NULL, 0, NULL, NULL, NULL, 'public', 1, 1, 1, 1, 1, 0, NULL, NULL, 60, 0, 0, 1, 1, 1, 1, 30),
(3, 'BloxiSkid', '$2y$10$2gn4tmj3aPI.v4frTkRC2eoSsM23kCkBUbQzekG.DFclyrMB0bHFC', NULL, NULL, NULL, 'BloxiSkid@protonmail.com', -1, '2024-06-16 09:29:32', 0, 0, NULL, 'public', 0, NULL, 0, NULL, 0, 0, NULL, '20.00', NULL, NULL, NULL, 0, NULL, NULL, 30, 0, NULL, 0, NULL, NULL, NULL, 'public', 1, 1, 1, 1, 1, 0, NULL, NULL, 60, 0, 0, 1, 1, 1, 1, 30),
(4, 'WW', '$2y$10$v1sk9DxbwOnypW3nOgMZneVglzclrYvmWpUlUfmzCOI7xBXfMn6bC', 'R', 'Yy', 'bb19b6a03040a94f', 'ww@gmail.com', 69696964, '2024-07-07 08:38:15', 1, 0, NULL, 'public', 0, NULL, 0, NULL, 0, 0, NULL, '20.00', NULL, NULL, NULL, 0, NULL, NULL, 30, 0, NULL, 0, NULL, NULL, NULL, 'public', 1, 1, 1, 1, 1, 0, NULL, NULL, 60, 0, 0, 1, 1, 1, 1, 30),
(5, 'Reaper', '$2y$10$kl5DIkTOpPqleE/NEgVvjemeJFajONH6sgNcStGW8Cvr3U0za57iu', NULL, 'RReaper', '172713835f0a52a0', 'FuckBloxii@gmail.com', 69696968, '2024-07-07 08:44:36', 1, 0, '', 'private', 0, NULL, 0, NULL, 0, 0, NULL, '20.00', NULL, NULL, NULL, 0, NULL, NULL, 30, 0, NULL, 0, NULL, NULL, NULL, 'public', 1, 1, 1, 1, 1, 0, NULL, NULL, 60, 0, 0, 1, 1, 1, 1, 30),
(7, 'Xiao Ling', '$2y$10$QgKLtWGxPfubQDkvlQURPuDhnTEJvYpE0rqXexc/J5e8P/fIvYzYm', NULL, NULL, NULL, 'XIaolingregister@gmail.com', 10, '2024-07-08 10:35:53', 0, 0, NULL, 'public', 0, '', 0, NULL, 0, 0, NULL, '20.00', NULL, NULL, NULL, 0, NULL, NULL, 30, 0, NULL, 0, NULL, NULL, NULL, 'public', 1, 1, 1, 1, 1, 0, NULL, NULL, 60, 0, 0, 1, 1, 1, 1, 30),
(8, 'osmanosmanman', '$2y$10$LpsdjkAa77q9DE0w9GglR.5TnhsZcHjcasXrwPrjUlfLSS/nz9WRe', NULL, NULL, NULL, 'kingsternigga@gmail.com', 0, '2024-07-08 10:42:41', 0, 0, NULL, 'public', 0, NULL, 0, NULL, 0, 0, NULL, '20.00', NULL, NULL, NULL, 0, NULL, NULL, 30, 0, NULL, 0, NULL, NULL, NULL, 'public', 1, 1, 1, 1, 1, 0, NULL, NULL, 60, 0, 0, 1, 1, 1, 1, 30),
(9, 'osmanjeosman', '$2y$10$G97RORkNacMV6p24Z/OsCem/fSHNanoeMRJGPJgQHYsjSVFxnrWwG', NULL, NULL, NULL, 'kingstapigga@gmail.com', 0, '2024-07-08 10:43:29', 0, 0, NULL, 'public', 0, NULL, 0, NULL, 0, 0, NULL, '20.00', NULL, NULL, NULL, 0, NULL, NULL, 30, 0, NULL, 0, NULL, NULL, NULL, 'public', 1, 1, 1, 1, 1, 0, NULL, NULL, 60, 0, 0, 1, 1, 1, 1, 30),
(10, 'te1', '$2y$10$.zhg5pfeN2dVa6yyLsUL5unYWhXPYQqJM3.yVcgs5Qxu/jAoCcaW.', NULL, NULL, NULL, 'anonymous.angel.annoucment@gmail.com', 0, '2024-07-08 14:21:28', 0, 0, NULL, 'public', 0, NULL, 0, NULL, 0, 0, NULL, '20.00', NULL, NULL, NULL, 0, NULL, NULL, 30, 0, NULL, 0, NULL, NULL, NULL, 'public', 1, 1, 1, 1, 1, 0, NULL, NULL, 60, 0, 0, 1, 1, 1, 1, 30),
(11, 'CombineInteligenceAgentShadow', '$2y$10$D9G3edU9ZJiCcyd0eWHNYuj7FWDcGNaTHPz6/AjHs9ZGRaHGyEika', NULL, NULL, NULL, 'Osirsaredumbers@gmail.com', 0, '2024-07-09 11:58:45', 0, 0, NULL, 'public', 0, NULL, 0, NULL, 0, 0, NULL, '20.00', NULL, NULL, NULL, 0, NULL, NULL, 30, 0, NULL, 0, NULL, NULL, NULL, 'public', 1, 1, 1, 1, 1, 0, NULL, NULL, 60, 0, 0, 1, 1, 1, 1, 30),
(12, 'CombineSecretiveShadow', '$2y$10$bZkHuBsYSdWvqVcju6OrA.KqsF1ktSCwskveNOj5VH1qs2s0Cd6EW', NULL, NULL, NULL, 'mkzgabriel@gmail.com', 0, '2024-07-09 11:59:44', 0, 0, NULL, 'public', 0, NULL, 0, NULL, 0, 0, NULL, '20.00', NULL, NULL, NULL, 0, NULL, NULL, 30, 0, NULL, 0, NULL, NULL, NULL, 'public', 1, 1, 1, 1, 1, 0, NULL, NULL, 60, 0, 0, 1, 1, 1, 1, 30),
(13, 'HHH', '$2y$10$9Nginm.aoerhJl3LWGBh2e9DATBzjKPivbGvLK8iqy9r7GqYyoLQ6', NULL, NULL, NULL, 'admin@lookshance.com', 0, '2024-07-09 12:53:48', 0, 0, NULL, 'public', 0, NULL, 0, NULL, 0, 0, NULL, '20.00', NULL, NULL, NULL, 0, NULL, NULL, 30, 0, NULL, 0, NULL, NULL, NULL, 'public', 1, 1, 1, 1, 1, 0, NULL, NULL, 60, 0, 0, 1, 1, 1, 1, 30),
(14, 'CombineMinisterofMilitarysShadow', '$2y$10$DFfZSLk7ueBv7PnuvHZSbO2Xea1O0yYvY6B0zdYXtGq6hS01F38fO', NULL, NULL, NULL, 'mkzgabriel+@gmail.com', 0, '2024-07-09 13:02:43', 0, 0, NULL, 'public', 0, NULL, 0, NULL, 0, 0, NULL, '20.00', NULL, NULL, NULL, 0, NULL, NULL, 30, 0, NULL, 0, NULL, NULL, NULL, 'public', 1, 1, 1, 1, 1, 0, NULL, NULL, 60, 0, 0, 1, 1, 1, 1, 30),
(15, 'FuckJews', '$2y$10$EctcRRdSOgOxKYx0BhUbnONImbcvWIJTFmz10Xp2zwJbTpjXn2DD2', NULL, NULL, NULL, 'FuckBloxii_InTheAss@gmail.com', 0, '2024-07-10 10:14:25', 0, 0, NULL, 'public', 1, 's', 0, NULL, 0, 0, NULL, '20.00', NULL, NULL, NULL, 0, NULL, NULL, 30, 0, NULL, 0, NULL, NULL, NULL, 'public', 1, 1, 1, 1, 1, 0, NULL, NULL, 60, 0, 0, 1, 1, 1, 1, 30),
(16, 'register', '$2y$10$kM5sxRLiJC0v2XYntLznLuqfX4svX6GTrxcH6e3j907aK0mwAhgTS', NULL, 'Otherapp', '7a84366205c43ea7', 'register@gmail.com', 6049, '2024-10-06 07:21:36', 1, 0, NULL, 'public', 0, 'BROKIE LOL - A.Tate', 0, NULL, 0, 0, 'BROKIE LOL - A.Tate', '20.00', NULL, NULL, NULL, 0, NULL, NULL, 30, 0, NULL, 0, NULL, NULL, NULL, 'public', 1, 1, 1, 1, 1, 0, NULL, NULL, 60, 0, 0, 1, 1, 1, 1, 30),
(17, 'DavidGogginsDavidGoggins', '$2y$10$V8a7nkHMuJjUqHdlWUc03.wIJCTMwe4XgqdXuioUmZ1om8RcGy.b2', NULL, NULL, NULL, 'DavidGogginsDavidGoggins@gmail.com', 0, '2024-10-06 10:15:22', 0, 0, NULL, 'public', 0, NULL, 0, NULL, 0, 0, NULL, '20.00', NULL, NULL, NULL, 0, NULL, NULL, 30, 0, NULL, 0, NULL, NULL, NULL, 'public', 1, 1, 1, 1, 1, 0, NULL, NULL, 60, 0, 0, 1, 1, 1, 1, 30),
(18, 'verteddoc', '$2y$10$HugPYzOkIDL9yfH9cOZliO9ylM6JjjWhyBMERcr03kV46McMIRXsa', NULL, NULL, NULL, 'willywonkaholder@gmail.com', 30215, '2024-10-14 16:23:51', 0, 0, NULL, 'public', 1, 'Illegal credits detected, if this is a mistake appeal', 0, NULL, 1, 0, 'Illegal credits detected, if this is a mistake appeal', '20.00', NULL, 'Enterprise', '2024-11-14', 0, NULL, NULL, 30, 0, NULL, 0, NULL, NULL, NULL, 'public', 1, 1, 1, 1, 1, 0, NULL, NULL, 60, 0, 0, 1, 1, 1, 1, 30),
(19, 'trr', '$2y$10$A6.LVMFRi6gq/SM4oG7tDOourf45tsBgtEVn.iAO14r1fTy3aEICm', NULL, 'test', 'ad135490acdaa60f', 'uoj2fdlsk@gmail.com', 60, '2024-10-16 15:35:53', 0, 0, NULL, 'public', 0, NULL, 0, NULL, 0, 0, NULL, '20.00', NULL, 'Enterprise', '2024-11-16', 0, NULL, NULL, 30, 0, NULL, 0, NULL, NULL, NULL, 'public', 1, 1, 1, 1, 1, 0, NULL, NULL, 60, 0, 0, 1, 1, 1, 1, 30);

-- --------------------------------------------------------

--
-- Table structure for table `user_2fa`
--

CREATE TABLE `user_2fa` (
  `user_id` int(11) NOT NULL,
  `secret_key` varchar(255) NOT NULL,
  `verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_logs`
--

CREATE TABLE `user_activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_messages`
--

CREATE TABLE `user_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_messages`
--

INSERT INTO `user_messages` (`id`, `sender_id`, `receiver_id`, `message`, `created_at`) VALUES
(1, 16, 1, '123', '2024-10-06 09:27:06');

-- --------------------------------------------------------

--
-- Table structure for table `user_scripts`
--

CREATE TABLE `user_scripts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `script` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `run_in_studio` tinyint(1) DEFAULT 0,
  `allow_no_https` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `anomaly_logs`
--
ALTER TABLE `anomaly_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `billing_settings`
--
ALTER TABLE `billing_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `custom_tiers`
--
ALTER TABLE `custom_tiers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `licenses`
--
ALTER TABLE `licenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `licenses_new`
--
ALTER TABLE `licenses_new`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_id_2` (`user_id`),
  ADD KEY `user_id_3` (`user_id`),
  ADD KEY `user_id_4` (`user_id`),
  ADD KEY `user_id_5` (`user_id`);

--
-- Indexes for table `license_custom_fields`
--
ALTER TABLE `license_custom_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_license_custom_fields` (`license_id`,`field_name`);

--
-- Indexes for table `license_history`
--
ALTER TABLE `license_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `license_id` (`license_id`);

--
-- Indexes for table `license_keys`
--
ALTER TABLE `license_keys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `issued_to` (`issued_to`);

--
-- Indexes for table `license_logs`
--
ALTER TABLE `license_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `license_id` (`license_id`);

--
-- Indexes for table `license_script_versions`
--
ALTER TABLE `license_script_versions`
  ADD PRIMARY KEY (`license_id`,`script_version_id`),
  ADD KEY `script_version_id` (`script_version_id`);

--
-- Indexes for table `license_transfers`
--
ALTER TABLE `license_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `license_id` (`license_id`),
  ADD KEY `from_user_id` (`from_user_id`),
  ADD KEY `to_user_id` (`to_user_id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `moderation_logs`
--
ALTER TABLE `moderation_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `moderator_id` (`moderator_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications_3`
--
ALTER TABLE `notifications_3`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `obfuscations`
--
ALTER TABLE `obfuscations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payment_logs`
--
ALTER TABLE `payment_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `registration_attempts`
--
ALTER TABLE `registration_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roblox_accounts`
--
ALTER TABLE `roblox_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `scripts`
--
ALTER TABLE `scripts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_active_version` (`active_version_id`);

--
-- Indexes for table `script_versions`
--
ALTER TABLE `script_versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_script_versions_script_id` (`script_id`);

--
-- Indexes for table `sellix_orders`
--
ALTER TABLE `sellix_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `license_id` (`license_id`);

--
-- Indexes for table `sellix_products`
--
ALTER TABLE `sellix_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `custom_tier_id` (`custom_tier_id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `subscription_levels`
--
ALTER TABLE `subscription_levels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscription_tiers`
--
ALTER TABLE `subscription_tiers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tos_agreements`
--
ALTER TABLE `tos_agreements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `usage_logs`
--
ALTER TABLE `usage_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `license_id` (`license_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_2fa`
--
ALTER TABLE `user_2fa`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_messages`
--
ALTER TABLE `user_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `user_scripts`
--
ALTER TABLE `user_scripts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `anomaly_logs`
--
ALTER TABLE `anomaly_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `billing_settings`
--
ALTER TABLE `billing_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `custom_tiers`
--
ALTER TABLE `custom_tiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `licenses`
--
ALTER TABLE `licenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `licenses_new`
--
ALTER TABLE `licenses_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `license_custom_fields`
--
ALTER TABLE `license_custom_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `license_history`
--
ALTER TABLE `license_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `license_keys`
--
ALTER TABLE `license_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `license_logs`
--
ALTER TABLE `license_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `license_transfers`
--
ALTER TABLE `license_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `moderation_logs`
--
ALTER TABLE `moderation_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications_3`
--
ALTER TABLE `notifications_3`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `obfuscations`
--
ALTER TABLE `obfuscations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_logs`
--
ALTER TABLE `payment_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registration_attempts`
--
ALTER TABLE `registration_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roblox_accounts`
--
ALTER TABLE `roblox_accounts`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2629862209;

--
-- AUTO_INCREMENT for table `scripts`
--
ALTER TABLE `scripts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `script_versions`
--
ALTER TABLE `script_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sellix_orders`
--
ALTER TABLE `sellix_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sellix_products`
--
ALTER TABLE `sellix_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_levels`
--
ALTER TABLE `subscription_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_tiers`
--
ALTER TABLE `subscription_tiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tos_agreements`
--
ALTER TABLE `tos_agreements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usage_logs`
--
ALTER TABLE `usage_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_messages`
--
ALTER TABLE `user_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_scripts`
--
ALTER TABLE `user_scripts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `anomaly_logs`
--
ALTER TABLE `anomaly_logs`
  ADD CONSTRAINT `anomaly_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD CONSTRAINT `api_keys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `billing`
--
ALTER TABLE `billing`
  ADD CONSTRAINT `billing_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `billing_settings`
--
ALTER TABLE `billing_settings`
  ADD CONSTRAINT `billing_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `licenses`
--
ALTER TABLE `licenses`
  ADD CONSTRAINT `licenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `licenses_new`
--
ALTER TABLE `licenses_new`
  ADD CONSTRAINT `licenses_new_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `license_custom_fields`
--
ALTER TABLE `license_custom_fields`
  ADD CONSTRAINT `license_custom_fields_ibfk_1` FOREIGN KEY (`license_id`) REFERENCES `licenses_new` (`id`);

--
-- Constraints for table `license_history`
--
ALTER TABLE `license_history`
  ADD CONSTRAINT `license_history_ibfk_1` FOREIGN KEY (`license_id`) REFERENCES `licenses_new` (`id`);

--
-- Constraints for table `license_keys`
--
ALTER TABLE `license_keys`
  ADD CONSTRAINT `license_keys_ibfk_1` FOREIGN KEY (`issued_to`) REFERENCES `users` (`id`);

--
-- Constraints for table `license_logs`
--
ALTER TABLE `license_logs`
  ADD CONSTRAINT `license_logs_ibfk_1` FOREIGN KEY (`license_id`) REFERENCES `licenses_new` (`id`);

--
-- Constraints for table `license_transfers`
--
ALTER TABLE `license_transfers`
  ADD CONSTRAINT `license_transfers_ibfk_1` FOREIGN KEY (`license_id`) REFERENCES `licenses_new` (`id`),
  ADD CONSTRAINT `license_transfers_ibfk_2` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `license_transfers_ibfk_3` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `moderation_logs`
--
ALTER TABLE `moderation_logs`
  ADD CONSTRAINT `moderation_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `moderation_logs_ibfk_2` FOREIGN KEY (`moderator_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications_3`
--
ALTER TABLE `notifications_3`
  ADD CONSTRAINT `notifications_3_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `obfuscations`
--
ALTER TABLE `obfuscations`
  ADD CONSTRAINT `obfuscations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `payment_logs`
--
ALTER TABLE `payment_logs`
  ADD CONSTRAINT `payment_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `roblox_accounts`
--
ALTER TABLE `roblox_accounts`
  ADD CONSTRAINT `roblox_accounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `tos_agreements`
--
ALTER TABLE `tos_agreements`
  ADD CONSTRAINT `tos_agreements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `usage_logs`
--
ALTER TABLE `usage_logs`
  ADD CONSTRAINT `usage_logs_ibfk_1` FOREIGN KEY (`license_id`) REFERENCES `licenses` (`id`);

--
-- Constraints for table `user_2fa`
--
ALTER TABLE `user_2fa`
  ADD CONSTRAINT `user_2fa_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD CONSTRAINT `user_activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  ADD CONSTRAINT `user_activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_messages`
--
ALTER TABLE `user_messages`
  ADD CONSTRAINT `user_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_scripts`
--
ALTER TABLE `user_scripts`
  ADD CONSTRAINT `user_scripts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


-- Update users table with new fields
ALTER TABLE users
ADD COLUMN trial_end_date DATE DEFAULT NULL,
ADD COLUMN subscription_tier VARCHAR(50) DEFAULT NULL,
ADD COLUMN subscription_end_date DATE DEFAULT NULL,
ADD COLUMN auto_renew BOOLEAN DEFAULT FALSE;

-- Create table for credit usage history
CREATE TABLE credit_usage_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    credits_used INT NOT NULL,
    usage_date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create table for feature usage
CREATE TABLE feature_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    feature_name VARCHAR(100) NOT NULL,
    credits_used INT NOT NULL,
    usage_date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Update subscription_tiers table
ALTER TABLE subscription_tiers
ADD COLUMN description TEXT,
MODIFY COLUMN benefits TEXT;

-- Create table for user subscriptions
CREATE TABLE user_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tier_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    auto_renew BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tier_id) REFERENCES subscription_tiers(id)
);

-- Create table for subscription transactions
CREATE TABLE subscription_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tier_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    transaction_date DATETIME NOT NULL,
    status ENUM('success', 'failed', 'pending') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tier_id) REFERENCES subscription_tiers(id)
);

-- Create table for credit purchase history
CREATE TABLE credit_purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount INT NOT NULL,
    cost DECIMAL(10, 2) NOT NULL,
    purchase_date DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Add indexes for performance
CREATE INDEX idx_credit_usage_user_date ON credit_usage_history (user_id, usage_date);
CREATE INDEX idx_feature_usage_user_date ON feature_usage (user_id, usage_date);
CREATE INDEX idx_user_subscriptions_user ON user_subscriptions (user_id);
CREATE INDEX idx_subscription_transactions_user ON subscription_transactions (user_id);
CREATE INDEX idx_credit_purchases_user ON credit_purchases (user_id);