-- Fitur budget departemen, pengajuan tambahan, transfer, dan log aktivitas.
-- Pilih database aplikasi pada phpMyAdmin sebelum mengimpor file ini.
-- Cadangkan database terlebih dahulu. Tidak menghapus atau mengubah data lama.
-- Tabel grup, departemen, dan users dari database aplikasi harus sudah tersedia.
-- IF NOT EXISTS mencegah pembuatan ulang; tidak memperbaiki tabel dengan struktur berbeda.
-- DDL MySQL/MariaDB melakukan implicit commit; impor bukan transaksi atomik.

CREATE TABLE IF NOT EXISTS `budget_departemen` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `departemen_id` bigint(20) unsigned NOT NULL,
  `tahun` smallint(5) unsigned NOT NULL,
  `jumlah` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `budget_departemen_departemen_id_tahun_unique` (`departemen_id`,`tahun`),
  CONSTRAINT `budget_departemen_departemen_id_foreign` FOREIGN KEY (`departemen_id`) REFERENCES `departemen` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `budget_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `departemen_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `tahun` smallint(5) unsigned NOT NULL,
  `jumlah` decimal(18,2) NOT NULL,
  `alasan` text NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `funded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `budget_requests_departemen_id_foreign` (`departemen_id`),
  KEY `budget_requests_user_id_foreign` (`user_id`),
  CONSTRAINT `budget_requests_departemen_id_foreign` FOREIGN KEY (`departemen_id`) REFERENCES `departemen` (`id`),
  CONSTRAINT `budget_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `budget_approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `budget_request_id` bigint(20) unsigned NOT NULL,
  `approver_id` bigint(20) unsigned NOT NULL,
  `tahap` varchar(30) NOT NULL,
  `urutan` tinyint(3) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `catatan` text DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `budget_approvals_budget_request_id_urutan_unique` (`budget_request_id`,`urutan`),
  KEY `budget_approvals_approver_id_foreign` (`approver_id`),
  CONSTRAINT `budget_approvals_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`),
  CONSTRAINT `budget_approvals_budget_request_id_foreign` FOREIGN KEY (`budget_request_id`) REFERENCES `budget_requests` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `budget_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `budget_request_id` bigint(20) unsigned NOT NULL,
  `sumber_id` bigint(20) unsigned NOT NULL,
  `tujuan_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `tahun` smallint(5) unsigned NOT NULL,
  `jumlah` decimal(18,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `budget_transfers_budget_request_id_foreign` (`budget_request_id`),
  KEY `budget_transfers_sumber_id_foreign` (`sumber_id`),
  KEY `budget_transfers_tujuan_id_foreign` (`tujuan_id`),
  KEY `budget_transfers_user_id_foreign` (`user_id`),
  CONSTRAINT `budget_transfers_budget_request_id_foreign` FOREIGN KEY (`budget_request_id`) REFERENCES `budget_requests` (`id`),
  CONSTRAINT `budget_transfers_sumber_id_foreign` FOREIGN KEY (`sumber_id`) REFERENCES `departemen` (`id`),
  CONSTRAINT `budget_transfers_tujuan_id_foreign` FOREIGN KEY (`tujuan_id`) REFERENCES `departemen` (`id`),
  CONSTRAINT `budget_transfers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` varchar(255) DEFAULT NULL,
  `before` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`before`)),
  `after` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`after`)),
  `method` varchar(10) DEFAULT NULL,
  `route` varchar(255) DEFAULT NULL,
  `response_status` smallint(5) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_index` (`user_id`),
  KEY `activity_logs_action_index` (`action`),
  KEY `activity_logs_created_at_index` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
