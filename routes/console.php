<?php

use App\Models\ActivityLog;
use App\Models\BudgetRequest;
use App\Models\NotificationMessage;
use App\Models\Rekening;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:retry {--limit=50}', function () {
    $ids = NotificationMessage::where('attempts', '<', 5)->where(function ($q) {
        $q->whereIn('status', ['pending', 'failed'])->where(fn ($q) => $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
            ->orWhere(fn ($q) => $q->where('status', 'processing')->where('updated_at', '<', now()->subMinutes(5)));
    })->orderBy('id')->limit(min(200, max(1, (int) $this->option('limit'))))->pluck('id');
    $sent = 0;
    foreach ($ids as $id) {
        if (app(NotificationService::class)->deliver($id)) {
            $sent++;
        }
    }
    $this->info('Diproses: '.$ids->count().'; diterima layanan: '.$sent.'.');
})->purpose('Retry notifikasi gagal tanpa menjalankan ulang transaksi keuangan');

Artisan::command('activity:archive {--days=180} {--purge}', function () {
    $days = max(30, (int) $this->option('days'));
    $cutoff = now()->subDays($days);
    $max = ActivityLog::where('created_at', '<', $cutoff)->max('id');
    if (! $max) {
        $this->info('Tidak ada log lama.');

        return;
    }
    $directory = storage_path('app/private/audit-archives');
    if (! is_dir($directory)) {
        mkdir($directory, 0700, true);
    }
    $file = $directory.'/audit-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.jsonl';
    $handle = fopen($file, 'xb');
    try {
        foreach (ActivityLog::where('id', '<=', $max)->where('created_at', '<', $cutoff)->orderBy('id')->lazyById(500) as $log) {
            $line = json_encode($log->toArray(), JSON_THROW_ON_ERROR)."\n";
            if (fwrite($handle, $line) !== strlen($line)) {
                throw new RuntimeException('Arsip gagal ditulis. Log tidak dihapus.');
            }
        }fflush($handle);
    } finally {
        fclose($handle);
    }
    $hash = hash_file('sha256', $file);
    if (file_put_contents($file.'.sha256', $hash) !== strlen($hash)) {
        throw new RuntimeException('Checksum arsip gagal disimpan; log tidak dihapus.');
    }
    ActivityLog::create(['action' => 'audit.archived', 'after' => ['archive' => basename($file), 'sha256' => $hash, 'purged' => (bool) $this->option('purge')]]);
    if ($this->option('purge')) {
        DB::table('activity_logs')->where('id', '<=', $max)->where('created_at', '<', $cutoff)->delete();
    }
    $this->info('Arsip privat disimpan: '.basename($file).'. Log '.($this->option('purge') ? 'lama dipindahkan setelah arsip berhasil.' : 'tetap dipertahankan.'));
})->purpose('Arsip audit privat; purge hanya dengan opsi eksplisit');

Artisan::command('workflow:remind', function () {
    $count = 0;
    foreach (BudgetRequest::whereIn('status', ['pending', 'disetujui'])->with('approvals.approver')->lazyById(100) as $request) {
        $next = $request->approvals->firstWhere('status', 'pending');
        $users = $next ? collect([$next->approver]) : User::where('role', 'keuangan')->where('is_active', 1)->get();
        foreach ($users as $user) {
            if ($user && $user->is_active) {
                app(NotificationService::class)->queue($user->nohp, 'Pengingat: tambahan budget #'.$request->id.' masih menunggu tindak lanjut.', 'Pengingat tambahan budget', $user->id, 'budget-reminder-'.$request->id.'-'.$user->id.'-'.now()->format('Ymd'));
                $count++;
            }
        }
    }
    $this->info('Pengingat dicatat: '.$count.'.');
})->purpose('Reminder harian tambahan budget dengan deduplikasi');

Schedule::command('notifications:retry')->everyMinute()->withoutOverlapping(10);
Schedule::command('workflow:remind')->dailyAt('09:00')->timezone(config('app.timezone'))->withoutOverlapping(60);

Artisan::command('security:check', function () {
    $issues = [];
    if (! User::where('role', 'admin')->where('is_active', 1)->exists()) {
        $issues[] = 'Belum ada admin aktif. Tetapkan admin yang sah sebelum penggunaan.';
    }
    $payments = DB::table('pencairan_dana')->selectRaw('proposal_id,SUM(jumlah_dana) paid')->groupBy('proposal_id');
    $notes = DB::table('nota_penggunaan_proposal')->selectRaw('proposal_id,SUM(jumlah) used')->groupBy('proposal_id');
    $refunds = DB::table('pengembalian_dana')->where('status', 'diterima')->selectRaw('proposal_id,SUM(jumlah) refunded')->groupBy('proposal_id');
    $inconsistent = DB::table('proposal')->leftJoinSub($payments, 'p', fn ($j) => $j->on('proposal.id', '=', 'p.proposal_id'))
        ->leftJoinSub($notes, 'n', fn ($j) => $j->on('proposal.id', '=', 'n.proposal_id'))->leftJoinSub($refunds, 'r', fn ($j) => $j->on('proposal.id', '=', 'r.proposal_id'))
        ->whereRaw('COALESCE(n.used,0)+COALESCE(r.refunded,0)>COALESCE(p.paid,0)')->count();
    if ($inconsistent) {
        $issues[] = $inconsistent.' proposal lama memerlukan rekonsiliasi nota/pengembalian. Data tidak diubah otomatis.';
    }
    if (Rekening::where('saldo', '<', 0)->exists()) {
        $issues[] = 'Ada saldo rekening negatif.';
    }
    foreach ($issues as $issue) {
        $this->warn($issue);
    }
    if (! $issues) {
        $this->info('Pemeriksaan data dasar selesai tanpa inkonsistensi yang diperiksa.');
    }
    $this->line('Framework tetap Laravel 11. Pemeriksaan ini bukan sertifikasi bebas kerentanan framework.');
})->purpose('Pemeriksaan read-only admin dan konsistensi dana lama');
