-- Express Cloud v1.0.5 preparation installer SQL
-- Synchronized with repository migrations through 2026-07-18, including Sprint 4 enterprise features.
-- Current schema, indexes, foreign keys, required system records and permissions are included.
-- MySQL 8.0 / MariaDB 10.6+ compatible.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
SET time_zone = '+00:00';
START TRANSACTION;

DROP TABLE IF EXISTS `daily_report_runs`;
DROP TABLE IF EXISTS `lisa_messages`;
DROP TABLE IF EXISTS `lisa_conversations`;
DROP TABLE IF EXISTS `asset_depreciation_postings`;
DROP TABLE IF EXISTS `journal_lines`;
DROP TABLE IF EXISTS `journal_entries`;
DROP TABLE IF EXISTS `accounting_periods`;
DROP TABLE IF EXISTS `ledger_accounts`;
DROP TABLE IF EXISTS `business_insights`;
DROP TABLE IF EXISTS `operation_document_logs`;
DROP TABLE IF EXISTS `fixed_assets`;
DROP TABLE IF EXISTS `purchase_return_lines`;
DROP TABLE IF EXISTS `purchase_returns`;
DROP TABLE IF EXISTS `standalone_receipts`;
DROP TABLE IF EXISTS `document_brandings`;
DROP TABLE IF EXISTS `backup_runs`;
DROP TABLE IF EXISTS `quote_conversions`;
DROP TABLE IF EXISTS `api_tokens`;
DROP TABLE IF EXISTS `purchase_receipt_lines`;
DROP TABLE IF EXISTS `purchase_receipts`;
DROP TABLE IF EXISTS `sale_return_items`;
DROP TABLE IF EXISTS `sale_returns`;
DROP TABLE IF EXISTS `voucher_redemptions`;
DROP TABLE IF EXISTS `discount_vouchers`;
DROP TABLE IF EXISTS `admin_notifications`;
DROP TABLE IF EXISTS `end_of_day_digests`;
DROP TABLE IF EXISTS `business_settings`;
DROP TABLE IF EXISTS `alert_recipients`;
DROP TABLE IF EXISTS `supplier_documents`;
DROP TABLE IF EXISTS `supplier_return_lines`;
DROP TABLE IF EXISTS `supplier_returns`;
DROP TABLE IF EXISTS `supplier_bill_payments`;
DROP TABLE IF EXISTS `supplier_bill_lines`;
DROP TABLE IF EXISTS `supplier_bills`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `sale_items`;
DROP TABLE IF EXISTS `sales`;
DROP TABLE IF EXISTS `payment_methods`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `low_stock_alerts`;
DROP TABLE IF EXISTS `purchase_order_lines`;
DROP TABLE IF EXISTS `purchase_orders`;
DROP TABLE IF EXISTS `stock_movements`;
DROP TABLE IF EXISTS `product_branch_stock`;
DROP TABLE IF EXISTS `product_import_rows`;
DROP TABLE IF EXISTS `product_imports`;
DROP TABLE IF EXISTS `product_branch_prices`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `tax_rates`;
DROP TABLE IF EXISTS `brands`;
DROP TABLE IF EXISTS `product_categories`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `account_branch`;
DROP TABLE IF EXISTS `account_role`;
DROP TABLE IF EXISTS `permission_role`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `branches`;
DROP TABLE IF EXISTS `companies`;
DROP TABLE IF EXISTS `security_events`;
DROP TABLE IF EXISTS `account_sessions`;
DROP TABLE IF EXISTS `accounts`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `migration` VARCHAR(255) NOT NULL,
  `batch` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key` VARCHAR(255) NOT NULL,
  `value` MEDIUMTEXT NOT NULL,
  `expiration` BIGINT NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` VARCHAR(255) NOT NULL,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` BIGINT NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` SMALLINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options` MEDIUMTEXT NULL,
  `cancelled_at` INT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(255) NOT NULL,
  `connection` VARCHAR(255) NOT NULL,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`, `queue`, `failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounts` (
  `id` CHAR(26) NOT NULL,
  `public_id` CHAR(36) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email_encrypted` TEXT NULL,
  `login_key_encrypted` TEXT NOT NULL,
  `login_key_blind_index` CHAR(64) NOT NULL,
  `login_key_version` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `profile_picture_path` VARCHAR(500) NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `last_authenticated_at` TIMESTAMP NULL,
  `remember_token` VARCHAR(100) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `is_allowed_all_branches` TINYINT(1) NOT NULL DEFAULT 0,
  `deprecated_at` TIMESTAMP NULL DEFAULT NULL,
  `deprecated_by_account_id` CHAR(26) NULL,
  `deprecation_reason` TEXT NULL,
  KEY `accounts_deprecated_at_index` (`deprecated_at`),
  KEY `accounts_deprecated_by_account_id_index` (`deprecated_by_account_id`),
  CONSTRAINT `accounts_deprecated_by_account_id_foreign` FOREIGN KEY (`deprecated_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounts_public_id_unique` (`public_id`),
  UNIQUE KEY `accounts_login_key_blind_index_unique` (`login_key_blind_index`),
  KEY `accounts_status_index` (`status`),
  KEY `accounts_last_authenticated_at_index` (`last_authenticated_at`),
  KEY `accounts_last_name_first_name_index` (`last_name`, `first_name`),
  KEY `accounts_status_last_name_first_name_index` (`status`, `last_name`, `first_name`),
  KEY `accounts_is_allowed_all_branches_index` (`is_allowed_all_branches`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `account_sessions` (
  `id` CHAR(26) NOT NULL,
  `account_id` CHAR(26) NOT NULL,
  `session_identifier` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `last_activity_at` TIMESTAMP NOT NULL,
  `revoked_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_sessions_session_identifier_unique` (`session_identifier`),
  KEY `account_sessions_last_activity_at_index` (`last_activity_at`),
  KEY `account_sessions_revoked_at_index` (`revoked_at`),
  KEY `account_sessions_activity_index` (`account_id`, `revoked_at`, `last_activity_at`),
  CONSTRAINT `account_sessions_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `security_events` (
  `id` CHAR(26) NOT NULL,
  `event_type` VARCHAR(64) NOT NULL,
  `actor_account_id` CHAR(26) NULL,
  `subject_account_id` CHAR(26) NULL,
  `session_identifier` VARCHAR(255) NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `context` JSON NULL,
  `occurred_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `security_events_event_type_index` (`event_type`),
  KEY `security_events_session_identifier_index` (`session_identifier`),
  KEY `security_events_ip_address_index` (`ip_address`),
  KEY `security_events_occurred_at_index` (`occurred_at`),
  KEY `security_events_type_time_index` (`event_type`, `occurred_at`),
  KEY `security_events_subject_time_index` (`subject_account_id`, `occurred_at`),
  KEY `security_events_actor_time_index` (`actor_account_id`, `occurred_at`),
  CONSTRAINT `security_events_actor_account_id_foreign` FOREIGN KEY (`actor_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `security_events_subject_account_id_foreign` FOREIGN KEY (`subject_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `companies` (
  `id` CHAR(26) NOT NULL,
  `legal_name` VARCHAR(180) NOT NULL,
  `trading_name` VARCHAR(180) NULL,
  `head_office_address` TEXT NOT NULL,
  `phone` VARCHAR(40) NULL,
  `email_encrypted` TEXT NULL,
  `logo_path` VARCHAR(500) NULL,
  `timezone` VARCHAR(64) NOT NULL DEFAULT 'Africa/Lagos',
  `is_configured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `companies_is_configured_index` (`is_configured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `branches` (
  `id` CHAR(26) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `code` VARCHAR(40) NOT NULL,
  `address` TEXT NOT NULL,
  `phone` VARCHAR(40) NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `allow_zero_stock_sales` TINYINT(1) NOT NULL DEFAULT 0,
  `is_head_office` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deprecated_at` TIMESTAMP NULL DEFAULT NULL,
  `deprecated_by_account_id` CHAR(26) NULL,
  `deprecation_reason` TEXT NULL,
  KEY `branches_deprecated_at_index` (`deprecated_at`),
  KEY `branches_deprecated_by_account_id_index` (`deprecated_by_account_id`),
  CONSTRAINT `branches_deprecated_by_account_id_foreign` FOREIGN KEY (`deprecated_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_code_unique` (`code`),
  KEY `branches_status_index` (`status`),
  KEY `branches_is_head_office_index` (`is_head_office`),
  KEY `branches_status_name_index` (`status`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `business_insights` (
  `id` CHAR(26) NOT NULL,
  `category` VARCHAR(40) NOT NULL,
  `severity` VARCHAR(20) NOT NULL DEFAULT 'info',
  `title` VARCHAR(160) NOT NULL,
  `summary` TEXT NOT NULL,
  `recommendation` TEXT NULL,
  `evidence` JSON NULL,
  `period_start` DATE NOT NULL,
  `period_end` DATE NOT NULL,
  `branch_id` CHAR(26) NULL,
  `generated_at` TIMESTAMP NOT NULL,
  `dismissed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_insights_category_index` (`category`),
  KEY `business_insights_severity_index` (`severity`),
  KEY `business_insights_generated_at_index` (`generated_at`),
  UNIQUE KEY `business_insights_scope_unique` (`category`, `branch_id`, `period_start`, `period_end`, `title`),
  CONSTRAINT `business_insights_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id` CHAR(26) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `description` TEXT NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deprecated_at` TIMESTAMP NULL DEFAULT NULL,
  `deprecated_by_account_id` CHAR(26) NULL,
  `deprecation_reason` TEXT NULL,
  KEY `roles_deprecated_at_index` (`deprecated_at`),
  KEY `roles_deprecated_by_account_id_index` (`deprecated_by_account_id`),
  CONSTRAINT `roles_deprecated_by_account_id_foreign` FOREIGN KEY (`deprecated_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_slug_unique` (`slug`),
  KEY `roles_is_system_index` (`is_system`),
  KEY `roles_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
  `id` CHAR(26) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `group` VARCHAR(80) NOT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_slug_unique` (`slug`),
  KEY `permissions_group_index` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permission_role` (
  `permission_id` CHAR(26) NOT NULL,
  `role_id` CHAR(26) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`permission_id`, `role_id`),
  CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  KEY `permission_role_role_id_index` (`role_id`),
  CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `account_role` (
  `account_id` CHAR(26) NOT NULL,
  `role_id` CHAR(26) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`account_id`, `role_id`),
  CONSTRAINT `account_role_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  KEY `account_role_role_id_index` (`role_id`),
  CONSTRAINT `account_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `account_branch` (
  `account_id` CHAR(26) NOT NULL,
  `branch_id` CHAR(26) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`account_id`, `branch_id`),
  CONSTRAINT `account_branch_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  KEY `account_branch_branch_id_index` (`branch_id`),
  CONSTRAINT `account_branch_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
  `id` CHAR(26) NOT NULL,
  `actor_account_id` CHAR(26) NULL,
  `actor_name` VARCHAR(220) NULL,
  `actor_role_snapshot` VARCHAR(220) NULL,
  `action` VARCHAR(180) NOT NULL,
  `entity_type` VARCHAR(100) NOT NULL,
  `entity_id` VARCHAR(64) NULL,
  `branch_id` CHAR(26) NULL,
  `before_data` JSON NULL,
  `after_data` JSON NULL,
  `context` JSON NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `occurred_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_entity_type_index` (`entity_type`),
  KEY `audit_logs_entity_id_index` (`entity_id`),
  KEY `audit_logs_ip_address_index` (`ip_address`),
  KEY `audit_logs_occurred_at_index` (`occurred_at`),
  KEY `audit_logs_entity_time_index` (`entity_type`, `entity_id`, `occurred_at`),
  KEY `audit_logs_branch_time_index` (`branch_id`, `occurred_at`),
  KEY `audit_logs_actor_time_index` (`actor_account_id`, `occurred_at`),
  CONSTRAINT `audit_logs_actor_account_id_foreign` FOREIGN KEY (`actor_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `audit_logs_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_categories` (
  `id` CHAR(26) NOT NULL,
  `name` VARCHAR(140) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `description` TEXT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deprecated_at` TIMESTAMP NULL DEFAULT NULL,
  `deprecated_by_account_id` CHAR(26) NULL,
  `deprecation_reason` TEXT NULL,
  KEY `product_categories_deprecated_at_index` (`deprecated_at`),
  KEY `product_categories_deprecated_by_account_id_index` (`deprecated_by_account_id`),
  CONSTRAINT `product_categories_deprecated_by_account_id_foreign` FOREIGN KEY (`deprecated_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_categories_slug_unique` (`slug`),
  KEY `product_categories_status_index` (`status`),
  KEY `product_categories_status_name_index` (`status`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `brands` (
  `id` CHAR(26) NOT NULL,
  `name` VARCHAR(140) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `description` TEXT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deprecated_at` TIMESTAMP NULL DEFAULT NULL,
  `deprecated_by_account_id` CHAR(26) NULL,
  `deprecation_reason` TEXT NULL,
  KEY `brands_deprecated_at_index` (`deprecated_at`),
  KEY `brands_deprecated_by_account_id_index` (`deprecated_by_account_id`),
  CONSTRAINT `brands_deprecated_by_account_id_foreign` FOREIGN KEY (`deprecated_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `brands_slug_unique` (`slug`),
  KEY `brands_status_index` (`status`),
  KEY `brands_status_name_index` (`status`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tax_rates` (
  `id` CHAR(26) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `rate_basis_points` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tax_rates_status_index` (`status`),
  KEY `tax_rates_is_default_index` (`is_default`),
  KEY `tax_rates_status_name_index` (`status`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `suppliers` (
  `id` CHAR(26) NOT NULL,
  `supplier_code` VARCHAR(60) NOT NULL,
  `company_name` VARCHAR(180) NOT NULL,
  `contact_person` VARCHAR(160) NULL,
  `category` VARCHAR(120) NULL,
  `email_encrypted` TEXT NULL,
  `phone` VARCHAR(40) NULL,
  `address` TEXT NULL,
  `tax_number_encrypted` TEXT NULL,
  `payment_terms_days` INT UNSIGNED NOT NULL DEFAULT 0,
  `credit_limit_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `lead_time_days` INT UNSIGNED NOT NULL DEFAULT 0,
  `delivery_terms` TEXT NULL,
  `return_policy` TEXT NULL,
  `is_preferred` TINYINT(1) NOT NULL DEFAULT 0,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deprecated_at` TIMESTAMP NULL DEFAULT NULL,
  `deprecated_by_account_id` CHAR(26) NULL,
  `deprecation_reason` TEXT NULL,
  KEY `suppliers_deprecated_at_index` (`deprecated_at`),
  KEY `suppliers_deprecated_by_account_id_index` (`deprecated_by_account_id`),
  CONSTRAINT `suppliers_deprecated_by_account_id_foreign` FOREIGN KEY (`deprecated_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_supplier_code_unique` (`supplier_code`),
  KEY `suppliers_category_index` (`category`),
  KEY `suppliers_is_preferred_index` (`is_preferred`),
  KEY `suppliers_status_index` (`status`),
  KEY `suppliers_status_company_name_index` (`status`, `company_name`),
  KEY `suppliers_is_preferred_status_index` (`is_preferred`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `products` (
  `id` CHAR(26) NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `sku` VARCHAR(100) NOT NULL,
  `barcode` VARCHAR(160) NULL,
  `category_id` CHAR(26) NOT NULL,
  `brand_id` CHAR(26) NULL,
  `tax_rate_id` CHAR(26) NULL,
  `description` TEXT NULL,
  `image_path` VARCHAR(500) NULL,
  `track_inventory` TINYINT(1) NOT NULL DEFAULT 1,
  `default_price_kobo` BIGINT UNSIGNED NOT NULL,
  `default_cost_price_kobo` BIGINT UNSIGNED NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deprecated_at` TIMESTAMP NULL DEFAULT NULL,
  `deprecated_by_account_id` CHAR(26) NULL,
  `deprecation_reason` TEXT NULL,
  KEY `products_deprecated_at_index` (`deprecated_at`),
  KEY `products_deprecated_by_account_id_index` (`deprecated_by_account_id`),
  CONSTRAINT `products_deprecated_by_account_id_foreign` FOREIGN KEY (`deprecated_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  UNIQUE KEY `products_barcode_unique` (`barcode`),
  KEY `products_track_inventory_index` (`track_inventory`),
  KEY `products_status_index` (`status`),
  KEY `products_status_name_index` (`status`, `name`),
  KEY `products_category_id_status_index` (`category_id`, `status`),
  KEY `products_brand_id_status_index` (`brand_id`, `status`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  KEY `products_tax_rate_id_index` (`tax_rate_id`),
  CONSTRAINT `products_tax_rate_id_foreign` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_branch_prices` (
  `id` CHAR(26) NOT NULL,
  `product_id` CHAR(26) NOT NULL,
  `branch_id` CHAR(26) NOT NULL,
  `price_kobo` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_branch_prices_product_id_branch_id_unique` (`product_id`, `branch_id`),
  KEY `product_branch_prices_branch_id_product_id_index` (`branch_id`, `product_id`),
  CONSTRAINT `product_branch_prices_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_branch_prices_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_imports` (
  `id` CHAR(26) NOT NULL,
  `account_id` CHAR(26) NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `stored_path` VARCHAR(500) NOT NULL,
  `error_report_path` VARCHAR(500) NULL,
  `status` VARCHAR(40) NOT NULL,
  `total_rows` INT UNSIGNED NOT NULL DEFAULT 0,
  `valid_rows` INT UNSIGNED NOT NULL DEFAULT 0,
  `invalid_rows` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_products` INT UNSIGNED NOT NULL DEFAULT 0,
  `updated_products` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_categories` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_brands` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_suppliers` INT UNSIGNED NOT NULL DEFAULT 0,
  `summary` JSON NULL,
  `validated_at` TIMESTAMP NULL,
  `completed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_imports_status_index` (`status`),
  KEY `product_imports_validated_at_index` (`validated_at`),
  KEY `product_imports_completed_at_index` (`completed_at`),
  KEY `product_imports_status_created_at_index` (`status`, `created_at`),
  KEY `product_imports_account_id_index` (`account_id`),
  CONSTRAINT `product_imports_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_import_rows` (
  `id` CHAR(26) NOT NULL,
  `product_import_id` CHAR(26) NOT NULL,
  `row_number` INT UNSIGNED NOT NULL,
  `payload` JSON NOT NULL,
  `errors` JSON NULL,
  `is_valid` TINYINT(1) NOT NULL DEFAULT 0,
  `processed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_import_rows_is_valid_index` (`is_valid`),
  KEY `product_import_rows_processed_at_index` (`processed_at`),
  UNIQUE KEY `product_import_rows_product_import_id_row_number_unique` (`product_import_id`, `row_number`),
  KEY `product_import_rows_lookup_index` (`product_import_id`, `is_valid`, `row_number`),
  CONSTRAINT `product_import_rows_product_import_id_foreign` FOREIGN KEY (`product_import_id`) REFERENCES `product_imports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_branch_stock` (
  `id` CHAR(26) NOT NULL,
  `product_id` CHAR(26) NOT NULL,
  `branch_id` CHAR(26) NOT NULL,
  `quantity_milliunits` BIGINT NOT NULL DEFAULT 0,
  `selling_price_kobo` BIGINT UNSIGNED NULL COMMENT 'Optional branch selling price. Null uses the product default price.',
  `minimum_stock_milliunits` BIGINT NOT NULL DEFAULT 5000,
  `last_movement_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_branch_stock_last_movement_at_index` (`last_movement_at`),
  UNIQUE KEY `product_branch_stock_product_id_branch_id_unique` (`product_id`, `branch_id`),
  KEY `product_branch_stock_low_index` (`branch_id`, `quantity_milliunits`, `minimum_stock_milliunits`),
  CONSTRAINT `product_branch_stock_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_branch_stock_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stock_movements` (
  `id` CHAR(26) NOT NULL,
  `product_id` CHAR(26) NOT NULL,
  `branch_id` CHAR(26) NOT NULL,
  `account_id` CHAR(26) NULL,
  `movement_type` VARCHAR(40) NOT NULL,
  `quantity_delta_milliunits` BIGINT NOT NULL,
  `balance_after_milliunits` BIGINT NOT NULL,
  `unit_cost_kobo` BIGINT UNSIGNED NULL,
  `reference_type` VARCHAR(60) NULL,
  `reference_id` VARCHAR(64) NULL,
  `correlation_id` CHAR(26) NULL,
  `reason_code` VARCHAR(60) NULL,
  `note` TEXT NULL,
  `occurred_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `stock_movements_movement_type_index` (`movement_type`),
  KEY `stock_movements_reference_type_index` (`reference_type`),
  KEY `stock_movements_reference_id_index` (`reference_id`),
  KEY `stock_movements_correlation_id_index` (`correlation_id`),
  KEY `stock_movements_reason_code_index` (`reason_code`),
  KEY `stock_movements_occurred_at_index` (`occurred_at`),
  KEY `stock_movements_product_branch_time_index` (`product_id`, `branch_id`, `occurred_at`),
  KEY `stock_movements_reference_index` (`reference_type`, `reference_id`),
  CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  KEY `stock_movements_branch_id_index` (`branch_id`),
  CONSTRAINT `stock_movements_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  KEY `stock_movements_account_id_index` (`account_id`),
  CONSTRAINT `stock_movements_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `purchase_orders` (
  `id` CHAR(26) NOT NULL,
  `order_number` VARCHAR(80) NOT NULL,
  `supplier_id` CHAR(26) NOT NULL,
  `branch_id` CHAR(26) NOT NULL,
  `created_by_account_id` CHAR(26) NULL,
  `approved_by_account_id` CHAR(26) NULL,
  `status` VARCHAR(40) NOT NULL,
  `expected_at` DATE NULL,
  `subtotal_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `tax_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `total_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `reference_note` TEXT NOT NULL,
  `approved_at` TIMESTAMP NULL,
  `received_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_orders_order_number_unique` (`order_number`),
  KEY `purchase_orders_status_index` (`status`),
  KEY `purchase_orders_expected_at_index` (`expected_at`),
  KEY `purchase_orders_approved_at_index` (`approved_at`),
  KEY `purchase_orders_received_at_index` (`received_at`),
  KEY `purchase_orders_supplier_id_status_index` (`supplier_id`, `status`),
  KEY `purchase_orders_branch_id_status_index` (`branch_id`, `status`),
  CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `purchase_orders_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  KEY `purchase_orders_created_by_account_id_index` (`created_by_account_id`),
  CONSTRAINT `purchase_orders_created_by_account_id_foreign` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  KEY `purchase_orders_approved_by_account_id_index` (`approved_by_account_id`),
  CONSTRAINT `purchase_orders_approved_by_account_id_foreign` FOREIGN KEY (`approved_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `purchase_order_lines` (
  `id` CHAR(26) NOT NULL,
  `purchase_order_id` CHAR(26) NOT NULL,
  `product_id` CHAR(26) NOT NULL,
  `ordered_quantity_milliunits` BIGINT NOT NULL,
  `received_quantity_milliunits` BIGINT NOT NULL DEFAULT 0,
  `unit_cost_kobo` BIGINT UNSIGNED NOT NULL,
  `tax_rate_basis_points` INT UNSIGNED NOT NULL DEFAULT 0,
  `line_total_kobo` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_order_lines_purchase_order_id_product_id_unique` (`purchase_order_id`, `product_id`),
  CONSTRAINT `purchase_order_lines_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  KEY `purchase_order_lines_product_id_index` (`product_id`),
  CONSTRAINT `purchase_order_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `low_stock_alerts` (
  `id` CHAR(26) NOT NULL,
  `product_id` CHAR(26) NOT NULL,
  `branch_id` CHAR(26) NOT NULL,
  `quantity_milliunits` BIGINT NOT NULL,
  `minimum_stock_milliunits` BIGINT NOT NULL,
  `opened_at` TIMESTAMP NOT NULL,
  `last_seen_at` TIMESTAMP NOT NULL,
  `resolved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `low_stock_alerts_opened_at_index` (`opened_at`),
  KEY `low_stock_alerts_last_seen_at_index` (`last_seen_at`),
  KEY `low_stock_alerts_resolved_at_index` (`resolved_at`),
  KEY `low_stock_alerts_branch_open_index` (`branch_id`, `resolved_at`, `opened_at`),
  UNIQUE KEY `low_stock_alerts_resolution_unique` (`product_id`, `branch_id`, `resolved_at`),
  CONSTRAINT `low_stock_alerts_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `low_stock_alerts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `customers` (
  `id` CHAR(26) NOT NULL,
  `customer_code` VARCHAR(60) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `email_encrypted` TEXT NULL,
  `address` TEXT NULL,
  `credit_limit_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `balance_kobo` BIGINT NOT NULL DEFAULT 0,
  `is_wholesale` TINYINT(1) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_by_account_id` CHAR(26) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deprecated_at` TIMESTAMP NULL DEFAULT NULL,
  `deprecated_by_account_id` CHAR(26) NULL,
  `deprecation_reason` TEXT NULL,
  KEY `customers_deprecated_at_index` (`deprecated_at`),
  KEY `customers_deprecated_by_account_id_index` (`deprecated_by_account_id`),
  CONSTRAINT `customers_deprecated_by_account_id_foreign` FOREIGN KEY (`deprecated_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  `whatsapp_phone` VARCHAR(40) NULL,
  `notes` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_customer_code_unique` (`customer_code`),
  KEY `customers_phone_index` (`phone`),
  KEY `customers_is_wholesale_index` (`is_wholesale`),
  KEY `customers_status_index` (`status`),
  KEY `customers_status_name_index` (`status`, `name`),
  KEY `customers_phone_status_index` (`phone`, `status`),
  KEY `customers_created_by_account_id_index` (`created_by_account_id`),
  CONSTRAINT `customers_created_by_account_id_foreign` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payment_methods` (
  `id` CHAR(26) NOT NULL,
  `name` VARCHAR(80) NOT NULL,
  `account_number_encrypted` TEXT NULL,
  `bank_name` VARCHAR(120) NULL,
  `description` TEXT NULL,
  `is_system_default` TINYINT(1) NOT NULL DEFAULT 0,
  `is_default_for_pos` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by_account_id` CHAR(26) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_methods_is_system_default_index` (`is_system_default`),
  KEY `payment_methods_is_default_for_pos_index` (`is_default_for_pos`),
  KEY `payment_methods_is_active_index` (`is_active`),
  UNIQUE KEY `payment_methods_name_unique` (`name`),
  KEY `payment_methods_is_active_name_index` (`is_active`, `name`),
  KEY `payment_methods_created_by_account_id_index` (`created_by_account_id`),
  CONSTRAINT `payment_methods_created_by_account_id_foreign` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales` (
  `id` CHAR(26) NOT NULL,
  `sale_code` VARCHAR(40) NOT NULL,
  `sale_type` VARCHAR(30) NOT NULL,
  `branch_id` CHAR(26) NOT NULL,
  `customer_id` CHAR(26) NULL,
  `sold_by_account_id` CHAR(26) NOT NULL,
  `converted_from_sale_id` CHAR(26) NULL,
  `sale_date` DATE NOT NULL,
  `subtotal_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `discount_amount_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `tax_amount_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `grand_total_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `paid_amount_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL,
  `idempotency_key` VARCHAR(80) NOT NULL,
  `notes` TEXT NULL,
  `confirmed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `discount_voucher_id` CHAR(26) NULL,
  `credit_note` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_sale_code_unique` (`sale_code`),
  KEY `sales_sale_type_index` (`sale_type`),
  KEY `sales_sale_date_index` (`sale_date`),
  KEY `sales_status_index` (`status`),
  UNIQUE KEY `sales_idempotency_key_unique` (`idempotency_key`),
  KEY `sales_confirmed_at_index` (`confirmed_at`),
  KEY `sales_branch_id_sale_date_index` (`branch_id`, `sale_date`),
  KEY `sales_sold_by_account_id_sale_date_index` (`sold_by_account_id`, `sale_date`),
  KEY `sales_sale_type_status_sale_date_index` (`sale_type`, `status`, `sale_date`),
  CONSTRAINT `sales_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  KEY `sales_customer_id_index` (`customer_id`),
  CONSTRAINT `sales_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_sold_by_account_id_foreign` FOREIGN KEY (`sold_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT,
  KEY `sales_converted_from_sale_id_index` (`converted_from_sale_id`),
  CONSTRAINT `sales_converted_from_sale_id_foreign` FOREIGN KEY (`converted_from_sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL,
  KEY `sales_discount_voucher_id_index` (`discount_voucher_id`),
  CONSTRAINT `sales_discount_voucher_id_foreign` FOREIGN KEY (`discount_voucher_id`) REFERENCES `discount_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sale_items` (
  `id` CHAR(26) NOT NULL,
  `sale_id` CHAR(26) NOT NULL,
  `product_id` CHAR(26) NOT NULL,
  `product_name_snapshot` VARCHAR(200) NOT NULL,
  `sku_snapshot` VARCHAR(100) NOT NULL,
  `track_inventory_snapshot` TINYINT(1) NOT NULL,
  `quantity_milliunits` BIGINT NOT NULL,
  `unit_price_kobo` BIGINT UNSIGNED NOT NULL,
  `discount_amount_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `tax_amount_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `line_total_kobo` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `unit_cost_kobo_snapshot` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `sale_items_sale_id_product_id_index` (`sale_id`, `product_id`),
  CONSTRAINT `sale_items_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  KEY `sale_items_product_id_index` (`product_id`),
  CONSTRAINT `sale_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payments` (
  `id` CHAR(26) NOT NULL,
  `sale_id` CHAR(26) NOT NULL,
  `payment_method_id` CHAR(26) NOT NULL,
  `amount_kobo` BIGINT UNSIGNED NOT NULL,
  `recorded_by_account_id` CHAR(26) NOT NULL,
  `reference` VARCHAR(160) NULL,
  `paid_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_paid_at_index` (`paid_at`),
  KEY `payments_sale_id_paid_at_index` (`sale_id`, `paid_at`),
  CONSTRAINT `payments_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  KEY `payments_payment_method_id_index` (`payment_method_id`),
  CONSTRAINT `payments_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE RESTRICT,
  KEY `payments_recorded_by_account_id_index` (`recorded_by_account_id`),
  CONSTRAINT `payments_recorded_by_account_id_foreign` FOREIGN KEY (`recorded_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `supplier_bills` (
  `id` CHAR(26) NOT NULL,
  `bill_number` VARCHAR(80) NOT NULL,
  `supplier_reference` VARCHAR(160) NULL,
  `supplier_id` CHAR(26) NOT NULL,
  `branch_id` CHAR(26) NOT NULL,
  `purchase_order_id` CHAR(26) NULL,
  `created_by_account_id` CHAR(26) NULL,
  `bill_date` DATE NOT NULL,
  `due_date` DATE NULL,
  `subtotal_kobo` BIGINT UNSIGNED NOT NULL,
  `tax_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `total_kobo` BIGINT UNSIGNED NOT NULL,
  `paid_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `status` VARCHAR(40) NOT NULL,
  `reference_note` TEXT NOT NULL,
  `posted_at` TIMESTAMP NULL,
  `cancelled_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_bills_bill_number_unique` (`bill_number`),
  KEY `supplier_bills_supplier_reference_index` (`supplier_reference`),
  KEY `supplier_bills_bill_date_index` (`bill_date`),
  KEY `supplier_bills_due_date_index` (`due_date`),
  KEY `supplier_bills_status_index` (`status`),
  KEY `supplier_bills_posted_at_index` (`posted_at`),
  KEY `supplier_bills_cancelled_at_index` (`cancelled_at`),
  KEY `supplier_bills_supplier_id_status_due_date_index` (`supplier_id`, `status`, `due_date`),
  KEY `supplier_bills_branch_id_bill_date_index` (`branch_id`, `bill_date`),
  CONSTRAINT `supplier_bills_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `supplier_bills_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  KEY `supplier_bills_purchase_order_id_index` (`purchase_order_id`),
  CONSTRAINT `supplier_bills_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  KEY `supplier_bills_created_by_account_id_index` (`created_by_account_id`),
  CONSTRAINT `supplier_bills_created_by_account_id_foreign` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `supplier_bill_lines` (
  `id` CHAR(26) NOT NULL,
  `supplier_bill_id` CHAR(26) NOT NULL,
  `product_id` CHAR(26) NULL,
  `description` VARCHAR(255) NOT NULL,
  `quantity_milliunits` BIGINT NOT NULL,
  `unit_cost_kobo` BIGINT UNSIGNED NOT NULL,
  `tax_rate_basis_points` INT UNSIGNED NOT NULL DEFAULT 0,
  `line_subtotal_kobo` BIGINT UNSIGNED NOT NULL,
  `tax_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `line_total_kobo` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_bill_lines_supplier_bill_id_product_id_index` (`supplier_bill_id`, `product_id`),
  CONSTRAINT `supplier_bill_lines_supplier_bill_id_foreign` FOREIGN KEY (`supplier_bill_id`) REFERENCES `supplier_bills` (`id`) ON DELETE CASCADE,
  KEY `supplier_bill_lines_product_id_index` (`product_id`),
  CONSTRAINT `supplier_bill_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `supplier_bill_payments` (
  `id` CHAR(26) NOT NULL,
  `supplier_bill_id` CHAR(26) NOT NULL,
  `payment_method_id` CHAR(26) NOT NULL,
  `recorded_by_account_id` CHAR(26) NOT NULL,
  `amount_kobo` BIGINT UNSIGNED NOT NULL,
  `reference` VARCHAR(160) NULL,
  `paid_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_bill_payments_paid_at_index` (`paid_at`),
  KEY `supplier_bill_payments_supplier_bill_id_paid_at_index` (`supplier_bill_id`, `paid_at`),
  CONSTRAINT `supplier_bill_payments_supplier_bill_id_foreign` FOREIGN KEY (`supplier_bill_id`) REFERENCES `supplier_bills` (`id`) ON DELETE CASCADE,
  KEY `supplier_bill_payments_payment_method_id_index` (`payment_method_id`),
  CONSTRAINT `supplier_bill_payments_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE RESTRICT,
  KEY `supplier_bill_payments_recorded_by_account_id_index` (`recorded_by_account_id`),
  CONSTRAINT `supplier_bill_payments_recorded_by_account_id_foreign` FOREIGN KEY (`recorded_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `supplier_returns` (
  `id` CHAR(26) NOT NULL,
  `return_number` VARCHAR(80) NOT NULL,
  `supplier_id` CHAR(26) NOT NULL,
  `branch_id` CHAR(26) NOT NULL,
  `supplier_bill_id` CHAR(26) NULL,
  `created_by_account_id` CHAR(26) NULL,
  `status` VARCHAR(40) NOT NULL,
  `return_date` DATE NOT NULL,
  `total_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `reason` VARCHAR(120) NOT NULL,
  `reference_note` TEXT NOT NULL,
  `confirmed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_returns_return_number_unique` (`return_number`),
  KEY `supplier_returns_status_index` (`status`),
  KEY `supplier_returns_return_date_index` (`return_date`),
  KEY `supplier_returns_confirmed_at_index` (`confirmed_at`),
  KEY `supplier_returns_supplier_id_return_date_index` (`supplier_id`, `return_date`),
  KEY `supplier_returns_branch_id_status_index` (`branch_id`, `status`),
  CONSTRAINT `supplier_returns_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `supplier_returns_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  KEY `supplier_returns_supplier_bill_id_index` (`supplier_bill_id`),
  CONSTRAINT `supplier_returns_supplier_bill_id_foreign` FOREIGN KEY (`supplier_bill_id`) REFERENCES `supplier_bills` (`id`) ON DELETE SET NULL,
  KEY `supplier_returns_created_by_account_id_index` (`created_by_account_id`),
  CONSTRAINT `supplier_returns_created_by_account_id_foreign` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `supplier_return_lines` (
  `id` CHAR(26) NOT NULL,
  `supplier_return_id` CHAR(26) NOT NULL,
  `product_id` CHAR(26) NOT NULL,
  `quantity_milliunits` BIGINT NOT NULL,
  `unit_cost_kobo` BIGINT UNSIGNED NOT NULL,
  `line_total_kobo` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_return_lines_supplier_return_id_product_id_unique` (`supplier_return_id`, `product_id`),
  CONSTRAINT `supplier_return_lines_supplier_return_id_foreign` FOREIGN KEY (`supplier_return_id`) REFERENCES `supplier_returns` (`id`) ON DELETE CASCADE,
  KEY `supplier_return_lines_product_id_index` (`product_id`),
  CONSTRAINT `supplier_return_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `supplier_documents` (
  `id` CHAR(26) NOT NULL,
  `supplier_id` CHAR(26) NOT NULL,
  `supplier_bill_id` CHAR(26) NULL,
  `uploaded_by_account_id` CHAR(26) NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `stored_path` VARCHAR(500) NOT NULL,
  `mime_type` VARCHAR(160) NOT NULL,
  `size_bytes` BIGINT UNSIGNED NOT NULL,
  `description` VARCHAR(500) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_documents_supplier_id_created_at_index` (`supplier_id`, `created_at`),
  KEY `supplier_documents_supplier_bill_id_created_at_index` (`supplier_bill_id`, `created_at`),
  CONSTRAINT `supplier_documents_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_documents_supplier_bill_id_foreign` FOREIGN KEY (`supplier_bill_id`) REFERENCES `supplier_bills` (`id`) ON DELETE CASCADE,
  KEY `supplier_documents_uploaded_by_account_id_index` (`uploaded_by_account_id`),
  CONSTRAINT `supplier_documents_uploaded_by_account_id_foreign` FOREIGN KEY (`uploaded_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `alert_recipients` (
  `id` CHAR(26) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `label` VARCHAR(80) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `added_by_account_id` CHAR(26) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alert_recipients_email_unique` (`email`),
  KEY `alert_recipients_is_active_index` (`is_active`),
  KEY `alert_recipients_is_active_email_index` (`is_active`, `email`),
  KEY `alert_recipients_added_by_account_id_index` (`added_by_account_id`),
  CONSTRAINT `alert_recipients_added_by_account_id_foreign` FOREIGN KEY (`added_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `business_settings` (
  `singleton_key` VARCHAR(20) NOT NULL,
  `business_name` VARCHAR(150) NOT NULL,
  `business_logo_path` VARCHAR(500) NULL,
  `head_office_address` TEXT NOT NULL,
  `end_of_day_digest_time` TIME NOT NULL DEFAULT '21:00:00',
  `session_inactivity_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 20,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`singleton_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `end_of_day_digests` (
  `id` CHAR(26) NOT NULL,
  `business_date` DATE NOT NULL,
  `status` VARCHAR(30) NOT NULL,
  `recipient_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `summary` JSON NULL,
  `started_at` TIMESTAMP NULL,
  `sent_at` TIMESTAMP NULL,
  `failure_message` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `end_of_day_digests_business_date_unique` (`business_date`),
  KEY `end_of_day_digests_status_index` (`status`),
  KEY `end_of_day_digests_started_at_index` (`started_at`),
  KEY `end_of_day_digests_sent_at_index` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `admin_notifications` (
  `id` CHAR(26) NOT NULL,
  `notification_type` VARCHAR(40) NOT NULL,
  `title` VARCHAR(180) NOT NULL,
  `message` TEXT NOT NULL,
  `entity_type` VARCHAR(80) NULL,
  `entity_id` VARCHAR(64) NULL,
  `branch_id` CHAR(26) NULL,
  `occurred_at` TIMESTAMP NOT NULL,
  `read_at` TIMESTAMP NULL,
  `resolved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_notifications_notification_type_index` (`notification_type`),
  KEY `admin_notifications_entity_type_index` (`entity_type`),
  KEY `admin_notifications_entity_id_index` (`entity_id`),
  KEY `admin_notifications_occurred_at_index` (`occurred_at`),
  KEY `admin_notifications_read_at_index` (`read_at`),
  KEY `admin_notifications_resolved_at_index` (`resolved_at`),
  KEY `admin_notifications_open_index` (`notification_type`, `resolved_at`, `occurred_at`),
  UNIQUE KEY `admin_notifications_open_entity_unique` (`notification_type`, `entity_type`, `entity_id`, `branch_id`, `resolved_at`),
  KEY `admin_notifications_branch_id_index` (`branch_id`),
  CONSTRAINT `admin_notifications_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `discount_vouchers` (
  `id` CHAR(26) NOT NULL,
  `code` VARCHAR(80) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `value_type` VARCHAR(30) NOT NULL,
  `value` BIGINT UNSIGNED NOT NULL,
  `minimum_sale_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `maximum_discount_kobo` BIGINT UNSIGNED NULL,
  `usage_limit` INT UNSIGNED NULL,
  `usage_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `starts_at` TIMESTAMP NULL,
  `ends_at` TIMESTAMP NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_by_account_id` CHAR(26) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `discount_vouchers_code_unique` (`code`),
  KEY `discount_vouchers_starts_at_index` (`starts_at`),
  KEY `discount_vouchers_ends_at_index` (`ends_at`),
  KEY `discount_vouchers_status_index` (`status`),
  KEY `discount_vouchers_created_by_account_id_index` (`created_by_account_id`),
  CONSTRAINT `discount_vouchers_created_by_account_id_foreign` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `voucher_redemptions` (
  `id` CHAR(26) NOT NULL,
  `discount_voucher_id` CHAR(26) NOT NULL,
  `sale_id` CHAR(26) NOT NULL,
  `customer_id` CHAR(26) NULL,
  `redeemed_by_account_id` CHAR(26) NOT NULL,
  `discount_amount_kobo` BIGINT UNSIGNED NOT NULL,
  `redeemed_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `voucher_redemptions_redeemed_at_index` (`redeemed_at`),
  UNIQUE KEY `voucher_redemptions_discount_voucher_id_sale_id_unique` (`discount_voucher_id`, `sale_id`),
  KEY `voucher_redemptions_customer_id_redeemed_at_index` (`customer_id`, `redeemed_at`),
  CONSTRAINT `voucher_redemptions_discount_voucher_id_foreign` FOREIGN KEY (`discount_voucher_id`) REFERENCES `discount_vouchers` (`id`) ON DELETE RESTRICT,
  KEY `voucher_redemptions_sale_id_index` (`sale_id`),
  CONSTRAINT `voucher_redemptions_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `voucher_redemptions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  KEY `voucher_redemptions_redeemed_by_account_id_index` (`redeemed_by_account_id`),
  CONSTRAINT `voucher_redemptions_redeemed_by_account_id_foreign` FOREIGN KEY (`redeemed_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sale_returns` (
  `id` CHAR(26) NOT NULL,
  `return_code` VARCHAR(40) NOT NULL,
  `sale_id` CHAR(26) NOT NULL,
  `branch_id` CHAR(26) NOT NULL,
  `customer_id` CHAR(26) NULL,
  `processed_by_account_id` CHAR(26) NOT NULL,
  `total_refund_kobo` BIGINT UNSIGNED NOT NULL,
  `refund_method` VARCHAR(80) NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'completed',
  `reason` TEXT NOT NULL,
  `returned_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sale_returns_return_code_unique` (`return_code`),
  KEY `sale_returns_status_index` (`status`),
  KEY `sale_returns_returned_at_index` (`returned_at`),
  KEY `sale_returns_sale_id_returned_at_index` (`sale_id`, `returned_at`),
  CONSTRAINT `sale_returns_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE RESTRICT,
  KEY `sale_returns_branch_id_index` (`branch_id`),
  CONSTRAINT `sale_returns_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  KEY `sale_returns_customer_id_index` (`customer_id`),
  CONSTRAINT `sale_returns_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  KEY `sale_returns_processed_by_account_id_index` (`processed_by_account_id`),
  CONSTRAINT `sale_returns_processed_by_account_id_foreign` FOREIGN KEY (`processed_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sale_return_items` (
  `id` CHAR(26) NOT NULL,
  `sale_return_id` CHAR(26) NOT NULL,
  `sale_item_id` CHAR(26) NOT NULL,
  `product_id` CHAR(26) NOT NULL,
  `quantity_milliunits` BIGINT NOT NULL,
  `refund_amount_kobo` BIGINT UNSIGNED NOT NULL,
  `restock` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_return_items_sale_return_id_product_id_index` (`sale_return_id`, `product_id`),
  CONSTRAINT `sale_return_items_sale_return_id_foreign` FOREIGN KEY (`sale_return_id`) REFERENCES `sale_returns` (`id`) ON DELETE CASCADE,
  KEY `sale_return_items_sale_item_id_index` (`sale_item_id`),
  CONSTRAINT `sale_return_items_sale_item_id_foreign` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`) ON DELETE RESTRICT,
  KEY `sale_return_items_product_id_index` (`product_id`),
  CONSTRAINT `sale_return_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `purchase_receipts` (
  `id` CHAR(26) NOT NULL,
  `receipt_number` VARCHAR(40) NOT NULL,
  `supplier_id` CHAR(26) NOT NULL,
  `branch_id` CHAR(26) NOT NULL,
  `recorded_by_account_id` CHAR(26) NOT NULL,
  `purchase_order_id` CHAR(26) NULL,
  `purchased_at` DATE NOT NULL,
  `supplier_reference` VARCHAR(160) NULL,
  `subtotal_kobo` BIGINT UNSIGNED NOT NULL,
  `discount_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `tax_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `total_kobo` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'recorded',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_receipts_receipt_number_unique` (`receipt_number`),
  KEY `purchase_receipts_purchased_at_index` (`purchased_at`),
  KEY `purchase_receipts_status_index` (`status`),
  KEY `purchase_receipts_supplier_id_purchased_at_index` (`supplier_id`, `purchased_at`),
  KEY `purchase_receipts_branch_id_purchased_at_index` (`branch_id`, `purchased_at`),
  CONSTRAINT `purchase_receipts_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `purchase_receipts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  KEY `purchase_receipts_recorded_by_account_id_index` (`recorded_by_account_id`),
  CONSTRAINT `purchase_receipts_recorded_by_account_id_foreign` FOREIGN KEY (`recorded_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT,
  KEY `purchase_receipts_purchase_order_id_index` (`purchase_order_id`),
  CONSTRAINT `purchase_receipts_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `purchase_receipt_lines` (
  `id` CHAR(26) NOT NULL,
  `purchase_receipt_id` CHAR(26) NOT NULL,
  `product_id` CHAR(26) NOT NULL,
  `quantity_milliunits` BIGINT NOT NULL,
  `unit_cost_kobo` BIGINT UNSIGNED NOT NULL,
  `discount_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `tax_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `line_total_kobo` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_receipt_lines_purchase_receipt_id_product_id_index` (`purchase_receipt_id`, `product_id`),
  CONSTRAINT `purchase_receipt_lines_purchase_receipt_id_foreign` FOREIGN KEY (`purchase_receipt_id`) REFERENCES `purchase_receipts` (`id`) ON DELETE CASCADE,
  KEY `purchase_receipt_lines_product_id_index` (`product_id`),
  CONSTRAINT `purchase_receipt_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `api_tokens` (
  `id` CHAR(26) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `token_prefix` VARCHAR(16) NOT NULL,
  `token_hash` VARCHAR(64) NOT NULL,
  `abilities` JSON NOT NULL,
  `created_by_account_id` CHAR(26) NOT NULL,
  `last_used_at` TIMESTAMP NULL,
  `expires_at` TIMESTAMP NULL,
  `revoked_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_tokens_token_prefix_index` (`token_prefix`),
  UNIQUE KEY `api_tokens_token_hash_unique` (`token_hash`),
  KEY `api_tokens_last_used_at_index` (`last_used_at`),
  KEY `api_tokens_expires_at_index` (`expires_at`),
  KEY `api_tokens_revoked_at_index` (`revoked_at`),
  KEY `api_tokens_validity_index` (`revoked_at`, `expires_at`),
  KEY `api_tokens_created_by_account_id_index` (`created_by_account_id`),
  CONSTRAINT `api_tokens_created_by_account_id_foreign` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quote_conversions` (
  `id` CHAR(26) NOT NULL,
  `source_quote_id` CHAR(26) NOT NULL,
  `converted_sale_id` CHAR(26) NOT NULL,
  `converted_by_account_id` CHAR(26) NOT NULL,
  `target_type` VARCHAR(20) NOT NULL,
  `converted_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quote_conversions_converted_at_index` (`converted_at`),
  UNIQUE KEY `quote_conversion_pair_unique` (`source_quote_id`, `converted_sale_id`),
  CONSTRAINT `quote_conversions_source_quote_id_foreign` FOREIGN KEY (`source_quote_id`) REFERENCES `sales` (`id`) ON DELETE RESTRICT,
  KEY `quote_conversions_converted_sale_id_index` (`converted_sale_id`),
  CONSTRAINT `quote_conversions_converted_sale_id_foreign` FOREIGN KEY (`converted_sale_id`) REFERENCES `sales` (`id`) ON DELETE RESTRICT,
  KEY `quote_conversions_converted_by_account_id_index` (`converted_by_account_id`),
  CONSTRAINT `quote_conversions_converted_by_account_id_foreign` FOREIGN KEY (`converted_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `backup_runs` (
  `id` CHAR(26) NOT NULL,
  `backup_type` VARCHAR(30) NOT NULL,
  `status` VARCHAR(30) NOT NULL,
  `disk` VARCHAR(60) NOT NULL,
  `path` VARCHAR(500) NULL,
  `checksum_sha256` VARCHAR(64) NULL,
  `size_bytes` BIGINT UNSIGNED NULL,
  `manifest` JSON NULL,
  `failure_message` TEXT NULL,
  `requested_by_account_id` CHAR(26) NULL,
  `started_at` TIMESTAMP NOT NULL,
  `completed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `backup_runs_status_index` (`status`),
  KEY `backup_runs_started_at_index` (`started_at`),
  KEY `backup_runs_completed_at_index` (`completed_at`),
  KEY `backup_runs_requested_by_account_id_index` (`requested_by_account_id`),
  CONSTRAINT `backup_runs_requested_by_account_id_foreign` FOREIGN KEY (`requested_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `document_brandings` (
  `id` CHAR(26) NOT NULL,
  `business_name` VARCHAR(180) NOT NULL,
  `logo_path` VARCHAR(500) NULL,
  `address` VARCHAR(500) NULL,
  `phone` VARCHAR(80) NULL,
  `email` VARCHAR(180) NULL,
  `receipt_footer` TEXT NULL,
  `document_terms` TEXT NULL,
  `updated_by_account_id` CHAR(26) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `document_brandings_updated_by_account_id_index` (`updated_by_account_id`),
  CONSTRAINT `document_brandings_updated_by_account_id_foreign` FOREIGN KEY (`updated_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `standalone_receipts` (
  `id` CHAR(26) NOT NULL,
  `receipt_number` VARCHAR(40) NOT NULL,
  `branch_id` CHAR(26) NOT NULL,
  `customer_id` CHAR(26) NULL,
  `payment_method_id` CHAR(26) NOT NULL,
  `received_by_account_id` CHAR(26) NOT NULL,
  `payer_name` VARCHAR(180) NOT NULL,
  `payer_phone` VARCHAR(80) NULL,
  `amount_kobo` BIGINT UNSIGNED NOT NULL,
  `reference` VARCHAR(180) NULL,
  `purpose` VARCHAR(255) NOT NULL,
  `notes` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'received',
  `received_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `standalone_receipts_receipt_number_unique` (`receipt_number`),
  KEY `standalone_receipts_status_index` (`status`),
  KEY `standalone_receipts_received_at_index` (`received_at`),
  KEY `standalone_receipts_customer_id_received_at_index` (`customer_id`, `received_at`),
  KEY `standalone_receipts_branch_id_received_at_index` (`branch_id`, `received_at`),
  CONSTRAINT `standalone_receipts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `standalone_receipts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  KEY `standalone_receipts_payment_method_id_index` (`payment_method_id`),
  CONSTRAINT `standalone_receipts_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE RESTRICT,
  KEY `standalone_receipts_received_by_account_id_index` (`received_by_account_id`),
  CONSTRAINT `standalone_receipts_received_by_account_id_foreign` FOREIGN KEY (`received_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `purchase_returns` (
  `id` CHAR(26) NOT NULL,
  `return_number` VARCHAR(40) NOT NULL,
  `purchase_receipt_id` CHAR(26) NOT NULL,
  `supplier_id` CHAR(26) NOT NULL,
  `branch_id` CHAR(26) NOT NULL,
  `processed_by_account_id` CHAR(26) NOT NULL,
  `total_kobo` BIGINT UNSIGNED NOT NULL,
  `supplier_credit_reference` VARCHAR(180) NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'completed',
  `reason` TEXT NOT NULL,
  `returned_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_returns_return_number_unique` (`return_number`),
  KEY `purchase_returns_status_index` (`status`),
  KEY `purchase_returns_returned_at_index` (`returned_at`),
  KEY `purchase_returns_supplier_id_returned_at_index` (`supplier_id`, `returned_at`),
  KEY `purchase_returns_purchase_receipt_id_returned_at_index` (`purchase_receipt_id`, `returned_at`),
  CONSTRAINT `purchase_returns_purchase_receipt_id_foreign` FOREIGN KEY (`purchase_receipt_id`) REFERENCES `purchase_receipts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `purchase_returns_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
  KEY `purchase_returns_branch_id_index` (`branch_id`),
  CONSTRAINT `purchase_returns_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  KEY `purchase_returns_processed_by_account_id_index` (`processed_by_account_id`),
  CONSTRAINT `purchase_returns_processed_by_account_id_foreign` FOREIGN KEY (`processed_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `purchase_return_lines` (
  `id` CHAR(26) NOT NULL,
  `purchase_return_id` CHAR(26) NOT NULL,
  `purchase_receipt_line_id` CHAR(26) NOT NULL,
  `product_id` CHAR(26) NOT NULL,
  `quantity_milliunits` BIGINT NOT NULL,
  `unit_cost_kobo` BIGINT UNSIGNED NOT NULL,
  `line_total_kobo` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_return_lines_purchase_return_id_product_id_index` (`purchase_return_id`, `product_id`),
  CONSTRAINT `purchase_return_lines_purchase_return_id_foreign` FOREIGN KEY (`purchase_return_id`) REFERENCES `purchase_returns` (`id`) ON DELETE CASCADE,
  KEY `purchase_return_lines_purchase_receipt_line_id_index` (`purchase_receipt_line_id`),
  CONSTRAINT `purchase_return_lines_purchase_receipt_line_id_foreign` FOREIGN KEY (`purchase_receipt_line_id`) REFERENCES `purchase_receipt_lines` (`id`) ON DELETE RESTRICT,
  KEY `purchase_return_lines_product_id_index` (`product_id`),
  CONSTRAINT `purchase_return_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fixed_assets` (
  `id` CHAR(26) NOT NULL,
  `asset_code` VARCHAR(40) NOT NULL,
  `name` VARCHAR(180) NOT NULL,
  `category` VARCHAR(120) NOT NULL,
  `branch_id` CHAR(26) NULL,
  `custodian_account_id` CHAR(26) NULL,
  `acquired_at` DATE NOT NULL,
  `cost_kobo` BIGINT UNSIGNED NOT NULL,
  `salvage_value_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `useful_life_months` INT UNSIGNED NOT NULL,
  `serial_number` VARCHAR(160) NULL,
  `location` VARCHAR(255) NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `notes` TEXT NULL,
  `created_by_account_id` CHAR(26) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fixed_assets_asset_code_unique` (`asset_code`),
  KEY `fixed_assets_acquired_at_index` (`acquired_at`),
  KEY `fixed_assets_status_index` (`status`),
  KEY `fixed_assets_category_status_index` (`category`, `status`),
  KEY `fixed_assets_branch_id_index` (`branch_id`),
  CONSTRAINT `fixed_assets_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  KEY `fixed_assets_custodian_account_id_index` (`custodian_account_id`),
  CONSTRAINT `fixed_assets_custodian_account_id_foreign` FOREIGN KEY (`custodian_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  KEY `fixed_assets_created_by_account_id_index` (`created_by_account_id`),
  CONSTRAINT `fixed_assets_created_by_account_id_foreign` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `operation_document_logs` (
  `id` CHAR(26) NOT NULL,
  `operation_type` VARCHAR(80) NOT NULL,
  `operation_id` VARCHAR(40) NOT NULL,
  `format` VARCHAR(20) NOT NULL,
  `document_hash` VARCHAR(64) NOT NULL,
  `generated_by_account_id` CHAR(26) NOT NULL,
  `generated_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `operation_document_logs_operation_type_index` (`operation_type`),
  KEY `operation_document_logs_operation_id_index` (`operation_id`),
  KEY `operation_document_logs_generated_at_index` (`generated_at`),
  KEY `operation_document_lookup` (`operation_type`, `operation_id`, `generated_at`),
  KEY `operation_document_logs_generated_by_account_id_index` (`generated_by_account_id`),
  CONSTRAINT `operation_document_logs_generated_by_account_id_foreign` FOREIGN KEY (`generated_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ledger_accounts` (
  `id` CHAR(26) NOT NULL,
  `code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(180) NOT NULL,
  `type` VARCHAR(30) NOT NULL,
  `parent_id` CHAR(26) NULL,
  `is_control_account` TINYINT(1) NOT NULL DEFAULT 0,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `allow_manual_posting` TINYINT(1) NOT NULL DEFAULT 1,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ledger_accounts_code_unique` (`code`),
  KEY `ledger_accounts_type_index` (`type`),
  KEY `ledger_accounts_is_control_account_index` (`is_control_account`),
  KEY `ledger_accounts_is_system_index` (`is_system`),
  KEY `ledger_accounts_is_active_index` (`is_active`),
  KEY `ledger_accounts_type_is_active_code_index` (`type`, `is_active`, `code`),
  KEY `ledger_accounts_parent_id_index` (`parent_id`),
  CONSTRAINT `ledger_accounts_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `ledger_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_periods` (
  `id` CHAR(26) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `starts_on` DATE NOT NULL,
  `ends_on` DATE NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `closed_by_account_id` CHAR(26) NULL,
  `closed_at` TIMESTAMP NULL,
  `locked_by_account_id` CHAR(26) NULL,
  `locked_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_periods_starts_on_index` (`starts_on`),
  KEY `accounting_periods_ends_on_index` (`ends_on`),
  KEY `accounting_periods_status_index` (`status`),
  UNIQUE KEY `accounting_periods_starts_on_ends_on_unique` (`starts_on`, `ends_on`),
  KEY `accounting_periods_closed_by_account_id_index` (`closed_by_account_id`),
  CONSTRAINT `accounting_periods_closed_by_account_id_foreign` FOREIGN KEY (`closed_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  KEY `accounting_periods_locked_by_account_id_index` (`locked_by_account_id`),
  CONSTRAINT `accounting_periods_locked_by_account_id_foreign` FOREIGN KEY (`locked_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `journal_entries` (
  `id` CHAR(26) NOT NULL,
  `journal_number` VARCHAR(50) NOT NULL,
  `entry_date` DATE NOT NULL,
  `accounting_period_id` CHAR(26) NOT NULL,
  `branch_id` CHAR(26) NULL,
  `source_type` VARCHAR(120) NULL,
  `source_id` VARCHAR(40) NULL,
  `source_event` VARCHAR(80) NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'posted',
  `memo` VARCHAR(500) NOT NULL,
  `created_by_account_id` CHAR(26) NULL,
  `reversal_of_entry_id` CHAR(26) NULL,
  `posted_at` TIMESTAMP NULL,
  `reversed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `journal_entries_journal_number_unique` (`journal_number`),
  KEY `journal_entries_entry_date_index` (`entry_date`),
  KEY `journal_entries_source_type_index` (`source_type`),
  KEY `journal_entries_source_id_index` (`source_id`),
  KEY `journal_entries_status_index` (`status`),
  KEY `journal_entries_posted_at_index` (`posted_at`),
  KEY `journal_entries_reversed_at_index` (`reversed_at`),
  UNIQUE KEY `journal_source_event_unique` (`source_type`, `source_id`, `source_event`),
  KEY `journal_entries_entry_date_status_index` (`entry_date`, `status`),
  KEY `journal_entries_accounting_period_id_index` (`accounting_period_id`),
  CONSTRAINT `journal_entries_accounting_period_id_foreign` FOREIGN KEY (`accounting_period_id`) REFERENCES `accounting_periods` (`id`) ON DELETE RESTRICT,
  KEY `journal_entries_branch_id_index` (`branch_id`),
  CONSTRAINT `journal_entries_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  KEY `journal_entries_created_by_account_id_index` (`created_by_account_id`),
  CONSTRAINT `journal_entries_created_by_account_id_foreign` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  KEY `journal_entries_reversal_of_entry_id_index` (`reversal_of_entry_id`),
  CONSTRAINT `journal_entries_reversal_of_entry_id_foreign` FOREIGN KEY (`reversal_of_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `journal_lines` (
  `id` CHAR(26) NOT NULL,
  `journal_entry_id` CHAR(26) NOT NULL,
  `ledger_account_id` CHAR(26) NOT NULL,
  `branch_id` CHAR(26) NULL,
  `customer_id` CHAR(26) NULL,
  `supplier_id` CHAR(26) NULL,
  `debit_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `credit_kobo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `description` VARCHAR(500) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `journal_lines_ledger_account_id_journal_entry_id_index` (`ledger_account_id`, `journal_entry_id`),
  KEY `journal_lines_customer_id_ledger_account_id_index` (`customer_id`, `ledger_account_id`),
  KEY `journal_lines_supplier_id_ledger_account_id_index` (`supplier_id`, `ledger_account_id`),
  KEY `journal_lines_journal_entry_id_index` (`journal_entry_id`),
  CONSTRAINT `journal_lines_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `journal_lines_ledger_account_id_foreign` FOREIGN KEY (`ledger_account_id`) REFERENCES `ledger_accounts` (`id`) ON DELETE RESTRICT,
  KEY `journal_lines_branch_id_index` (`branch_id`),
  CONSTRAINT `journal_lines_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journal_lines_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journal_lines_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `asset_depreciation_postings` (
  `id` CHAR(26) NOT NULL,
  `fixed_asset_id` CHAR(26) NOT NULL,
  `journal_entry_id` CHAR(26) NOT NULL,
  `period_end` DATE NOT NULL,
  `amount_kobo` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_depreciation_postings_period_end_index` (`period_end`),
  UNIQUE KEY `asset_depreciation_postings_fixed_asset_id_period_end_unique` (`fixed_asset_id`, `period_end`),
  CONSTRAINT `asset_depreciation_postings_fixed_asset_id_foreign` FOREIGN KEY (`fixed_asset_id`) REFERENCES `fixed_assets` (`id`) ON DELETE RESTRICT,
  KEY `asset_depreciation_postings_journal_entry_id_index` (`journal_entry_id`),
  CONSTRAINT `asset_depreciation_postings_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `lisa_conversations` (
  `id` CHAR(26) NOT NULL,
  `account_id` CHAR(26) NOT NULL,
  `branch_id` CHAR(26) NULL,
  `title` VARCHAR(180) NOT NULL DEFAULT 'New conversation',
  `last_message_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lisa_conversations_account_id_index` (`account_id`),
  KEY `lisa_conversations_branch_id_index` (`branch_id`),
  CONSTRAINT `lisa_conversations_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lisa_conversations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lisa_messages` (
  `id` CHAR(26) NOT NULL,
  `conversation_id` CHAR(26) NOT NULL,
  `account_id` CHAR(26) NULL,
  `role` ENUM('user','assistant','system') NOT NULL,
  `content` LONGTEXT NOT NULL,
  `context_snapshot` JSON NULL,
  `response_time_ms` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lisa_messages_conversation_id_index` (`conversation_id`),
  KEY `lisa_messages_account_id_index` (`account_id`),
  CONSTRAINT `lisa_messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `lisa_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lisa_messages_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `daily_report_runs` (
  `id` CHAR(26) NOT NULL,
  `report_date` DATE NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `generated_files` JSON NULL,
  `summary_html` LONGTEXT NULL,
  `failure_message` TEXT NULL,
  `started_at` TIMESTAMP NULL DEFAULT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `daily_report_runs_report_date_unique` (`report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES
(1,'0001_01_01_000001_create_cache_table',1),
(2,'0001_01_01_000002_create_jobs_table',1),
(3,'2026_07_15_000100_create_accounts_table',1),
(4,'2026_07_15_000110_create_account_sessions_table',1),
(5,'2026_07_15_000120_create_security_events_table',1),
(6,'2026_07_15_000200_create_companies_table',1),
(7,'2026_07_15_000210_create_branches_table',1),
(8,'2026_07_15_000220_create_roles_and_permissions_tables',1),
(9,'2026_07_15_000230_create_account_branch_table',1),
(10,'2026_07_15_000240_create_audit_logs_table',1),
(11,'2026_07_15_000300_create_catalog_classification_tables',1),
(12,'2026_07_15_000310_create_suppliers_table',1),
(13,'2026_07_15_000320_create_products_tables',1),
(14,'2026_07_15_000400_create_product_imports_tables',1),
(15,'2026_07_15_000500_create_inventory_tables',1),
(16,'2026_07_15_000600_create_procurement_and_alert_tables',1),
(17,'2026_07_15_000700_create_customers_and_payment_methods',1),
(18,'2026_07_15_000800_create_sales_tables',1),
(19,'2026_07_15_000900_create_supplier_finance_tables',1),
(20,'2026_07_15_001000_create_operations_dashboard_tables',1),
(21,'2026_07_15_001500_create_commercial_controls',1),
(22,'2026_07_15_001600_create_api_tokens_and_quote_conversions',1),
(23,'2026_07_15_001700_create_backup_runs',1),
(24,'2026_07_16_001800_create_operational_accounting_records',1),
(25,'2026_07_16_001900_create_double_entry_accounting',1),
(26,'2026_07_16_001900_create_business_insights_table',1),
(27,'2026_07_17_000001_add_zero_stock_policy_to_branches',1),
(28,'2026_07_18_000400_create_sprint4_enterprise_features',1),
(29,'2026_07_18_120000_add_selling_price_kobo_to_product_branch_stocks_table',1);

INSERT INTO `companies` (`id`,`legal_name`,`trading_name`,`head_office_address`,`phone`,`email_encrypted`,`logo_path`,`timezone`,`is_configured`,`created_at`,`updated_at`) VALUES ('01KXNCRHG0Y5MCSQCHAJ65N347','Express Cloud','Express Cloud','Update after installation',NULL,NULL,NULL,'Africa/Lagos',1,'2026-07-16 12:00:00','2026-07-16 12:00:00');
INSERT INTO `branches` (`id`,`name`,`code`,`address`,`phone`,`status`,`is_head_office`,`created_at`,`updated_at`) VALUES ('01KXNCRHG0YE67CK4AM05PAY7M','Head Office','HQ','Update after installation',NULL,'active',1,'2026-07-16 12:00:00','2026-07-16 12:00:00');
INSERT INTO `accounts` (`id`,`public_id`,`first_name`,`last_name`,`email_encrypted`,`login_key_encrypted`,`login_key_blind_index`,`login_key_version`,`profile_picture_path`,`status`,`is_allowed_all_branches`,`last_authenticated_at`,`remember_token`,`created_at`,`updated_at`) VALUES ('01KXNCRHG0KBS12CMV5Z42WQQY','4932de79-670c-40fe-9a44-37bb01ad738b','Administrator','Account',NULL,'{"v":1,"ciphertext":"eyJpdiI6InJNWlllSGtvQjFtcm85SVlwK1BkT1E9PSIsInZhbHVlIjoiMmFZMXZJQ1l4eVZKZnphUGp3NzZoQT09IiwibWFjIjoiMjA5NDI3YjE4ZGE4YzU3ZTQ4NWNhMzE4NTFjYTI2ZDUyMDhiZTUzYWRhNzRlZmI5ZWQwZGQwOTBmNzhhODZjZiJ9"}','4a3033e5df7f542aacd141ce4cd0427d10e4a2820efa257fac5fc2e2f3610fcb',1,NULL,'active',1,NULL,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00');
INSERT INTO `roles` (`id`,`name`,`slug`,`description`,`is_system`,`is_active`,`created_at`,`updated_at`) VALUES ('01KXNCRHG09CB8V26WGWN7EMY2','System Owner','system-owner','Full installation owner access.',1,1,'2026-07-16 12:00:00','2026-07-16 12:00:00');
INSERT INTO `permissions` (`id`,`name`,`slug`,`group`,`description`,`created_at`,`updated_at`) VALUES
('01KXNCRHG1MFZ5GR2B5RZZ1ZKF','View company details','company.view','organisation','View company details','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHG29ERR8BEG5812N1GY','Update company details','company.update','organisation','Update company details','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHG3D82HAMA0HHF3V9J3','View branches','branches.view','organisation','View branches','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHG4C4KVJPSJGWGP6V67','Create branches','branches.create','organisation','Create branches','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHG50CXF2C2P2NCMNRJJ','Update branches','branches.update','organisation','Update branches','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHG6MC1E6RC89TNTTY64','Deactivate branches','branches.deactivate','organisation','Deactivate branches','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHG791NSJVSA54GRF5YR','View staff accounts','staff.view','staff','View staff accounts','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHG8DTJC4Z9W3KQY3AGE','Create staff accounts','staff.create','staff','Create staff accounts','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHG9NTM5SX82P67FPA3K','Update staff accounts','staff.update','staff','Update staff accounts','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGAR0Z27BFJMG7Q3T17','Suspend staff accounts','staff.suspend','staff','Suspend staff accounts','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGBCW69HJJHNXD67WF6','Reactivate staff accounts','staff.reactivate','staff','Reactivate staff accounts','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGCJ06PYWYRNPTSMH6Y','Revoke staff accounts','staff.revoke','staff','Revoke staff accounts','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGDDP96A3848CACMH43','Reveal staff access keys','staff.access-key.reveal','staff','Reveal staff access keys','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGE2ZV7WMZ5EZNJM84X','Regenerate staff access keys','staff.access-key.regenerate','staff','Regenerate staff access keys','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGF17GJZW0K4T88GGDR','View active staff sessions','staff.sessions.view','staff','View active staff sessions','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGGDQJDMHRNZPF7NZ1X','Revoke staff sessions','staff.sessions.revoke','staff','Revoke staff sessions','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGHW9WB9HFR8NVK7FZK','View roles','roles.view','authorization','View roles','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGJZ58CQB990A28B223','Create roles','roles.create','authorization','Create roles','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGKECMTF6C8KPW37Q0F','Update roles','roles.update','authorization','Update roles','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGMXN2C0RCAXDX57TH5','Delete custom roles','roles.delete','authorization','Delete custom roles','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGNTH92Y91QKSW0SA29','Assign permissions','permissions.assign','authorization','Assign permissions','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGPF6XGFJ4YFEBMXCKJ','View products','products.view','catalog','View products','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGQ0HFJ16BWEE2N4CP2','Create products','products.create','catalog','Create products','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGRW4PGG8SDCC6D7EYF','Update products','products.update','catalog','Update products','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGS5MT0M5V9RRNQZ8XD','Deactivate products','products.deactivate','catalog','Deactivate products','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGTV3H1WBG301B3033Z','Manage product categories','categories.manage','catalog','Manage product categories','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGVPG4H8716FNW3TKBB','Manage brands','brands.manage','catalog','Manage brands','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGW33NY1KV6FRXHMXQ9','Manage tax rates','tax-rates.manage','catalog','Manage tax rates','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGXGNGHAK7T5VPGBQEC','View suppliers','suppliers.view','catalog','View suppliers','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGYV3J7GJ0CXVZVJ53W','Create suppliers','suppliers.create','catalog','Create suppliers','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGZPM22YCXAYDP91RQC','Update suppliers','suppliers.update','catalog','Update suppliers','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH011K83W7KFGV4QYEV','Archive suppliers','suppliers.archive','catalog','Archive suppliers','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH1J5ZHP2QN9V5TGF1P','Import products from Excel','products.import','catalog','Import products from Excel','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH2041M5NE6TJEN6MKJ','View product import history','products.import-history','catalog','View product import history','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH3AEBT7TP6YXBWSGNX','View branch inventory','inventory.view','catalog','View branch inventory','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH48K5BD1252FQHR7M1','View stock movement ledger','inventory.movements.view','catalog','View stock movement ledger','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH5V0VGK14NS959KN5R','Record stock intake','inventory.intake','catalog','Record stock intake','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH6GAXZDJJAAX1NXE98','Transfer stock between branches','inventory.transfer','catalog','Transfer stock between branches','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH7SF0YVQ34G6YSCCEJ','Adjust stock with a reason','inventory.adjust','catalog','Adjust stock with a reason','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH842ZYSP8CPSKE83S1','View purchase orders','procurement.view','catalog','View purchase orders','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH9Z0PRX88MZD0A857K','Create purchase orders','procurement.create','catalog','Create purchase orders','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHARYBW390H1KR1MFYS','Approve purchase orders','procurement.approve','catalog','Approve purchase orders','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHBJ09N711C0EGWKMKN','Receive goods against purchase orders','procurement.receive','catalog','Receive goods against purchase orders','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHC7R0Y9W4HCWJ0JJG5','View low-stock report','reports.low-stock','catalog','View low-stock report','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHDQ4SB81DTDC9ZGD13','View customers','customers.view','catalog','View customers','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHET1GBRVJMZYVN0QJW','Create customers','customers.create','catalog','Create customers','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHFSGTXH80E4VWK3GAZ','Update customers','customers.update','catalog','Update customers','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHGA7F99ZE27HZ2VD68','View payment methods','payment-methods.view','catalog','View payment methods','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHHC09WZGBR9PX0GGFP','Manage payment methods','payment-methods.manage','catalog','Manage payment methods','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHJVBQW4DDWPMJNH0QD','View sales','sales.view','catalog','View sales','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHKYWNCN9JVSMRCPHZ3','Create invoices, quotes, and POS sales','sales.create','catalog','Create invoices, quotes, and POS sales','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHM6ANDVERDE288Y2JQ','Record sale payments','sales.payments','catalog','Record sale payments','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHNPTF1J0FKE5MQY3SP','Convert quotes','sales.convert-quotes','catalog','Convert quotes','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHP0581FXJVQ07RHQ6P','View supplier bills','supplier-bills.view','catalog','View supplier bills','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHQJYPS1JJ9ETBEG3DN','Create supplier bills','supplier-bills.create','catalog','Create supplier bills','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHRMAG76MM2XC2W0YJD','Record supplier bill payments','supplier-bills.pay','catalog','Record supplier bill payments','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHSPVW0F460VNQQK8JV','Download supplier documents','supplier-documents.download','catalog','Download supplier documents','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHTSFPHQQ2R0A2Y2ZQM','View supplier returns','supplier-returns.view','catalog','View supplier returns','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHVJYK2870YT6T6SG46','Create supplier returns','supplier-returns.create','catalog','Create supplier returns','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHW4BDH8992MKYZXXS7','View supplier balances','reports.supplier-balances','catalog','View supplier balances','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHXE5CTVEWT5G73S5DD','View admin dashboard','dashboard.view','catalog','View admin dashboard','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHYF6ZCE16QY45B39W6','View operational alerts','alerts.view','catalog','View operational alerts','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHZ12HSM0NJCA3QQ7DE','Manage alert recipients','alerts.manage-recipients','catalog','Manage alert recipients','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ0BMVCR9GH69Y7MBZA','Manage business settings','settings.business.manage','catalog','Manage business settings','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ1GCW8KCANP0J84EHZ','View staff performance','reports.staff-performance','catalog','View staff performance','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ2YB2P7QQRQBFJQR4S','View reports hub','reports.hub.view','catalog','View reports hub','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ3R1H1P86BTTQ4BW7N','Export reports','reports.export','catalog','Export reports','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ4J0VK2CFQWVVH8C1M','Print and download sale documents','documents.sales.print','catalog','Print and download sale documents','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ5RDP84MHBZDPSKA1C','Print product labels','documents.products.labels','catalog','Print product labels','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ608SK2J1Z4FD3PNW1','View system activity log','activity.view','catalog','View system activity log','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ7J2HT3STPY6ZZVSGX','View product activity','activity.products.view','catalog','View product activity','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ8D5H03243APN9BCB3','View live sessions','security.sessions.view','catalog','View live sessions','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ96CAKVVFM6KAW75HS','Terminate live sessions','security.sessions.terminate','catalog','Terminate live sessions','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJA8662JXKNG9RKG5ZE','View security events','security-events.view','security','View security events','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJB883KZE818F7E756A','View audit logs','audit-log.view','security','View audit logs','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJC6Z5WQRZBY1E0JJBS','Export audit logs','audit-log.export','security','Export audit logs','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJDYCB4QZYRRRTNX4R0','View own sales','sales.view.own','Commercial','View own sales','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJEMAAZZQNVY15EVKAC','View all sales','sales.view.all','Commercial','View all sales','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJF1AD40GXMSM3JR9X4','Record sale payments','sales.payments.record','Commercial','Record sale payments','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJGJ8WN7F1GFQ8382FW','Create sale returns','sales.returns.create','Commercial','Create sale returns','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJH2GK6GQ11NX171YMQ','Manage discount vouchers','vouchers.manage','Commercial','Manage discount vouchers','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJJMDTTGAPT3ZAWQFFS','Apply discount vouchers','vouchers.apply','Commercial','Apply discount vouchers','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJKPJDD4W9XJMGGF31H','View customer receivables','customers.receivables.view','Commercial','View customer receivables','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJMYM2Y0EGMXNR0HSKP','Record direct purchases','purchases.record','Commercial','Record direct purchases','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJND2C61V2X9EKSKHZ7','Manage API tokens','api.tokens.manage','Commercial','Manage API tokens','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJPD3EBZ03T0H09094R','Convert quotes','quotes.convert','Commercial','Convert quotes','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJQHFEBJV6TJP473ZF1','View backups','backups.view','Backup and Recovery','View backups','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJR2PSRHKW57MVQ66XX','Create backups','backups.create','Backup and Recovery','Create backups','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJSRDBB069K3YW89Z0S','Verify backups','backups.verify','Backup and Recovery','Verify backups','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJTR48S0Q90Q5R6K6V5','Manage document branding','documents.branding.manage','Accounting Operations','Manage document branding','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJVFAQA2SEKCQVY71E7','View standalone receipts','receipts.view','Accounting Operations','View standalone receipts','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJWQ2EK6GTM2K8V6VXH','Create standalone receipts','receipts.create','Accounting Operations','Create standalone receipts','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJX117W4EN83PWFQSE0','View purchase returns','purchase_returns.view','Accounting Operations','View purchase returns','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJY88SZP7XN3F1D443X','Create purchase returns','purchase_returns.create','Accounting Operations','Create purchase returns','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJZBZQNA59XXQP1FPZV','View fixed assets','assets.view','Accounting Operations','View fixed assets','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK0W1QH5CVGNKWCV9R7','Manage fixed assets','assets.manage','Accounting Operations','Manage fixed assets','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK14XRHW2X6DGR40M8R','Download operation documents','operation_documents.download','Accounting Operations','Download operation documents','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK2VHBVC6HD3K1R2XEC','View chart of accounts','accounting.accounts.view','Accounting Operations','View chart of accounts','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK3DNVMKHAEMYWM5KX8','Manage chart of accounts','accounting.accounts.manage','Accounting Operations','Manage chart of accounts','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK47AKRG7DE05T5NF7D','View journals','accounting.journals.view','Accounting Operations','View journals','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK55VTGJSX7MZCQ8FR1','Create manual journals','accounting.journals.create','Accounting Operations','Create manual journals','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK63BZYZ9J8KVCSY48V','Reverse journals','accounting.journals.reverse','Accounting Operations','Reverse journals','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK7GJW53RB58AFA5G14','Manage accounting periods','accounting.periods.manage','Accounting Operations','Manage accounting periods','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK8B125VH984R32NMC3','View financial reports','accounting.reports.view','Accounting Operations','View financial reports','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK92A8SF7FMQ08GXCWE','Synchronize operational accounting','accounting.sync','Accounting Operations','Synchronize operational accounting','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHKAYS3E6VG69CKM8BKA','Post fixed-asset depreciation','accounting.depreciation.post','Accounting Operations','Post fixed-asset depreciation','2026-07-16 12:00:00','2026-07-16 12:00:00');
INSERT INTO `permission_role` (`permission_id`,`role_id`,`created_at`,`updated_at`) VALUES
('01KXNCRHG1MFZ5GR2B5RZZ1ZKF','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHG29ERR8BEG5812N1GY','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHG3D82HAMA0HHF3V9J3','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHG4C4KVJPSJGWGP6V67','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHG50CXF2C2P2NCMNRJJ','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHG6MC1E6RC89TNTTY64','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHG791NSJVSA54GRF5YR','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHG8DTJC4Z9W3KQY3AGE','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHG9NTM5SX82P67FPA3K','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGAR0Z27BFJMG7Q3T17','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGBCW69HJJHNXD67WF6','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGCJ06PYWYRNPTSMH6Y','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGDDP96A3848CACMH43','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGE2ZV7WMZ5EZNJM84X','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGF17GJZW0K4T88GGDR','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGGDQJDMHRNZPF7NZ1X','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGHW9WB9HFR8NVK7FZK','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGJZ58CQB990A28B223','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGKECMTF6C8KPW37Q0F','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGMXN2C0RCAXDX57TH5','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGNTH92Y91QKSW0SA29','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGPF6XGFJ4YFEBMXCKJ','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGQ0HFJ16BWEE2N4CP2','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGRW4PGG8SDCC6D7EYF','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGS5MT0M5V9RRNQZ8XD','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGTV3H1WBG301B3033Z','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGVPG4H8716FNW3TKBB','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGW33NY1KV6FRXHMXQ9','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGXGNGHAK7T5VPGBQEC','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGYV3J7GJ0CXVZVJ53W','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHGZPM22YCXAYDP91RQC','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH011K83W7KFGV4QYEV','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH1J5ZHP2QN9V5TGF1P','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH2041M5NE6TJEN6MKJ','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH3AEBT7TP6YXBWSGNX','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH48K5BD1252FQHR7M1','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH5V0VGK14NS959KN5R','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH6GAXZDJJAAX1NXE98','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH7SF0YVQ34G6YSCCEJ','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH842ZYSP8CPSKE83S1','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHH9Z0PRX88MZD0A857K','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHARYBW390H1KR1MFYS','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHBJ09N711C0EGWKMKN','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHC7R0Y9W4HCWJ0JJG5','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHDQ4SB81DTDC9ZGD13','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHET1GBRVJMZYVN0QJW','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHFSGTXH80E4VWK3GAZ','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHGA7F99ZE27HZ2VD68','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHHC09WZGBR9PX0GGFP','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHJVBQW4DDWPMJNH0QD','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHKYWNCN9JVSMRCPHZ3','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHM6ANDVERDE288Y2JQ','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHNPTF1J0FKE5MQY3SP','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHP0581FXJVQ07RHQ6P','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHQJYPS1JJ9ETBEG3DN','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHRMAG76MM2XC2W0YJD','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHSPVW0F460VNQQK8JV','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHTSFPHQQ2R0A2Y2ZQM','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHVJYK2870YT6T6SG46','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHW4BDH8992MKYZXXS7','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHXE5CTVEWT5G73S5DD','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHYF6ZCE16QY45B39W6','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHHZ12HSM0NJCA3QQ7DE','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ0BMVCR9GH69Y7MBZA','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ1GCW8KCANP0J84EHZ','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ2YB2P7QQRQBFJQR4S','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ3R1H1P86BTTQ4BW7N','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ4J0VK2CFQWVVH8C1M','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ5RDP84MHBZDPSKA1C','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ608SK2J1Z4FD3PNW1','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ7J2HT3STPY6ZZVSGX','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ8D5H03243APN9BCB3','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJ96CAKVVFM6KAW75HS','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJA8662JXKNG9RKG5ZE','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJB883KZE818F7E756A','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJC6Z5WQRZBY1E0JJBS','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJDYCB4QZYRRRTNX4R0','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJEMAAZZQNVY15EVKAC','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJF1AD40GXMSM3JR9X4','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJGJ8WN7F1GFQ8382FW','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJH2GK6GQ11NX171YMQ','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJJMDTTGAPT3ZAWQFFS','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJKPJDD4W9XJMGGF31H','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJMYM2Y0EGMXNR0HSKP','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJND2C61V2X9EKSKHZ7','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJPD3EBZ03T0H09094R','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJQHFEBJV6TJP473ZF1','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJR2PSRHKW57MVQ66XX','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJSRDBB069K3YW89Z0S','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJTR48S0Q90Q5R6K6V5','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJVFAQA2SEKCQVY71E7','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJWQ2EK6GTM2K8V6VXH','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJX117W4EN83PWFQSE0','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJY88SZP7XN3F1D443X','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHJZBZQNA59XXQP1FPZV','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK0W1QH5CVGNKWCV9R7','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK14XRHW2X6DGR40M8R','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK2VHBVC6HD3K1R2XEC','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK3DNVMKHAEMYWM5KX8','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK47AKRG7DE05T5NF7D','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK55VTGJSX7MZCQ8FR1','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK63BZYZ9J8KVCSY48V','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK7GJW53RB58AFA5G14','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK8B125VH984R32NMC3','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHK92A8SF7FMQ08GXCWE','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRHKAYS3E6VG69CKM8BKA','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00');
INSERT INTO `account_role` (`account_id`,`role_id`,`created_at`,`updated_at`) VALUES ('01KXNCRHG0KBS12CMV5Z42WQQY','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-16 12:00:00','2026-07-16 12:00:00');
INSERT INTO `account_branch` (`account_id`,`branch_id`,`created_at`,`updated_at`) VALUES ('01KXNCRHG0KBS12CMV5Z42WQQY','01KXNCRHG0YE67CK4AM05PAY7M','2026-07-16 12:00:00','2026-07-16 12:00:00');

-- Permissions added by the current PermissionCatalog and Sprint4Permissions seeders.
INSERT INTO `permissions` (`id`,`name`,`slug`,`group`,`description`,`created_at`,`updated_at`) VALUES
('01KZV105P00000000000000001','Bulk-adjust branch product prices','products.prices.adjust','catalog','Bulk-adjust branch product prices','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000002','Manage zero-stock sale policy','products.zero-stock.manage','catalog','Manage zero-stock sale policy','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000003','Search sale catalogue with branch availability','catalog.sale-search','catalog','Search sale catalogue with branch availability','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000004','Search inventory catalogue','catalog.inventory-search','catalog','Search inventory catalogue','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000005','Search procurement catalogue','catalog.procurement-search','catalog','Search procurement catalogue','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000006','View customers linked to own sales','customers.view.assigned','catalog','View customers linked to own sales','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000007','Update customers linked to own sales','customers.update.assigned','catalog','Update customers linked to own sales','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000008','View sales staff dashboard','dashboard.staff.view','catalog','View sales staff dashboard','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000009','View inventory dashboard','dashboard.inventory.view','catalog','View inventory dashboard','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000A','View procurement dashboard','dashboard.procurement.view','catalog','View procurement dashboard','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000B','View accounting dashboard','dashboard.accounting.view','catalog','View accounting dashboard','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000C','View audit dashboard','dashboard.audit.view','catalog','View audit dashboard','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000D','View Lisa AI business insights','insights.view','Accounting Operations','View Lisa AI business insights','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000E','Generate Lisa AI business insights','insights.generate','Accounting Operations','Generate Lisa AI business insights','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000F','Dismiss Lisa AI business insights','insights.dismiss','Accounting Operations','Dismiss Lisa AI business insights','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000G','Customers Deprecate','customers.deprecate','crm','Customers Deprecate','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000H','Customers Restore','customers.restore','crm','Customers Restore','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000J','Purchases View','purchases.view','procurement','Purchases View','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000K','Purchases Create','purchases.create','procurement','Purchases Create','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000M','Activity Export','activity.export','audit','Activity Export','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000N','Activity View All-Branches','activity.view.all-branches','audit','Activity View All-Branches','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000P','Insights View','lisa.insights.view','lisa','Insights View','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000Q','Lisa Chat','lisa.chat','lisa','Lisa Chat','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000R','Lisa Conversations Search','lisa.conversations.search','lisa','Lisa Conversations Search','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000S','Lisa Audit View','lisa.audit.view','lisa','Lisa Audit View','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000T','Lisa Generate','lisa.generate','lisa','Lisa Generate','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000V','Exports Sales','exports.sales','exports','Exports Sales','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000W','Exports Inventory','exports.inventory','exports','Exports Inventory','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000X','Exports Procurement','exports.procurement','exports','Exports Procurement','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000Y','Exports Reports','exports.reports','exports','Exports Reports','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000Z','Records Deprecate','records.deprecate','records','Records Deprecate','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000010','Records Restore','records.restore','records','Records Restore','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000011','Records Archive View','records.archive.view','records','Records Archive View','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000012','Reports Daily-Digest View','reports.daily-digest.view','reports','Reports Daily-Digest View','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000013','Reports Daily-Digest Generate','reports.daily-digest.generate','reports','Reports Daily-Digest Generate','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000014','Reports Daily-Digest Email','reports.daily-digest.email','reports','Reports Daily-Digest Email','2026-07-18 12:00:00','2026-07-18 12:00:00');
INSERT IGNORE INTO `permission_role` (`permission_id`,`role_id`,`created_at`,`updated_at`) VALUES
('01KZV105P00000000000000001','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000002','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000003','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000004','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000005','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000006','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000007','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000008','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000009','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000A','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000B','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000C','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000D','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000E','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000F','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000G','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000H','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000J','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000K','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000M','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000N','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000P','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000Q','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000R','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000S','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000T','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000V','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000W','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000X','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000Y','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P0000000000000000Z','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000010','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000011','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000012','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000013','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00'),
('01KZV105P00000000000000014','01KXNCRHG09CB8V26WGWN7EMY2','2026-07-18 12:00:00','2026-07-18 12:00:00');

INSERT INTO `payment_methods` (`id`,`name`,`description`,`is_system_default`,`is_default_for_pos`,`is_active`,`created_by_account_id`,`created_at`,`updated_at`) VALUES
('01KXNCRJF8VAB0ZGKDB9CYV7ET','Cash','Cash',1,1,1,'01KXNCRHG0KBS12CMV5Z42WQQY','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRJF90J40FW3A70WFE34C','Bank Transfer','Bank Transfer',1,0,1,'01KXNCRHG0KBS12CMV5Z42WQQY','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRJFA01GTTQNN7AFNFCA7','Card / POS Terminal','Card / POS Terminal',1,0,1,'01KXNCRHG0KBS12CMV5Z42WQQY','2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRJFB8P6KX36JDMHRYV6T','Customer Credit','Customer Credit',1,0,1,'01KXNCRHG0KBS12CMV5Z42WQQY','2026-07-16 12:00:00','2026-07-16 12:00:00');
INSERT INTO `document_brandings` (`id`,`business_name`,`logo_path`,`address`,`phone`,`email`,`receipt_footer`,`document_terms`,`updated_by_account_id`,`created_at`,`updated_at`) VALUES ('01KXNCRHG0KW5RP17Y1T0BM13C','Express Cloud',NULL,'Update after installation',NULL,NULL,NULL,NULL,'01KXNCRHG0KBS12CMV5Z42WQQY','2026-07-16 12:00:00','2026-07-16 12:00:00');
INSERT INTO `accounting_periods` (`id`,`name`,`starts_on`,`ends_on`,`status`,`closed_by_account_id`,`closed_at`,`locked_by_account_id`,`locked_at`,`created_at`,`updated_at`) VALUES ('01KXNCRHG0A56B2FV06HJFJY29','2026 Financial Year','2026-01-01','2026-12-31','open',NULL,NULL,NULL,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00');
INSERT INTO `ledger_accounts` (`id`,`code`,`name`,`type`,`parent_id`,`is_control_account`,`is_system`,`is_active`,`allow_manual_posting`,`description`,`created_at`,`updated_at`) VALUES
('01KXNCRKEG4D53FM4HHW8XS5B1','1000','Cash on Hand','asset',NULL,1,1,1,0,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRKEH2YQXMNY2ZREKFFGX','1010','Bank Accounts','asset',NULL,1,1,1,0,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRKEJ7KG630NMGGD9TWHP','1020','Card and POS Clearing','asset',NULL,1,1,1,0,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRKEK0AMXTBA9NN6CSF4T','1100','Accounts Receivable','asset',NULL,1,1,1,0,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRKEM5W1GBZEXSNM837ZX','1200','Inventory Asset','asset',NULL,1,1,1,0,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRKEND2FD8R0A986FC3CJ','1300','Fixed Assets','asset',NULL,1,1,1,0,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRKEPQB27Y5K434PHXA3G','1390','Accumulated Depreciation','asset',NULL,1,1,1,0,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRKEQNEKTNBSX9SNPDB3X','2000','Accounts Payable','liability',NULL,1,1,1,0,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRKER92EQ5RG0YSWZRD8T','2100','Output Tax Payable','liability',NULL,1,1,1,0,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRKESQDQMG138D3VNANXG','2200','Customer Deposits','liability',NULL,1,1,1,0,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRKETGX4HKD7S7W4WKPAQ','2300','Fixed Asset Clearing','liability',NULL,1,1,1,0,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRKEVF0H5RGYPXC2P3K02','3000','Owner Equity','equity',NULL,1,1,1,0,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRKEWPTAB32YYB0F18CHT','4000','Sales Revenue','revenue',NULL,1,1,1,0,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRKEXVT7DGRFXJKQ10F7C','4010','Sales Returns and Allowances','revenue',NULL,1,1,1,0,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRKEYX8BYT2B9W2V07FJN','5000','Cost of Goods Sold','expense',NULL,1,1,1,0,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRKEZWBP9B1KWDMW9JYQQ','5010','Purchase Returns','expense',NULL,1,1,1,0,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRKF0M6S1JNDQJZCCTWJN','6000','Depreciation Expense','expense',NULL,1,1,1,0,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRKF147CD69QK2CTFPFW9','6100','General Operating Expense','expense',NULL,0,1,1,1,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00'),
('01KXNCRKF2PCXYJ127MAJSC452','9990','Opening Balance Clearing','equity',NULL,1,1,1,0,NULL,'2026-07-16 12:00:00','2026-07-16 12:00:00');

COMMIT;
SET FOREIGN_KEY_CHECKS=1;

-- First login:
-- Access key: EC19-7KQW
-- Administrator name: Administrator Account
-- IMPORTANT: use the exact APP/DATA/BLIND keys in the companion production environment file.