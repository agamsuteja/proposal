# Pembaruan keamanan dan workflow — 28 Oktober 2026

## Versi dan batas klaim

Aplikasi tetap **Laravel 11.46.1**. Tidak ada perpindahan ke Laravel 12. Dependensi lain diperbarui ke versi yang kompatibel dengan PHP 8.2; Symfony Console dibatasi ke seri 7.3 agar sesuai integrasi Console Laravel 11 yang digunakan. `composer.lock` disertakan dan npm menggunakan lockfile untuk build yang dapat diulang. Hosting memakai hasil build siap pakai, tanpa npm.

Temuan audit pada endpoint aplikasi telah diperbaiki dan diuji ulang. Ini bukan sertifikasi bebas semua celah. Metadata advisory Packagist masih menandai **lima advisory pada Laravel 11**. Tidak ada ignore advisory global atau penonaktifan pemeriksaan TLS/integritas paket. Karena Laravel 11 dipertahankan sesuai permintaan pengguna, risiko framework yang belum mendapat patch tetap harus dipantau.

Mitigasi exposure yang diterapkan/ditinjau:

- `APP_DEBUG=false` diwajibkan oleh pemeriksaan deploy produksi untuk mencegah paparan debug/XSS debug page.
- Aplikasi menggunakan session guard, bukan token-based API guard yang disebut advisory. Jangan memperkenalkan guard API legacy tanpa evaluasi terpisah.
- Relasi Eloquent pada endpoint baca ditetapkan oleh kode, tidak diambil dari parameter include yang dapat memilih method relasi.
- Penyajian local disk melalui temporary signed URL dinonaktifkan. Dokumen disajikan melalui route login dengan pemeriksaan lingkup proposal.
- Semua email masukan divalidasi ketat untuk menolak karakter kontrol/CRLF, dengan pembaruan Symfony Mailer/Mime.

Advisory upstream yang ditinjau: GHSA-jh5r-qr3c-85q8, GHSA-f9x3-7vq9-cph6, GHSA-5wcm-3v2w-8362, GHSA-crmm-hgp2-wgrp, GHSA-5vg9-5847-vvmq. Perbaikan aplikasi tidak menghapus penandaan advisory pada versi framework tersebut. Tidak ada backport vendor yang diklaim telah dilakukan.

## Perubahan fungsi

### Akun dan sesi

- Registrasi publik hanya membuat karyawan nonaktif. Departemen menentukan grup; masukan grup/role istimewa tidak dipercaya.
- OTP disimpan sebagai hash dalam `auth_challenges`, memiliki tujuan terpisah untuk registrasi/reset, kedaluwarsa dan hanya dapat dipakai sekali.
- Admin hanya dapat menyetujui akun registrasi yang telah memverifikasi OTP. Akun yang dibuat langsung oleh admin dianggap diverifikasi melalui admin.
- Ada kirim ulang kode registrasi; kegagalan pengiriman tidak dinyatakan sebagai pesan berhasil terkirim.
- Login, pengiriman OTP, dan verifikasi OTP dibatasi berdasarkan IP dan identitas akun. Password tidak dimasukkan ke old input.
- Sesi diperiksa ulang untuk status aktif dan fingerprint password. Sesi lama diminta login ulang; sesi yang passwordnya berubah ditolak.
- Akun dinonaktifkan, bukan dihapus bersama riwayatnya. Admin terakhir tidak dapat dihapus/dinonaktifkan dan admin tidak dapat menurunkan dirinya sendiri.

### Hak akses

- Admin: pengguna, grup, departemen, aktivasi dan pengalihan approver; membaca audit/notifikasi.
- Keuangan: pencairan, verifikasi refund, rekening, alokasi, periode, transfer/koreksi budget; membaca audit/notifikasi.
- Admin dapat mengelola rekening untuk administrasi, tetapi pencairan/refund/transfer memerlukan role keuangan.
- Karyawan/kepala departemen hanya mengakses proposal dan laporan departemennya. Kepala grup hanya dalam grupnya. Gembala/admin/keuangan dapat membaca sesuai fungsi masing-masing.
- Perubahan proposal hanya oleh pemilik pada status diajukan yang belum diproses atau revisi. Pencairan menutup pengubahan/penghapusan proposal.

