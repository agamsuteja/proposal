-- Empty legacy structure only. NO user data. Used exclusively on isolated test databases.
SET FOREIGN_KEY_CHECKS=0;
CREATE TABLE `activity_logs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `anggaran_tahunan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tahun` int(11) NOT NULL,
  `rekening_id` bigint(20) unsigned NOT NULL,
  `total_anggaran` decimal(18,2) DEFAULT 0.00,
  `total_terpakai` decimal(18,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_anggaran_tahun` (`tahun`,`rekening_id`),
  KEY `rekening_id` (`rekening_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `approval_proposal` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proposal_id` bigint(20) unsigned NOT NULL,
  `tahap` enum('departemen_head','group_head','gembala','keuangan') NOT NULL,
  `urutan` int(11) NOT NULL,
  `approver_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('pending','disetujui','ditolak','revisi') DEFAULT 'pending',
  `catatan` text DEFAULT NULL,
  `tanggal_approve` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_proposal_stage` (`proposal_id`,`tahap`),
  KEY `approver_id` (`approver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `budget_approvals` (
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

CREATE TABLE `budget_departemen` (
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

CREATE TABLE `budget_requests` (
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

CREATE TABLE `budget_transfers` (
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

CREATE TABLE `departemen` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `grup_id` bigint(20) unsigned NOT NULL,
  `nama_departemen` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `grup_id` (`grup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `grup` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nama_grup` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `log_proposal` (
  `id` bigint(20) unsigned NOT NULL,
  `proposal_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `aksi` varchar(100) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `proposal_id` (`proposal_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `nota_penggunaan_proposal` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proposal_id` bigint(20) unsigned NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `file_nota` varchar(255) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_nota_penggunaan_proposal` (`proposal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `otp` varchar(10) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pencairan_dana` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proposal_id` bigint(20) unsigned NOT NULL,
  `rekening_id` bigint(20) unsigned NOT NULL,
  `jumlah_dana` decimal(15,2) NOT NULL,
  `catatan` text DEFAULT NULL,
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `tanggal_pencairan` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `proposal_id` (`proposal_id`),
  KEY `rekening_id` (`rekening_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pengembalian_dana` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proposal_id` bigint(20) unsigned NOT NULL,
  `rekening_id` bigint(20) unsigned DEFAULT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `metode` enum('transfer','cash') NOT NULL DEFAULT 'transfer',
  `bukti` varchar(255) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `status` enum('pending','diterima','ditolak') NOT NULL DEFAULT 'pending',
  `tanggal_pengembalian` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pengembalian_proposal` (`proposal_id`),
  KEY `idx_pengembalian_status` (`status`),
  KEY `idx_pengembalian_rekening` (`rekening_id`),
  CONSTRAINT `fk_pengembalian_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `proposal` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pengembalian_rekening` FOREIGN KEY (`rekening_id`) REFERENCES `rekening` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `proposal` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `jenis` enum('barang','acara','maintenance') DEFAULT 'barang',
  `user_id` bigint(20) unsigned NOT NULL,
  `departemen_id` bigint(20) unsigned NOT NULL,
  `judul` varchar(150) NOT NULL,
  `tujuan` text DEFAULT NULL,
  `total_anggaran` decimal(15,2) DEFAULT 0.00,
  `tanggal_pengajuan` date NOT NULL,
  `tanggal_acara` date DEFAULT NULL,
  `lokasi_acara` varchar(255) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `status` enum('diajukan','disetujui_mh','disetujui_gh','disetujui_senior_pastor','ditolak','dicairkan','revisi','dicairkan_sebagian') DEFAULT 'diajukan',
  `lampiran` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `departemen_id` (`departemen_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `proposal_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proposal_id` bigint(20) unsigned NOT NULL,
  `nama_barang` varchar(150) NOT NULL,
  `lokasi` varchar(150) DEFAULT NULL,
  `deskripsi_kerusakan` text DEFAULT NULL,
  `merk_tipe` varchar(150) DEFAULT NULL,
  `jumlah` varchar(50) DEFAULT NULL,
  `tujuan_pembelian` text DEFAULT NULL,
  `anggaran` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proposal_id` (`proposal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `rekening` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nama_rekening` varchar(100) NOT NULL,
  `nomor_rekening` varchar(50) DEFAULT NULL,
  `bank` varchar(100) DEFAULT NULL,
  `saldo` bigint(20) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transaksi_rekening` (
  `id` bigint(20) unsigned NOT NULL,
  `rekening_id` bigint(20) unsigned NOT NULL,
  `proposal_id` bigint(20) unsigned DEFAULT NULL,
  `tanggal_transaksi` date NOT NULL,
  `nominal` decimal(18,2) NOT NULL,
  `tipe` enum('debit','kredit') NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `rekening_id` (`rekening_id`),
  KEY `proposal_id` (`proposal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `departemen_id` bigint(20) unsigned DEFAULT NULL,
  `grup_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `nohp` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('karyawan','departemen_head','group_head','gembala','keuangan','admin') DEFAULT 'karyawan',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `departemen_id` (`departemen_id`),
  KEY `users_grup_id_foreign` (`grup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS=1;
