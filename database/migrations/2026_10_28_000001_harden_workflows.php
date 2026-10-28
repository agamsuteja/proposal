<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'registration_verified_at')) {
            Schema::table('users', fn (Blueprint $t) => $t->timestamp('registration_verified_at')->nullable());
        }
        if (! Schema::hasColumn('pencairan_dana', 'metode')) {
            Schema::table('pencairan_dana', fn (Blueprint $t) => $t->string('metode', 20)->nullable());
        }
        if (! Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $t) {
                $t->string('key')->primary();
                $t->mediumText('value');
                $t->integer('expiration');
            });
        }
        if (! Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $t) {
                $t->string('key')->primary();
                $t->string('owner');
                $t->integer('expiration');
            });
        }
        Schema::create('financial_operations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->string('purpose', 30);
            $t->char('fingerprint', 64);
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('auth_challenges', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('purpose', 30);
            $t->string('otp_hash');
            $t->timestamp('expires_at');
            $t->timestamps();
            $t->unique(['user_id', 'purpose']);
        });
        Schema::create('notification_outbox', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('target', 30);
            $t->text('message');
            $t->string('subject', 150);
            $t->string('dedupe_key')->nullable()->unique();
            $t->string('status', 20)->default('pending')->index();
            $t->unsignedSmallInteger('attempts')->default(0);
            $t->timestamp('next_attempt_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->string('last_error')->nullable();
            $t->timestamps();
        });
        Schema::create('budget_periods', function (Blueprint $t) {
            $t->unsignedSmallInteger('tahun')->primary();
            $t->string('status', 20)->default('open');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->text('alasan')->nullable();
            $t->timestamps();
        });
        Schema::create('budget_adjustments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('departemen_id')->constrained('departemen')->restrictOnDelete();
            $t->unsignedSmallInteger('tahun');
            $t->decimal('before_amount', 18, 2);
            $t->decimal('after_amount', 18, 2);
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->text('alasan');
            $t->timestamps();
        });
        Schema::create('budget_request_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('budget_request_id')->constrained('budget_requests')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->json('snapshot');
            $t->text('alasan');
            $t->timestamps();
        });
        Schema::create('budget_reversals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('budget_request_id')->unique()->constrained('budget_requests')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->text('alasan');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['financial_operations', 'budget_reversals', 'budget_request_versions', 'budget_adjustments', 'budget_periods', 'notification_outbox', 'auth_challenges'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('pencairan_dana', fn (Blueprint $t) => $t->dropColumn('metode'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('registration_verified_at'));
    }
};
