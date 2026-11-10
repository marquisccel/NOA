@php
    // Gagal masuk ditampilkan sebagai alert di atas formulir; galat format tetap di bawah isian.
    $gagalMasuk = $errors->first('email') === __('auth.failed');
@endphp

<x-guest-layout title="Masuk">
    <div class="space-y-2">
        <h1 class="font-heading text-[26px] font-semibold tracking-[-0.4px]">Masuk ke NOA</h1>
        <p class="text-sm leading-[21px] text-ink-secondary">Satu pintu masuk untuk Peternak, ABK, dan Pengawas Inti. Akses menyesuaikan peran akun Anda.</p>
    </div>

    @if (session('status'))
        <x-alert variant="success">{{ session('status') }}</x-alert>
    @endif
    @if ($gagalMasuk)
        <x-alert variant="error">{{ $errors->first('email') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-6" x-data="{ memproses: false }" @submit="memproses = true">
        @csrf

        <div class="space-y-4">
            {{-- Pesan gagal-masuk sudah ada di alert; field hanya menandai merah. --}}
            <x-field name="email" label="Email" type="email" icon="mail" placeholder="nama@email.com" required autofocus autocomplete="username" :senyap="$gagalMasuk" />

            <x-field name="password" label="Kata sandi" type="password" icon="lock" placeholder="Masukkan kata sandi" required autocomplete="current-password">
                <x-slot name="aux">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-[13px] font-semibold text-ink-brand hover:underline">Lupa kata sandi?</a>
                    @endif
                </x-slot>
            </x-field>

            <label for="remember_me" class="flex min-h-11 items-center gap-2.5 text-[13px] text-ink-secondary">
                <input id="remember_me" type="checkbox" name="remember" class="size-[18px] rounded-[5px] border-line-control text-brand focus:ring-brand">
                Ingat saya di perangkat ini
            </label>
        </div>

        <x-button class="w-full" x-bind:disabled="memproses">
            <span :class="memproses ? 'hidden' : ''" class="inline-flex items-center gap-2"><x-icon name="arrow-right" class="size-4" /> Masuk</span>
            <span x-cloak :class="memproses ? 'inline-flex' : 'hidden'" class="items-center gap-2"><x-icon name="loader-circle" class="size-4 animate-spin" /> Memproses…</span>
        </x-button>
    </form>

    @if (Route::has('register'))
        <p class="text-center text-[13px] text-ink-secondary">
            Peternak baru? <a href="{{ route('register') }}" class="font-semibold text-ink-brand hover:underline">Daftar akun</a>
        </p>
    @endif
</x-guest-layout>
