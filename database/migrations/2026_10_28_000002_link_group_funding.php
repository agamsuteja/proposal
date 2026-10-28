<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy saldo is BIGINT; preserve its full integer range and add exact cents.
        Schema::table('rekening', fn (Blueprint $t) => $t->decimal('saldo', 22, 2)->nullable()->change());
        Schema::create('budget_funding_mutex', fn (Blueprint $t) => $t->unsignedTinyInteger('id')->primary());
        DB::table('budget_funding_mutex')->insert(['id' => 1]);
        Schema::create('group_fundings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('grup_id')->constrained('grup')->restrictOnDelete();
            $t->unsignedSmallInteger('tahun');
            $t->foreignId('rekening_id')->constrained('rekening')->restrictOnDelete();
            $t->decimal('plafon', 18, 2);
            $t->text('alasan');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
            $t->unique(['grup_id', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_fundings');
        Schema::dropIfExists('budget_funding_mutex');
    }
};
