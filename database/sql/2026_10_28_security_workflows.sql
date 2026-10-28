-- Pembaruan keamanan dan workflow, Laravel 11 / MariaDB 10.11+.
-- Pilih database aplikasi di phpMyAdmin dan cadangkan sebelum impor.
-- Jalankan setelah SQL budget 2026_10_27. Data pengguna/transaksi tidak dihapus; sesi login lama dicabut.
-- Tidak memverifikasi atau mengaktifkan akun lama secara otomatis.
-- DDL tidak atomik; jika impor gagal, simpan pesan error sebelum mengulang.

ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `registration_verified_at` TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE `pencairan_dana` ADD COLUMN IF NOT EXISTS `metode` VARCHAR(20) NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `financial_operations` (
  `id` char(36) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `purpose` varchar(30) NOT NULL,
  `fingerprint` char(64) NOT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `financial_operations_user_id_foreign` (`user_id`),
  CONSTRAINT `financial_operations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `auth_challenges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `purpose` varchar(30) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `auth_challenges_user_id_purpose_unique` (`user_id`,`purpose`),
  CONSTRAINT `auth_challenges_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notification_outbox` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `target` varchar(30) NOT NULL,
  `message` text NOT NULL,
  `subject` varchar(150) NOT NULL,
  `dedupe_key` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `attempts` smallint(5) unsigned NOT NULL DEFAULT 0,
  `next_attempt_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `last_error` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_outbox_dedupe_key_unique` (`dedupe_key`),
  KEY `notification_outbox_user_id_index` (`user_id`),
  KEY `notification_outbox_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `budget_periods` (
  `tahun` smallint(5) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `alasan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`tahun`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `budget_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `departemen_id` bigint(20) unsigned NOT NULL,
  `tahun` smallint(5) unsigned NOT NULL,
  `before_amount` decimal(18,2) NOT NULL,
  `after_amount` decimal(18,2) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `alasan` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `budget_adjustments_departemen_id_foreign` (`departemen_id`),
  KEY `budget_adjustments_user_id_foreign` (`user_id`),
  CONSTRAINT `budget_adjustments_departemen_id_foreign` FOREIGN KEY (`departemen_id`) REFERENCES `departemen` (`id`),
  CONSTRAINT `budget_adjustments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `budget_request_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `budget_request_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`snapshot`)),
  `alasan` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `budget_request_versions_budget_request_id_foreign` (`budget_request_id`),
  KEY `budget_request_versions_user_id_foreign` (`user_id`),
  CONSTRAINT `budget_request_versions_budget_request_id_foreign` FOREIGN KEY (`budget_request_id`) REFERENCES `budget_requests` (`id`),
  CONSTRAINT `budget_request_versions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `budget_reversals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `budget_request_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `alasan` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `budget_reversals_budget_request_id_unique` (`budget_request_id`),
  KEY `budget_reversals_user_id_foreign` (`user_id`),
  CONSTRAINT `budget_reversals_budget_request_id_foreign` FOREIGN KEY (`budget_request_id`) REFERENCES `budget_requests` (`id`),
  CONSTRAINT `budget_reversals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cache database Laravel, jika hosting menggunakan CACHE_STORE=database.
CREATE TABLE IF NOT EXISTS `cache` (
  `key` VARCHAR(255) NOT NULL PRIMARY KEY,
  `value` MEDIUMTEXT NOT NULL,
  `expiration` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` VARCHAR(255) NOT NULL PRIMARY KEY,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cabut seluruh sesi login aplikasi untuk membersihkan old input password lama.
-- Pengguna perlu login kembali. Data pengguna/proposal/transaksi tidak dihapus.
DELETE FROM `sessions`;
