# Budget departemen dan tambahan budget

## Penggunaan

1. Keuangan membuka **Budget Departemen**, memilih tahun, lalu mengisi alokasi setiap departemen. Contoh IT Rp5.000.000 dan Data Manager Rp5.000.000 menghasilkan total Office Rp10.000.000. Alokasi awal tidak ditebak dari saldo rekening atau dibagi otomatis dari database lama.
2. Pengguna membuka menu khusus **Tambahan Budget** dan mengisi departemen, nominal, serta alasan. Pengajuan dapat diulang tanpa kuota. Pengguna departemen hanya dapat mengajukan untuk departemennya; kepala grup untuk departemen dalam grupnya. Admin, keuangan, dan gembala dapat melihat semua departemen.
3. Approval berurutan: kepala departemen → kepala grup → gembala. Hanya approver yang ditetapkan dan masih aktif yang dapat memutuskan pada gilirannya. Approver harus tersedia saat pengajuan dibuat. Pengajuan ditolak dapat diajukan kembali sebagai pengajuan baru.
4. Sesudah semua tahap disetujui, hanya keuangan yang dapat memilih satu atau beberapa sumber dalam grup yang sama. Jumlah sumber harus tepat sama dengan nominal yang disetujui. Transfer tidak dapat dijalankan dua kali.
5. Sumber tidak boleh sama dengan penerima, di luar grup, atau melebihi saldo tersedia. Saldo dan transfer dikunci dalam transaksi database. Total budget grup tetap sama; transfer tidak mengubah saldo rekening bank.

Nominal memakai dua angka desimal, tanpa batas bisnis tambahan selain kapasitas penyimpanan angka. Setiap pengajuan transfer mendukung hingga 100 departemen sumber. Tidak ada batas jumlah pengajuan.

## Perhitungan dan riwayat

Budget mengikuti periode tahunan. Terpakai = pencairan proposal − pengembalian berstatus `diterima`, dengan minimum nol. Periode mengikuti `tanggal_pengajuan` proposal, termasuk jika pencairan/pengembalian dilakukan pada tahun berikutnya. Persetujuan proposal belum mengurangi budget; pengecekan saldo dilakukan saat pencairan. Proposal yang sudah disetujui dapat menunggu tambahan budget jika alokasi telah habis.

Budget awal belum diisi otomatis. Keuangan perlu mengisi alokasi yang benar sebelum pencairan berikutnya. Saldo yang telah dipakai tidak boleh dihapus melalui pengurangan budget. Budget yang sudah memiliki transfer tidak dapat ditimpa melalui form alokasi awal; gunakan pengajuan tambahan. Departemen dengan budget tidak dapat dipindahkan ke grup lain, dan proposal yang sudah dicairkan tidak dapat dipindah departemen/tahun atau dihapus.

Menu **Log Aktivitas** tersedia bagi admin dan keuangan. Log mencatat akses aplikasi, hasil request termasuk penolakan, login/logout/gagal login, dan pembuatan/perubahan/penghapusan melalui model aplikasi, dengan pelaku serta waktu. Perubahan model menyimpan nilai sebelum/sesudah. Password, token, kredensial request, dan query URL tidak disimpan. Log tidak mempunyai menu edit/hapus. Log mulai saat fitur diaktifkan, bukan rekonstruksi aktivitas lama; perubahan langsung melalui SQL di luar aplikasi tidak diamati.

Notifikasi WhatsApp/email untuk pengajuan tambahan belum ditambahkan. Approval tambahan dilakukan melalui menu Tambahan Budget. Notifikasi proposal yang sudah ada tetap mengikuti integrasi sebelumnya.

## Memasang pada database legacy

Database dari dump pengguna memakai skema legacy yang berbeda dari migrasi awal Laravel. Cadangkan database sebelum pemasangan. Setelah dependensi dipasang, jalankan **hanya** migrasi baru:

```sh
php artisan migrate --path=database/migrations/2026_10_27_000001_create_department_budgets.php --force
php artisan view:clear
```

Jangan menjalankan `migrate:fresh` atau mengimpor ulang dump pada database berisi pekerjaan pengguna. Migrasi fitur menambah tabel tanpa mengubah data lama. Hasil migrasi sudah diuji pada MariaDB 11.4 dengan dump yang diberikan. Perubahan masih lokal, belum diterapkan ke produksi.

## Validasi

```sh
export PATH=/workspace/tooling/bin:$PATH
php artisan test
npm run build
```

Tes fitur memakai SQLite in-memory dan tidak memodifikasi database pengguna. Untuk pengujian ulang pada MariaDB lokal, buat database kosong khusus `proposal_budget_test` dan jalankan:

```sh
BUDGET_TEST_MYSQL_DATABASE=proposal_budget_test php artisan test --filter=DepartmentBudgetTest
```

