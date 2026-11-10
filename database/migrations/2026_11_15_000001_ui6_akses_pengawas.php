<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Satu akses = satu siklus, hanya-baca. Mencabut mengisi dicabut_pada; riwayat tetap tersimpan. */
    public function up(): void
    {
        Schema::create('akses_pengawas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengawas_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('siklus_id')->constrained('siklus')->cascadeOnDelete();
            $table->foreignId('peternak_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('dicabut_pada')->nullable();
            $table->timestamps();

            $table->index(['siklus_id']);
        });

        // Satu pengawas hanya punya satu akses aktif per siklus.
        DB::statement('CREATE UNIQUE INDEX akses_pengawas_aktif_unik ON akses_pengawas (pengawas_id, siklus_id) WHERE dicabut_pada IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('akses_pengawas');
    }
};
