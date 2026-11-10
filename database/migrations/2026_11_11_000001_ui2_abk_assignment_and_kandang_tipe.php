<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Akun nonaktif tidak bisa masuk; riwayat yang dicatatnya tetap tersimpan.
            $table->boolean('aktif')->default(true)->after('peternak_id');
            // ABK ditugaskan ke satu siklus (kandangnya mengikuti siklus itu).
            $table->foreignId('siklus_id')->nullable()->after('aktif')->constrained('siklus')->nullOnDelete();
        });

        // Hanya ABK yang punya penugasan siklus.
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_siklus_hanya_abk_check CHECK (role = 'abk' OR siklus_id IS NULL)");

        // Tipe kandang: dua pilihan tetap. Nilai lama dipetakan; yang tak dikenal dikosongkan
        // (formulir meminta peternak memilih saat kandangnya diubah).
        DB::statement("UPDATE kandang SET tipe = CASE
            WHEN lower(replace(tipe, ' ', '_')) IN ('open_house', 'closed_house') THEN lower(replace(tipe, ' ', '_'))
            ELSE NULL END");
        DB::statement("ALTER TABLE kandang ADD CONSTRAINT kandang_tipe_check CHECK (tipe IS NULL OR tipe IN ('open_house', 'closed_house'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE kandang DROP CONSTRAINT IF EXISTS kandang_tipe_check');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_siklus_hanya_abk_check');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('siklus_id');
            $table->dropColumn('aktif');
        });
    }
};
