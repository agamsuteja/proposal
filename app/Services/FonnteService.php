<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pengiriman WhatsApp via Fonnte (https://fonnte.com).
 * Satu-satunya tempat yang bicara ke API WA, dipakai semua controller.
 */
class FonnteService
{
    private ?string $failureReason = null;

    public function failureReason(): ?string
    {
        return $this->failureReason;
    }

    /**
     * Kirim pesan WhatsApp.
     *
     * @return bool true jika Fonnte menerima pesan (response "status": true)
     */
    public function send(?string $nohp, string $pesan): bool
    {
        $this->failureReason = null;
        $target = $this->normalizePhone($nohp);

        if (! $target) {
            $this->failureReason = 'Nomor WhatsApp tujuan belum valid.';
            Log::error('Fonnte: nomor HP tidak valid');

            return false;
        }

        $token = config('services.fonnte.token');

        if (! $token) {
            $this->failureReason = 'FONNTE_TOKEN belum tersedia pada konfigurasi aplikasi. Lengkapi konfigurasi hosting dan jalankan config:clear.';
            Log::error('Fonnte: token belum dikonfigurasi');

            return false;
        }

        try {
            Log::info('Fonnte: mengirim WA', [
                'target_suffix' => substr($target, -4),
                'length' => mb_strlen($pesan),
            ]);

            $payload = [
                'target' => $target,
                'message' => $pesan,
                'countryCode' => '62',
            ];

            // Jika pesan memuat link, minta Fonnte tidak membuat kartu pratinjau (gambar/ikon situs)
            if (stripos($pesan, 'http') !== false) {
                $payload['preview'] = 0;
            }

            $response = Http::timeout(30)
                ->withHeaders(['Authorization' => $token]) // Fonnte: token langsung, tanpa "Bearer"
                ->asForm()
                ->post(config('services.fonnte.url'), $payload);

            $data = $response->json();

            // Fonnte membalas {"status": true, ...} jika sukses
            // dan {"status": false, "reason": "..."} jika gagal
            if ($response->successful() && is_array($data) && ($data['status'] ?? false) === true) {
                Log::info('Fonnte: pesan diterima layanan', ['target_suffix' => substr($target, -4)]);

                return true;
            }

            $this->failureReason = $this->describeFailure($response->status(), is_array($data) ? ($data['reason'] ?? null) : null);
            Log::error('Fonnte: gagal kirim', [
                'target_suffix' => substr($target, -4),
                'http' => $response->status(),
                'accepted' => false,
            ]);

            return false;
        } catch (\Throwable $e) {
            $this->failureReason = 'Koneksi ke Fonnte gagal atau melewati batas waktu. Periksa jaringan hosting, HTTPS, dan FONNTE_URL.';
            Log::error('Fonnte: koneksi gagal', ['exception_type' => class_basename($e)]);

            return false;
        }
    }

    private function describeFailure(int $status, mixed $reason): string
    {
        // Never persist the raw provider response: it can contain phone numbers or credentials.
        $reason = is_string($reason) ? strtolower($reason) : '';
        if (in_array($status, [401, 403], true) || preg_match('/token|unauthor|invalid key/', $reason)) {
            return 'Otorisasi Fonnte ditolak. Periksa token dan akun perangkat di dashboard Fonnte.';
        }
        if (preg_match('/device|disconnect|not connected/', $reason)) {
            return 'Perangkat WhatsApp di Fonnte belum terhubung atau tidak aktif. Periksa koneksi perangkat.';
        }
        if (preg_match('/quota|credit|balance|subscription|package/', $reason)) {
            return 'Kuota atau paket Fonnte tidak tersedia. Periksa masa aktif dan kuota pengiriman.';
        }
        if (preg_match('/target|number|invalid phone/', $reason)) {
            return 'Fonnte menolak nomor tujuan. Periksa nomor WhatsApp terdaftar.';
        }
        if ($status === 429) {
            return 'Fonnte membatasi permintaan pengiriman. Coba lagi sesuai jadwal retry.';
        }

        return 'Fonnte belum menerima pesan (HTTP '.$status.'). Periksa perangkat, paket, dan status layanan pada dashboard Fonnte.';
    }

    /**
     * Ubah nomor ke format 62xxxxxxxxxx. Return null jika tidak masuk akal.
     * 0812-3456-7890 / +62 812... / 812... / 620812... semuanya jadi 62812...
     */
    public function normalizePhone(?string $nohp): ?string
    {
        $n = preg_replace('/[^0-9]/', '', (string) $nohp);

        if ($n === '') {
            return null;
        }

        if (str_starts_with($n, '620')) {
            $n = '62'.substr($n, 3);   // salah ketik "+62 0812..."
        } elseif (str_starts_with($n, '0')) {
            $n = '62'.substr($n, 1);   // 0812... -> 62812...
        } elseif (str_starts_with($n, '8')) {
            $n = '62'.$n;              // 812... -> 62812...
        }

        $len = strlen($n);

        return ($len >= 10 && $len <= 15) ? $n : null;
    }
}
