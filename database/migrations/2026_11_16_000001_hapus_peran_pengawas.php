<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Peran Pengawas Inti ditiadakan: NOA hanya untuk plasma (peternak) dan ABK yang dibuatnya. */
    public function up(): void
    {
        Schema::dropIfExists('akses_pengawas');

        DB::table('users')->where('role', 'pengawas')->delete();
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('peternak', 'abk'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('peternak', 'abk', 'pengawas'))");
    }
};
