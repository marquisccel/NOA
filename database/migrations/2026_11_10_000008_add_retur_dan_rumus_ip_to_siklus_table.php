<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siklus', function (Blueprint $table) {
            // Pakan sisa/retur di akhir siklus: pengurang total pakan terpakai agar FCR tidak melar.
            $table->decimal('pakan_retur_sak', 8, 2)->default(0)->after('bobot_sak_kg');
            // Rumus IP berbeda antar perusahaan inti: disimpan sebagai pengaturan per siklus, bukan konstanta.
            // Daftar nama rumus di App\Services\PerformanceCalculator::RUMUS_IP (bukan enum).
            $table->string('rumus_ip', 30)->default('standar')->after('pakan_retur_sak');
        });

        DB::statement('ALTER TABLE siklus ADD CONSTRAINT siklus_pakan_retur_check CHECK (pakan_retur_sak >= 0)');
    }

    public function down(): void
    {
        Schema::table('siklus', function (Blueprint $table) {
            $table->dropColumn(['pakan_retur_sak', 'rumus_ip']);
        });
    }
};
