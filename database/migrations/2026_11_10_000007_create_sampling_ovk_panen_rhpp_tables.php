<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sampling_bobot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siklus_id')->constrained('siklus')->cascadeOnDelete();
            $table->date('tanggal');
            $table->unsignedSmallInteger('hari_ke');
            $table->unsignedInteger('jumlah_sampel');
            $table->decimal('total_bobot_kg', 8, 3);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['siklus_id', 'hari_ke']);
        });

        Schema::create('pemberian_ovk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siklus_id')->constrained('siklus')->cascadeOnDelete();
            $table->date('tanggal');
            // obat | vaksin | kimia — daftar nilai di App\Models\PemberianOvk, bukan enum.
            $table->string('jenis', 20);
            $table->string('nama_produk');
            $table->string('dosis')->nullable();
            $table->decimal('biaya', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['siklus_id', 'tanggal']);
        });

        Schema::create('panen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siklus_id')->constrained('siklus')->cascadeOnDelete();
            $table->date('tanggal');
            $table->unsignedSmallInteger('umur_hari');
            $table->unsignedInteger('jumlah_ekor');
            $table->decimal('tonase_kg', 10, 2);
            $table->decimal('harga_per_kg', 15, 2)->nullable();
            $table->string('nomor_btpa')->nullable();
            $table->string('foto_btpa')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['siklus_id', 'tanggal']);
        });

        Schema::create('rhpp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siklus_id')->unique()->constrained('siklus')->cascadeOnDelete();
            $table->date('tanggal_terbit');
            $table->decimal('total_pakan_kg', 10, 2);
            $table->decimal('pakan_retur_sak', 8, 2)->default(0);
            $table->unsignedInteger('total_ekor');
            $table->decimal('total_tonase', 10, 2);
            $table->decimal('abw', 6, 3);
            $table->decimal('umur_panen', 5, 2);
            $table->decimal('deplesi_persen', 5, 2);
            $table->decimal('fcr', 5, 3);
            $table->decimal('ip', 7, 2);
            $table->decimal('pendapatan', 15, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('ALTER TABLE sampling_bobot ADD CONSTRAINT sampling_bobot_check CHECK (jumlah_sampel > 0 AND total_bobot_kg > 0)');
        DB::statement("ALTER TABLE pemberian_ovk ADD CONSTRAINT pemberian_ovk_jenis_check CHECK (jenis IN ('obat', 'vaksin', 'kimia'))");
        DB::statement('ALTER TABLE panen ADD CONSTRAINT panen_check CHECK (jumlah_ekor > 0 AND tonase_kg > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('rhpp');
        Schema::dropIfExists('panen');
        Schema::dropIfExists('pemberian_ovk');
        Schema::dropIfExists('sampling_bobot');
    }
};