### Proposal dan keuangan

- Tiga approver aktif wajib tersedia: kepala departemen, kepala grup, gembala.
- Endpoint approval memeriksa pengguna yang ditetapkan, role, scope, urutan, status dan replay dalam transaksi terkunci.
- Nominal setelah persetujuan tidak dapat diganti langsung. Admin dapat mengembalikan proposal belum dicairkan untuk revisi dengan alasan, termasuk memperbaiki alur legacy yang tidak lengkap.
- Revisi membangun ulang seluruh tahap approval dan menyimpan penghapusan item lama melalui event audit.
- Persetujuan akhir mencadangkan budget. Saldo tersedia untuk transfer = alokasi − pencairan neto − cadangan proposal disetujui. Saat pencairan, cadangan milik proposal itu sendiri boleh digunakan.
- Nota + pengembalian pending/diterima tidak boleh melampaui pencairan. Penerimaan refund memeriksa ulang konsistensi dan tidak bisa dijalankan dua kali.
- Seluruh perubahan dana terkait diproses dengan lock periode/departemen/proposal/rekening dan isolation level READ COMMITTED. Tiga uji MariaDB menjalankan request paralel, termasuk retry dengan kunci sama.
- Form pencairan, refund, dan nota memakai kunci operasi. Retry identik tidak membukukan ulang; kunci yang sama dengan data berbeda ditolak.
- Metode pencairan kini disimpan. Perubahan saldo rekening memerlukan alasan; rekening/grup/departemen dengan riwayat terkait tidak dihapus melalui cascade.
- Laporan HTML/Excel/PDF memakai satu query role dan filter. Ekspor melindungi nilai teks dari formula spreadsheet yang tidak dipercaya.

### Budget

- Ada revisi pengajuan ditolak, pembatalan sebelum transfer, pengalihan approver oleh admin, dan pembalikan transfer oleh keuangan dengan alasan serta histori.
- Pembalikan ditolak bila dana penerima sudah digunakan/dicadangkan. Transfer awal dan histori tidak dihapus.
- Periode dapat ditutup/dibuka kembali dengan alasan. Perubahan periode tertutup ditolak; penutupan ditolak bila masih ada pekerjaan yang belum selesai.
- Alokasi tahun berikutnya dapat disalin tanpa menimpa alokasi yang sudah ada.
- CSV budget/realisasi menampilkan alokasi non-transfer, transfer masuk/keluar, alokasi efektif, terpakai, dicadangkan, tersedia.
- Keuangan tetap harus menetapkan alokasi awal yang sah. Sistem tidak mengarang nominal dan tidak memperbaiki data lama yang tidak konsisten secara otomatis.

### Audit, notifikasi, dokumen

- Log model tidak dapat diedit/dihapus melalui model. Penghapusan item dilakukan per model agar snapshot terjaga.
- Log akses mempunyai fallback log privat bila database audit sedang gagal. Transaksi keuangan tetap membutuhkan pencatatan audit yang berhasil di database.
- Ekspor audit mengikuti filter; arsip privat memakai checksum. Purge hanya dengan opsi CLI eksplisit setelah arsip berhasil.
- Pesan WhatsApp disimpan terenkripsi dalam outbox. Status gagal/pending/diterima layanan dibedakan dan dapat dicoba ulang tanpa mengulang transaksi.
- Notifikasi bisnis dicoba setelah transaksi commit dan respons dikirim; OTP dikirim langsung agar hasilnya bisa diperiksa. Outbox tetap dapat diproses cron bila proses web terputus.
- Ada reminder harian tambahan budget dan retry bertahap, maksimal lima percobaan. Diterima layanan bukan bukti dibaca oleh penerima.
- Dokumen baru disimpan privat. Dokumen lama dibaca melalui route yang sama; script cPanel menambahkan aturan Apache untuk memblokir akses langsung pada empat direktori upload lama.
- Akses/mutasi SQL langsung melalui phpMyAdmin berada di luar audit model aplikasi. Jangan mengubah transaksi langsung dengan SQL; perubahan schema manual tetap dilakukan melalui phpMyAdmin sesuai pilihan pengguna.

