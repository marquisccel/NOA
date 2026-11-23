<?php

namespace App\Providers;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Memo nomor siklus per request (lihat Siklus::nomorMap).
        $this->app->scoped('siklus.nomor', fn () => new \ArrayObject);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Syarat kata sandi: minimal 8 karakter, memuat huruf dan angka. Berlaku di pendaftaran,
        // atur ulang, ganti kata sandi, dan pembuatan akun ABK; daftar syarat di layar mengikuti ini.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Memo nomor siklus hanya berlaku dalam satu request.
        Event::listen(RequestHandled::class, fn () => $this->app->make('siklus.nomor')->exchangeArray([]));

        Password::defaults(fn () => Password::min(8)->letters()->numbers());
    }
}
