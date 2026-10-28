<?php

namespace App\Services;

use App\Models\NotificationMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function queue(?string $phone, string $message, string $subject = 'Notifikasi aplikasi', ?int $userId = null, ?string $key = null, $expires = null, bool $immediate = false): NotificationMessage
    {
        $target = app(FonnteService::class)->normalizePhone($phone);
        $key ??= hash('sha256', (string) $phone.'|'.$message.'|'.now()->format('YmdHi'));
        $row = NotificationMessage::firstOrCreate(['dedupe_key' => $key], [
            'target' => $target ?? '', 'message' => $message, 'subject' => $subject,
            'user_id' => $userId, 'status' => $target ? 'pending' : 'failed',
            'last_error' => $target ? null : 'Nomor WhatsApp belum valid.', 'expires_at' => $expires,
        ]);
        if ($target && $row->wasRecentlyCreated) {
            DB::afterCommit(function () use ($row, $immediate) {
                $send = function () use ($row) {
                    try {
                        $this->deliver($row->id);
                    } catch (\Throwable $error) {
                        Log::error('Pengiriman ditunda; periksa outbox.', ['notification_id' => $row->id]);
                    }
                };
                if ($immediate) {
                    $send();
                } else {
                    app()->terminating($send);
                }
            });
        }

        return $row;
    }

    public function deliver(int $id): bool
    {
        $row = DB::transaction(function () use ($id) {
            $row = NotificationMessage::lockForUpdate()->findOrFail($id);
            if (in_array($row->status, ['sent', 'expired']) || ($row->status === 'processing' && $row->updated_at->gt(now()->subMinutes(5)))) {
                return null;
            }
            if ($row->expires_at && $row->expires_at->isPast()) {
                $row->update(['status' => 'expired']);

                return null;
            }
            if (! $row->target || $row->attempts >= 5) {
                return null;
            }
            $row->update(['status' => 'processing', 'attempts' => $row->attempts + 1]);

            return $row;
        });
        if (! $row) {
            return NotificationMessage::findOrFail($id)->status === 'sent';
        }
        $sent = app(FonnteService::class)->send($row->target, $row->message);
        $row->update(['status' => $sent ? 'sent' : 'failed', 'sent_at' => $sent ? now() : null,
            'last_error' => $sent ? null : 'Layanan WhatsApp belum menerima pesan. Periksa konfigurasi pengiriman.',
            'next_attempt_at' => $sent ? null : now()->addMinutes(min(60, 2 ** $row->attempts))]);

        return $sent;
    }
}
