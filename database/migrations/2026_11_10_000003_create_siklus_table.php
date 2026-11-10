<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siklus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kandang_id')->constrained('kandang')->cascadeOnDelete();
            $table->date('tanggal_masuk');
            $table->unsignedInteger('populasi');
            $table->string('strain_doc', 50)->nullable();
            $table->string('jenis_pakan', 50)->nullable();
            $table->string('nama_ppl')->nullable();
            $table->decimal('bobot_sak_kg', 6, 2)->default(50);
            $table->string('nama_inti')->nullable();
            $table->date('tanggal_panen')->nullable();
            $table->date('tanggal_rechick')->nullable();
            // berjalan | selesai | dihentikan — daftar nilai di App\Models\Siklus, bukan enum.
            $table->string('status', 20)->default('berjalan');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kandang_id', 'status']);
        });

        DB::statement("ALTER TABLE siklus ADD CONSTRAINT siklus_status_check CHECK (status IN ('berjalan', 'selesai', 'dihentikan'))");
        DB::statement('ALTER TABLE siklus ADD CONSTRAINT siklus_populasi_check CHECK (populasi > 0)');
        DB::statement('ALTER TABLE siklus ADD CONSTRAINT siklus_bobot_sak_check CHECK (bobot_sak_kg > 0)');
        // R2: satu kandang hanya boleh punya satu siklus berjalan.
        DB::statement("CREATE UNIQUE INDEX siklus_satu_berjalan_per_kandang ON siklus (kandang_id) WHERE status = 'berjalan' AND deleted_at IS NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('siklus');
    }
};
