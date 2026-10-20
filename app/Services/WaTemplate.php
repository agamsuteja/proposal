<?php

namespace App\Services;

/**
 * Template pesan WhatsApp yang dibuat seaman mungkin dari deteksi spam:
 *  - teks polos: tanpa emoji, tanpa HURUF KAPITAL penuh, tanpa format tebal/miring
 *  - tanpa link (tidak ada kartu pratinjau gambar/ikon situs)
 *  - kalimat pembuka dan penutup dibuat bervariasi + ada waktu kirim,
 *    sehingga tidak ada dua pesan yang persis sama
 *  - teks bebas dari user (judul, catatan) dibersihkan dari link
 *
 * Link hanya disertakan jika FONNTE_SERTAKAN_LINK=true di .env
 * (dan Fonnte akan diminta mematikan pratinjau link).
 */
class WaTemplate
{
    private const APLIKASI = 'ESC Proposal';

    // ===================================================================
    // OTP
    // ===================================================================

    public static function otpReset(string $nama, $otp): string
    {
        return implode("\n", [
            self::salam($nama),
            '',
            'Kode OTP untuk reset password akun ' . self::APLIKASI . ' Anda: ' . $otp,
            'Kode berlaku 10 menit. Mohon jangan membagikan kode ini kepada siapa pun.',
            '',
            'Jika Anda tidak merasa meminta reset password, abaikan pesan ini.',
        ]);
    }

    public static function otpRegistrasi(string $nama, $otp): string
    {
        return implode("\n", [
            self::salam($nama),
            '',
            'Kode verifikasi pendaftaran akun ' . self::APLIKASI . ' Anda: ' . $otp,
            'Kode berlaku 10 menit. Mohon jangan membagikan kode ini kepada siapa pun.',
            '',
            'Jika Anda tidak merasa mendaftar, abaikan pesan ini.',
        ]);
    }

    // ===================================================================
    // ALUR PERSETUJUAN PROPOSAL
    // ===================================================================

    /** Ke Kepala Departemen: proposal baru diajukan */
    public static function proposalBaru($nama, $dept, $judul, $total): string
    {
        return self::notif($nama, [
            'Ada pengajuan proposal baru dari departemen ' . self::bersih($dept, 80) . ' yang menunggu persetujuan Anda.',
            '',
            'Judul: ' . self::bersih($judul),
            'Total anggaran: ' . self::rp($total),
        ], 'Silakan buka aplikasi ' . self::APLIKASI . ' untuk memberikan persetujuan.');
    }

    /** Ke approver: proposal revisi diajukan ulang */
    public static function proposalRevisi($nama, $judul): string
    {
        return self::notif($nama, [
            'Ada proposal revisi yang menunggu persetujuan Anda.',
            '',
            'Judul: ' . self::bersih($judul),
        ], 'Silakan buka aplikasi ' . self::APLIKASI . ' untuk memberikan persetujuan.');
    }

    /** Ke Group Head: proposal sudah disetujui Kepala Departemen */
    public static function proposalMasukGroupHead($nama, $dept, $judul, $total): string
    {
        return self::notif($nama, [
            'Proposal dari departemen ' . self::bersih($dept, 80) . ' telah disetujui Kepala Departemen dan menunggu peninjauan Anda.',
            '',
            'Judul: ' . self::bersih($judul),
            'Total anggaran: ' . self::rp($total),
        ], 'Silakan buka aplikasi ' . self::APLIKASI . ' untuk meninjau proposal.');
    }

    /** Ke Senior Pastor: proposal sudah disetujui Group Head */
    public static function proposalMenungguGembala($nama, $dept, $judul, $total): string
    {
        return self::notif($nama, [
            'Proposal dari departemen ' . self::bersih($dept, 80) . ' telah disetujui Group Head dan menunggu persetujuan Anda.',
            '',
            'Judul: ' . self::bersih($judul),
            'Total anggaran: ' . self::rp($total),
        ], 'Silakan buka aplikasi ' . self::APLIKASI . ' untuk memberikan persetujuan.');
    }

    /** Ke Tim Keuangan: proposal disetujui Senior Pastor */
    public static function proposalSiapDicairkan($judul, $total): string
    {
        return self::notif('Tim Keuangan', [
            'Proposal berikut telah disetujui Senior Pastor dan siap diproses pencairannya.',
            '',
            'Judul: ' . self::bersih($judul),
            'Total anggaran: ' . self::rp($total),
        ], 'Silakan buka aplikasi ' . self::APLIKASI . ' untuk memproses pencairan.');
    }

    /** Ke Tim Keuangan: Group Head meminta konfirmasi ketersediaan dana */
    public static function konfirmasiDanaKeuangan($pengirim, $judul, $dept, $total): string
    {
        return self::notif('Tim Keuangan', [
            'Group Head ' . self::bersih($pengirim, 60) . ' akan meneruskan proposal berikut ke Senior Pastor dan memohon konfirmasi ketersediaan dana.',
            '',
            'Judul: ' . self::bersih($judul),
            'Departemen: ' . self::bersih($dept, 80),
            'Anggaran: ' . self::rp($total),
        ], 'Mohon segera menghubungi yang bersangkutan untuk konfirmasi.');
    }

