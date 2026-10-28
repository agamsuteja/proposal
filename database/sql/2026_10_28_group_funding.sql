-- Pendanaan grup dari rekening, plafon, dan pembagian budget departemen.
-- Impor manual melalui phpMyAdmin setelah SQL budget 2026_10_27 dan security_workflows 2026_10_28.
-- Backup database sebelum impor. DDL tidak atomik.
-- Tidak mengubah saldo, alokasi, proposal, atau transaksi lama. Tidak menebak rekening grup dari nama.
-- Setelah deploy: keuangan memilih sumber/plafon setiap grup/tahun di Budget Departemen.
-- Rekonsiliasi data lama terlebih dahulu jika dana/riwayat tidak sesuai.

-- Mendukung sen hasil pembagian rata; 20 digit integer mempertahankan rentang BIGINT lama.
ALTER TABLE `rekening` MODIFY COLUMN `saldo` decimal(22,2) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `budget_funding_mutex` (
  `id` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO `budget_funding_mutex` (`id`) VALUES (1);

CREATE TABLE IF NOT EXISTS `group_fundings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `grup_id` bigint(20) unsigned NOT NULL,
  `tahun` smallint(5) unsigned NOT NULL,
  `rekening_id` bigint(20) unsigned NOT NULL,
  `plafon` decimal(18,2) NOT NULL,
  `alasan` text NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_fundings_grup_id_tahun_unique` (`grup_id`,`tahun`),
  KEY `group_fundings_rekening_id_foreign` (`rekening_id`),
  CONSTRAINT `group_fundings_grup_id_foreign` FOREIGN KEY (`grup_id`) REFERENCES `grup` (`id`),
  CONSTRAINT `group_fundings_rekening_id_foreign` FOREIGN KEY (`rekening_id`) REFERENCES `rekening` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
