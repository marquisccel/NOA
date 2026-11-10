<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kontrak_siklus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siklus_id')->unique()->constrained('siklus')->cascadeOnDelete();
            $table->decimal('harga_doc', 15, 2)->nullable();
            $table->decimal('harga_pakan_sak', 15, 2)->nullable();
            $table->decimal('harga_ovk', 15, 2)->nullable();
            $table->decimal('harga_kontrak_ayam', 15, 2)->nullable();
            // Skema bonus bisa diaktifkan terpisah (F1.4): bukan nilai tetap.
            $table->boolean('bonus_fcr')->default(false);
            $table->boolean('bonus_deplesi')->default(false);
            $table->boolean('bonus_ip')->default(false);
            $table->boolean('bonus_harga_pasar')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kontrak_siklus');
    }
};
