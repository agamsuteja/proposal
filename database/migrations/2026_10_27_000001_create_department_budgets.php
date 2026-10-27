<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('budget_departemen', function (Blueprint $t) {
            $t->id();
            $t->foreignId('departemen_id')->constrained('departemen')->restrictOnDelete();
            $t->unsignedSmallInteger('tahun');
            $t->decimal('jumlah', 18, 2)->default(0);
            $t->timestamps();
            $t->unique(['departemen_id', 'tahun']);
        });
        Schema::create('budget_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('departemen_id')->constrained('departemen')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->unsignedSmallInteger('tahun');
            $t->decimal('jumlah', 18, 2);
            $t->text('alasan');
            $t->string('status', 30)->default('pending');
            $t->timestamp('funded_at')->nullable();
            $t->timestamps();
        });
        Schema::create('budget_approvals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('budget_request_id')->constrained('budget_requests')->restrictOnDelete();
            $t->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $t->string('tahap', 30);
            $t->unsignedTinyInteger('urutan');
            $t->string('status', 20)->default('pending');
            $t->text('catatan')->nullable();
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
            $t->unique(['budget_request_id', 'urutan']);
        });
        Schema::create('budget_transfers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('budget_request_id')->constrained('budget_requests')->restrictOnDelete();
            $t->foreignId('sumber_id')->constrained('departemen')->restrictOnDelete();
            $t->foreignId('tujuan_id')->constrained('departemen')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->unsignedSmallInteger('tahun');
            $t->decimal('jumlah', 18, 2);
            $t->timestamps();
        });
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('action', 100)->index();
            $t->string('subject_type')->nullable();
            $t->string('subject_id')->nullable();
            $t->json('before')->nullable();
            $t->json('after')->nullable();
            $t->string('method', 10)->nullable();
            $t->string('route')->nullable();
            $t->unsignedSmallInteger('response_status')->nullable();
            $t->timestamp('created_at')->useCurrent()->index();
        });
    }
    public function down(): void
    {
        foreach (['activity_logs', 'budget_transfers', 'budget_approvals', 'budget_requests', 'budget_departemen'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