**Database dengan nama pengujian tersebut akan dikosongkan pada setiap tes.** Jangan memakai database pengguna. Nama database dibatasi awalan `proposal_budget_test` dalam runner tes. Pengujian memalsukan HTTP keluar agar tidak mengirim notifikasi ke pengguna asli.

## Deployment cPanel untuk proposal.myesc.id

Konfigurasi `.cpanel.yml` menjalankan `scripts/deploy-cpanel.sh` di checkout hosting `/home/myey1771/public_html/proposal`. Subdomain proposal sudah dipilih menggunakan PHP 8.2; `myesc.id` tidak perlu diubah dari PHP 8.1. Pastikan document root subdomain menunjuk ke direktori `public` Laravel.

Script memakai `/opt/cpanel/ea-php82/root/usr/bin/php` untuk Composer dan Artisan, tanpa mengganti PHP default terminal. Hosting tidak memerlukan Node.js/npm: hasil build frontend disertakan di `deployment/frontend`, diperiksa dengan SHA-256, lalu disalin ke `public/build` saat deploy. Setelah mengubah frontend atau template, jalankan `npm run build` di mesin pengembangan, perbarui folder artefak beserta `SHA256SUMS`, dan commit hasilnya. Hosting tidak diperiksa langsung dari cloud dan deployment produksi belum dijalankan.

Cadangkan database sebelum deployment. Gabungkan perubahan ke `master`, kemudian pilih **Update from Remote** pada Git Version Control cPanel. Periksa dahulu melalui Terminal cPanel:

```sh
cd /home/myey1771/public_html/proposal
bash scripts/deploy-cpanel.sh --check
```

Jika lolos, jalankan **Deploy HEAD Commit**. Script memasang dependensi produksi dari lockfile, memasang hasil build frontend, memeriksa tabel hasil impor SQL manual, dan memperbarui cache Blade. `.env`, upload, dan data lama tidak disalin/ditimpa. Tidak ada `migrate:fresh`, reset Git, atau impor ulang SQL. Deployment tidak mengisi alokasi budget departemen secara otomatis.


## Pembaruan: pemasangan database manual melalui phpMyAdmin

Sesuai pilihan pengguna, deployment cPanel tidak lagi menjalankan migrasi. Pilih database aplikasi pada phpMyAdmin, cadangkan database, lalu impor `database/sql/2026_10_27_department_budgets.sql` melalui tab **Import** (atau salin seluruh SQL ke tab **SQL**). Lima tabel baru dibuat tanpa mengubah tabel/data lama. File ini setara dengan migrasi fitur dan tidak mengisi alokasi awal. Jangan menjalankan migrasi fitur setelah mengimpor SQL karena tabelnya sudah ada. Petunjuk migrasi di atas hanya berlaku untuk instalasi yang memilih jalur migrasi, bukan hosting ini.

Jika suatu tabel sudah ada, `IF NOT EXISTS` melewatinya; ini tidak menjamin struktur tabel lama cocok. SQL telah diuji dua kali pada database pengujian MariaDB dengan struktur dasar dari dump pengguna. Jika impor gagal, periksa pesan error sebelum mengulang; DDL tidak dibatalkan secara atomik. Setelah impor berhasil, script deploy hanya memeriksa keberadaan lima tabel dan tidak mengubah schema database.

## Pembaruan keamanan 28 Oktober 2026

Panduan operasional terkini ada di [SECURITY_UPDATE_2026-10-28.md](SECURITY_UPDATE_2026-10-28.md). Perubahan ini menambah SQL manual baru, verifikasi akun, pemeriksaan hak akses, cadangan budget, koreksi transfer, periode, notifikasi dan perlindungan dokumen. Rumus saldo tersedia kini memperhitungkan cadangan proposal disetujui. Alokasi dapat disesuaikan oleh keuangan dengan alasan tercatat, termasuk setelah ada transfer. Petunjuk migrasi/larangan penyesuaian alokasi pada bagian lama merupakan riwayat implementasi dan bukan prosedur hosting terbaru.

## Pembaruan pendanaan grup

Alokasi kini wajib didukung rekening sumber dan plafon grup/tahun. Lihat [GROUP_FUNDING.md](GROUP_FUNDING.md) untuk pembagian rata, pengajuan tambahan antar-departemen dalam grup, SQL manual baru, serta penanganan periode/data legacy. Penjelasan lama tentang alokasi yang bebas dari pendanaan rekening telah digantikan.

## Deploy tanpa pull manual berulang

Lihat [CPANEL_DEPLOY.md](CPANEL_DEPLOY.md). Setelah satu kali mengambil konfigurasi baru ke server, tombol **Deploy HEAD Commit** menjalankan sinkronisasi `origin/master` dan deployment dalam satu tugas. Commit dari komputer tetap harus di-push ke GitHub terlebih dahulu. Impor SQL manual tetap dilakukan bila rilis memerlukannya.
