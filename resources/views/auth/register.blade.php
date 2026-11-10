<x-guest-layout title="Daftar">
    <div class="space-y-2">
        <h1 class="font-heading text-[26px] font-semibold tracking-[-0.4px]">Daftar sebagai Peternak</h1>
        <p class="text-sm leading-[21px] text-ink-secondary">Pendaftaran mandiri hanya untuk Peternak Plasma. Akun ABK dibuat oleh Peternak.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-6" x-data="{ memproses: false }" @submit="memproses = true">
        @csrf

        <div class="space-y-4">
            <x-field name="name" label="Nama lengkap" placeholder="Nama sesuai identitas" required autofocus autocomplete="name" />
            <x-field name="email" label="Email" type="email" icon="mail" placeholder="nama@email.com" required autocomplete="username" />
            <x-field name="password" label="Kata sandi" type="password" icon="lock" placeholder="Minimal 8 karakter, huruf dan angka" required autocomplete="new-password" />
            <x-field name="password_confirmation" label="Konfirmasi kata sandi" type="password" icon="lock" placeholder="Ulangi kata sandi" required autocomplete="new-password" />
        </div>

        <x-button class="w-full" x-bind:disabled="memproses">Buat akun</x-button>
    </form>

    <p class="text-center text-[13px] text-ink-secondary">
        Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-ink-brand hover:underline">Masuk</a>
    </p>
</x-guest-layout>
