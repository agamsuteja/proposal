# Rekening sumber, plafon grup, dan alokasi departemen

Pembaruan 28 Oktober 2026. Tetap Laravel 11 dan PHP 8.2. SQL hosting diimpor manual melalui phpMyAdmin; script deploy tidak menjalankan migrasi.

## Mengapa berubah

Alokasi departemen sebelumnya tidak dibatasi dana rekening. Sekarang alurnya:

**Rekening → pendanaan grup per tahun → alokasi departemen → approval proposal → pencairan.**

Satu grup/tahun memiliki satu rekening sumber. Satu rekening boleh mendanai beberapa grup/tahun, tetapi jumlah komitmen semua periode terbuka tidak boleh melampaui saldo rekening. Nama rekening tidak otomatis menentukan grup: penautan dilakukan keuangan secara eksplisit.

## Contoh Community Rp20 juta

1. Login **keuangan**, buka **Budget Departemen**, pilih tahun 2026.
2. Di kartu **Community**, buka **Tetapkan rekening & plafon grup**.
3. Pilih rekening sumber yang sudah dibuat, masukkan plafon `20000000` dan alasan; simpan.
4. Buka **Bagi rata ke seluruh departemen dalam grup**, isi alasan, lalu klik **Bagi rata sekarang**.
5. Dengan dua departemen, Disciples Community dan KIDS masing-masing mendapat Rp10 juta. Total alokasi Rp20 juta; saldo rekening tetap Rp20 juta karena belum dibayarkan.
6. Perubahan alokasi manual tidak boleh membuat jumlah seluruh departemen melampaui plafon. Nilai yang dimasukkan adalah alokasi pengganti, bukan tambahan otomatis.

Pembagian rata mengganti alokasi seluruh departemen, dengan konfirmasi. Histori/pemakaian tidak dihapus. Jika nominal tidak habis dibagi, selisih sen diberikan menurut urutan ID departemen; jumlah hasil selalu tepat sama dengan plafon. Pembagian ditolak seluruhnya bila jatah baru salah satu departemen kurang dari dana terpakai + cadangan. Gunakan pembagian manual yang sah pada kondisi itu.

## Departemen kehabisan dana

Contoh Disciples sudah memakai seluruh Rp10 juta dan membutuhkan Rp2 juta lagi:

1. Pemohon membuka **Tambahan Budget**, memasukkan Rp2 juta dan alasan untuk departemennya.
2. Approval tetap **Kepala Departemen → Kepala Grup → Gembala**.
3. Setelah disetujui, keuangan memilih KIDS atau beberapa departemen lain **dalam Community**, lalu mengisi total sumber tepat Rp2 juta.
4. Sumber harus memiliki saldo bebas: alokasi − pencairan neto − cadangan. Dana terpakai/dicadangkan tidak boleh dipindahkan.
5. Alokasi Disciples menjadi Rp12 juta, dengan Rp10 juta terpakai dan Rp2 juta tersedia. Alokasi KIDS menjadi Rp8 juta. Total Community tetap Rp20 juta dan saldo rekening tidak berubah oleh transfer budget.

Tidak ada batas jumlah pengajuan. Total transfer harus sesuai pengajuan, tidak dapat diproses dua kali, dan tidak boleh memakai sumber dari grup lain. Koreksi transfer tetap memakai pembalikan beralasan bila saldo bebas penerima mencukupi.

## Mencegah pengalokasian dana yang sama dua kali

Untuk setiap grup/tahun terbuka:

- **Dana masih terikat di rekening = plafon grup − pencairan neto grup.**
- **Pencairan neto = seluruh pencairan − pengembalian diterima**, berdasarkan tahun pengajuan proposal.
- **Saldo rekening belum terikat = saldo rekening − total dana masih terikat seluruh grup/tahun terbuka.**

Keuangan tidak dapat menetapkan plafon baru melebihi saldo bebas tersebut. Saat mengganti plafon grup yang sama, sistem menghitung nilai pengganti, bukan menjumlahkannya lagi. Plafon tidak boleh kurang dari alokasi yang sudah dibagikan atau pemakaian/cadangan yang sudah ada.

Jika plafon Rp20 juta, lalu dicairkan Rp2 juta: saldo rekening menjadi Rp18 juta; dana yang masih terikat menjadi Rp18 juta, tetapi plafon dan total alokasi historis tetap Rp20 juta. Tidak muncul ruang alokasi baru Rp2 juta. Refund diterima Rp500 ribu menambah saldo dan komitmen tersisa masing-masing Rp500 ribu; plafon tidak berubah.

Pencairan hanya boleh memakai rekening sumber grup/tahun proposal. Pengembalian diterima pada rekening yang sama. Form menampilkan sumber yang sesuai dan server memeriksanya lagi. Saldo rekening tidak dapat diturunkan secara manual di bawah seluruh dana yang masih terikat; rekening yang mempunyai penautan pendanaan tidak dapat dihapus.

Penetapan plafon, perubahan alokasi, pembagian rata, penyesuaian rekening, dan transfer mencatat aktor serta histori/alasan. Penulisan pendanaan, alokasi, periode, pencairan, dan refund diserialisasi dengan lock database yang sama agar request bersamaan tidak memakai dana dua kali.

## Tahun berikutnya dan penutupan

