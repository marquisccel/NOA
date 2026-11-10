<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siklus', function (Blueprint $table) {
            $table->string('alasan_penutupan', 120)->nullable()->after('ditutup_pada');
            $table->text('keterangan_penutupan')->nullable()->after('alasan_penutupan');
        });

        // RHPP diketik apa adanya dari dokumen inti, termasuk biaya, bonus, dan hasil bersih (hanya untuk dibandingkan).
        Schema::table('rhpp', function (Blueprint $table) {
            $table->string('nomor_rhpp', 100)->nullable()->after('siklus_id');
            $table->string('lampiran', 255)->nullable()->after('tanggal_terbit');
            $table->unsignedInteger('populasi_doc')->nullable();
            $table->unsignedInteger('deplesi_ekor')->nullable();
            $table->decimal('pakan_terpakai_kg', 10, 2)->nullable();
            $table->decimal('biaya_doc', 15, 2)->nullable();
            $table->decimal('biaya_pakan', 15, 2)->nullable();
            $table->decimal('biaya_ovk', 15, 2)->nullable();
            $table->decimal('bonus', 15, 2)->nullable();
            $table->decimal('hasil_bersih', 15, 2)->nullable();
            // Keterangan selisih per baris, kunci baris => teks.
            $table->jsonb('catatan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rhpp', function (Blueprint $table) {
            $table->dropColumn(['nomor_rhpp', 'lampiran', 'populasi_doc', 'deplesi_ekor', 'pakan_terpakai_kg', 'biaya_doc', 'biaya_pakan', 'biaya_ovk', 'bonus', 'hasil_bersih', 'catatan']);
        });
        Schema::table('siklus', function (Blueprint $table) {
            $table->dropColumn(['alasan_penutupan', 'keterangan_penutupan']);
        });
    }
};
