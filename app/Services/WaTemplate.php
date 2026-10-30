<?php

namespace App\Services;

use App\Models\BudgetRequest;
use App\Models\Proposal;

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
    public static function proposalSiapDicairkan(Proposal $proposal, string $nama, bool $pengingat = false): string
    {
        $proposal->loadMissing(['user', 'departemen.grup', 'approval_flows.approver']);
        $pastor = $proposal->approval_flows->first(fn ($flow) => $flow->tahap === 'gembala' && $flow->status === 'disetujui');
        $paid = DepartmentBudgetService::cents($proposal->pencairan()->sum('jumlah_dana'));
        $remaining = max(0, DepartmentBudgetService::cents($proposal->total_anggaran) - $paid);
        $isi = [
            ($pengingat ? 'Pengingat: proposal' : 'Proposal').' #'.$proposal->id.' telah disetujui Gembala (Senior Pastor) dan siap diproses pencairannya.',
            '',
            'Judul: '.self::bersih($proposal->judul),
            'Pemohon: '.self::bersih($proposal->user?->name ?? '-', 100),
            'Departemen: '.self::bersih($proposal->departemen?->nama_departemen ?? '-', 150),
            'Grup: '.self::bersih($proposal->departemen?->grup?->nama_grup ?? '-', 150),
            'Jenis proposal: '.(['barang'=>'Pengadaan Barang', 'acara'=>'Kegiatan / Acara', 'maintenance'=>'Maintenance'][$proposal->jenis] ?? self::bersih($proposal->jenis, 60)),
            'Tanggal pengajuan: '.\Illuminate\Support\Carbon::parse($proposal->tanggal_pengajuan)->format('d/m/Y'),
            'Total anggaran disetujui: '.self::budgetRp($proposal->total_anggaran),
            'Sudah dicairkan: '.self::budgetRp(DepartmentBudgetService::amount($paid)),
            'Sisa dapat dicairkan: '.self::budgetRp(DepartmentBudgetService::amount($remaining)),
            'Tujuan / alasan: '.(self::bersih($proposal->tujuan, 1000) ?: '-'),
        ];
        if ($proposal->keterangan) {
            $isi[] = 'Keterangan: '.self::bersih($proposal->keterangan, 1000);
        }
        $isi[] = 'Disetujui Gembala: '.self::bersih($pastor?->approver?->name ?? '-', 100);
        if ($pastor?->tanggal_approve) {
            $isi[] = 'Waktu persetujuan: '.\Illuminate\Support\Carbon::parse($pastor->tanggal_approve)->timezone('Asia/Jakarta')->format('d/m/Y H:i').' WIB';
        }

        return self::notif($nama, $isi, 'Silakan buka menu Pencairan Dana di aplikasi '.self::APLIKASI.' untuk memeriksa rincian dan lampiran, lalu memproses pencairan dari rekening sumber grup. Persetujuan ini belum berarti dana sudah dicairkan.');
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

    /** Ke approver tahap pertama: pemilik mengajukan kembali proposal yang direvisi */
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
    // TAMBAHAN BUDGET
    // ===================================================================

    public static function tambahanBudgetMenunggu(BudgetRequest $request, string $nama, ?string $tahap, bool $pengingat = false): string
    {
        $status = match ($tahap) {
            'departemen_head' => 'Menunggu persetujuan Kepala Departemen (MH).',
            'group_head' => 'Telah disetujui Kepala Departemen (MH); menunggu persetujuan Kepala Grup (GH).',
            'gembala' => 'Telah disetujui Kepala Departemen (MH) dan Kepala Grup (GH); menunggu persetujuan Gembala.',
            default => 'Telah disetujui MH, GH, dan Gembala; menunggu transfer alokasi oleh Keuangan.',
        };

        return self::notif($nama, array_merge([
            ($pengingat ? 'Pengingat pengajuan' : 'Pengajuan').' tambahan budget #'.$request->id,
            '',
        ], self::rincianTambahanBudget($request), ['', 'Status: '.$status]), $tahap
            ? 'Silakan buka menu Tambahan Budget di aplikasi ESC Proposal untuk meninjau alasan dan memberikan keputusan.'
            : 'Silakan buka menu Tambahan Budget di aplikasi ESC Proposal. Pilih satu atau beberapa departemen sumber dalam grup yang sama, sesuai budget tersedia, lalu proses transfer alokasi. Ini bukan pencairan uang dari rekening.');
    }

    public static function tambahanBudgetDitolak(BudgetRequest $request, string $peninjau, string $tahap, ?string $catatan): string
    {
        $peran = match ($tahap) {
            'departemen_head' => 'Kepala Departemen (MH)',
            'group_head' => 'Kepala Grup (GH)',
            default => 'Gembala',
        };

        return self::notif($request->user->name, array_merge([
            'Pengajuan tambahan budget #'.$request->id.' dikembalikan untuk direvisi.', '',
        ], self::rincianTambahanBudget($request), [
            '', 'Ditolak oleh: '.self::bersih($peninjau, 100).' — '.$peran,
            'Catatan penolakan: '.(self::bersih($catatan, 1000) ?: 'Tidak ada catatan.'),
        ]), 'Silakan buka menu Tambahan Budget di aplikasi ESC Proposal, perbaiki pengajuan sesuai catatan, lalu ajukan kembali untuk persetujuan ulang.');
    }

    public static function tambahanBudgetDitransfer(BudgetRequest $request, string $keuangan): string
    {
        $request->loadMissing('transfers.sumber');
        $sumber = ['Sumber alokasi:'];
        foreach ($request->transfers as $transfer) {
            $sumber[] = '- '.self::bersih($transfer->sumber?->nama_departemen ?? '-', 150).': '.self::budgetRp($transfer->jumlah);
        }

        return self::notif($request->user->name, array_merge([
            'Tambahan budget #'.$request->id.' telah ditransfer ke alokasi departemen Anda.', '',
        ], self::rincianTambahanBudget($request), ['', ...$sumber, 'Diproses oleh Keuangan: '.self::bersih($keuangan, 100)]),
            'Silakan periksa menu Budget Departemen di aplikasi ESC Proposal. Transfer ini menambah alokasi budget departemen; bukan pencairan uang dari rekening.');
    }

    private static function rincianTambahanBudget(BudgetRequest $request): array
    {
        $request->loadMissing(['user', 'departemen.grup']);

        return [
            'Pemohon: '.self::bersih($request->user?->name ?? '-', 100),
            'Departemen: '.self::bersih($request->departemen?->nama_departemen ?? '-', 150),
            'Grup: '.self::bersih($request->departemen?->grup?->nama_grup ?? '-', 150),
            'Tahun budget: '.$request->tahun,
            'Tambahan yang diajukan: '.self::budgetRp($request->jumlah),
            'Alasan pengajuan: '.self::bersih($request->alasan, 5000),
        ];
    }

    private static function budgetRp($amount): string
    {
        $cents = DepartmentBudgetService::cents($amount);

        return 'Rp '.number_format(intdiv($cents, 100), 0, ',', '.').','.sprintf('%02d', $cents % 100);
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
