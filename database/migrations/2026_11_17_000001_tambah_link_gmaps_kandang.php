<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link Google Maps kandang (opsional). peta_query adalah hasil olahan link itu (koordinat atau nama tempat)
     * untuk peta sematan di detail kandang, diisi saat disimpan agar halaman detail tidak perlu memanggil layanan luar.
     */
    public function up(): void
    {
        Schema::table('kandang', function (Blueprint $table) {
            $table->string('link_gmaps', 2048)->nullable()->after('alamat');
            $table->string('peta_query', 500)->nullable()->after('link_gmaps');
        });
    }

    public function down(): void
    {
        Schema::table('kandang', function (Blueprint $table) {
            $table->dropColumn(['link_gmaps', 'peta_query']);
        });
    }
};