Pendanaan periode tertutup tidak mengikat saldo rekening lagi. Histori plafon, alokasi, dan pemakaian tetap tersimpan. Karena dana itu dapat dipakai untuk tahun/grup lain, pembukaan periode lama akan ditolak jika saldo tidak lagi mencukupi komitmen gabungan.

Penutupan sekarang memeriksa seluruh pekerjaan: proposal/pengajuan belum selesai, refund pending, serta selisih pencairan − nota − refund diterima. Status proposal “Dicairkan” saja tidak cukup untuk menutup periode. Ini juga menutup temuan audit A01; temuan audit lainnya tidak otomatis dianggap selesai oleh perubahan ini.

Untuk menyalin alokasi ke tahun baru, tetapkan dulu sumber/plafon tahun tujuan yang benar-benar didukung saldo. Salin tetap tidak menimpa alokasi tahun tujuan yang sudah ada, dan seluruh proses dibatalkan jika satu grup belum mempunyai pendanaan cukup.

## Memasang di hosting

1. Backup database dan aplikasi; aktifkan maintenance sesuai prosedur hosting.
2. Pastikan SQL budget `2026_10_27_department_budgets.sql` dan keamanan `2026_10_28_security_workflows.sql` sudah diimpor sebelumnya.
3. Pilih database aplikasi di phpMyAdmin, lalu impor **`database/sql/2026_10_28_group_funding.sql`**.
4. SQL menambah dua tabel dan satu baris lock, serta memperluas `rekening.saldo` menjadi `DECIMAL(22,2)` agar hasil pembagian sen tidak dibulatkan sebagai integer. Rentang integer BIGINT lama dan nilai NULL dipertahankan; tidak mengisi uang atau menautkan grup secara otomatis.
5. Tarik kode terbaru dan periksa schema:

```sh
cd /home/myey1771/public_html/proposal
git pull --ff-only origin master
/opt/cpanel/ea-php82/root/usr/bin/php artisan config:clear
bash scripts/deploy-cpanel.sh --check
```

6. Jalankan **Deploy HEAD Commit** dari cPanel. Tidak ada migrasi otomatis.
7. Login sebagai keuangan dan tetapkan sumber/plafon setiap grup/tahun aktif, lalu bagi rata atau atur alokasi dengan alasan. Jalankan `security:check` dan selesaikan peringatan sebelum penggunaan rutin.
8. Setelah pemeriksaan dan konfigurasi selesai, keluar dari maintenance memakai PHP 8.2 `artisan up`.

Data lama tidak dihapus atau dipotong agar terlihat cocok. Jika alokasi lama melebihi dana nyata, sistem menolak pendanaan; rekonsiliasi dengan bukti dan otorisasi keuangan. Jika riwayat grup/tahun memakai beberapa rekening, atau refund lama tidak mempunyai rekening penerimaan, penautan satu rekening ditolak dan perlu rekonsiliasi/penanganan riwayat yang disepakati terlebih dahulu. Jangan mengganti rekening transaksi lama dengan SQL demi melewati validasi.

Alokasi lama yang belum memiliki pendanaan tetap terlihat dengan peringatan. Pengaturan alokasi, transfer budget, persetujuan akhir proposal dan pencairan yang memerlukan pendanaan ditahan sampai sumber/plafon disiapkan. Pengajuan dan histori tidak dihapus.

## Pengujian

Gunakan database khusus tes, jangan database produksi:

```sh
LEGACY_TEST_SCHEMA=1 BUDGET_TEST_MYSQL_DATABASE=proposal_budget_test php vendor/bin/phpunit --configuration phpunit.xml
```

Suite mencakup pembagian Rp20 juta, batas plafon, sumber lintas grup/tahun, pemakaian/cadangan, ketepatan sen, pencairan/refund pada rekening yang benar, pembatasan role, data legacy, tutup/buka periode, dan dua request paralel untuk pendanaan serta alokasi. Fixture mengosongkan database tes berawalan `proposal_budget_test` per tes.

Panduan PDF audit sebelumnya menggambarkan commit `390ccf1`. Gunakan dokumen ini sebagai pembaruan khusus hubungan rekening–budget; kontrol framework Laravel 11 dan batas audit hosting pada panduan keamanan tetap berlaku.

## Validasi sebelum mengajukan proposal

Pembuatan dan penyimpanan revisi proposal sekarang memeriksa sumber pendanaan dan budget tersedia departemen sesuai tahun pengajuan, setelah memperhitungkan pemakaian dan cadangan proposal lain. Total acara maupun penjumlahan anggaran barang/maintenance yang melampaui saldo tersedia ditolak sebelum proposal/approval/notifikasi baru disimpan. Misalnya proposal Rp15 juta dengan budget DC tersedia Rp10 juta langsung ditolak dengan informasi kekurangan Rp5 juta. Selesaikan tambahan budget dan transfer keuangan terlebih dahulu, lalu ajukan kembali. Revisi yang gagal tidak menimpa data atau membangun ulang approval.

Pemeriksaan saldo saat approval akhir tetap berlaku untuk perubahan saldo setelah pengajuan. Halaman gembala sekarang menampilkan saldo tersedia, cadangan, kekurangan dan semua pesan validasi, sehingga kegagalan tidak lagi tampak sebagai refresh tanpa alasan. Proposal lama yang sudah telanjur masuk dengan anggaran berlebih dapat dikembalikan untuk revisi; data dan keputusan sebelumnya tidak diubah otomatis. Perubahan validasi ini tidak memerlukan SQL tambahan.