## SQL manual — wajib sebelum pembaruan kode di hosting

1. Cadangkan database dan file aplikasi. Pastikan ada admin aktif yang sah. Jika data lama sudah mempunyai inkonsistensi, rekonsiliasi berdasarkan dokumen asli; jangan menebak jumlah uang atau membuat admin fiktif.
2. Pilih database aplikasi (`myey1771_escpro`) di phpMyAdmin.
3. Impor `database/sql/2026_10_28_security_workflows.sql`. SQL budget 2026_10_27 harus sudah diimpor sebelumnya.
4. SQL menambah kolom verifikasi/metode, tujuh tabel workflow/keamanan, dan dua tabel cache bila belum ada. Data pengguna/proposal/keuangan tidak dihapus. Seluruh sesi login lama dicabut untuk membersihkan old input password; pengguna login kembali.
5. `CREATE TABLE IF NOT EXISTS` tidak memperbaiki tabel dengan struktur berbeda. DDL tidak atomik; bila ada error, simpan error dan jangan menutupi kegagalan dengan menghapus data.

## Deployment cPanel

Document root subdomain wajib menunjuk ke `/home/myey1771/public_html/proposal/public`, bukan root repositori. Domain utama myesc.id tidak perlu berubah versi PHP.

Pastikan nilai non-rahasia berikut pada `.env` hosting sesuai; pertahankan APP_KEY/kredensial yang sudah ada, jangan mengirimkannya ke chat:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://proposal.myesc.id
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=proposal.myesc.id
CACHE_STORE=file
```

Setelah SQL diimpor dan backup dibuat, gunakan Terminal:

```sh
cd /home/myey1771/public_html/proposal
/opt/cpanel/ea-php82/root/usr/bin/php artisan down --retry=60
git pull --ff-only origin master
/opt/cpanel/ea-php82/root/usr/bin/php artisan config:clear
bash scripts/deploy-cpanel.sh --check
```

Jika pemeriksaan berhasil, jalankan **Deploy HEAD Commit** di cPanel. Script memasang dependensi produksi dan hasil build, memeriksa schema/lingkungan, menutup akses upload publik lama pada Apache, serta memperbarui cache Blade. Tidak ada migrasi otomatis.

Setelah deploy berhasil:

```sh
/opt/cpanel/ea-php82/root/usr/bin/php artisan security:check
/opt/cpanel/ea-php82/root/usr/bin/php artisan up
```

Buka login, masuk sebagai admin/keuangan, cek role pengguna, alokasi/cadangan, approval, laporan dan satu transaksi terkontrol. Jika deploy gagal, jangan menjalankan `up` sebelum penyebabnya diketahui. Cek log privat, bukan menampilkan `.env` atau output debug ke publik.

`security:check` hanya melaporkan inkonsistensi data lama; tidak menyunting uang/role. Peringatan admin aktif atau nota/refund berlebih harus ditangani sebelum penggunaan finansial normal. Pengaturan document root, HTTPS, pemeliharaan PHP hosting, backup/restore dan data lama belum diverifikasi langsung dari cloud.

## Cron notifikasi/reminder

Tambahkan satu Cron Job cPanel, setiap menit:

```cron
* * * * * /opt/cpanel/ea-php82/root/usr/bin/php /home/myey1771/public_html/proposal/artisan schedule:run >> /home/myey1771/proposal-scheduler.log 2>&1
```

Reminder dijalankan pukul 09:00 menurut APP_TIMEZONE. Sesuaikan APP_TIMEZONE pada environment. Token Fonnte yang valid tetap diperlukan. Nilainya tidak disertakan dalam Git. Pengiriman nyata belum diuji; tes memakai layanan palsu agar tidak mengirim ke pengguna asli.

Arsip audit opsional, dari CLI:

```sh
php artisan activity:archive --days=180
# Tambahkan --purge hanya setelah kebijakan retensi disepakati; arsip privat tetap disimpan.
```

## Validasi yang dapat diulang

```sh
php artisan test
# MariaDB khusus tes; database dengan prefix proposal_budget_test DIKOSONGKAN per tes.
LEGACY_TEST_SCHEMA=1 BUDGET_TEST_MYSQL_DATABASE=proposal_budget_test php vendor/bin/phpunit --configuration phpunit.xml
npm ci
npm run build
```

Hasil validasi PHP 8.2: 41 tes dan 330 assertions lulus pada MariaDB dengan schema legacy, termasuk tiga tes transaksi paralel. Instalasi produksi tanpa dependensi development juga diperiksa. Tampilan budget, pengajuan, audit, dan notifikasi diperiksa pada lebar layar 390 piksel.

Tes menggunakan akun sintetis dan database terpisah. Tiga tes concurrency hanya dijalankan pada MariaDB, sehingga ditandai skipped pada suite SQLite. SQL manual diuji dua kali pada schema legacy kosong. Metadata advisory diperiksa untuk paket lockfile; lima advisory Laravel 11 tetap tercantum. npm audit pada lockfile melaporkan nol advisory saat pemeriksaan, bukan jaminan tidak ada kerentanan baru di masa depan.

## Pembaruan setelah audit: pendanaan rekening–grup

Lihat [GROUP_FUNDING.md](GROUP_FUNDING.md). Sebelum menarik kode terbaru, impor juga `database/sql/2026_10_28_group_funding.sql`. Pendanaan grup sekarang membatasi total alokasi, rekening pencairan/refund, dan pengalokasian dana yang sama lintas grup/tahun. Penutupan periode memeriksa nota/refund yang belum selesai. Angka validasi dan perilaku yang disebut di atas adalah snapshot commit `390ccf1`; rincian perubahan ini ada pada panduan baru.

## Perbaikan konfirmasi keuangan kepala grup (A06)

Tombol WhatsApp pra-persetujuan sekarang dapat mencatat permintaan setelah kepala departemen menyetujui dan saat kepala grup yang ditetapkan masih pending. Role, scope, kelengkapan alur dan urutan tetap diperiksa; permintaan tidak mengubah approval atau mencairkan dana. Pengingat setelah final approval menggunakan pesan yang berbeda. Pengulangan permintaan pada tahap yang sama tidak membuat pesan duplikat. UI membedakan permintaan tercatat dari pesan terkirim/konfirmasi keuangan, menampilkan alasan penolakan, dan memulihkan tombol untuk retry. Admin/keuangan memantau hasil layanan pada Status Notifikasi. Tidak ada SQL tambahan untuk perbaikan ini.

## Perbaikan isi dan penerima notifikasi proposal

Pengajuan baru mengirim pesan proposal baru ke approver tahap pertama. Setelah disetujui kepala departemen, kepala grup mendapat pesan bahwa tahap departemen sudah selesai; setelah kepala grup menyetujui, gembala mendapat pesan tahap grup sudah selesai. Keduanya tidak lagi memakai template proposal revisi. Penolakan oleh tahap mana pun mengirim permintaan revisi beserta catatan ke pemilik proposal, bukan instruksi memberi persetujuan. Pengajuan kembali setelah status revisi memakai pesan revisi diajukan kembali ke approver tahap pertama dan tetap mengulang semua approval. Permintaan revisi admin menggunakan template pemohon yang sama. Tidak memerlukan SQL baru; pesan WhatsApp yang sudah terkirim tidak diubah.

## Nama acara dan tampilan revisi

Sebelum pull/deploy pembaruan nama acara, impor `database/sql/2026_10_28_event_name.sql` melalui phpMyAdmin setelah backup. SQL hanya menambah kolom nullable `proposal.judul_acara`; tidak mengubah data lama dan tidak perlu migrasi otomatis. Nama acara sekarang divalidasi/disimpan terpisah dari judul proposal serta dipertahankan saat revisi. Nama terpisah yang dahulu tidak pernah disimpan tidak dapat dipulihkan otomatis: untuk data lama, judul proposal ditampilkan sebagai isian awal, lalu pemilik dapat membetulkannya saat revisi. Kolom total anggaran acara pada pembuatan/edit dibatasi maksimal 320 piksel, responsif, dan rata kiri.