    /** Ke pemohon: proposal dikembalikan untuk direvisi */
    public static function revisiKePemohon($nama, $judul, $catatan): string
    {
        $catatan = self::bersih($catatan);

        return self::notif($nama, [
            'Proposal Anda telah dikembalikan untuk direvisi.',
            '',
            'Judul: ' . self::bersih($judul),
            'Catatan: ' . ($catatan !== '' ? $catatan : '-'),
        ], 'Silakan buka aplikasi ' . self::APLIKASI . ' untuk melakukan revisi.');
    }

    /** Ke Group Head: proposal revisi diajukan kembali */
    public static function diajukanKembali($nama, $dept, $judul, $total): string
    {
        return self::notif($nama, [
            'Proposal revisi dari departemen ' . self::bersih($dept, 80) . ' telah diajukan kembali.',
            '',
            'Judul: ' . self::bersih($judul),
            'Total anggaran: ' . self::rp($total),
        ], 'Silakan buka aplikasi ' . self::APLIKASI . ' untuk meninjau kembali.');
    }

    // ===================================================================
    // KEUANGAN
    // ===================================================================

    /** Ke pemohon: dana dicairkan */
    public static function pencairan($nama, $judul, $status, $jumlahCair, $totalCair, $totalAnggaran, $catatan = null): string
    {
        $isi = [
            'Proposal Anda telah diproses oleh Tim Keuangan.',
            '',
            'Judul: ' . self::bersih($judul),
            'Status: ' . self::bersih($status, 60),
            'Dicairkan sekarang: ' . self::rp($jumlahCair),
            'Total dicairkan: ' . self::rp($totalCair),
            'Total anggaran: ' . self::rp($totalAnggaran),
        ];

        $catatan = self::bersih($catatan);
        if ($catatan !== '') {
            $isi[] = '';
            $isi[] = 'Catatan Tim Keuangan: ' . $catatan;
        }

        return self::notif($nama, $isi, 'Silakan buka aplikasi ' . self::APLIKASI . ' untuk melihat detail proposal.');
    }

    /** Ke Tim Keuangan: ada pengembalian dana */
    public static function pengembalian($nama, $judul, $jumlah, $pengirim, $catatan = null): string
    {
        $catatan = self::bersih($catatan);

        return self::notif($nama, [
            'Ada pengembalian dana proposal yang perlu diverifikasi.',
            '',
            'Judul: ' . self::bersih($judul),
            'Jumlah: ' . self::rp($jumlah),
            'Dikirim oleh: ' . self::bersih($pengirim, 60),
            'Catatan: ' . ($catatan !== '' ? $catatan : '-'),
        ], 'Silakan buka aplikasi ' . self::APLIKASI . ' untuk memverifikasi.');
    }

    // ===================================================================
    // PEMBANTU
    // ===================================================================

    /** Kerangka standar semua notifikasi */
    private static function notif(string $nama, array $isi, string $aksi): string
    {
        $baris = [self::salam($nama), ''];

        foreach ($isi as $b) {
            $baris[] = $b;
        }

        $baris[] = 'Waktu: ' . self::waktu();
        $baris[] = '';
        $baris[] = $aksi;

        if (self::sertakanLink()) {
            $baris[] = 'Akses: ' . url('/login');
        }

        $baris[] = '';
        $baris[] = self::penutup();
        $baris[] = 'Pesan otomatis dari sistem ' . self::APLIKASI . '.';

        return implode("\n", $baris);
    }

    /** Salam pembuka yang berganti-ganti */
    private static function salam(string $nama): string
    {
        $nama = self::bersih($nama, 60);
        $jam  = (int) self::sekarang()->format('G');

        $waktu = $jam < 4 ? 'malam'
            : ($jam < 11 ? 'pagi'
            : ($jam < 15 ? 'siang'
            : ($jam < 18 ? 'sore' : 'malam')));

        $pilihan = [
            "Shalom {$nama},",
            "Selamat {$waktu}, {$nama}.",
            "Halo {$nama},",
        ];

        return $pilihan[random_int(0, count($pilihan) - 1)];
    }

    /** Kalimat penutup yang berganti-ganti */
    private static function penutup(): string
    {
        $pilihan = [
            'Terima kasih.',
            'Terima kasih atas perhatiannya.',
            'Tuhan memberkati.',
        ];

        return $pilihan[random_int(0, count($pilihan) - 1)];
    }

    /** Format rupiah. Menerima angka mentah ATAU teks yang sudah diformat (mis. "1.500.000") */
    private static function rp($angka): string
    {
        if (is_numeric($angka)) {
            $angka = number_format((float) $angka, 0, ',', '.');
        }

        return 'Rp ' . $angka;
    }

    /** Bersihkan teks bebas dari user: buang link, rapikan spasi, batasi panjang */
    private static function bersih($teks, int $maks = 300): string
    {
        $teks = (string) $teks;
        $teks = preg_replace('~(https?://|www\.)\S+~i', '[tautan dihapus]', $teks) ?? $teks;
        $teks = preg_replace('/\s+/u', ' ', $teks) ?? $teks;
        $teks = trim($teks);

        return mb_strlen($teks) > $maks ? mb_substr($teks, 0, $maks - 3) . '...' : $teks;
    }

    private static function sekarang(): \DateTime
    {
        return new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
    }

    private static function waktu(): string
    {
        return self::sekarang()->format('d/m/Y H:i') . ' WIB';
    }

    private static function sertakanLink(): bool
    {
        return (bool) config('services.fonnte.sertakan_link', false);
    }
}
