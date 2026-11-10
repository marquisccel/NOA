<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Status yang masih menahan kandang dari siklus baru (R2). */
    private const TERBUKA = "'berjalan', 'panen_selesai', 'menunggu_rhpp', 'rekonsiliasi'";

    public function up(): void
    {
        // Daftar status 7 tahap dari desain. Nilai 'selesai' dipertahankan dan tampil sebagai "Ditutup".
        DB::statement('ALTER TABLE siklus DROP CONSTRAINT siklus_status_check');
        DB::statement("ALTER TABLE siklus ADD CONSTRAINT siklus_status_check CHECK (status IN ('berjalan', 'panen_selesai', 'menunggu_rhpp', 'rekonsiliasi', 'selesai', 'ditutup_tanpa_rhpp', 'dihentikan'))");

        // R2: satu kandang hanya boleh punya satu siklus terbuka (bukan hanya "berjalan").
        DB::statement('DROP INDEX siklus_satu_berjalan_per_kandang');
        DB::statement('CREATE UNIQUE INDEX siklus_satu_terbuka_per_kandang ON siklus (kandang_id) WHERE status IN ('.self::TERBUKA.') AND deleted_at IS NULL');

        Schema::table('siklus', function (Blueprint $table) {
            $table->date('ditutup_pada')->nullable()->after('tanggal_rechick');
        });

        // Nilai bonus per kg sesuai kontrak (hanya disimpan; NOA tidak menghitung rupiah bonus).
        Schema::table('kontrak_siklus', function (Blueprint $table) {
            foreach (['fcr', 'deplesi', 'ip', 'harga_pasar'] as $jenis) {
                $table->decimal("nilai_bonus_{$jenis}", 15, 2)->nullable()->after("bonus_{$jenis}");
            }
        });

        Schema::table('standar_harian', function (Blueprint $table) {
            $table->decimal('deplesi_std_persen', 5, 2)->nullable()->after('feed_intake_std_gram');
        });
    }

    public function down(): void
    {
        Schema::table('standar_harian', fn (Blueprint $t) => $t->dropColumn('deplesi_std_persen'));
        Schema::table('kontrak_siklus', fn (Blueprint $t) => $t->dropColumn(['nilai_bonus_fcr', 'nilai_bonus_deplesi', 'nilai_bonus_ip', 'nilai_bonus_harga_pasar']));
        Schema::table('siklus', fn (Blueprint $t) => $t->dropColumn('ditutup_pada'));

        DB::statement('DROP INDEX siklus_satu_terbuka_per_kandang');
        DB::statement("CREATE UNIQUE INDEX siklus_satu_berjalan_per_kandang ON siklus (kandang_id) WHERE status = 'berjalan' AND deleted_at IS NULL");
        DB::statement('ALTER TABLE siklus DROP CONSTRAINT siklus_status_check');
        DB::statement("ALTER TABLE siklus ADD CONSTRAINT siklus_status_check CHECK (status IN ('berjalan', 'selesai', 'dihentikan'))");
    }
};
