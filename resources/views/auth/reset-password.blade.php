{{-- Aturan ini sama dengan yang diperiksa server (AppServiceProvider::Password::defaults). --}}
<x-guest-layout title="Kata sandi baru">
    <div class="space-y-2">
        <h1 class="font-heading text-[26px] font-semibold tracking-[-0.4px]">Buat kata sandi baru</h1>
        <p class="text-sm leading-[21px] text-ink-secondary">Gunakan kata sandi yang kuat dan belum pernah dipakai di akun ini.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-6"
          x-data="{ sandi: '', ulang: '',
              get panjang() { return this.sandi.length >= 8 },
              get huruf() { return /[A-Za-z]/.test(this.sandi) },
              get angka() { return /\d/.test(this.sandi) },
              get sama() { return this.ulang === '' || this.ulang === this.sandi } }">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <input type="hidden" name="email" value="{{ old('email', $request->email) }}">
        <x-input-error :messages="$errors->get('email')" />

        <div class="space-y-4">
            <x-field name="password" label="Kata sandi baru" type="password" icon="lock" required autofocus autocomplete="new-password" x-model="sandi" />
            <x-field name="password_confirmation" label="Konfirmasi kata sandi" type="password" icon="lock" required autocomplete="new-password" x-model="ulang" />
            <p x-bind:hidden="sama" hidden class="text-xs text-danger-text">Konfirmasi tidak sama dengan kata sandi baru.</p>
        </div>

        <ul class="space-y-1.5 text-[13px]" aria-label="Syarat kata sandi">
            @foreach (['panjang' => 'Minimal 8 karakter', 'huruf' => 'Mengandung huruf', 'angka' => 'Mengandung angka'] as $kunci => $teks)
                <li class="flex items-center gap-2" :class="{{ $kunci }} ? 'text-success-text' : 'text-ink-secondary'">
                    <x-icon name="circle-check" class="size-4" x-bind:hidden="! {{ $kunci }}" hidden />
                    <x-icon name="minus" class="size-4" x-bind:hidden="{{ $kunci }}" />
                    {{ $teks }}<span class="sr-only" x-text="{{ $kunci }} ? ' (terpenuhi)' : ' (belum terpenuhi)'"></span>
                </li>
            @endforeach
        </ul>

        <x-button class="w-full">Simpan kata sandi baru</x-button>
    </form>
</x-guest-layout>
