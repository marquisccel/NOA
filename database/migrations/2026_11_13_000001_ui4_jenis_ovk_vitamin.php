<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Desain OVK membedakan Obat, Vitamin, Vaksin; "kimia" lama tetap sah agar data lama tidak rusak. */
    public function up(): void
    {
        DB::statement('ALTER TABLE pemberian_ovk DROP CONSTRAINT IF EXISTS pemberian_ovk_jenis_check');
        DB::statement("ALTER TABLE pemberian_ovk ADD CONSTRAINT pemberian_ovk_jenis_check CHECK (jenis IN ('obat', 'vitamin', 'vaksin', 'kimia'))");
    }

    public function down(): void
    {
        DB::statement("UPDATE pemberian_ovk SET jenis = 'obat' WHERE jenis = 'vitamin'");
        DB::statement('ALTER TABLE pemberian_ovk DROP CONSTRAINT IF EXISTS pemberian_ovk_jenis_check');
        DB::statement("ALTER TABLE pemberian_ovk ADD CONSTRAINT pemberian_ovk_jenis_check CHECK (jenis IN ('obat', 'vaksin', 'kimia'))");
    }
};
