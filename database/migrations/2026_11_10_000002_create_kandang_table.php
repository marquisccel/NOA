<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kandang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nama_farm');
            $table->text('alamat')->nullable();
            $table->unsignedInteger('kapasitas');
            $table->string('tipe', 30)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('ALTER TABLE kandang ADD CONSTRAINT kandang_kapasitas_check CHECK (kapasitas > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('kandang');
    }
};
