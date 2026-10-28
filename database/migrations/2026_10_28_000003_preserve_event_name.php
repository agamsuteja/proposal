<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('proposal', 'judul_acara')) {
            Schema::table('proposal', fn (Blueprint $t) => $t->string('judul_acara', 150)->nullable());
        }
    }

    public function down(): void
    {
        Schema::table('proposal', fn (Blueprint $t) => $t->dropColumn('judul_acara'));
    }
};
