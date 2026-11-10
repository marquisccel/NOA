<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Angka RHPP diketik apa adanya dari lembar inti; tidak semua inti mencetak semua kolom.
        Schema::table('rhpp', function (Blueprint $table) {
            $table->decimal('total_pakan_kg', 10, 2)->nullable()->change();
            $table->decimal('pakan_retur_sak', 8, 2)->nullable()->default(null)->change();
            $table->unsignedInteger('total_ekor')->nullable()->change();
            $table->decimal('total_tonase', 10, 2)->nullable()->change();
            $table->decimal('abw', 6, 3)->nullable()->change();
            $table->decimal('umur_panen', 5, 2)->nullable()->change();
            $table->decimal('deplesi_persen', 5, 2)->nullable()->change();
            $table->decimal('fcr', 5, 3)->nullable()->change();
            $table->decimal('ip', 7, 2)->nullable()->change();
        });

        Schema::table('siklus', function (Blueprint $table) {
            // Ambang selisih wajar per metrik yang menimpa bawaan di config/noa.php (F6.3).
            $table->jsonb('ambang_rekonsiliasi')->nullable()->after('rumus_ip');
        });

        // Akun ABK dimiliki satu peternak (F8.2).
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('peternak_id')->nullable()->after('role')->constrained('users')->cascadeOnDelete();
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_abk_punya_peternak_check CHECK (role <> 'abk' OR peternak_id IS NOT NULL)");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_abk_punya_peternak_check');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('peternak_id');
        });

        Schema::table('siklus', function (Blueprint $table) {
            $table->dropColumn('ambang_rekonsiliasi');
        });
    }
};
