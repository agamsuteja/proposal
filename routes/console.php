<?php

use App\Models\ActivityLog;
use App\Models\AuthChallenge;
use App\Models\BudgetRequest;
use App\Models\GroupFunding;
use App\Models\NotificationMessage;
use App\Models\Rekening;
use App\Models\User;
use App\Services\DepartmentBudgetService;
use App\Services\FonnteService;
use App\Services\GroupFundingService;
use App\Services\NotificationService;
use App\Services\WaTemplate;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Validation\ValidationException;

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
                app(NotificationService::class)->queue($user->nohp, WaTemplate::tambahanBudgetMenunggu($request, $user->name, $next?->tahap, true), 'Pengingat tambahan budget', $user->id, 'budget-reminder-'.$request->id.'-'.$user->id.'-'.now()->format('Ymd'));
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
    $fundingService = app(GroupFundingService::class);
    $missingFunding = DB::table('budget_departemen')->join('departemen', 'departemen.id', '=', 'budget_departemen.departemen_id')
        ->leftJoin('group_fundings', fn ($j) => $j->on('group_fundings.grup_id', '=', 'departemen.grup_id')->on('group_fundings.tahun', '=', 'budget_departemen.tahun'))
        ->whereNull('group_fundings.id')->count();
    if ($missingFunding) {
        $issues[] = $missingFunding.' alokasi departemen belum ditautkan ke pendanaan grup. Tetapkan sumber/plafon melalui Budget Departemen.';
    }
    foreach (GroupFunding::all() as $funding) {
        if ($fundingService->allocated($funding->grup_id, $funding->tahun) > DepartmentBudgetService::cents($funding->plafon)) {
            $issues[] = 'Total alokasi grup #'.$funding->grup_id.' tahun '.$funding->tahun.' melebihi plafon.';
        }
    }
    foreach (Rekening::all() as $account) {
        try {
            $fundingService->assertBacked($account->id);
        } catch (ValidationException $e) {
            $issues[] = 'Pendanaan rekening #'.$account->id.' perlu rekonsiliasi: '.implode(' ', Arr::flatten($e->errors()));
        }
    }
    foreach ($issues as $issue) {
        $this->warn($issue);
    }
    if (! $issues) {
        $this->info('Pemeriksaan data dasar selesai tanpa inkonsistensi yang diperiksa.');
    }
    $this->line('Framework tetap Laravel 11. Pemeriksaan ini bukan sertifikasi bebas kerentanan framework.');
})->purpose('Pemeriksaan read-only admin dan konsistensi dana lama');

Artisan::command('auth:diagnose-reset {email} {--phone=}', function () {
    $email = (string) $this->argument('email');
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Format email tidak valid.');

        return 1;
    }
    $yes = fn ($condition) => $condition ? 'Ya' : 'Tidak';
    $this->line('Diagnostik read-only: tidak mengirim OTP dan tidak mengubah akun.');
    $user = User::where('email', $email)->first();
    $this->line('Email ditemukan: '.$yes((bool) $user));
    $this->line('Akun aktif: '.$yes($user && $user->is_active));
    $sender = app(FonnteService::class);
    $phone = $sender->normalizePhone($user?->nohp);
    $this->line('Nomor terdaftar valid: '.$yes((bool) $phone));
    if ($this->option('phone') !== null) {
        $input = $sender->normalizePhone((string) $this->option('phone'));
        $this->line('Nomor masukan cocok setelah normalisasi: '.$yes($phone && $input && hash_equals($phone, $input)));
    }
    $this->line('FONNTE_TOKEN tersedia di konfigurasi aktif: '.$yes((bool) config('services.fonnte.token')));
    $this->line('Endpoint Fonnte menggunakan HTTPS: '.$yes(parse_url((string) config('services.fonnte.url'), PHP_URL_SCHEME) === 'https'));
    if ($user) {
        $challenge = AuthChallenge::where('user_id', $user->id)->where('purpose', 'password_reset')->first();
        $this->line('Challenge reset belum kedaluwarsa: '.$yes($challenge && $challenge->expires_at->isFuture()));
        $message = NotificationMessage::where('user_id', $user->id)->where('dedupe_key', 'like', 'auth-otp-password_reset-'.$user->id.'-%')->latest('id')->first();
        $this->line('Outbox reset terakhir: '.($message ? 'ID '.$message->id.'; status '.$message->status.'; percobaan '.$message->attempts : 'Tidak ada'));
        if ($message?->last_error) {
            $this->line('Alasan gagal: '.$message->last_error);
        }
    }
    $this->line('Token, kode OTP, isi pesan dan nomor lengkap tidak ditampilkan.');
})->purpose('Diagnostik lupa password tanpa mengirim pesan atau membuka kredensial');
