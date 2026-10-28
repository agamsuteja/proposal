<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class LegacySchema
{
    public static function build(): void
    {
        $database = getenv('BUDGET_TEST_MYSQL_DATABASE');
        if ($database) {
            if (! str_starts_with($database, 'proposal_budget_test')) {
                throw new \RuntimeException('Unsafe test database');
            }
            config(['database.default' => 'mysql', 'database.connections.mysql.database' => $database]);
            DB::purge('mysql');
            Schema::dropAllTables();
        } else {
            config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
            DB::purge('sqlite');
        }
        config(['session.driver' => 'array', 'cache.default' => 'array']);
        if ($database && getenv('LEGACY_TEST_SCHEMA')) {
            DB::unprepared(file_get_contents(base_path('tests/Fixtures/legacy-schema.mysql.sql')));
            (require database_path('migrations/2026_10_28_000001_harden_workflows.php'))->up();

            return;
        }
        Schema::create('grup', function (Blueprint $t) {
            $t->id();
            $t->string('nama_grup');
            $t->text('deskripsi')->nullable();
            $t->timestamps();
        });
        Schema::create('departemen', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('grup_id');
            $t->string('nama_departemen');
            $t->text('deskripsi')->nullable();
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->string('role');
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('departemen_id')->nullable();
            $t->unsignedBigInteger('grup_id')->nullable();
            $t->string('nohp')->nullable();
            $t->timestamps();
        });
        Schema::create('proposal', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('departemen_id');
            $t->unsignedBigInteger('user_id');
            $t->string('judul');
            $t->string('jenis')->default('acara');
            $t->text('tujuan')->nullable();
            $t->date('tanggal_pengajuan');
            $t->date('tanggal_acara')->nullable();
            $t->string('lokasi_acara')->nullable();
            $t->text('keterangan')->nullable();
            $t->string('lampiran')->nullable();
            $t->decimal('total_anggaran', 15, 2);
            $t->string('status');
            $t->timestamps();
        });
        Schema::create('approval_proposal', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('proposal_id');
            $t->unsignedBigInteger('approver_id');
            $t->string('tahap');
            $t->unsignedTinyInteger('urutan');
            $t->string('status');
            $t->text('catatan')->nullable();
            $t->timestamp('tanggal_approve')->nullable();
            $t->timestamps();
        });
        Schema::create('proposal_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('proposal_id');
            $t->string('nama_barang');
            $t->string('merk_tipe')->nullable();
            $t->integer('jumlah')->nullable();
            $t->text('tujuan_pembelian')->nullable();
            $t->decimal('anggaran', 15, 2);
            $t->timestamps();
        });
        Schema::create('rekening', function (Blueprint $t) {
            $t->id();
            $t->string('nama_rekening');
            $t->string('nomor_rekening')->nullable();
            $t->string('bank')->nullable();
            $t->text('keterangan')->nullable();
            $t->decimal('saldo', 15, 2);
            $t->timestamps();
        });
        Schema::create('pencairan_dana', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('proposal_id');
            $t->unsignedBigInteger('rekening_id');
            $t->decimal('jumlah_dana', 15, 2);
            $t->text('catatan')->nullable();
            $t->string('bukti_transfer')->nullable();
            $t->timestamp('tanggal_pencairan')->nullable();
            $t->timestamps();
        });
        Schema::create('pengembalian_dana', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('proposal_id');
            $t->unsignedBigInteger('rekening_id')->nullable();
            $t->decimal('jumlah', 15, 2);
            $t->string('status');
            $t->string('metode')->nullable();
            $t->string('bukti')->nullable();
            $t->text('catatan')->nullable();
            $t->timestamp('tanggal_pengembalian')->nullable();
            $t->timestamps();
        });
        Schema::create('nota_penggunaan_proposal', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('proposal_id');
            $t->string('file_nota');
            $t->decimal('jumlah', 15, 2);
            $t->string('keterangan')->nullable();
            $t->timestamps();
        });
        (require database_path('migrations/2026_10_27_000001_create_department_budgets.php'))->up();
        (require database_path('migrations/2026_10_28_000001_harden_workflows.php'))->up();
    }
}
