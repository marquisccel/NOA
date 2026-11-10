<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tiga tabel acuan disalin ke tiap siklus, bukan dirujuk dari tabel global.
        Schema::create('standar_harian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siklus_id')->constrained('siklus')->cascadeOnDelete();
            $table->unsignedSmallInteger('hari_ke');
            $table->decimal('pakan_std_sak', 8, 2);
            $table->decimal('bobot_std_gram', 8, 2);
            $table->decimal('fcr_std', 5, 3);
            $table->decimal('feed_intake_std_gram', 8, 2);
            $table->timestamps();

            $table->unique(['siklus_id', 'hari_ke']);
        });

        Schema::create('parameter_umur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siklus_id')->constrained('siklus')->cascadeOnDelete();
            $table->unsignedSmallInteger('umur_awal');
            $table->unsignedSmallInteger('umur_akhir');
            $table->decimal('suhu_min', 4, 1)->nullable();
            $table->decimal('suhu_maks', 4, 1)->nullable();
            $table->decimal('kepadatan_ekor_m2', 5, 1)->nullable();
            $table->unsignedSmallInteger('frekuensi_pakan')->nullable();
            $table->unsignedSmallInteger('jumlah_tma')->nullable();
            $table->unsignedSmallInteger('jumlah_tra')->nullable();
            $table->timestamps();

            $table->index('siklus_id');
        });

        Schema::create('jadwal_vaksin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siklus_id')->constrained('siklus')->cascadeOnDelete();
            $table->unsignedSmallInteger('hari_ke');
            $table->string('nama_vaksin');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['siklus_id', 'hari_ke']);
        });

        DB::statement('ALTER TABLE standar_harian ADD CONSTRAINT standar_harian_hari_ke_check CHECK (hari_ke >= 1)');
        DB::statement('ALTER TABLE parameter_umur ADD CONSTRAINT parameter_umur_rentang_check CHECK (umur_akhir >= umur_awal)');
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_vaksin');
        Schema::dropIfExists('parameter_umur');
        Schema::dropIfExists('standar_harian');
    }
};
