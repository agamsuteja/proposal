# Deploy cPanel dengan sinkronisasi GitHub

Pembaruan 30 Oktober 2026. Konfigurasi ini mempertahankan Laravel 11, PHP hosting yang sudah dipakai, dan impor SQL manual.

## Tiga salinan yang berbeda

1. Komputer Anda: GitHub Desktop mengelola commit/pull/push lokal.
2. GitHub: menyimpan branch `master` bersama. Commit lokal belum ada di GitHub sampai **Push origin** berhasil.
3. Server cPanel: checkout di `/home/myey1771/public_html/proposal` adalah salinan terpisah.

Tombol bawaan **Deploy HEAD Commit** menjalankan tugas pada kode yang sudah ada di server. Ia bukan tombol membuat commit dan, pada konfigurasi sebelumnya, tidak otomatis menarik GitHub. Karena itu manual `git pull` membuat perubahan baru muncul sebelum bisa dideploy. Penulis commit tidak memengaruhi hal ini.

Cara bawaan lain adalah **Update from Remote**, bila tombol tersedia di cPanel. Screenshot hosting saat perubahan ini dibuat tidak menampilkan tombol itu; konfigurasi remote/metadata internal panel belum dapat diperiksa dari cloud. Solusi di repositori ini menggabungkan sinkronisasi dengan tugas deploy yang sudah ada.

## Pemasangan satu kali

Script baru harus diambil ke server terlebih dahulu, karena server yang masih memakai script lama belum memiliki langkah pull otomatis. Jalankan satu kali di Terminal cPanel:

```sh
cd /home/myey1771/public_html/proposal
git pull --ff-only origin master
```

Setelah perintah berhasil, refresh Git Version Control dan pilih **Deploy HEAD Commit**. `.cpanel.yml` sekarang memanggil `scripts/pull-and-deploy-cpanel.sh`, lalu script itu menjalankan `scripts/deploy-cpanel.sh` dari commit terbaru.

Tidak ada SQL baru untuk pemasangan ini. Jika server mempunyai perubahan lokal/commit yang belum ada di GitHub, jangan memakai reset/force untuk menghapusnya. Simpan dan selesaikan perbedaan terlebih dahulu.

## Alur sehari-hari sesudah pemasangan

**Perubahan dari komputer:**

1. GitHub Desktop: pilih `master`, Fetch/Pull sebelum mengedit.
2. Ubah kode, Commit, lalu **Push origin**. Bila ada konflik, selesaikan di komputer dan push hasilnya.
3. cPanel: klik **Deploy HEAD Commit**.

**Perubahan dari Codex:**

1. Pastikan perubahan sudah di-push ke GitHub `master`.
2. cPanel: klik **Deploy HEAD Commit**.
3. Komputer: Fetch/Pull agar perubahan tersebut masuk ke komputer sebelum mengedit lagi.

Pada kedua alur, script menarik `origin/master` secara noninteraktif, menerapkan fast-forward, lalu memasang dependency/build dan menjalankan pemeriksaan deploy yang sudah ada. Script tidak melihat commit di komputer yang belum di-push dan tidak otomatis mengambil branch lain yang belum digabung ke `master`.

## Perlindungan dan perilaku kegagalan

- Dua deployment tidak berjalan bersamaan (`flock`). Server membutuhkan Git, Bash dan flock; PHP/Composer tetap mengikuti script deploy lama.
- Branch server harus `master`.
- Perubahan file terlacak di server menyebabkan proses berhenti sebelum pull.
- Commit lokal server yang tidak termasuk riwayat GitHub terbaru menyebabkan proses berhenti, bukan di-reset atau dipaksa.
- Git tidak meminta password secara interaktif. Akun cPanel harus dapat membaca remote melalui kredensial/SSH deploy key yang sesuai jika repo privat. Keberhasilan login GitHub Desktop tidak otomatis memberi akses Git kepada akun cPanel.
- File lokal tidak dihapus dengan reset/clean. `.env` lokal tetap dipertahankan. File tidak terlacak yang bertabrakan dengan file baru akan ditolak oleh Git.
- Jika pull gagal, deploy tidak dijalankan. Jika deploy gagal, proses mengembalikan error dan tidak menulis penanda sukses baru.
- Jika rilis menambahkan SQL manual, backup dan impor SQL tersebut sebelum deploy. Tidak ada `artisan migrate` atau perubahan schema otomatis.
- Pull memperbarui checkout aplikasi seperti prosedur manual sebelumnya; proses ini bukan deployment atomik atau mekanisme rollback otomatis. Gunakan maintenance sesuai kebutuhan untuk perubahan dependency/schema yang besar.

`bash scripts/pull-and-deploy-cpanel.sh --check` tetap hanya meneruskan pemeriksaan prasyarat tanpa fetch/pull. Perintah `bash scripts/deploy-cpanel.sh --check` yang lama juga tetap berlaku.

## Commit mana yang benar-benar dipakai?

Log tugas menampilkan **Commit aktual yang akan di-deploy** setelah sinkronisasi dan **Sinkronisasi dan deployment selesai** setelah semua tugas berhasil. SHA sukses aktual juga disimpan di `.git/proposal-last-successful-deploy` untuk pemeriksaan operator.

Refresh halaman cPanel setelah deploy untuk memperbarui HEAD. Sebagian versi panel dapat mencatat Last Deployed SHA berdasarkan HEAD saat tombol awal ditekan, sebelum tugas pull berjalan; pada kondisi itu, gunakan SHA aktual dari output tugas/penanda sukses dan HEAD setelah refresh. Script ini tidak menyunting metadata internal cPanel atau mengklaim telah memverifikasi perilaku versi hosting tersebut.

## Validasi lokal terisolasi

```sh
python3 tests/Deployment/test_pull_and_deploy.py
```

Delapan uji memakai remote Git bare dan checkout sementara pada disk lokal, tanpa jaringan, database aplikasi, kredensial hosting atau deploy produksi: pembaruan commit/script, deploy ulang, file lokal berubah, commit server berbeda, branch berbeda, lock bersamaan, remote gagal, deploy gagal dan mode check-only.
