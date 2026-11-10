<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Syarat kata sandi: minimal 8 karakter, memuat huruf dan angka. Berlaku di pendaftaran,
        // atur ulang, ganti kata sandi, dan pembuatan akun ABK; daftar syarat di layar mengikuti ini.
        Password::defaults(fn () => Password::min(8)->letters()->numbers());
    }
}
