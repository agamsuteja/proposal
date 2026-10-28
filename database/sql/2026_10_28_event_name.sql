-- Simpan nama acara terpisah dari judul proposal.
-- Backup lalu pilih database aplikasi di phpMyAdmin sebelum impor.
-- Impor sebelum pull/deploy kode yang memakai kolom ini. Tidak perlu artisan migrate.
-- Data lama tidak diubah. Nama acara kosong ditampilkan memakai judul proposal sebagai isian awal.
ALTER TABLE `proposal` ADD COLUMN IF NOT EXISTS `judul_acara` varchar(150) DEFAULT NULL;
