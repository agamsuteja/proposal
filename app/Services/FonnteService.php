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
    /**
     * Kirim pesan WhatsApp.
     *
     * @return bool true jika Fonnte menerima pesan (response "status": true)
     */
    public function send(?string $nohp, string $pesan): bool
    {
        $target = $this->normalizePhone($nohp);

        if (! $target) {
            Log::error('Fonnte: nomor HP tidak valid', ['nohp' => $nohp]);

            return false;
        }

        $token = config('services.fonnte.token');

        if (! $token) {
            Log::error('Fonnte: FONNTE_TOKEN belum diisi di .env (lalu jalankan php artisan config:clear)');

            return false;
        }

        try {
            Log::info('Fonnte: mengirim WA', [
                'target' => $target,
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
                Log::info('Fonnte: pesan diterima untuk dikirim ke '.$target);

                return true;
            }

            Log::error('Fonnte: gagal kirim', [
                'target' => $target,
                'http' => $response->status(),
                'accepted' => false,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Fonnte: exception - '.$e->getMessage());

            return false;
        }
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
