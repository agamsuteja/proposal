<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Read-only check; schema changes are imported manually through phpMyAdmin.
chdir(dirname(__DIR__));
require 'vendor/autoload.php';
try {
    $app = require 'bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    $schema = [
        'group_fundings' => ['grup_id', 'tahun', 'rekening_id', 'plafon'], 'budget_funding_mutex' => ['id'],
        'budget_departemen' => ['departemen_id', 'tahun', 'jumlah'], 'budget_requests' => ['status', 'funded_at'],
        'budget_approvals' => ['approver_id', 'urutan', 'status'], 'budget_transfers' => ['sumber_id', 'tujuan_id', 'jumlah'],
        'activity_logs' => ['user_id', 'before', 'after'], 'users' => ['registration_verified_at'], 'pencairan_dana' => ['metode'],
        'auth_challenges' => ['purpose', 'otp_hash', 'expires_at'], 'notification_outbox' => ['message', 'dedupe_key', 'status', 'attempts', 'expires_at'],
        'financial_operations' => ['fingerprint', 'completed_at'], 'budget_periods' => ['tahun', 'status'],
        'budget_adjustments' => ['before_amount', 'after_amount', 'alasan'], 'budget_request_versions' => ['snapshot', 'alasan'], 'budget_reversals' => ['budget_request_id', 'alasan'],
    ];
    if (config('cache.default') === 'database') {
        $schema['cache'] = ['key', 'value', 'expiration'];
        $schema['cache_locks'] = ['key', 'owner', 'expiration'];
    }
    $missing = [];
    foreach ($schema as $table => $columns) {
        if (! Schema::hasTable($table) || ! Schema::hasColumns($table, $columns)) {
            $missing[] = $table;
        }
    }
    if ($missing) {
        fwrite(STDERR, 'Schema belum lengkap: '.implode(', ', $missing).".\nImport SQL 2026_10_27 dan 2026_10_28 serta 2026_10_28_group_funding melalui phpMyAdmin. Tidak ada migrasi otomatis.\n");
        exit(1);
    }
    if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true) && ! preg_match('/^decimal\(\d+,\s*2\)$/i', Schema::getColumnType('rekening', 'saldo', true))) {
        fwrite(STDERR, "Kolom saldo rekening belum mendukung sen. Impor SQL 2026_10_28_group_funding.sql.\n");
        exit(1);
    }
    if (! DB::table('budget_funding_mutex')->where('id', 1)->exists()) {
        fwrite(STDERR, "Seed pendanaan belum tersedia. Impor SQL 2026_10_28_group_funding.sql.\n");
        exit(1);
    }
    if (! config('app.key')) {
        fwrite(STDERR, "APP_KEY belum tersedia. Jangan mengganti key yang sudah digunakan.\n");
        exit(1);
    }
    if (in_array('--production', $argv, true) && (config('app.debug') || config('app.env') !== 'production')) {
        fwrite(STDERR, "Atur APP_ENV=production dan APP_DEBUG=false pada .env hosting, lalu hapus cache konfigurasi sebelum deploy.\n");
        exit(1);
    }
    echo "Schema database lengkap; tidak ada perubahan data.\n";
    if (! config('services.fonnte.token')) {
        echo "Catatan: token WhatsApp belum tersedia; OTP/notifikasi tidak dapat dikirim sampai konfigurasi dilengkapi.\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Pemeriksaan database gagal. Periksa koneksi .env dan log privat server; kredensial tidak ditampilkan.\n");
    exit(1);
}
