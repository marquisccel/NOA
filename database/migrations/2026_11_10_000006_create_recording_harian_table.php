<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recording_harian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siklus_id')->constrained('siklus')->cascadeOnDelete();
            $table->date('tanggal');
            $table->unsignedSmallInteger('hari_ke');
            $table->decimal('pakan_masuk_sak', 8, 2)->default(0);
            $table->decimal('pakan_pakai_sak', 8, 2)->default(0);
            $table->decimal('pakan_sisa_sak', 8, 2)->nullable();
            $table->unsignedInteger('mati')->default(0);
            $table->unsignedInteger('afkir')->default(0);
            // sebab afkir — daftar nilai di App\Models\RecordingHarian, bukan enum.
            $table->string('sebab_afkir', 30)->nullable();
            // R3: deplesi harian = mati + afkir, satu sumber kebenaran.
            $table->unsignedInteger('deplesi_harian')->storedAs('mati + afkir');
            $table->decimal('bobot_aktual_gram', 8, 2)->nullable();
            $table->decimal('suhu_pagi', 4, 1)->nullable();
            $table->decimal('suhu_sore', 4, 1)->nullable();
            $table->text('ovk_pagi')->nullable();
            $table->text('ovk_sore')->nullable();
            $table->boolean('diparaf_ppl')->default(false);
            $table->date('tanggal_paraf')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('ALTER TABLE recording_harian ADD CONSTRAINT recording_harian_hari_ke_check CHECK (hari_ke >= 1)');
        DB::statement('ALTER TABLE recording_harian ADD CONSTRAINT recording_harian_pakan_check CHECK (pakan_masuk_sak >= 0 AND pakan_pakai_sak >= 0)');
        // R1: satu entri per hari umur (soft delete tidak ikut memblokir entri ulang).
        DB::statement('CREATE UNIQUE INDEX recording_harian_siklus_hari_ke_unique ON recording_harian (siklus_id, hari_ke) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('recording_harian');
    }
};
