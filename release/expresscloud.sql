-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 17, 2026 at 02:53 AM
-- Server version: 10.11.16-MariaDB-cll-lve
-- PHP Version: 8.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `icfd_expresscloud`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounting_periods`
--

DROP TABLE IF EXISTS `accounting_periods`;
CREATE TABLE `accounting_periods` (
  `id` char(26) NOT NULL,
  `name` varchar(120) NOT NULL,
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'open',
  `closed_by_account_id` char(26) DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `locked_by_account_id` char(26) DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounting_periods`
--

INSERT INTO `accounting_periods` (`id`, `name`, `starts_on`, `ends_on`, `status`, `closed_by_account_id`, `closed_at`, `locked_by_account_id`, `locked_at`, `created_at`, `updated_at`) VALUES
('01KXM3J600B86AV45SSD79NP63', '2026 Financial Year', '2026-01-01', '2026-12-31', 'open', NULL, NULL, NULL, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
CREATE TABLE `accounts` (
  `id` char(26) NOT NULL,
  `public_id` char(36) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email_encrypted` text DEFAULT NULL,
  `login_key_encrypted` text NOT NULL,
  `login_key_blind_index` char(64) NOT NULL,
  `login_key_version` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `profile_picture_path` varchar(500) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `last_authenticated_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_allowed_all_branches` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `public_id`, `first_name`, `last_name`, `email_encrypted`, `login_key_encrypted`, `login_key_blind_index`, `login_key_version`, `profile_picture_path`, `status`, `last_authenticated_at`, `remember_token`, `created_at`, `updated_at`, `is_allowed_all_branches`) VALUES
('01KXM3J6007BWNQEDX4BZMV8C9', '4932de79-670c-40fe-9a44-37bb01ad738b', 'Administrator', 'Account', NULL, '{\"v\":1,\"ciphertext\":\"eyJpdiI6InJNWlllSGtvQjFtcm85SVlwK1BkT1E9PSIsInZhbHVlIjoiMmFZMXZJQ1l4eVZKZnphUGp3NzZoQT09IiwibWFjIjoiMjA5NDI3YjE4ZGE4YzU3ZTQ4NWNhMzE4NTFjYTI2ZDUyMDhiZTUzYWRhNzRlZmI5ZWQwZGQwOTBmNzhhODZjZiJ9\"}', '4a3033e5df7f542aacd141ce4cd0427d10e4a2820efa257fac5fc2e2f3610fcb', 1, NULL, 'active', '2026-07-17 05:01:40', NULL, '2026-07-16 16:00:00', '2026-07-17 05:01:40', 1);

-- --------------------------------------------------------

--
-- Table structure for table `account_branch`
--

DROP TABLE IF EXISTS `account_branch`;
CREATE TABLE `account_branch` (
  `account_id` char(26) NOT NULL,
  `branch_id` char(26) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `account_branch`
--

INSERT INTO `account_branch` (`account_id`, `branch_id`, `created_at`, `updated_at`) VALUES
('01KXM3J6007BWNQEDX4BZMV8C9', '01KXM3J60087WJDZ6QRTG9PMTX', '2026-07-16 16:00:00', '2026-07-16 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `account_role`
--

DROP TABLE IF EXISTS `account_role`;
CREATE TABLE `account_role` (
  `account_id` char(26) NOT NULL,
  `role_id` char(26) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `account_role`
--

INSERT INTO `account_role` (`account_id`, `role_id`, `created_at`, `updated_at`) VALUES
('01KXM3J6007BWNQEDX4BZMV8C9', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `account_sessions`
--

DROP TABLE IF EXISTS `account_sessions`;
CREATE TABLE `account_sessions` (
  `id` char(26) NOT NULL,
  `account_id` char(26) NOT NULL,
  `session_identifier` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity_at` timestamp NOT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `account_sessions`
--

INSERT INTO `account_sessions` (`id`, `account_id`, `session_identifier`, `ip_address`, `user_agent`, `last_activity_at`, `revoked_at`, `created_at`, `updated_at`) VALUES
('01kxpkphnpghkytaets4wd0e5s', '01KXM3J6007BWNQEDX4BZMV8C9', 'LO4YepWElfYLPNX09jE8Ub5I747hR0H4cH953XLB', '197.210.55.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-17 04:30:25', '2026-07-17 04:30:36', '2026-07-17 04:20:29', '2026-07-17 04:30:36'),
('01kxpma3ds00c39wztb0x0gpee', '01KXM3J6007BWNQEDX4BZMV8C9', 'e2Z7FuNnVcWGXmoWbLPZtXVyiapRQlCyDZbcVW0M', '197.210.55.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-17 04:31:58', '2026-07-17 04:32:26', '2026-07-17 04:31:09', '2026-07-17 04:32:26'),
('01kxpp1z6k871wazp638m12h6g', '01KXM3J6007BWNQEDX4BZMV8C9', '1GGZe3uzCJkVWga7XoWZmsKZTSfKsUSZoHYhzWc9', '197.210.55.248', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36', '2026-07-17 05:01:40', NULL, '2026-07-17 05:01:40', '2026-07-17 05:01:40');

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

DROP TABLE IF EXISTS `admin_notifications`;
CREATE TABLE `admin_notifications` (
  `id` char(26) NOT NULL,
  `notification_type` varchar(40) NOT NULL,
  `title` varchar(180) NOT NULL,
  `message` text NOT NULL,
  `entity_type` varchar(80) DEFAULT NULL,
  `entity_id` varchar(64) DEFAULT NULL,
  `branch_id` char(26) DEFAULT NULL,
  `occurred_at` timestamp NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alert_recipients`
--

DROP TABLE IF EXISTS `alert_recipients`;
CREATE TABLE `alert_recipients` (
  `id` char(26) NOT NULL,
  `email` varchar(255) NOT NULL,
  `label` varchar(80) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `added_by_account_id` char(26) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_tokens`
--

DROP TABLE IF EXISTS `api_tokens`;
CREATE TABLE `api_tokens` (
  `id` char(26) NOT NULL,
  `name` varchar(120) NOT NULL,
  `token_prefix` varchar(16) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `abilities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`abilities`)),
  `created_by_account_id` char(26) NOT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_depreciation_postings`
--

DROP TABLE IF EXISTS `asset_depreciation_postings`;
CREATE TABLE `asset_depreciation_postings` (
  `id` char(26) NOT NULL,
  `fixed_asset_id` char(26) NOT NULL,
  `journal_entry_id` char(26) NOT NULL,
  `period_end` date NOT NULL,
  `amount_kobo` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` char(26) NOT NULL,
  `actor_account_id` char(26) DEFAULT NULL,
  `actor_name` varchar(220) DEFAULT NULL,
  `actor_role_snapshot` varchar(220) DEFAULT NULL,
  `action` varchar(180) NOT NULL,
  `entity_type` varchar(100) NOT NULL,
  `entity_id` varchar(64) DEFAULT NULL,
  `branch_id` char(26) DEFAULT NULL,
  `before_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`before_data`)),
  `after_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`after_data`)),
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `occurred_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `actor_account_id`, `actor_name`, `actor_role_snapshot`, `action`, `entity_type`, `entity_id`, `branch_id`, `before_data`, `after_data`, `context`, `ip_address`, `user_agent`, `occurred_at`, `created_at`) VALUES
('01kxpm7bk9qavyj4hs6egp83hh', '01KXM3J6007BWNQEDX4BZMV8C9', 'Administrator Account', 'System Owner', 'staff.created', 'account', '01kxpm7bjmwqkbtwqdet22cps0', NULL, NULL, '{\"name\":\"williams staff\",\"status\":\"active\",\"all_branches\":false}', '[]', '197.210.55.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-17 04:29:39', '2026-07-17 04:29:39'),
('01kxpmav5beyqk8xg1r8tndegt', '01KXM3J6007BWNQEDX4BZMV8C9', 'Administrator Account', 'System Owner', 'staff.suspended', 'account', '01kxpm7bjmwqkbtwqdet22cps0', NULL, '{\"id\":\"01kxpm7bjmwqkbtwqdet22cps0\",\"public_id\":\"013efd3c-a7ed-4f59-9667-c30428f6a2c6\",\"first_name\":\"williams\",\"last_name\":\"staff\",\"login_key_version\":1,\"profile_picture_path\":null,\"status\":\"active\",\"last_authenticated_at\":null,\"created_at\":\"2026-07-16T23:29:39.000000Z\",\"updated_at\":\"2026-07-16T23:29:39.000000Z\",\"is_allowed_all_branches\":false}', '{\"id\":\"01kxpm7bjmwqkbtwqdet22cps0\",\"public_id\":\"013efd3c-a7ed-4f59-9667-c30428f6a2c6\",\"first_name\":\"williams\",\"last_name\":\"staff\",\"login_key_version\":1,\"profile_picture_path\":null,\"status\":\"suspended\",\"last_authenticated_at\":null,\"created_at\":\"2026-07-16T23:29:39.000000Z\",\"updated_at\":\"2026-07-16T23:31:34.000000Z\",\"is_allowed_all_branches\":false}', '[]', '197.210.55.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-17 04:31:34', '2026-07-17 04:31:34'),
('01kxpmbjvxve0zfqppn6r07rf4', '01KXM3J6007BWNQEDX4BZMV8C9', 'Administrator Account', 'System Owner', 'staff.created', 'account', '01kxpmbjvncd4amgq3mt73kaw4', NULL, NULL, '{\"name\":\"test staff\",\"status\":\"active\",\"all_branches\":false}', '[]', '197.210.55.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-17 04:31:58', '2026-07-17 04:31:58');

-- --------------------------------------------------------

--
-- Table structure for table `backup_runs`
--

DROP TABLE IF EXISTS `backup_runs`;
CREATE TABLE `backup_runs` (
  `id` char(26) NOT NULL,
  `backup_type` varchar(30) NOT NULL,
  `status` varchar(30) NOT NULL,
  `disk` varchar(60) NOT NULL,
  `path` varchar(500) DEFAULT NULL,
  `checksum_sha256` varchar(64) DEFAULT NULL,
  `size_bytes` bigint(20) UNSIGNED DEFAULT NULL,
  `manifest` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`manifest`)),
  `failure_message` text DEFAULT NULL,
  `requested_by_account_id` char(26) DEFAULT NULL,
  `started_at` timestamp NOT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
CREATE TABLE `branches` (
  `id` char(26) NOT NULL,
  `name` varchar(160) NOT NULL,
  `code` varchar(40) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `is_head_office` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `allow_zero_stock_sales` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `code`, `address`, `phone`, `status`, `is_head_office`, `created_at`, `updated_at`, `allow_zero_stock_sales`) VALUES
('01KXM3J60087WJDZ6QRTG9PMTX', 'Head Office', 'HQ', 'To be updated after installation', NULL, 'active', 1, '2026-07-16 16:00:00', '2026-07-16 16:00:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `id` char(26) NOT NULL,
  `name` varchar(140) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_insights`
--

DROP TABLE IF EXISTS `business_insights`;
CREATE TABLE `business_insights` (
  `id` char(26) NOT NULL,
  `category` varchar(40) NOT NULL,
  `severity` varchar(20) NOT NULL DEFAULT 'info',
  `title` varchar(160) NOT NULL,
  `summary` text NOT NULL,
  `recommendation` text DEFAULT NULL,
  `evidence` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`evidence`)),
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `branch_id` char(26) DEFAULT NULL,
  `generated_at` timestamp NOT NULL,
  `dismissed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_settings`
--

DROP TABLE IF EXISTS `business_settings`;
CREATE TABLE `business_settings` (
  `singleton_key` varchar(20) NOT NULL,
  `business_name` varchar(150) NOT NULL,
  `business_logo_path` varchar(500) DEFAULT NULL,
  `head_office_address` text NOT NULL,
  `end_of_day_digest_time` time NOT NULL DEFAULT '21:00:00',
  `session_inactivity_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT 20,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `business_settings`
--

INSERT INTO `business_settings` (`singleton_key`, `business_name`, `business_logo_path`, `head_office_address`, `end_of_day_digest_time`, `session_inactivity_minutes`, `created_at`, `updated_at`) VALUES
('default', 'Express Cloud', NULL, '', '21:00:00', 20, '2026-07-17 04:25:14', '2026-07-17 04:25:14'),
('primary', 'Express Cloud Company', NULL, 'To be updated after installation', '21:00:00', 20, '2026-07-16 16:00:00', '2026-07-16 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
  `id` char(26) NOT NULL,
  `legal_name` varchar(180) NOT NULL,
  `trading_name` varchar(180) DEFAULT NULL,
  `head_office_address` text NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `email_encrypted` text DEFAULT NULL,
  `logo_path` varchar(500) DEFAULT NULL,
  `timezone` varchar(64) NOT NULL DEFAULT 'Africa/Lagos',
  `is_configured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `legal_name`, `trading_name`, `head_office_address`, `phone`, `email_encrypted`, `logo_path`, `timezone`, `is_configured`, `created_at`, `updated_at`) VALUES
('01KXM3J60080C37BBS74B5V1GC', 'Express Cloud Company', 'Express Cloud Company', 'To be updated after installation', NULL, NULL, NULL, 'Africa/Lagos', 1, '2026-07-16 16:00:00', '2026-07-16 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` char(26) NOT NULL,
  `customer_code` varchar(60) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `email_encrypted` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `credit_limit_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `balance_kobo` bigint(20) NOT NULL DEFAULT 0,
  `is_wholesale` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_by_account_id` char(26) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discount_vouchers`
--

DROP TABLE IF EXISTS `discount_vouchers`;
CREATE TABLE `discount_vouchers` (
  `id` char(26) NOT NULL,
  `code` varchar(80) NOT NULL,
  `name` varchar(160) NOT NULL,
  `value_type` varchar(30) NOT NULL,
  `value` bigint(20) UNSIGNED NOT NULL,
  `minimum_sale_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `maximum_discount_kobo` bigint(20) UNSIGNED DEFAULT NULL,
  `usage_limit` int(10) UNSIGNED DEFAULT NULL,
  `usage_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_by_account_id` char(26) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_brandings`
--

DROP TABLE IF EXISTS `document_brandings`;
CREATE TABLE `document_brandings` (
  `id` char(26) NOT NULL,
  `business_name` varchar(180) NOT NULL,
  `logo_path` varchar(500) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `phone` varchar(80) DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `receipt_footer` text DEFAULT NULL,
  `document_terms` text DEFAULT NULL,
  `updated_by_account_id` char(26) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_brandings`
--

INSERT INTO `document_brandings` (`id`, `business_name`, `logo_path`, `address`, `phone`, `email`, `receipt_footer`, `document_terms`, `updated_by_account_id`, `created_at`, `updated_at`) VALUES
('01KXM3J600TG0T9KYQQBVDXGTD', 'Express Cloud Company', NULL, 'To be updated after installation', NULL, NULL, NULL, NULL, '01KXM3J6007BWNQEDX4BZMV8C9', '2026-07-16 16:00:00', '2026-07-16 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `end_of_day_digests`
--

DROP TABLE IF EXISTS `end_of_day_digests`;
CREATE TABLE `end_of_day_digests` (
  `id` char(26) NOT NULL,
  `business_date` date NOT NULL,
  `status` varchar(30) NOT NULL,
  `recipient_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`summary`)),
  `started_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `failure_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fixed_assets`
--

DROP TABLE IF EXISTS `fixed_assets`;
CREATE TABLE `fixed_assets` (
  `id` char(26) NOT NULL,
  `asset_code` varchar(40) NOT NULL,
  `name` varchar(180) NOT NULL,
  `category` varchar(120) NOT NULL,
  `branch_id` char(26) DEFAULT NULL,
  `custodian_account_id` char(26) DEFAULT NULL,
  `acquired_at` date NOT NULL,
  `cost_kobo` bigint(20) UNSIGNED NOT NULL,
  `salvage_value_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `useful_life_months` int(10) UNSIGNED NOT NULL,
  `serial_number` varchar(160) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by_account_id` char(26) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_entries`
--

DROP TABLE IF EXISTS `journal_entries`;
CREATE TABLE `journal_entries` (
  `id` char(26) NOT NULL,
  `journal_number` varchar(50) NOT NULL,
  `entry_date` date NOT NULL,
  `accounting_period_id` char(26) NOT NULL,
  `branch_id` char(26) DEFAULT NULL,
  `source_type` varchar(120) DEFAULT NULL,
  `source_id` varchar(40) DEFAULT NULL,
  `source_event` varchar(80) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'posted',
  `memo` varchar(500) NOT NULL,
  `created_by_account_id` char(26) DEFAULT NULL,
  `reversal_of_entry_id` char(26) DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `reversed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_lines`
--

DROP TABLE IF EXISTS `journal_lines`;
CREATE TABLE `journal_lines` (
  `id` char(26) NOT NULL,
  `journal_entry_id` char(26) NOT NULL,
  `ledger_account_id` char(26) NOT NULL,
  `branch_id` char(26) DEFAULT NULL,
  `customer_id` char(26) DEFAULT NULL,
  `supplier_id` char(26) DEFAULT NULL,
  `debit_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `credit_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ledger_accounts`
--

DROP TABLE IF EXISTS `ledger_accounts`;
CREATE TABLE `ledger_accounts` (
  `id` char(26) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(180) NOT NULL,
  `type` varchar(30) NOT NULL,
  `parent_id` char(26) DEFAULT NULL,
  `is_control_account` tinyint(1) NOT NULL DEFAULT 0,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `allow_manual_posting` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ledger_accounts`
--

INSERT INTO `ledger_accounts` (`id`, `code`, `name`, `type`, `parent_id`, `is_control_account`, `is_system`, `is_active`, `allow_manual_posting`, `description`, `created_at`, `updated_at`) VALUES
('01KXM3J6002KV0CQ6AWCQMNK8H', '1020', 'Card and POS Clearing', 'asset', NULL, 1, 1, 1, 0, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6002XBJ1HTXVZX9SX41', '6000', 'Depreciation Expense', 'expense', NULL, 1, 1, 1, 0, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6003KF3XQ3A685D91A4', '3000', 'Owner Equity', 'equity', NULL, 1, 1, 1, 0, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60050QAS1RJYV3AZ5YS', '2100', 'Output Tax Payable', 'liability', NULL, 1, 1, 1, 0, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6007GK4507T5B13W6P5', '1000', 'Cash on Hand', 'asset', NULL, 1, 1, 1, 0, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6007GYR4EWV36802FP9', '6100', 'General Operating Expense', 'expense', NULL, 0, 1, 1, 1, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6009TYDB2J5821NC74R', '5010', 'Purchase Returns', 'expense', NULL, 1, 1, 1, 0, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600AVQHNJ1XZGX10DFX', '9990', 'Opening Balance Clearing', 'equity', NULL, 1, 1, 1, 0, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600BVCR77TJYX9NCAKS', '1300', 'Fixed Assets', 'asset', NULL, 1, 1, 1, 0, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600D6AD0RER7QFPJEY8', '2200', 'Customer Deposits', 'liability', NULL, 1, 1, 1, 0, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DYHRV0431J92T7MW', '2000', 'Accounts Payable', 'liability', NULL, 1, 1, 1, 0, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600H8WG8C8CMFWC4G8M', '1010', 'Bank Accounts', 'asset', NULL, 1, 1, 1, 0, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600HJPSS9ASSYVT1950', '1390', 'Accumulated Depreciation', 'asset', NULL, 1, 1, 1, 0, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MMPKEKG6DTVK0N4R', '1200', 'Inventory Asset', 'asset', NULL, 1, 1, 1, 0, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600PBS8S61895JD9Y66', '5000', 'Cost of Goods Sold', 'expense', NULL, 1, 1, 1, 0, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600PEG6F6E46EXTJP17', '2300', 'Fixed Asset Clearing', 'liability', NULL, 1, 1, 1, 0, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600QA1FS7S706CMEG85', '4000', 'Sales Revenue', 'revenue', NULL, 1, 1, 1, 0, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600QSKBTAQPDMBFMDZB', '4010', 'Sales Returns and Allowances', 'revenue', NULL, 1, 1, 1, 0, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600ZHDKSJS9NZA91GDT', '1100', 'Accounts Receivable', 'asset', NULL, 1, 1, 1, 0, NULL, '2026-07-16 16:00:00', '2026-07-16 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `low_stock_alerts`
--

DROP TABLE IF EXISTS `low_stock_alerts`;
CREATE TABLE `low_stock_alerts` (
  `id` char(26) NOT NULL,
  `product_id` char(26) NOT NULL,
  `branch_id` char(26) NOT NULL,
  `quantity_milliunits` bigint(20) NOT NULL,
  `minimum_stock_milliunits` bigint(20) NOT NULL,
  `opened_at` timestamp NOT NULL,
  `last_seen_at` timestamp NOT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000001_create_cache_table', 1),
(2, '0001_01_01_000002_create_jobs_table', 1),
(3, '2026_07_15_000100_create_accounts_table', 1),
(4, '2026_07_15_000110_create_account_sessions_table', 1),
(5, '2026_07_15_000120_create_security_events_table', 1),
(6, '2026_07_15_000200_create_companies_table', 1),
(7, '2026_07_15_000210_create_branches_table', 1),
(8, '2026_07_15_000220_create_roles_and_permissions_tables', 1),
(9, '2026_07_15_000230_create_account_branch_table', 1),
(10, '2026_07_15_000240_create_audit_logs_table', 1),
(11, '2026_07_15_000300_create_catalog_classification_tables', 1),
(12, '2026_07_15_000310_create_suppliers_table', 1),
(13, '2026_07_15_000320_create_products_tables', 1),
(14, '2026_07_15_000400_create_product_imports_tables', 1),
(15, '2026_07_15_000500_create_inventory_tables', 1),
(16, '2026_07_15_000600_create_procurement_and_alert_tables', 1),
(17, '2026_07_15_000700_create_customers_and_payment_methods', 1),
(18, '2026_07_15_000800_create_sales_tables', 1),
(19, '2026_07_15_000900_create_supplier_finance_tables', 1),
(20, '2026_07_15_001000_create_operations_dashboard_tables', 1),
(21, '2026_07_15_001500_create_commercial_controls', 1),
(22, '2026_07_15_001600_create_api_tokens_and_quote_conversions', 1),
(23, '2026_07_15_001700_create_backup_runs', 1),
(24, '2026_07_16_001800_create_operational_accounting_records', 1),
(25, '2026_07_16_001900_create_business_insights_table', 1),
(26, '2026_07_16_001900_create_double_entry_accounting', 1);

-- --------------------------------------------------------

--
-- Table structure for table `operation_document_logs`
--

DROP TABLE IF EXISTS `operation_document_logs`;
CREATE TABLE `operation_document_logs` (
  `id` char(26) NOT NULL,
  `operation_type` varchar(80) NOT NULL,
  `operation_id` varchar(40) NOT NULL,
  `format` varchar(20) NOT NULL,
  `document_hash` varchar(64) NOT NULL,
  `generated_by_account_id` char(26) NOT NULL,
  `generated_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` char(26) NOT NULL,
  `sale_id` char(26) NOT NULL,
  `payment_method_id` char(26) NOT NULL,
  `amount_kobo` bigint(20) UNSIGNED NOT NULL,
  `recorded_by_account_id` char(26) NOT NULL,
  `reference` varchar(160) DEFAULT NULL,
  `paid_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
CREATE TABLE `payment_methods` (
  `id` char(26) NOT NULL,
  `name` varchar(80) NOT NULL,
  `account_number_encrypted` text DEFAULT NULL,
  `bank_name` varchar(120) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_system_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_default_for_pos` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_account_id` char(26) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `name`, `account_number_encrypted`, `bank_name`, `description`, `is_system_default`, `is_default_for_pos`, `is_active`, `created_by_account_id`, `created_at`, `updated_at`) VALUES
('01KXM3J600G2EGW59R46PADX11', 'Cash', NULL, NULL, 'Cash', 1, 1, 1, '01KXM3J6007BWNQEDX4BZMV8C9', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600JXJZQMNMRDKA689T', 'Bank Transfer', NULL, NULL, 'Bank Transfer', 1, 0, 1, '01KXM3J6007BWNQEDX4BZMV8C9', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600Z9NYMPB9VGRN92KE', 'Customer Credit', NULL, NULL, 'Customer Credit', 1, 0, 1, '01KXM3J6007BWNQEDX4BZMV8C9', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600ZATAWXRRCCWWCH0G', 'Card / POS Terminal', NULL, NULL, 'Card / POS Terminal', 1, 0, 1, '01KXM3J6007BWNQEDX4BZMV8C9', '2026-07-16 16:00:00', '2026-07-16 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` char(26) NOT NULL,
  `name` varchar(160) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `group` varchar(80) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `slug`, `group`, `description`, `created_at`, `updated_at`) VALUES
('01KXM3J60005C8J2096EPP7C4G', 'View fixed assets', 'assets.view', 'Accounting Operations', 'View fixed assets', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6000JM7P8PBMYDM6T41', 'View supplier bills', 'supplier-bills.view', 'catalog', 'View supplier bills', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6000PEJJX50F2W98KKJ', 'View purchase returns', 'purchase_returns.view', 'Accounting Operations', 'View purchase returns', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60014Y0K4D3SF388PR8', 'Update roles', 'roles.update', 'authorization', 'Update roles', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6001BYF6PKHTA7EM8YZ', 'Deactivate branches', 'branches.deactivate', 'organisation', 'Deactivate branches', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6001FBFFRZZVDSAVP5N', 'View staff accounts', 'staff.view', 'staff', 'View staff accounts', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6001WBGKA9PRTJ9G49X', 'Manage accounting periods', 'accounting.periods.manage', 'Accounting Operations', 'Manage accounting periods', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6001YPQKE9PMRPX2V7N', 'Record supplier bill payments', 'supplier-bills.pay', 'catalog', 'Record supplier bill payments', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6002GWM3NNPM872AE8R', 'Manage document branding', 'documents.branding.manage', 'Accounting Operations', 'Manage document branding', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6002JMCHFRXA7ECDJFD', 'View financial reports', 'accounting.reports.view', 'Accounting Operations', 'View financial reports', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6002T6Y8JY13WH5H2KX', 'Apply discount vouchers', 'vouchers.apply', 'Commercial', 'Apply discount vouchers', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6003AEQK9WWPF9EWSVM', 'View branches', 'branches.view', 'organisation', 'View branches', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6003VTW8075FFN0H85E', 'Update staff accounts', 'staff.update', 'staff', 'Update staff accounts', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60043JQZA8M0T2F5NMK', 'View audit logs', 'audit-log.view', 'security', 'View audit logs', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60047BM6YRMK5A45RNF', 'Suspend staff accounts', 'staff.suspend', 'staff', 'Suspend staff accounts', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6004D8PP1HNNC0FSK83', 'Create supplier bills', 'supplier-bills.create', 'catalog', 'Create supplier bills', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6004MG8V37775GP0MP0', 'Record direct purchases', 'purchases.record', 'Commercial', 'Record direct purchases', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6004YETRVTJ8Q7MRM5J', 'Manage payment methods', 'payment-methods.manage', 'catalog', 'Manage payment methods', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6005KM3F1RR9A3SCS95', 'Record sale payments', 'sales.payments', 'catalog', 'Record sale payments', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6005RF0HGYJQSHEK5BG', 'Import products from Excel', 'products.import', 'catalog', 'Import products from Excel', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60062TJZTTNB76RQBMA', 'Delete custom roles', 'roles.delete', 'authorization', 'Delete custom roles', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60063X9Y2PGYBXWZ8FV', 'Verify backups', 'backups.verify', 'Backup and Recovery', 'Verify backups', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6006545WY682TVTW76A', 'Regenerate staff access keys', 'staff.access-key.regenerate', 'staff', 'Regenerate staff access keys', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6006HVYHE5797HK8T1D', 'View customers', 'customers.view', 'catalog', 'View customers', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6006MNY2HABM5DHZ03Q', 'View purchase orders', 'procurement.view', 'catalog', 'View purchase orders', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6006XP8983RJEDSZG49', 'Update suppliers', 'suppliers.update', 'catalog', 'Update suppliers', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6007ZDRHGZ2V6SPK570', 'Dismiss Lisa AI business insights', 'insights.dismiss', 'Accounting Operations', 'Dismiss Lisa AI business insights', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60085NKHRX7NTCSMVWQ', 'View payment methods', 'payment-methods.view', 'catalog', 'View payment methods', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6008MD8ANZK5SSKR52J', 'Manage brands', 'brands.manage', 'catalog', 'Manage brands', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6008SJKP479Y93Q97AF', 'Create manual journals', 'accounting.journals.create', 'Accounting Operations', 'Create manual journals', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60092BBD3QK9WYHZ3EJ', 'Create purchase returns', 'purchase_returns.create', 'Accounting Operations', 'Create purchase returns', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6009M25F6JHTG0JEN9G', 'Create backups', 'backups.create', 'Backup and Recovery', 'Create backups', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6009X1JWY41XSHYV2VP', 'Receive goods against purchase orders', 'procurement.receive', 'catalog', 'Receive goods against purchase orders', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6009YY60EAW956N4MJ7', 'Manage fixed assets', 'assets.manage', 'Accounting Operations', 'Manage fixed assets', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600A15WEFC0AFEEK3KN', 'Update products', 'products.update', 'catalog', 'Update products', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600A1KP99ERCC5CCVZS', 'View active staff sessions', 'staff.sessions.view', 'staff', 'View active staff sessions', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600A4261K9SFPX7QMZ5', 'View roles', 'roles.view', 'authorization', 'View roles', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600B86PDKWVRA0GFSBM', 'Create standalone receipts', 'receipts.create', 'Accounting Operations', 'Create standalone receipts', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600BST89DRWQ512K19F', 'Create supplier returns', 'supplier-returns.create', 'catalog', 'Create supplier returns', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600BSZ9RNN183R0XMV7', 'Manage alert recipients', 'alerts.manage-recipients', 'catalog', 'Manage alert recipients', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600BWYKH9ZH3X4F4HZF', 'View admin dashboard', 'dashboard.view', 'catalog', 'View admin dashboard', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600C769EX8SRYZFG9V2', 'Manage chart of accounts', 'accounting.accounts.manage', 'Accounting Operations', 'Manage chart of accounts', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600CAN83YJ8V3M7SK7N', 'View Lisa AI business insights', 'insights.view', 'Accounting Operations', 'View Lisa AI business insights', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600CFK3JV5P3MMM353Y', 'Convert quotes', 'quotes.convert', 'Commercial', 'Convert quotes', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600CY4A9H0JKSK1PWT4', 'Revoke staff sessions', 'staff.sessions.revoke', 'staff', 'Revoke staff sessions', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600D83G2Z6RCHEAM9RW', 'Terminate live sessions', 'security.sessions.terminate', 'catalog', 'Terminate live sessions', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DP1MD6TKYKEP1D49', 'Create roles', 'roles.create', 'authorization', 'Create roles', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DPKFV4WXGCAK4TY9', 'Reveal staff access keys', 'staff.access-key.reveal', 'staff', 'Reveal staff access keys', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DPMS6274DDA33TZ5', 'Convert quotes', 'sales.convert-quotes', 'catalog', 'Convert quotes', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DQMDY5VD6M3V1NTR', 'Reverse journals', 'accounting.journals.reverse', 'Accounting Operations', 'Reverse journals', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DSFFNTVVSNF2A78V', 'View staff performance', 'reports.staff-performance', 'catalog', 'View staff performance', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600E1K4AD8T5V1QNX03', 'Create customers', 'customers.create', 'catalog', 'Create customers', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600EP38V3XPE866BQJJ', 'Generate Lisa AI business insights', 'insights.generate', 'Accounting Operations', 'Generate Lisa AI business insights', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600ESGV20BJ9JSQV3EM', 'View live sessions', 'security.sessions.view', 'catalog', 'View live sessions', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600EYV099TJZ4C9C87A', 'Export audit logs', 'audit-log.export', 'security', 'Export audit logs', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600F5ZPDZ9CWZ91CPFA', 'Deactivate products', 'products.deactivate', 'catalog', 'Deactivate products', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600FJR5342A6NHAGAGT', 'Create suppliers', 'suppliers.create', 'catalog', 'Create suppliers', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600FPAGSWMC20J9VTCC', 'Manage tax rates', 'tax-rates.manage', 'catalog', 'Manage tax rates', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600G392ZEQMRXVD769F', 'Transfer stock between branches', 'inventory.transfer', 'catalog', 'Transfer stock between branches', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600GAQ3819WYP2Q9SBT', 'View journals', 'accounting.journals.view', 'Accounting Operations', 'View journals', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600GSZDAR3NCWDXRX9Z', 'View product import history', 'products.import-history', 'catalog', 'View product import history', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600H56RKZGCMEC3ZJVS', 'Revoke staff accounts', 'staff.revoke', 'staff', 'Revoke staff accounts', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600HJNC87STWYF3508W', 'View product activity', 'activity.products.view', 'catalog', 'View product activity', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600HY0K1NTKTGDV4B7Q', 'View all sales', 'sales.view.all', 'Commercial', 'View all sales', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600K5A7Q4X6X9EZR6AR', 'Record stock intake', 'inventory.intake', 'catalog', 'Record stock intake', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600KAM05NBP5JY6S6K6', 'View supplier returns', 'supplier-returns.view', 'catalog', 'View supplier returns', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600KZCZ1WM77YFDQ199', 'Reactivate staff accounts', 'staff.reactivate', 'staff', 'Reactivate staff accounts', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600M1D7P2WY2G7AK26T', 'Create products', 'products.create', 'catalog', 'Create products', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600M47P1SYDMTPX6VZF', 'View stock movement ledger', 'inventory.movements.view', 'catalog', 'View stock movement ledger', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MC6YER0HJCYQ6ZR9', 'View customer receivables', 'customers.receivables.view', 'Commercial', 'View customer receivables', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MDRN6YMA925XV74Q', 'Print and download sale documents', 'documents.sales.print', 'catalog', 'Print and download sale documents', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MF1ZYAJS7PB7HQV2', 'View products', 'products.view', 'catalog', 'View products', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MSJP5PSC2KHB96JF', 'Archive suppliers', 'suppliers.archive', 'catalog', 'Archive suppliers', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600N52074S0GVB2QK14', 'View operational alerts', 'alerts.view', 'catalog', 'View operational alerts', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600N8YFVPX910NG17V6', 'View security events', 'security-events.view', 'security', 'View security events', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NAVR1T6E6AF1RFEA', 'Adjust stock with a reason', 'inventory.adjust', 'catalog', 'Adjust stock with a reason', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NG8JZDHEBSZB7AVR', 'View low-stock report', 'reports.low-stock', 'catalog', 'View low-stock report', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NHEVA96TSS6J17ZG', 'Download operation documents', 'operation_documents.download', 'Accounting Operations', 'Download operation documents', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NQEEJX4T1ZN40BG2', 'View company details', 'company.view', 'organisation', 'View company details', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NSHKV5228WH2JJRG', 'View reports hub', 'reports.hub.view', 'catalog', 'View reports hub', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600P4WEXHKKWRCWJQ77', 'View own sales', 'sales.view.own', 'Commercial', 'View own sales', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600P4WMP7Y605XW7YME', 'View standalone receipts', 'receipts.view', 'Accounting Operations', 'View standalone receipts', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600P8AJWJ7F92DX2SM1', 'Synchronize operational accounting', 'accounting.sync', 'Accounting Operations', 'Synchronize operational accounting', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600PCEQK5FR3MF2T94W', 'Assign permissions', 'permissions.assign', 'authorization', 'Assign permissions', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600Q6JHEB9ME7J1VZTT', 'Manage API tokens', 'api.tokens.manage', 'Commercial', 'Manage API tokens', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600QB12ZSNQXPWEGDZE', 'Download supplier documents', 'supplier-documents.download', 'catalog', 'Download supplier documents', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600QQ2ZM8F5F3BF26CA', 'Post fixed-asset depreciation', 'accounting.depreciation.post', 'Accounting Operations', 'Post fixed-asset depreciation', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600RMD4A585JP6CWC2W', 'Update branches', 'branches.update', 'organisation', 'Update branches', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600RNGAAGBQ0TJTBRMD', 'Manage product categories', 'categories.manage', 'catalog', 'Manage product categories', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600RXD2MQCH2DTP9576', 'View supplier balances', 'reports.supplier-balances', 'catalog', 'View supplier balances', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600S2ETJPW6FW0ZS9TB', 'Record sale payments', 'sales.payments.record', 'Commercial', 'Record sale payments', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600S2FBM7DKQ7H5APB4', 'Update customers', 'customers.update', 'catalog', 'Update customers', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600SQ4TMQQKCZES490R', 'Create sale returns', 'sales.returns.create', 'Commercial', 'Create sale returns', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600SYR8QMKD4D68TS9X', 'Export reports', 'reports.export', 'catalog', 'Export reports', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600V4XR21K1V7Q662PN', 'Manage business settings', 'settings.business.manage', 'catalog', 'Manage business settings', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600VQJGP3ZBR343SREX', 'Print product labels', 'documents.products.labels', 'catalog', 'Print product labels', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600VXBT6CNXEBPZ5FN7', 'Create purchase orders', 'procurement.create', 'catalog', 'Create purchase orders', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600VXE022GTTCEVNE4T', 'Update company details', 'company.update', 'organisation', 'Update company details', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600VYD45ZC64DTREK7G', 'View chart of accounts', 'accounting.accounts.view', 'Accounting Operations', 'View chart of accounts', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600WY71CXD72B4N9Y50', 'View system activity log', 'activity.view', 'catalog', 'View system activity log', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600X8MET42W3MSAZRKT', 'View backups', 'backups.view', 'Backup and Recovery', 'View backups', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600XBJMZC1DF9XK018E', 'View sales', 'sales.view', 'catalog', 'View sales', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600XPMG0N8C85ME248D', 'Approve purchase orders', 'procurement.approve', 'catalog', 'Approve purchase orders', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600XTFRZMQJS398WAAY', 'View branch inventory', 'inventory.view', 'catalog', 'View branch inventory', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600YBDGVEWTHWJSGR4K', 'Create invoices, quotes, and POS sales', 'sales.create', 'catalog', 'Create invoices, quotes, and POS sales', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600YX0XV38996SG6WE1', 'View suppliers', 'suppliers.view', 'catalog', 'View suppliers', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600Z2ZFH7K7N5AK9KVT', 'Create staff accounts', 'staff.create', 'staff', 'Create staff accounts', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600ZFE48PEHWJQS7805', 'Create branches', 'branches.create', 'organisation', 'Create branches', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600ZN40EX34RQEEWDEZ', 'Manage discount vouchers', 'vouchers.manage', 'Commercial', 'Manage discount vouchers', '2026-07-16 16:00:00', '2026-07-16 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `permission_role`
--

DROP TABLE IF EXISTS `permission_role`;
CREATE TABLE `permission_role` (
  `permission_id` char(26) NOT NULL,
  `role_id` char(26) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permission_role`
--

INSERT INTO `permission_role` (`permission_id`, `role_id`, `created_at`, `updated_at`) VALUES
('01KXM3J60005C8J2096EPP7C4G', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60005C8J2096EPP7C4G', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60005C8J2096EPP7C4G', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6000JM7P8PBMYDM6T41', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6000JM7P8PBMYDM6T41', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6000JM7P8PBMYDM6T41', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6000JM7P8PBMYDM6T41', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6000PEJJX50F2W98KKJ', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6000PEJJX50F2W98KKJ', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60014Y0K4D3SF388PR8', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60014Y0K4D3SF388PR8', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6001BYF6PKHTA7EM8YZ', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6001BYF6PKHTA7EM8YZ', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6001BYF6PKHTA7EM8YZ', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6001FBFFRZZVDSAVP5N', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6001FBFFRZZVDSAVP5N', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6001FBFFRZZVDSAVP5N', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6001WBGKA9PRTJ9G49X', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6001WBGKA9PRTJ9G49X', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6001WBGKA9PRTJ9G49X', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6001YPQKE9PMRPX2V7N', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6001YPQKE9PMRPX2V7N', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6001YPQKE9PMRPX2V7N', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6001YPQKE9PMRPX2V7N', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6002GWM3NNPM872AE8R', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6002GWM3NNPM872AE8R', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6002GWM3NNPM872AE8R', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6002JMCHFRXA7ECDJFD', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6002JMCHFRXA7ECDJFD', '01KXM3J600DC4CQH1N4JP054J3', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6002JMCHFRXA7ECDJFD', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6002JMCHFRXA7ECDJFD', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6002T6Y8JY13WH5H2KX', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6002T6Y8JY13WH5H2KX', '01KXM3J600QCQ2409DB59QWQCX', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6002T6Y8JY13WH5H2KX', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6002T6Y8JY13WH5H2KX', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6003AEQK9WWPF9EWSVM', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6003AEQK9WWPF9EWSVM', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6003AEQK9WWPF9EWSVM', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6003VTW8075FFN0H85E', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6003VTW8075FFN0H85E', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6003VTW8075FFN0H85E', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60043JQZA8M0T2F5NMK', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60043JQZA8M0T2F5NMK', '01KXM3J600DC4CQH1N4JP054J3', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60043JQZA8M0T2F5NMK', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60047BM6YRMK5A45RNF', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60047BM6YRMK5A45RNF', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6004D8PP1HNNC0FSK83', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6004D8PP1HNNC0FSK83', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6004D8PP1HNNC0FSK83', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6004D8PP1HNNC0FSK83', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6004MG8V37775GP0MP0', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6004MG8V37775GP0MP0', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6004MG8V37775GP0MP0', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6004MG8V37775GP0MP0', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6004YETRVTJ8Q7MRM5J', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6004YETRVTJ8Q7MRM5J', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6004YETRVTJ8Q7MRM5J', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6005KM3F1RR9A3SCS95', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6005KM3F1RR9A3SCS95', '01KXM3J600QCQ2409DB59QWQCX', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6005KM3F1RR9A3SCS95', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6005KM3F1RR9A3SCS95', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6005RF0HGYJQSHEK5BG', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6005RF0HGYJQSHEK5BG', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6005RF0HGYJQSHEK5BG', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6005RF0HGYJQSHEK5BG', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60062TJZTTNB76RQBMA', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60062TJZTTNB76RQBMA', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60063X9Y2PGYBXWZ8FV', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60063X9Y2PGYBXWZ8FV', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6006545WY682TVTW76A', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6006545WY682TVTW76A', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6006HVYHE5797HK8T1D', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6006HVYHE5797HK8T1D', '01KXM3J600QCQ2409DB59QWQCX', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6006HVYHE5797HK8T1D', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6006HVYHE5797HK8T1D', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6006MNY2HABM5DHZ03Q', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6006MNY2HABM5DHZ03Q', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6006MNY2HABM5DHZ03Q', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6006MNY2HABM5DHZ03Q', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6006XP8983RJEDSZG49', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6006XP8983RJEDSZG49', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6006XP8983RJEDSZG49', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6006XP8983RJEDSZG49', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6007ZDRHGZ2V6SPK570', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6007ZDRHGZ2V6SPK570', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60085NKHRX7NTCSMVWQ', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60085NKHRX7NTCSMVWQ', '01KXM3J600QCQ2409DB59QWQCX', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60085NKHRX7NTCSMVWQ', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60085NKHRX7NTCSMVWQ', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6008MD8ANZK5SSKR52J', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6008MD8ANZK5SSKR52J', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6008MD8ANZK5SSKR52J', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6008MD8ANZK5SSKR52J', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6008SJKP479Y93Q97AF', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6008SJKP479Y93Q97AF', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6008SJKP479Y93Q97AF', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60092BBD3QK9WYHZ3EJ', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J60092BBD3QK9WYHZ3EJ', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6009M25F6JHTG0JEN9G', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6009M25F6JHTG0JEN9G', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6009X1JWY41XSHYV2VP', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6009X1JWY41XSHYV2VP', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6009X1JWY41XSHYV2VP', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6009X1JWY41XSHYV2VP', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6009YY60EAW956N4MJ7', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6009YY60EAW956N4MJ7', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J6009YY60EAW956N4MJ7', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600A15WEFC0AFEEK3KN', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600A15WEFC0AFEEK3KN', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600A15WEFC0AFEEK3KN', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600A15WEFC0AFEEK3KN', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600A1KP99ERCC5CCVZS', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600A1KP99ERCC5CCVZS', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600A4261K9SFPX7QMZ5', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600A4261K9SFPX7QMZ5', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600B86PDKWVRA0GFSBM', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600B86PDKWVRA0GFSBM', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600B86PDKWVRA0GFSBM', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600BST89DRWQ512K19F', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600BST89DRWQ512K19F', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600BST89DRWQ512K19F', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600BST89DRWQ512K19F', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600BSZ9RNN183R0XMV7', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600BSZ9RNN183R0XMV7', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600BWYKH9ZH3X4F4HZF', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600BWYKH9ZH3X4F4HZF', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600BWYKH9ZH3X4F4HZF', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600C769EX8SRYZFG9V2', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600C769EX8SRYZFG9V2', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600C769EX8SRYZFG9V2', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600CAN83YJ8V3M7SK7N', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600CAN83YJ8V3M7SK7N', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600CFK3JV5P3MMM353Y', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600CFK3JV5P3MMM353Y', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600CY4A9H0JKSK1PWT4', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600CY4A9H0JKSK1PWT4', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600D83G2Z6RCHEAM9RW', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600D83G2Z6RCHEAM9RW', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DP1MD6TKYKEP1D49', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DP1MD6TKYKEP1D49', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DPKFV4WXGCAK4TY9', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DPKFV4WXGCAK4TY9', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DPMS6274DDA33TZ5', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DPMS6274DDA33TZ5', '01KXM3J600QCQ2409DB59QWQCX', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DPMS6274DDA33TZ5', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DPMS6274DDA33TZ5', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DQMDY5VD6M3V1NTR', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DQMDY5VD6M3V1NTR', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DQMDY5VD6M3V1NTR', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DSFFNTVVSNF2A78V', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DSFFNTVVSNF2A78V', '01KXM3J600DC4CQH1N4JP054J3', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DSFFNTVVSNF2A78V', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DSFFNTVVSNF2A78V', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DSFFNTVVSNF2A78V', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600E1K4AD8T5V1QNX03', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600E1K4AD8T5V1QNX03', '01KXM3J600QCQ2409DB59QWQCX', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600E1K4AD8T5V1QNX03', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600E1K4AD8T5V1QNX03', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600EP38V3XPE866BQJJ', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600EP38V3XPE866BQJJ', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600ESGV20BJ9JSQV3EM', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600ESGV20BJ9JSQV3EM', '01KXM3J600DC4CQH1N4JP054J3', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600ESGV20BJ9JSQV3EM', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600EYV099TJZ4C9C87A', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600EYV099TJZ4C9C87A', '01KXM3J600DC4CQH1N4JP054J3', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600EYV099TJZ4C9C87A', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600F5ZPDZ9CWZ91CPFA', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600F5ZPDZ9CWZ91CPFA', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600F5ZPDZ9CWZ91CPFA', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600F5ZPDZ9CWZ91CPFA', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600FJR5342A6NHAGAGT', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600FJR5342A6NHAGAGT', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600FJR5342A6NHAGAGT', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600FJR5342A6NHAGAGT', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600FPAGSWMC20J9VTCC', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600FPAGSWMC20J9VTCC', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600FPAGSWMC20J9VTCC', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600FPAGSWMC20J9VTCC', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600G392ZEQMRXVD769F', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600G392ZEQMRXVD769F', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600G392ZEQMRXVD769F', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600G392ZEQMRXVD769F', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600GAQ3819WYP2Q9SBT', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600GAQ3819WYP2Q9SBT', '01KXM3J600DC4CQH1N4JP054J3', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600GAQ3819WYP2Q9SBT', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600GAQ3819WYP2Q9SBT', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600GSZDAR3NCWDXRX9Z', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600GSZDAR3NCWDXRX9Z', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600GSZDAR3NCWDXRX9Z', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600GSZDAR3NCWDXRX9Z', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600H56RKZGCMEC3ZJVS', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600H56RKZGCMEC3ZJVS', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600HJNC87STWYF3508W', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600HJNC87STWYF3508W', '01KXM3J600DC4CQH1N4JP054J3', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600HJNC87STWYF3508W', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600HJNC87STWYF3508W', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600HY0K1NTKTGDV4B7Q', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600HY0K1NTKTGDV4B7Q', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600HY0K1NTKTGDV4B7Q', '01KXM3J600QCQ2409DB59QWQCX', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600HY0K1NTKTGDV4B7Q', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600HY0K1NTKTGDV4B7Q', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600K5A7Q4X6X9EZR6AR', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600K5A7Q4X6X9EZR6AR', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600K5A7Q4X6X9EZR6AR', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600K5A7Q4X6X9EZR6AR', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600KAM05NBP5JY6S6K6', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600KAM05NBP5JY6S6K6', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600KAM05NBP5JY6S6K6', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600KAM05NBP5JY6S6K6', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600KZCZ1WM77YFDQ199', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600KZCZ1WM77YFDQ199', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600M1D7P2WY2G7AK26T', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600M1D7P2WY2G7AK26T', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600M1D7P2WY2G7AK26T', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600M1D7P2WY2G7AK26T', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600M47P1SYDMTPX6VZF', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600M47P1SYDMTPX6VZF', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600M47P1SYDMTPX6VZF', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600M47P1SYDMTPX6VZF', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MC6YER0HJCYQ6ZR9', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MC6YER0HJCYQ6ZR9', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MC6YER0HJCYQ6ZR9', '01KXM3J600QCQ2409DB59QWQCX', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MC6YER0HJCYQ6ZR9', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MC6YER0HJCYQ6ZR9', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MDRN6YMA925XV74Q', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MDRN6YMA925XV74Q', '01KXM3J600QCQ2409DB59QWQCX', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MDRN6YMA925XV74Q', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MDRN6YMA925XV74Q', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MF1ZYAJS7PB7HQV2', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MF1ZYAJS7PB7HQV2', '01KXM3J600QCQ2409DB59QWQCX', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MF1ZYAJS7PB7HQV2', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MF1ZYAJS7PB7HQV2', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MF1ZYAJS7PB7HQV2', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MSJP5PSC2KHB96JF', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MSJP5PSC2KHB96JF', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MSJP5PSC2KHB96JF', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600MSJP5PSC2KHB96JF', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600N52074S0GVB2QK14', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600N52074S0GVB2QK14', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600N52074S0GVB2QK14', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600N8YFVPX910NG17V6', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600N8YFVPX910NG17V6', '01KXM3J600DC4CQH1N4JP054J3', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600N8YFVPX910NG17V6', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NAVR1T6E6AF1RFEA', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NAVR1T6E6AF1RFEA', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NAVR1T6E6AF1RFEA', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NAVR1T6E6AF1RFEA', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NG8JZDHEBSZB7AVR', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NG8JZDHEBSZB7AVR', '01KXM3J600DC4CQH1N4JP054J3', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NG8JZDHEBSZB7AVR', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NG8JZDHEBSZB7AVR', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NG8JZDHEBSZB7AVR', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NG8JZDHEBSZB7AVR', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NHEVA96TSS6J17ZG', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NHEVA96TSS6J17ZG', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NHEVA96TSS6J17ZG', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NQEEJX4T1ZN40BG2', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NQEEJX4T1ZN40BG2', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NSHKV5228WH2JJRG', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NSHKV5228WH2JJRG', '01KXM3J600DC4CQH1N4JP054J3', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NSHKV5228WH2JJRG', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NSHKV5228WH2JJRG', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600NSHKV5228WH2JJRG', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600P4WEXHKKWRCWJQ77', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600P4WEXHKKWRCWJQ77', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600P4WEXHKKWRCWJQ77', '01KXM3J600QCQ2409DB59QWQCX', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600P4WEXHKKWRCWJQ77', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600P4WEXHKKWRCWJQ77', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600P4WMP7Y605XW7YME', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600P4WMP7Y605XW7YME', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600P4WMP7Y605XW7YME', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600P8AJWJ7F92DX2SM1', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600P8AJWJ7F92DX2SM1', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600P8AJWJ7F92DX2SM1', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600PCEQK5FR3MF2T94W', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600PCEQK5FR3MF2T94W', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600Q6JHEB9ME7J1VZTT', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600Q6JHEB9ME7J1VZTT', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600QB12ZSNQXPWEGDZE', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600QB12ZSNQXPWEGDZE', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600QB12ZSNQXPWEGDZE', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600QQ2ZM8F5F3BF26CA', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600QQ2ZM8F5F3BF26CA', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600QQ2ZM8F5F3BF26CA', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600RMD4A585JP6CWC2W', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600RMD4A585JP6CWC2W', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600RMD4A585JP6CWC2W', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600RNGAAGBQ0TJTBRMD', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600RNGAAGBQ0TJTBRMD', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600RNGAAGBQ0TJTBRMD', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600RNGAAGBQ0TJTBRMD', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600RXD2MQCH2DTP9576', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600RXD2MQCH2DTP9576', '01KXM3J600DC4CQH1N4JP054J3', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600RXD2MQCH2DTP9576', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600RXD2MQCH2DTP9576', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600RXD2MQCH2DTP9576', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600S2ETJPW6FW0ZS9TB', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600S2ETJPW6FW0ZS9TB', '01KXM3J600QCQ2409DB59QWQCX', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600S2ETJPW6FW0ZS9TB', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600S2ETJPW6FW0ZS9TB', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600S2FBM7DKQ7H5APB4', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600S2FBM7DKQ7H5APB4', '01KXM3J600QCQ2409DB59QWQCX', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600S2FBM7DKQ7H5APB4', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600S2FBM7DKQ7H5APB4', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600SQ4TMQQKCZES490R', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600SQ4TMQQKCZES490R', '01KXM3J600QCQ2409DB59QWQCX', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600SQ4TMQQKCZES490R', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600SQ4TMQQKCZES490R', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600SYR8QMKD4D68TS9X', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600SYR8QMKD4D68TS9X', '01KXM3J600DC4CQH1N4JP054J3', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600SYR8QMKD4D68TS9X', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600SYR8QMKD4D68TS9X', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600SYR8QMKD4D68TS9X', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600V4XR21K1V7Q662PN', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600V4XR21K1V7Q662PN', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600VQJGP3ZBR343SREX', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600VQJGP3ZBR343SREX', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600VXBT6CNXEBPZ5FN7', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600VXBT6CNXEBPZ5FN7', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600VXBT6CNXEBPZ5FN7', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600VXBT6CNXEBPZ5FN7', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600VXE022GTTCEVNE4T', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600VXE022GTTCEVNE4T', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600VYD45ZC64DTREK7G', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600VYD45ZC64DTREK7G', '01KXM3J600DC4CQH1N4JP054J3', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600VYD45ZC64DTREK7G', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600VYD45ZC64DTREK7G', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600WY71CXD72B4N9Y50', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600WY71CXD72B4N9Y50', '01KXM3J600DC4CQH1N4JP054J3', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600WY71CXD72B4N9Y50', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600X8MET42W3MSAZRKT', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600X8MET42W3MSAZRKT', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600XBJMZC1DF9XK018E', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600XBJMZC1DF9XK018E', '01KXM3J600ERVF9ADSMQMSKTQ4', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600XBJMZC1DF9XK018E', '01KXM3J600QCQ2409DB59QWQCX', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600XBJMZC1DF9XK018E', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600XBJMZC1DF9XK018E', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600XPMG0N8C85ME248D', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600XPMG0N8C85ME248D', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600XPMG0N8C85ME248D', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600XPMG0N8C85ME248D', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600XTFRZMQJS398WAAY', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600XTFRZMQJS398WAAY', '01KXM3J600QCQ2409DB59QWQCX', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600XTFRZMQJS398WAAY', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600XTFRZMQJS398WAAY', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600XTFRZMQJS398WAAY', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600YBDGVEWTHWJSGR4K', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600YBDGVEWTHWJSGR4K', '01KXM3J600QCQ2409DB59QWQCX', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600YBDGVEWTHWJSGR4K', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600YBDGVEWTHWJSGR4K', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600YX0XV38996SG6WE1', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600YX0XV38996SG6WE1', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600YX0XV38996SG6WE1', '01KXM3J600SK7C6MY0H24VMV2R', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600YX0XV38996SG6WE1', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600Z2ZFH7K7N5AK9KVT', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600Z2ZFH7K7N5AK9KVT', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600Z2ZFH7K7N5AK9KVT', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600ZFE48PEHWJQS7805', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600ZFE48PEHWJQS7805', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600ZFE48PEHWJQS7805', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600ZN40EX34RQEEWDEZ', '01KXM3J6009DJAYM1NZBY9FARF', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600ZN40EX34RQEEWDEZ', '01KXM3J600R373WDVBY1AG1Y44', '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600ZN40EX34RQEEWDEZ', '01KXM3J600ZRCJT9JM5F71N0V5', '2026-07-16 16:00:00', '2026-07-16 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` char(26) NOT NULL,
  `name` varchar(200) NOT NULL,
  `sku` varchar(100) NOT NULL,
  `barcode` varchar(160) DEFAULT NULL,
  `category_id` char(26) NOT NULL,
  `brand_id` char(26) DEFAULT NULL,
  `tax_rate_id` char(26) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `track_inventory` tinyint(1) NOT NULL DEFAULT 1,
  `default_price_kobo` bigint(20) UNSIGNED NOT NULL,
  `default_cost_price_kobo` bigint(20) UNSIGNED DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_branch_prices`
--

DROP TABLE IF EXISTS `product_branch_prices`;
CREATE TABLE `product_branch_prices` (
  `id` char(26) NOT NULL,
  `product_id` char(26) NOT NULL,
  `branch_id` char(26) NOT NULL,
  `price_kobo` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_branch_stock`
--

DROP TABLE IF EXISTS `product_branch_stock`;
CREATE TABLE `product_branch_stock` (
  `id` char(26) NOT NULL,
  `product_id` char(26) NOT NULL,
  `branch_id` char(26) NOT NULL,
  `quantity_milliunits` bigint(20) NOT NULL DEFAULT 0,
  `minimum_stock_milliunits` bigint(20) NOT NULL DEFAULT 5000,
  `last_movement_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
CREATE TABLE `product_categories` (
  `id` char(26) NOT NULL,
  `name` varchar(140) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_imports`
--

DROP TABLE IF EXISTS `product_imports`;
CREATE TABLE `product_imports` (
  `id` char(26) NOT NULL,
  `account_id` char(26) DEFAULT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_path` varchar(500) NOT NULL,
  `error_report_path` varchar(500) DEFAULT NULL,
  `status` varchar(40) NOT NULL,
  `total_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `valid_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `invalid_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_products` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_products` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_categories` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_brands` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_suppliers` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`summary`)),
  `validated_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_import_rows`
--

DROP TABLE IF EXISTS `product_import_rows`;
CREATE TABLE `product_import_rows` (
  `id` char(26) NOT NULL,
  `product_import_id` char(26) NOT NULL,
  `row_number` int(10) UNSIGNED NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`errors`)),
  `is_valid` tinyint(1) NOT NULL DEFAULT 0,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `id` char(26) NOT NULL,
  `order_number` varchar(80) NOT NULL,
  `supplier_id` char(26) NOT NULL,
  `branch_id` char(26) NOT NULL,
  `created_by_account_id` char(26) DEFAULT NULL,
  `approved_by_account_id` char(26) DEFAULT NULL,
  `status` varchar(40) NOT NULL,
  `expected_at` date DEFAULT NULL,
  `subtotal_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `tax_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `total_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `reference_note` text NOT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_lines`
--

DROP TABLE IF EXISTS `purchase_order_lines`;
CREATE TABLE `purchase_order_lines` (
  `id` char(26) NOT NULL,
  `purchase_order_id` char(26) NOT NULL,
  `product_id` char(26) NOT NULL,
  `ordered_quantity_milliunits` bigint(20) NOT NULL,
  `received_quantity_milliunits` bigint(20) NOT NULL DEFAULT 0,
  `unit_cost_kobo` bigint(20) UNSIGNED NOT NULL,
  `tax_rate_basis_points` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `line_total_kobo` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_receipts`
--

DROP TABLE IF EXISTS `purchase_receipts`;
CREATE TABLE `purchase_receipts` (
  `id` char(26) NOT NULL,
  `receipt_number` varchar(40) NOT NULL,
  `supplier_id` char(26) NOT NULL,
  `branch_id` char(26) NOT NULL,
  `recorded_by_account_id` char(26) NOT NULL,
  `purchase_order_id` char(26) DEFAULT NULL,
  `purchased_at` date NOT NULL,
  `supplier_reference` varchar(160) DEFAULT NULL,
  `subtotal_kobo` bigint(20) UNSIGNED NOT NULL,
  `discount_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `tax_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `total_kobo` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'recorded',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_receipt_lines`
--

DROP TABLE IF EXISTS `purchase_receipt_lines`;
CREATE TABLE `purchase_receipt_lines` (
  `id` char(26) NOT NULL,
  `purchase_receipt_id` char(26) NOT NULL,
  `product_id` char(26) NOT NULL,
  `quantity_milliunits` bigint(20) NOT NULL,
  `unit_cost_kobo` bigint(20) UNSIGNED NOT NULL,
  `discount_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `tax_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `line_total_kobo` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_returns`
--

DROP TABLE IF EXISTS `purchase_returns`;
CREATE TABLE `purchase_returns` (
  `id` char(26) NOT NULL,
  `return_number` varchar(40) NOT NULL,
  `purchase_receipt_id` char(26) NOT NULL,
  `supplier_id` char(26) NOT NULL,
  `branch_id` char(26) NOT NULL,
  `processed_by_account_id` char(26) NOT NULL,
  `total_kobo` bigint(20) UNSIGNED NOT NULL,
  `supplier_credit_reference` varchar(180) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'completed',
  `reason` text NOT NULL,
  `returned_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_return_lines`
--

DROP TABLE IF EXISTS `purchase_return_lines`;
CREATE TABLE `purchase_return_lines` (
  `id` char(26) NOT NULL,
  `purchase_return_id` char(26) NOT NULL,
  `purchase_receipt_line_id` char(26) NOT NULL,
  `product_id` char(26) NOT NULL,
  `quantity_milliunits` bigint(20) NOT NULL,
  `unit_cost_kobo` bigint(20) UNSIGNED NOT NULL,
  `line_total_kobo` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quote_conversions`
--

DROP TABLE IF EXISTS `quote_conversions`;
CREATE TABLE `quote_conversions` (
  `id` char(26) NOT NULL,
  `source_quote_id` char(26) NOT NULL,
  `converted_sale_id` char(26) NOT NULL,
  `converted_by_account_id` char(26) NOT NULL,
  `target_type` varchar(20) NOT NULL,
  `converted_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` char(26) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `is_system`, `is_active`, `created_at`, `updated_at`) VALUES
('01KXM3J6009DJAYM1NZBY9FARF', 'Administrator', 'administrator', 'Administrative access across configured business modules.', 1, 1, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600DC4CQH1N4JP054J3', 'Auditor', 'auditor', 'Read-only audit, security, reports and accounting review.', 1, 1, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600ERVF9ADSMQMSKTQ4', 'Accountant', 'accountant', 'Accounting, receivables, supplier finance and financial reports.', 1, 1, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600QCQ2409DB59QWQCX', 'Sales Staff', 'sales-staff', 'Sales, customers, payments and own-record operations.', 1, 1, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600R373WDVBY1AG1Y44', 'Branch Manager', 'branch-manager', 'Branch-scoped operational management.', 1, 1, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600SK7C6MY0H24VMV2R', 'Inventory Manager', 'inventory-manager', 'Products, stock, transfers and procurement operations.', 1, 1, '2026-07-16 16:00:00', '2026-07-16 16:00:00'),
('01KXM3J600ZRCJT9JM5F71N0V5', 'System Owner', 'system-owner', 'Full installation owner access.', 1, 1, '2026-07-16 16:00:00', '2026-07-16 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (
  `id` char(26) NOT NULL,
  `sale_code` varchar(40) NOT NULL,
  `sale_type` varchar(30) NOT NULL,
  `branch_id` char(26) NOT NULL,
  `customer_id` char(26) DEFAULT NULL,
  `sold_by_account_id` char(26) NOT NULL,
  `converted_from_sale_id` char(26) DEFAULT NULL,
  `sale_date` date NOT NULL,
  `subtotal_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `discount_amount_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `tax_amount_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `grand_total_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `paid_amount_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL,
  `idempotency_key` varchar(80) NOT NULL,
  `notes` text DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `discount_voucher_id` char(26) DEFAULT NULL,
  `credit_note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
CREATE TABLE `sale_items` (
  `id` char(26) NOT NULL,
  `sale_id` char(26) NOT NULL,
  `product_id` char(26) NOT NULL,
  `product_name_snapshot` varchar(200) NOT NULL,
  `sku_snapshot` varchar(100) NOT NULL,
  `track_inventory_snapshot` tinyint(1) NOT NULL,
  `quantity_milliunits` bigint(20) NOT NULL,
  `unit_price_kobo` bigint(20) UNSIGNED NOT NULL,
  `discount_amount_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `tax_amount_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `line_total_kobo` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `unit_cost_kobo_snapshot` bigint(20) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sale_returns`
--

DROP TABLE IF EXISTS `sale_returns`;
CREATE TABLE `sale_returns` (
  `id` char(26) NOT NULL,
  `return_code` varchar(40) NOT NULL,
  `sale_id` char(26) NOT NULL,
  `branch_id` char(26) NOT NULL,
  `customer_id` char(26) DEFAULT NULL,
  `processed_by_account_id` char(26) NOT NULL,
  `total_refund_kobo` bigint(20) UNSIGNED NOT NULL,
  `refund_method` varchar(80) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'completed',
  `reason` text NOT NULL,
  `returned_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sale_return_items`
--

DROP TABLE IF EXISTS `sale_return_items`;
CREATE TABLE `sale_return_items` (
  `id` char(26) NOT NULL,
  `sale_return_id` char(26) NOT NULL,
  `sale_item_id` char(26) NOT NULL,
  `product_id` char(26) NOT NULL,
  `quantity_milliunits` bigint(20) NOT NULL,
  `refund_amount_kobo` bigint(20) UNSIGNED NOT NULL,
  `restock` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_events`
--

DROP TABLE IF EXISTS `security_events`;
CREATE TABLE `security_events` (
  `id` char(26) NOT NULL,
  `event_type` varchar(64) NOT NULL,
  `actor_account_id` char(26) DEFAULT NULL,
  `subject_account_id` char(26) DEFAULT NULL,
  `session_identifier` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `occurred_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `security_events`
--

INSERT INTO `security_events` (`id`, `event_type`, `actor_account_id`, `subject_account_id`, `session_identifier`, `ip_address`, `user_agent`, `context`, `occurred_at`, `created_at`) VALUES
('01kxpkcfam6srr17pda13ke8qk', 'login_failed', NULL, NULL, 'SYYdC2vaMZtetfp1yBQAnsFbhfhE9Bdksku1h2cP', '197.210.55.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"reason\":\"invalid_credentials\"}', '2026-07-17 04:14:59', '2026-07-17 04:14:59'),
('01kxpkec85zszy8w6crf08qr59', 'login_failed', NULL, NULL, 'SYYdC2vaMZtetfp1yBQAnsFbhfhE9Bdksku1h2cP', '197.210.55.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '{\"reason\":\"invalid_credentials\"}', '2026-07-17 04:16:01', '2026-07-17 04:16:01'),
('01kxpkphnvg5xbgeeecg1hbffd', 'login_succeeded', '01KXM3J6007BWNQEDX4BZMV8C9', '01KXM3J6007BWNQEDX4BZMV8C9', 'LO4YepWElfYLPNX09jE8Ub5I747hR0H4cH953XLB', '197.210.55.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '[]', '2026-07-17 04:20:29', '2026-07-17 04:20:29'),
('01kxpm931rpxccn0tb2q5gk5zk', 'logout', '01KXM3J6007BWNQEDX4BZMV8C9', '01KXM3J6007BWNQEDX4BZMV8C9', 'LO4YepWElfYLPNX09jE8Ub5I747hR0H4cH953XLB', '197.210.55.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '[]', '2026-07-17 04:30:36', '2026-07-17 04:30:36'),
('01kxpma3dxnyntp2011cxjjw5y', 'login_succeeded', '01KXM3J6007BWNQEDX4BZMV8C9', '01KXM3J6007BWNQEDX4BZMV8C9', 'e2Z7FuNnVcWGXmoWbLPZtXVyiapRQlCyDZbcVW0M', '197.210.55.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '[]', '2026-07-17 04:31:09', '2026-07-17 04:31:09'),
('01kxpmav5gka98kgex7xsms0cw', 'account_suspended', '01KXM3J6007BWNQEDX4BZMV8C9', NULL, 'e2Z7FuNnVcWGXmoWbLPZtXVyiapRQlCyDZbcVW0M', '197.210.55.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '[]', '2026-07-17 04:31:34', '2026-07-17 04:31:34'),
('01kxpmcdrpn1nps097hqe6sac2', 'logout', '01KXM3J6007BWNQEDX4BZMV8C9', '01KXM3J6007BWNQEDX4BZMV8C9', 'e2Z7FuNnVcWGXmoWbLPZtXVyiapRQlCyDZbcVW0M', '197.210.55.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '[]', '2026-07-17 04:32:26', '2026-07-17 04:32:26'),
('01kxpmczkw0c95wr49myz0ghkd', 'login_succeeded', NULL, NULL, 'nLB5NB6ZQcPBA5cY9vmK5wHGKfjnCb24tUTR3YKJ', '197.210.55.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '[]', '2026-07-17 04:32:44', '2026-07-17 04:32:44'),
('01kxpp1z6qtznqfgyqd1qnz9b7', 'login_succeeded', '01KXM3J6007BWNQEDX4BZMV8C9', '01KXM3J6007BWNQEDX4BZMV8C9', '1GGZe3uzCJkVWga7XoWZmsKZTSfKsUSZoHYhzWc9', '197.210.55.248', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36', '[]', '2026-07-17 05:01:40', '2026-07-17 05:01:40');

-- --------------------------------------------------------

--
-- Table structure for table `standalone_receipts`
--

DROP TABLE IF EXISTS `standalone_receipts`;
CREATE TABLE `standalone_receipts` (
  `id` char(26) NOT NULL,
  `receipt_number` varchar(40) NOT NULL,
  `branch_id` char(26) NOT NULL,
  `customer_id` char(26) DEFAULT NULL,
  `payment_method_id` char(26) NOT NULL,
  `received_by_account_id` char(26) NOT NULL,
  `payer_name` varchar(180) NOT NULL,
  `payer_phone` varchar(80) DEFAULT NULL,
  `amount_kobo` bigint(20) UNSIGNED NOT NULL,
  `reference` varchar(180) DEFAULT NULL,
  `purpose` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'received',
  `received_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE `stock_movements` (
  `id` char(26) NOT NULL,
  `product_id` char(26) NOT NULL,
  `branch_id` char(26) NOT NULL,
  `account_id` char(26) DEFAULT NULL,
  `movement_type` varchar(40) NOT NULL,
  `quantity_delta_milliunits` bigint(20) NOT NULL,
  `balance_after_milliunits` bigint(20) NOT NULL,
  `unit_cost_kobo` bigint(20) UNSIGNED DEFAULT NULL,
  `reference_type` varchar(60) DEFAULT NULL,
  `reference_id` varchar(64) DEFAULT NULL,
  `correlation_id` char(26) DEFAULT NULL,
  `reason_code` varchar(60) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `occurred_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` char(26) NOT NULL,
  `supplier_code` varchar(60) NOT NULL,
  `company_name` varchar(180) NOT NULL,
  `contact_person` varchar(160) DEFAULT NULL,
  `category` varchar(120) DEFAULT NULL,
  `email_encrypted` text DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tax_number_encrypted` text DEFAULT NULL,
  `payment_terms_days` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `credit_limit_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `lead_time_days` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `delivery_terms` text DEFAULT NULL,
  `return_policy` text DEFAULT NULL,
  `is_preferred` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_bills`
--

DROP TABLE IF EXISTS `supplier_bills`;
CREATE TABLE `supplier_bills` (
  `id` char(26) NOT NULL,
  `bill_number` varchar(80) NOT NULL,
  `supplier_reference` varchar(160) DEFAULT NULL,
  `supplier_id` char(26) NOT NULL,
  `branch_id` char(26) NOT NULL,
  `purchase_order_id` char(26) DEFAULT NULL,
  `created_by_account_id` char(26) DEFAULT NULL,
  `bill_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal_kobo` bigint(20) UNSIGNED NOT NULL,
  `tax_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `total_kobo` bigint(20) UNSIGNED NOT NULL,
  `paid_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `status` varchar(40) NOT NULL,
  `reference_note` text NOT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_bill_lines`
--

DROP TABLE IF EXISTS `supplier_bill_lines`;
CREATE TABLE `supplier_bill_lines` (
  `id` char(26) NOT NULL,
  `supplier_bill_id` char(26) NOT NULL,
  `product_id` char(26) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity_milliunits` bigint(20) NOT NULL,
  `unit_cost_kobo` bigint(20) UNSIGNED NOT NULL,
  `tax_rate_basis_points` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `line_subtotal_kobo` bigint(20) UNSIGNED NOT NULL,
  `tax_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `line_total_kobo` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_bill_payments`
--

DROP TABLE IF EXISTS `supplier_bill_payments`;
CREATE TABLE `supplier_bill_payments` (
  `id` char(26) NOT NULL,
  `supplier_bill_id` char(26) NOT NULL,
  `payment_method_id` char(26) NOT NULL,
  `recorded_by_account_id` char(26) NOT NULL,
  `amount_kobo` bigint(20) UNSIGNED NOT NULL,
  `reference` varchar(160) DEFAULT NULL,
  `paid_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_documents`
--

DROP TABLE IF EXISTS `supplier_documents`;
CREATE TABLE `supplier_documents` (
  `id` char(26) NOT NULL,
  `supplier_id` char(26) NOT NULL,
  `supplier_bill_id` char(26) DEFAULT NULL,
  `uploaded_by_account_id` char(26) DEFAULT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_path` varchar(500) NOT NULL,
  `mime_type` varchar(160) NOT NULL,
  `size_bytes` bigint(20) UNSIGNED NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_returns`
--

DROP TABLE IF EXISTS `supplier_returns`;
CREATE TABLE `supplier_returns` (
  `id` char(26) NOT NULL,
  `return_number` varchar(80) NOT NULL,
  `supplier_id` char(26) NOT NULL,
  `branch_id` char(26) NOT NULL,
  `supplier_bill_id` char(26) DEFAULT NULL,
  `created_by_account_id` char(26) DEFAULT NULL,
  `status` varchar(40) NOT NULL,
  `return_date` date NOT NULL,
  `total_kobo` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `reason` varchar(120) NOT NULL,
  `reference_note` text NOT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_return_lines`
--

DROP TABLE IF EXISTS `supplier_return_lines`;
CREATE TABLE `supplier_return_lines` (
  `id` char(26) NOT NULL,
  `supplier_return_id` char(26) NOT NULL,
  `product_id` char(26) NOT NULL,
  `quantity_milliunits` bigint(20) NOT NULL,
  `unit_cost_kobo` bigint(20) UNSIGNED NOT NULL,
  `line_total_kobo` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tax_rates`
--

DROP TABLE IF EXISTS `tax_rates`;
CREATE TABLE `tax_rates` (
  `id` char(26) NOT NULL,
  `name` varchar(120) NOT NULL,
  `rate_basis_points` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voucher_redemptions`
--

DROP TABLE IF EXISTS `voucher_redemptions`;
CREATE TABLE `voucher_redemptions` (
  `id` char(26) NOT NULL,
  `discount_voucher_id` char(26) NOT NULL,
  `sale_id` char(26) NOT NULL,
  `customer_id` char(26) DEFAULT NULL,
  `redeemed_by_account_id` char(26) NOT NULL,
  `discount_amount_kobo` bigint(20) UNSIGNED NOT NULL,
  `redeemed_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounting_periods`
--
ALTER TABLE `accounting_periods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_accounting_periods_starts_on_ends_on` (`starts_on`,`ends_on`),
  ADD KEY `idx_accounting_periods_starts_on` (`starts_on`),
  ADD KEY `idx_accounting_periods_ends_on` (`ends_on`),
  ADD KEY `idx_accounting_periods_status` (`status`),
  ADD KEY `fk_accounting_periods_closed_by_account_id` (`closed_by_account_id`),
  ADD KEY `fk_accounting_periods_locked_by_account_id` (`locked_by_account_id`);

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_accounts_public_id` (`public_id`),
  ADD UNIQUE KEY `uniq_accounts_login_key_blind_index` (`login_key_blind_index`),
  ADD KEY `idx_accounts_status` (`status`),
  ADD KEY `idx_accounts_last_authenticated_at` (`last_authenticated_at`),
  ADD KEY `idx_accounts_last_name_first_name` (`last_name`,`first_name`),
  ADD KEY `idx_accounts_status_last_name_first_name` (`status`,`last_name`,`first_name`),
  ADD KEY `idx_accounts_is_allowed_all_branches` (`is_allowed_all_branches`);

--
-- Indexes for table `account_branch`
--
ALTER TABLE `account_branch`
  ADD PRIMARY KEY (`account_id`,`branch_id`),
  ADD KEY `fk_account_branch_branch_id` (`branch_id`);

--
-- Indexes for table `account_role`
--
ALTER TABLE `account_role`
  ADD PRIMARY KEY (`account_id`,`role_id`),
  ADD KEY `fk_account_role_role_id` (`role_id`);

--
-- Indexes for table `account_sessions`
--
ALTER TABLE `account_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_account_sessions_session_identifier` (`session_identifier`),
  ADD KEY `idx_account_sessions_last_activity_at` (`last_activity_at`),
  ADD KEY `idx_account_sessions_revoked_at` (`revoked_at`),
  ADD KEY `account_sessions_activity_index` (`account_id`,`revoked_at`,`last_activity_at`);

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_notifications_open_entity_unique` (`notification_type`,`entity_type`,`entity_id`,`branch_id`,`resolved_at`),
  ADD KEY `idx_admin_notifications_notification_type` (`notification_type`),
  ADD KEY `idx_admin_notifications_entity_type` (`entity_type`),
  ADD KEY `idx_admin_notifications_entity_id` (`entity_id`),
  ADD KEY `idx_admin_notifications_occurred_at` (`occurred_at`),
  ADD KEY `idx_admin_notifications_read_at` (`read_at`),
  ADD KEY `idx_admin_notifications_resolved_at` (`resolved_at`),
  ADD KEY `admin_notifications_open_index` (`notification_type`,`resolved_at`,`occurred_at`),
  ADD KEY `fk_admin_notifications_branch_id` (`branch_id`);

--
-- Indexes for table `alert_recipients`
--
ALTER TABLE `alert_recipients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_alert_recipients_email` (`email`),
  ADD KEY `idx_alert_recipients_is_active` (`is_active`),
  ADD KEY `idx_alert_recipients_is_active_email` (`is_active`,`email`),
  ADD KEY `fk_alert_recipients_added_by_account_id` (`added_by_account_id`);

--
-- Indexes for table `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_api_tokens_token_hash` (`token_hash`),
  ADD KEY `idx_api_tokens_token_prefix` (`token_prefix`),
  ADD KEY `idx_api_tokens_last_used_at` (`last_used_at`),
  ADD KEY `idx_api_tokens_expires_at` (`expires_at`),
  ADD KEY `idx_api_tokens_revoked_at` (`revoked_at`),
  ADD KEY `api_tokens_validity_index` (`revoked_at`,`expires_at`),
  ADD KEY `fk_api_tokens_created_by_account_id` (`created_by_account_id`);

--
-- Indexes for table `asset_depreciation_postings`
--
ALTER TABLE `asset_depreciation_postings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_asset_depreciation_postings_fixed_asset_id_period_end` (`fixed_asset_id`,`period_end`),
  ADD KEY `idx_asset_depreciation_postings_period_end` (`period_end`),
  ADD KEY `fk_asset_depreciation_postings_journal_entry_id` (`journal_entry_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_logs_action` (`action`),
  ADD KEY `idx_audit_logs_entity_type` (`entity_type`),
  ADD KEY `idx_audit_logs_entity_id` (`entity_id`),
  ADD KEY `idx_audit_logs_ip_address` (`ip_address`),
  ADD KEY `idx_audit_logs_occurred_at` (`occurred_at`),
  ADD KEY `audit_logs_entity_time_index` (`entity_type`,`entity_id`,`occurred_at`),
  ADD KEY `audit_logs_branch_time_index` (`branch_id`,`occurred_at`),
  ADD KEY `audit_logs_actor_time_index` (`actor_account_id`,`occurred_at`);

--
-- Indexes for table `backup_runs`
--
ALTER TABLE `backup_runs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_backup_runs_status` (`status`),
  ADD KEY `idx_backup_runs_started_at` (`started_at`),
  ADD KEY `idx_backup_runs_completed_at` (`completed_at`),
  ADD KEY `fk_backup_runs_requested_by_account_id` (`requested_by_account_id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_branches_code` (`code`),
  ADD KEY `idx_branches_status` (`status`),
  ADD KEY `idx_branches_is_head_office` (`is_head_office`),
  ADD KEY `idx_branches_status_name` (`status`,`name`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_brands_slug` (`slug`),
  ADD KEY `idx_brands_status` (`status`),
  ADD KEY `idx_brands_status_name` (`status`,`name`);

--
-- Indexes for table `business_insights`
--
ALTER TABLE `business_insights`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_insights_scope_unique` (`category`,`branch_id`,`period_start`,`period_end`,`title`),
  ADD KEY `idx_business_insights_category` (`category`),
  ADD KEY `idx_business_insights_severity` (`severity`),
  ADD KEY `idx_business_insights_generated_at` (`generated_at`),
  ADD KEY `fk_business_insights_branch_id` (`branch_id`);

--
-- Indexes for table `business_settings`
--
ALTER TABLE `business_settings`
  ADD PRIMARY KEY (`singleton_key`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `idx_cache_expiration` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `idx_cache_locks_expiration` (`expiration`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_companies_is_configured` (`is_configured`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_customers_customer_code` (`customer_code`),
  ADD KEY `idx_customers_phone` (`phone`),
  ADD KEY `idx_customers_is_wholesale` (`is_wholesale`),
  ADD KEY `idx_customers_status` (`status`),
  ADD KEY `idx_customers_status_name` (`status`,`name`),
  ADD KEY `idx_customers_phone_status` (`phone`,`status`),
  ADD KEY `fk_customers_created_by_account_id` (`created_by_account_id`);

--
-- Indexes for table `discount_vouchers`
--
ALTER TABLE `discount_vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_discount_vouchers_code` (`code`),
  ADD KEY `idx_discount_vouchers_starts_at` (`starts_at`),
  ADD KEY `idx_discount_vouchers_ends_at` (`ends_at`),
  ADD KEY `idx_discount_vouchers_status` (`status`),
  ADD KEY `fk_discount_vouchers_created_by_account_id` (`created_by_account_id`);

--
-- Indexes for table `document_brandings`
--
ALTER TABLE `document_brandings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_document_brandings_updated_by_account_id` (`updated_by_account_id`);

--
-- Indexes for table `end_of_day_digests`
--
ALTER TABLE `end_of_day_digests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_end_of_day_digests_business_date` (`business_date`),
  ADD KEY `idx_end_of_day_digests_status` (`status`),
  ADD KEY `idx_end_of_day_digests_started_at` (`started_at`),
  ADD KEY `idx_end_of_day_digests_sent_at` (`sent_at`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_failed_jobs_uuid` (`uuid`),
  ADD KEY `idx_failed_jobs_connection_queue_failed_at` (`connection`,`queue`,`failed_at`);

--
-- Indexes for table `fixed_assets`
--
ALTER TABLE `fixed_assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_fixed_assets_asset_code` (`asset_code`),
  ADD KEY `idx_fixed_assets_acquired_at` (`acquired_at`),
  ADD KEY `idx_fixed_assets_status` (`status`),
  ADD KEY `idx_fixed_assets_category_status` (`category`,`status`),
  ADD KEY `fk_fixed_assets_branch_id` (`branch_id`),
  ADD KEY `fk_fixed_assets_custodian_account_id` (`custodian_account_id`),
  ADD KEY `fk_fixed_assets_created_by_account_id` (`created_by_account_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_jobs_queue` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_journal_entries_journal_number` (`journal_number`),
  ADD UNIQUE KEY `journal_source_event_unique` (`source_type`,`source_id`,`source_event`),
  ADD KEY `idx_journal_entries_entry_date` (`entry_date`),
  ADD KEY `idx_journal_entries_source_type` (`source_type`),
  ADD KEY `idx_journal_entries_source_id` (`source_id`),
  ADD KEY `idx_journal_entries_status` (`status`),
  ADD KEY `idx_journal_entries_posted_at` (`posted_at`),
  ADD KEY `idx_journal_entries_reversed_at` (`reversed_at`),
  ADD KEY `idx_journal_entries_entry_date_status` (`entry_date`,`status`),
  ADD KEY `fk_journal_entries_accounting_period_id` (`accounting_period_id`),
  ADD KEY `fk_journal_entries_branch_id` (`branch_id`),
  ADD KEY `fk_journal_entries_created_by_account_id` (`created_by_account_id`),
  ADD KEY `fk_journal_entries_reversal_of_entry_id` (`reversal_of_entry_id`);

--
-- Indexes for table `journal_lines`
--
ALTER TABLE `journal_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_journal_lines_ledger_account_id_journal_entry_id` (`ledger_account_id`,`journal_entry_id`),
  ADD KEY `idx_journal_lines_customer_id_ledger_account_id` (`customer_id`,`ledger_account_id`),
  ADD KEY `idx_journal_lines_supplier_id_ledger_account_id` (`supplier_id`,`ledger_account_id`),
  ADD KEY `fk_journal_lines_journal_entry_id` (`journal_entry_id`),
  ADD KEY `fk_journal_lines_branch_id` (`branch_id`);

--
-- Indexes for table `ledger_accounts`
--
ALTER TABLE `ledger_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_ledger_accounts_code` (`code`),
  ADD KEY `idx_ledger_accounts_type` (`type`),
  ADD KEY `idx_ledger_accounts_is_control_account` (`is_control_account`),
  ADD KEY `idx_ledger_accounts_is_system` (`is_system`),
  ADD KEY `idx_ledger_accounts_is_active` (`is_active`),
  ADD KEY `idx_ledger_accounts_type_is_active_code` (`type`,`is_active`,`code`),
  ADD KEY `fk_ledger_accounts_parent_id` (`parent_id`);

--
-- Indexes for table `low_stock_alerts`
--
ALTER TABLE `low_stock_alerts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `low_stock_alerts_resolution_unique` (`product_id`,`branch_id`,`resolved_at`),
  ADD KEY `idx_low_stock_alerts_opened_at` (`opened_at`),
  ADD KEY `idx_low_stock_alerts_last_seen_at` (`last_seen_at`),
  ADD KEY `idx_low_stock_alerts_resolved_at` (`resolved_at`),
  ADD KEY `low_stock_alerts_branch_open_index` (`branch_id`,`resolved_at`,`opened_at`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `operation_document_logs`
--
ALTER TABLE `operation_document_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_operation_document_logs_operation_type` (`operation_type`),
  ADD KEY `idx_operation_document_logs_operation_id` (`operation_id`),
  ADD KEY `idx_operation_document_logs_generated_at` (`generated_at`),
  ADD KEY `operation_document_lookup` (`operation_type`,`operation_id`,`generated_at`),
  ADD KEY `fk_operation_document_logs_generated_by_account_id` (`generated_by_account_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payments_paid_at` (`paid_at`),
  ADD KEY `idx_payments_sale_id_paid_at` (`sale_id`,`paid_at`),
  ADD KEY `fk_payments_payment_method_id` (`payment_method_id`),
  ADD KEY `fk_payments_recorded_by_account_id` (`recorded_by_account_id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_payment_methods_name` (`name`),
  ADD KEY `idx_payment_methods_is_system_default` (`is_system_default`),
  ADD KEY `idx_payment_methods_is_default_for_pos` (`is_default_for_pos`),
  ADD KEY `idx_payment_methods_is_active` (`is_active`),
  ADD KEY `idx_payment_methods_is_active_name` (`is_active`,`name`),
  ADD KEY `fk_payment_methods_created_by_account_id` (`created_by_account_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_permissions_slug` (`slug`),
  ADD KEY `idx_permissions_group` (`group`);

--
-- Indexes for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `fk_permission_role_role_id` (`role_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_products_sku` (`sku`),
  ADD UNIQUE KEY `uniq_products_barcode` (`barcode`),
  ADD KEY `idx_products_track_inventory` (`track_inventory`),
  ADD KEY `idx_products_status` (`status`),
  ADD KEY `idx_products_status_name` (`status`,`name`),
  ADD KEY `idx_products_category_id_status` (`category_id`,`status`),
  ADD KEY `idx_products_brand_id_status` (`brand_id`,`status`),
  ADD KEY `fk_products_tax_rate_id` (`tax_rate_id`);

--
-- Indexes for table `product_branch_prices`
--
ALTER TABLE `product_branch_prices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_product_branch_prices_product_id_branch_id` (`product_id`,`branch_id`),
  ADD KEY `idx_product_branch_prices_branch_id_product_id` (`branch_id`,`product_id`);

--
-- Indexes for table `product_branch_stock`
--
ALTER TABLE `product_branch_stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_product_branch_stock_product_id_branch_id` (`product_id`,`branch_id`),
  ADD KEY `idx_product_branch_stock_last_movement_at` (`last_movement_at`),
  ADD KEY `product_branch_stock_low_index` (`branch_id`,`quantity_milliunits`,`minimum_stock_milliunits`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_product_categories_slug` (`slug`),
  ADD KEY `idx_product_categories_status` (`status`),
  ADD KEY `idx_product_categories_status_name` (`status`,`name`);

--
-- Indexes for table `product_imports`
--
ALTER TABLE `product_imports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_imports_status` (`status`),
  ADD KEY `idx_product_imports_validated_at` (`validated_at`),
  ADD KEY `idx_product_imports_completed_at` (`completed_at`),
  ADD KEY `idx_product_imports_status_created_at` (`status`,`created_at`),
  ADD KEY `fk_product_imports_account_id` (`account_id`);

--
-- Indexes for table `product_import_rows`
--
ALTER TABLE `product_import_rows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_product_import_rows_product_import_id_row_number` (`product_import_id`,`row_number`),
  ADD KEY `idx_product_import_rows_is_valid` (`is_valid`),
  ADD KEY `idx_product_import_rows_processed_at` (`processed_at`),
  ADD KEY `product_import_rows_lookup_index` (`product_import_id`,`is_valid`,`row_number`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_purchase_orders_order_number` (`order_number`),
  ADD KEY `idx_purchase_orders_status` (`status`),
  ADD KEY `idx_purchase_orders_expected_at` (`expected_at`),
  ADD KEY `idx_purchase_orders_approved_at` (`approved_at`),
  ADD KEY `idx_purchase_orders_received_at` (`received_at`),
  ADD KEY `idx_purchase_orders_supplier_id_status` (`supplier_id`,`status`),
  ADD KEY `idx_purchase_orders_branch_id_status` (`branch_id`,`status`),
  ADD KEY `fk_purchase_orders_created_by_account_id` (`created_by_account_id`),
  ADD KEY `fk_purchase_orders_approved_by_account_id` (`approved_by_account_id`);

--
-- Indexes for table `purchase_order_lines`
--
ALTER TABLE `purchase_order_lines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_purchase_order_lines_purchase_order_id_product_id` (`purchase_order_id`,`product_id`),
  ADD KEY `fk_purchase_order_lines_product_id` (`product_id`);

--
-- Indexes for table `purchase_receipts`
--
ALTER TABLE `purchase_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_purchase_receipts_receipt_number` (`receipt_number`),
  ADD KEY `idx_purchase_receipts_purchased_at` (`purchased_at`),
  ADD KEY `idx_purchase_receipts_status` (`status`),
  ADD KEY `idx_purchase_receipts_supplier_id_purchased_at` (`supplier_id`,`purchased_at`),
  ADD KEY `idx_purchase_receipts_branch_id_purchased_at` (`branch_id`,`purchased_at`),
  ADD KEY `fk_purchase_receipts_recorded_by_account_id` (`recorded_by_account_id`),
  ADD KEY `fk_purchase_receipts_purchase_order_id` (`purchase_order_id`);

--
-- Indexes for table `purchase_receipt_lines`
--
ALTER TABLE `purchase_receipt_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_purchase_receipt_lines_purchase_receipt_id_product_id` (`purchase_receipt_id`,`product_id`),
  ADD KEY `fk_purchase_receipt_lines_product_id` (`product_id`);

--
-- Indexes for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_purchase_returns_return_number` (`return_number`),
  ADD KEY `idx_purchase_returns_status` (`status`),
  ADD KEY `idx_purchase_returns_returned_at` (`returned_at`),
  ADD KEY `idx_purchase_returns_supplier_id_returned_at` (`supplier_id`,`returned_at`),
  ADD KEY `idx_purchase_returns_purchase_receipt_id_returned_at` (`purchase_receipt_id`,`returned_at`),
  ADD KEY `fk_purchase_returns_branch_id` (`branch_id`),
  ADD KEY `fk_purchase_returns_processed_by_account_id` (`processed_by_account_id`);

--
-- Indexes for table `purchase_return_lines`
--
ALTER TABLE `purchase_return_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_purchase_return_lines_purchase_return_id_product_id` (`purchase_return_id`,`product_id`),
  ADD KEY `fk_purchase_return_lines_purchase_receipt_line_id` (`purchase_receipt_line_id`),
  ADD KEY `fk_purchase_return_lines_product_id` (`product_id`);

--
-- Indexes for table `quote_conversions`
--
ALTER TABLE `quote_conversions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quote_conversion_pair_unique` (`source_quote_id`,`converted_sale_id`),
  ADD KEY `idx_quote_conversions_converted_at` (`converted_at`),
  ADD KEY `fk_quote_conversions_converted_sale_id` (`converted_sale_id`),
  ADD KEY `fk_quote_conversions_converted_by_account_id` (`converted_by_account_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_roles_slug` (`slug`),
  ADD KEY `idx_roles_is_system` (`is_system`),
  ADD KEY `idx_roles_is_active` (`is_active`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_sales_sale_code` (`sale_code`),
  ADD UNIQUE KEY `uniq_sales_idempotency_key` (`idempotency_key`),
  ADD KEY `idx_sales_sale_type` (`sale_type`),
  ADD KEY `idx_sales_sale_date` (`sale_date`),
  ADD KEY `idx_sales_status` (`status`),
  ADD KEY `idx_sales_confirmed_at` (`confirmed_at`),
  ADD KEY `idx_sales_branch_id_sale_date` (`branch_id`,`sale_date`),
  ADD KEY `idx_sales_sold_by_account_id_sale_date` (`sold_by_account_id`,`sale_date`),
  ADD KEY `idx_sales_sale_type_status_sale_date` (`sale_type`,`status`,`sale_date`),
  ADD KEY `fk_sales_customer_id` (`customer_id`),
  ADD KEY `fk_sales_converted_from_sale_id` (`converted_from_sale_id`),
  ADD KEY `fk_sales_discount_voucher_id` (`discount_voucher_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sale_items_sale_id_product_id` (`sale_id`,`product_id`),
  ADD KEY `fk_sale_items_product_id` (`product_id`);

--
-- Indexes for table `sale_returns`
--
ALTER TABLE `sale_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_sale_returns_return_code` (`return_code`),
  ADD KEY `idx_sale_returns_status` (`status`),
  ADD KEY `idx_sale_returns_returned_at` (`returned_at`),
  ADD KEY `idx_sale_returns_sale_id_returned_at` (`sale_id`,`returned_at`),
  ADD KEY `fk_sale_returns_branch_id` (`branch_id`),
  ADD KEY `fk_sale_returns_customer_id` (`customer_id`),
  ADD KEY `fk_sale_returns_processed_by_account_id` (`processed_by_account_id`);

--
-- Indexes for table `sale_return_items`
--
ALTER TABLE `sale_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sale_return_items_sale_return_id_product_id` (`sale_return_id`,`product_id`),
  ADD KEY `fk_sale_return_items_sale_item_id` (`sale_item_id`),
  ADD KEY `fk_sale_return_items_product_id` (`product_id`);

--
-- Indexes for table `security_events`
--
ALTER TABLE `security_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_security_events_event_type` (`event_type`),
  ADD KEY `idx_security_events_session_identifier` (`session_identifier`),
  ADD KEY `idx_security_events_ip_address` (`ip_address`),
  ADD KEY `idx_security_events_occurred_at` (`occurred_at`),
  ADD KEY `security_events_type_time_index` (`event_type`,`occurred_at`),
  ADD KEY `security_events_subject_time_index` (`subject_account_id`,`occurred_at`),
  ADD KEY `security_events_actor_time_index` (`actor_account_id`,`occurred_at`);

--
-- Indexes for table `standalone_receipts`
--
ALTER TABLE `standalone_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_standalone_receipts_receipt_number` (`receipt_number`),
  ADD KEY `idx_standalone_receipts_status` (`status`),
  ADD KEY `idx_standalone_receipts_received_at` (`received_at`),
  ADD KEY `idx_standalone_receipts_customer_id_received_at` (`customer_id`,`received_at`),
  ADD KEY `idx_standalone_receipts_branch_id_received_at` (`branch_id`,`received_at`),
  ADD KEY `fk_standalone_receipts_payment_method_id` (`payment_method_id`),
  ADD KEY `fk_standalone_receipts_received_by_account_id` (`received_by_account_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stock_movements_movement_type` (`movement_type`),
  ADD KEY `idx_stock_movements_reference_type` (`reference_type`),
  ADD KEY `idx_stock_movements_reference_id` (`reference_id`),
  ADD KEY `idx_stock_movements_correlation_id` (`correlation_id`),
  ADD KEY `idx_stock_movements_reason_code` (`reason_code`),
  ADD KEY `idx_stock_movements_occurred_at` (`occurred_at`),
  ADD KEY `stock_movements_product_branch_time_index` (`product_id`,`branch_id`,`occurred_at`),
  ADD KEY `stock_movements_reference_index` (`reference_type`,`reference_id`),
  ADD KEY `fk_stock_movements_branch_id` (`branch_id`),
  ADD KEY `fk_stock_movements_account_id` (`account_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_suppliers_supplier_code` (`supplier_code`),
  ADD KEY `idx_suppliers_category` (`category`),
  ADD KEY `idx_suppliers_is_preferred` (`is_preferred`),
  ADD KEY `idx_suppliers_status` (`status`),
  ADD KEY `idx_suppliers_status_company_name` (`status`,`company_name`),
  ADD KEY `idx_suppliers_is_preferred_status` (`is_preferred`,`status`);

--
-- Indexes for table `supplier_bills`
--
ALTER TABLE `supplier_bills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_supplier_bills_bill_number` (`bill_number`),
  ADD KEY `idx_supplier_bills_supplier_reference` (`supplier_reference`),
  ADD KEY `idx_supplier_bills_bill_date` (`bill_date`),
  ADD KEY `idx_supplier_bills_due_date` (`due_date`),
  ADD KEY `idx_supplier_bills_status` (`status`),
  ADD KEY `idx_supplier_bills_posted_at` (`posted_at`),
  ADD KEY `idx_supplier_bills_cancelled_at` (`cancelled_at`),
  ADD KEY `idx_supplier_bills_supplier_id_status_due_date` (`supplier_id`,`status`,`due_date`),
  ADD KEY `idx_supplier_bills_branch_id_bill_date` (`branch_id`,`bill_date`),
  ADD KEY `fk_supplier_bills_purchase_order_id` (`purchase_order_id`),
  ADD KEY `fk_supplier_bills_created_by_account_id` (`created_by_account_id`);

--
-- Indexes for table `supplier_bill_lines`
--
ALTER TABLE `supplier_bill_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_supplier_bill_lines_supplier_bill_id_product_id` (`supplier_bill_id`,`product_id`),
  ADD KEY `fk_supplier_bill_lines_product_id` (`product_id`);

--
-- Indexes for table `supplier_bill_payments`
--
ALTER TABLE `supplier_bill_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_supplier_bill_payments_paid_at` (`paid_at`),
  ADD KEY `idx_supplier_bill_payments_supplier_bill_id_paid_at` (`supplier_bill_id`,`paid_at`),
  ADD KEY `fk_supplier_bill_payments_payment_method_id` (`payment_method_id`),
  ADD KEY `fk_supplier_bill_payments_recorded_by_account_id` (`recorded_by_account_id`);

--
-- Indexes for table `supplier_documents`
--
ALTER TABLE `supplier_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_supplier_documents_supplier_id_created_at` (`supplier_id`,`created_at`),
  ADD KEY `idx_supplier_documents_supplier_bill_id_created_at` (`supplier_bill_id`,`created_at`),
  ADD KEY `fk_supplier_documents_uploaded_by_account_id` (`uploaded_by_account_id`);

--
-- Indexes for table `supplier_returns`
--
ALTER TABLE `supplier_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_supplier_returns_return_number` (`return_number`),
  ADD KEY `idx_supplier_returns_status` (`status`),
  ADD KEY `idx_supplier_returns_return_date` (`return_date`),
  ADD KEY `idx_supplier_returns_confirmed_at` (`confirmed_at`),
  ADD KEY `idx_supplier_returns_supplier_id_return_date` (`supplier_id`,`return_date`),
  ADD KEY `idx_supplier_returns_branch_id_status` (`branch_id`,`status`),
  ADD KEY `fk_supplier_returns_supplier_bill_id` (`supplier_bill_id`),
  ADD KEY `fk_supplier_returns_created_by_account_id` (`created_by_account_id`);

--
-- Indexes for table `supplier_return_lines`
--
ALTER TABLE `supplier_return_lines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_supplier_return_lines_supplier_return_id_product_id` (`supplier_return_id`,`product_id`),
  ADD KEY `fk_supplier_return_lines_product_id` (`product_id`);

--
-- Indexes for table `tax_rates`
--
ALTER TABLE `tax_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tax_rates_status` (`status`),
  ADD KEY `idx_tax_rates_is_default` (`is_default`),
  ADD KEY `idx_tax_rates_status_name` (`status`,`name`);

--
-- Indexes for table `voucher_redemptions`
--
ALTER TABLE `voucher_redemptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_voucher_redemptions_discount_voucher_id_sale_id` (`discount_voucher_id`,`sale_id`),
  ADD KEY `idx_voucher_redemptions_redeemed_at` (`redeemed_at`),
  ADD KEY `idx_voucher_redemptions_customer_id_redeemed_at` (`customer_id`,`redeemed_at`),
  ADD KEY `fk_voucher_redemptions_sale_id` (`sale_id`),
  ADD KEY `fk_voucher_redemptions_redeemed_by_account_id` (`redeemed_by_account_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounting_periods`
--
ALTER TABLE `accounting_periods`
  ADD CONSTRAINT `fk_accounting_periods_closed_by_account_id` FOREIGN KEY (`closed_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_accounting_periods_locked_by_account_id` FOREIGN KEY (`locked_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `account_branch`
--
ALTER TABLE `account_branch`
  ADD CONSTRAINT `fk_account_branch_account_id` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_account_branch_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `account_role`
--
ALTER TABLE `account_role`
  ADD CONSTRAINT `fk_account_role_account_id` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_account_role_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `account_sessions`
--
ALTER TABLE `account_sessions`
  ADD CONSTRAINT `fk_account_sessions_account_id` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD CONSTRAINT `fk_admin_notifications_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `alert_recipients`
--
ALTER TABLE `alert_recipients`
  ADD CONSTRAINT `fk_alert_recipients_added_by_account_id` FOREIGN KEY (`added_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD CONSTRAINT `fk_api_tokens_created_by_account_id` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `asset_depreciation_postings`
--
ALTER TABLE `asset_depreciation_postings`
  ADD CONSTRAINT `fk_asset_depreciation_postings_fixed_asset_id` FOREIGN KEY (`fixed_asset_id`) REFERENCES `fixed_assets` (`id`),
  ADD CONSTRAINT `fk_asset_depreciation_postings_journal_entry_id` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`);

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_logs_actor_account_id` FOREIGN KEY (`actor_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_audit_logs_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `backup_runs`
--
ALTER TABLE `backup_runs`
  ADD CONSTRAINT `fk_backup_runs_requested_by_account_id` FOREIGN KEY (`requested_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `business_insights`
--
ALTER TABLE `business_insights`
  ADD CONSTRAINT `fk_business_insights_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_customers_created_by_account_id` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `discount_vouchers`
--
ALTER TABLE `discount_vouchers`
  ADD CONSTRAINT `fk_discount_vouchers_created_by_account_id` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `document_brandings`
--
ALTER TABLE `document_brandings`
  ADD CONSTRAINT `fk_document_brandings_updated_by_account_id` FOREIGN KEY (`updated_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fixed_assets`
--
ALTER TABLE `fixed_assets`
  ADD CONSTRAINT `fk_fixed_assets_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_fixed_assets_created_by_account_id` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `fk_fixed_assets_custodian_account_id` FOREIGN KEY (`custodian_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD CONSTRAINT `fk_journal_entries_accounting_period_id` FOREIGN KEY (`accounting_period_id`) REFERENCES `accounting_periods` (`id`),
  ADD CONSTRAINT `fk_journal_entries_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_journal_entries_created_by_account_id` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_journal_entries_reversal_of_entry_id` FOREIGN KEY (`reversal_of_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `journal_lines`
--
ALTER TABLE `journal_lines`
  ADD CONSTRAINT `fk_journal_lines_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_journal_lines_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_journal_lines_journal_entry_id` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_journal_lines_ledger_account_id` FOREIGN KEY (`ledger_account_id`) REFERENCES `ledger_accounts` (`id`),
  ADD CONSTRAINT `fk_journal_lines_supplier_id` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ledger_accounts`
--
ALTER TABLE `ledger_accounts`
  ADD CONSTRAINT `fk_ledger_accounts_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `ledger_accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `low_stock_alerts`
--
ALTER TABLE `low_stock_alerts`
  ADD CONSTRAINT `fk_low_stock_alerts_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_low_stock_alerts_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `operation_document_logs`
--
ALTER TABLE `operation_document_logs`
  ADD CONSTRAINT `fk_operation_document_logs_generated_by_account_id` FOREIGN KEY (`generated_by_account_id`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_payment_method_id` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`),
  ADD CONSTRAINT `fk_payments_recorded_by_account_id` FOREIGN KEY (`recorded_by_account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `fk_payments_sale_id` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD CONSTRAINT `fk_payment_methods_created_by_account_id` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD CONSTRAINT `fk_permission_role_permission_id` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_permission_role_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_brand_id` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_products_category_id` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`),
  ADD CONSTRAINT `fk_products_tax_rate_id` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_branch_prices`
--
ALTER TABLE `product_branch_prices`
  ADD CONSTRAINT `fk_product_branch_prices_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_branch_prices_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_branch_stock`
--
ALTER TABLE `product_branch_stock`
  ADD CONSTRAINT `fk_product_branch_stock_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_branch_stock_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_imports`
--
ALTER TABLE `product_imports`
  ADD CONSTRAINT `fk_product_imports_account_id` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_import_rows`
--
ALTER TABLE `product_import_rows`
  ADD CONSTRAINT `fk_product_import_rows_product_import_id` FOREIGN KEY (`product_import_id`) REFERENCES `product_imports` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `fk_purchase_orders_approved_by_account_id` FOREIGN KEY (`approved_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_purchase_orders_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fk_purchase_orders_created_by_account_id` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_purchase_orders_supplier_id` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `purchase_order_lines`
--
ALTER TABLE `purchase_order_lines`
  ADD CONSTRAINT `fk_purchase_order_lines_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_purchase_order_lines_purchase_order_id` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_receipts`
--
ALTER TABLE `purchase_receipts`
  ADD CONSTRAINT `fk_purchase_receipts_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fk_purchase_receipts_purchase_order_id` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_purchase_receipts_recorded_by_account_id` FOREIGN KEY (`recorded_by_account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `fk_purchase_receipts_supplier_id` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `purchase_receipt_lines`
--
ALTER TABLE `purchase_receipt_lines`
  ADD CONSTRAINT `fk_purchase_receipt_lines_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_purchase_receipt_lines_purchase_receipt_id` FOREIGN KEY (`purchase_receipt_id`) REFERENCES `purchase_receipts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD CONSTRAINT `fk_purchase_returns_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fk_purchase_returns_processed_by_account_id` FOREIGN KEY (`processed_by_account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `fk_purchase_returns_purchase_receipt_id` FOREIGN KEY (`purchase_receipt_id`) REFERENCES `purchase_receipts` (`id`),
  ADD CONSTRAINT `fk_purchase_returns_supplier_id` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `purchase_return_lines`
--
ALTER TABLE `purchase_return_lines`
  ADD CONSTRAINT `fk_purchase_return_lines_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_purchase_return_lines_purchase_receipt_line_id` FOREIGN KEY (`purchase_receipt_line_id`) REFERENCES `purchase_receipt_lines` (`id`),
  ADD CONSTRAINT `fk_purchase_return_lines_purchase_return_id` FOREIGN KEY (`purchase_return_id`) REFERENCES `purchase_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quote_conversions`
--
ALTER TABLE `quote_conversions`
  ADD CONSTRAINT `fk_quote_conversions_converted_by_account_id` FOREIGN KEY (`converted_by_account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `fk_quote_conversions_converted_sale_id` FOREIGN KEY (`converted_sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `fk_quote_conversions_source_quote_id` FOREIGN KEY (`source_quote_id`) REFERENCES `sales` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fk_sales_converted_from_sale_id` FOREIGN KEY (`converted_from_sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sales_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sales_discount_voucher_id` FOREIGN KEY (`discount_voucher_id`) REFERENCES `discount_vouchers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sales_sold_by_account_id` FOREIGN KEY (`sold_by_account_id`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `fk_sale_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_sale_items_sale_id` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sale_returns`
--
ALTER TABLE `sale_returns`
  ADD CONSTRAINT `fk_sale_returns_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fk_sale_returns_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sale_returns_processed_by_account_id` FOREIGN KEY (`processed_by_account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `fk_sale_returns_sale_id` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`);

--
-- Constraints for table `sale_return_items`
--
ALTER TABLE `sale_return_items`
  ADD CONSTRAINT `fk_sale_return_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_sale_return_items_sale_item_id` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`),
  ADD CONSTRAINT `fk_sale_return_items_sale_return_id` FOREIGN KEY (`sale_return_id`) REFERENCES `sale_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `security_events`
--
ALTER TABLE `security_events`
  ADD CONSTRAINT `fk_security_events_actor_account_id` FOREIGN KEY (`actor_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_security_events_subject_account_id` FOREIGN KEY (`subject_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `standalone_receipts`
--
ALTER TABLE `standalone_receipts`
  ADD CONSTRAINT `fk_standalone_receipts_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fk_standalone_receipts_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_standalone_receipts_payment_method_id` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`),
  ADD CONSTRAINT `fk_standalone_receipts_received_by_account_id` FOREIGN KEY (`received_by_account_id`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `fk_stock_movements_account_id` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_stock_movements_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fk_stock_movements_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `supplier_bills`
--
ALTER TABLE `supplier_bills`
  ADD CONSTRAINT `fk_supplier_bills_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fk_supplier_bills_created_by_account_id` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_supplier_bills_purchase_order_id` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_supplier_bills_supplier_id` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `supplier_bill_lines`
--
ALTER TABLE `supplier_bill_lines`
  ADD CONSTRAINT `fk_supplier_bill_lines_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_supplier_bill_lines_supplier_bill_id` FOREIGN KEY (`supplier_bill_id`) REFERENCES `supplier_bills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_bill_payments`
--
ALTER TABLE `supplier_bill_payments`
  ADD CONSTRAINT `fk_supplier_bill_payments_payment_method_id` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`),
  ADD CONSTRAINT `fk_supplier_bill_payments_recorded_by_account_id` FOREIGN KEY (`recorded_by_account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `fk_supplier_bill_payments_supplier_bill_id` FOREIGN KEY (`supplier_bill_id`) REFERENCES `supplier_bills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_documents`
--
ALTER TABLE `supplier_documents`
  ADD CONSTRAINT `fk_supplier_documents_supplier_bill_id` FOREIGN KEY (`supplier_bill_id`) REFERENCES `supplier_bills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_supplier_documents_supplier_id` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_supplier_documents_uploaded_by_account_id` FOREIGN KEY (`uploaded_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `supplier_returns`
--
ALTER TABLE `supplier_returns`
  ADD CONSTRAINT `fk_supplier_returns_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fk_supplier_returns_created_by_account_id` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_supplier_returns_supplier_bill_id` FOREIGN KEY (`supplier_bill_id`) REFERENCES `supplier_bills` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_supplier_returns_supplier_id` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `supplier_return_lines`
--
ALTER TABLE `supplier_return_lines`
  ADD CONSTRAINT `fk_supplier_return_lines_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_supplier_return_lines_supplier_return_id` FOREIGN KEY (`supplier_return_id`) REFERENCES `supplier_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `voucher_redemptions`
--
ALTER TABLE `voucher_redemptions`
  ADD CONSTRAINT `fk_voucher_redemptions_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_voucher_redemptions_discount_voucher_id` FOREIGN KEY (`discount_voucher_id`) REFERENCES `discount_vouchers` (`id`),
  ADD CONSTRAINT `fk_voucher_redemptions_redeemed_by_account_id` FOREIGN KEY (`redeemed_by_account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `fk_voucher_redemptions_sale_id` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`);
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
