@php $user = auth()->user(); @endphp

<x-app-layout title="Pengaturan" :crumbs="[['Beranda', route('dashboard')], ['Pengaturan']]">
    <x-page lebar="max-w-3xl">
        <x-page-header title="Pengaturan" subtitle="Profil dan keamanan akun" />

        <section class="space-y-5 rounded-[16px] border border-line bg-surface p-5 sm:p-6" aria-labelledby="judul-profil">
            <h2 id="judul-profil" class="text-base font-bold">Profil</h2>

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf @method('PATCH')
                <x-field name="name" label="Nama lengkap *" :value="$user->name" required autocomplete="name" />
                <x-field name="email" label="Email *" type="email" icon="mail" :value="$user->email" required autocomplete="username" />

                <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                    <span class="inline-flex items-center gap-2 text-[13px] text-ink-secondary">Peran akun <x-badge variant="brand">{{ $user->labelPeran() }}</x-badge></span>
                    <x-button>Simpan profil</x-button>
                </div>
            </form>
        </section>

        <section class="space-y-5 rounded-[16px] border border-line bg-surface p-5 sm:p-6" aria-labelledby="judul-sandi">
            <h2 id="judul-sandi" class="text-base font-bold">Ubah kata sandi</h2>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4"
                  x-data="{ sandi: '', ulang: '',
                      get panjang() { return this.sandi.length >= 8 },
                      get hurufAngka() { return /[A-Za-z]/.test(this.sandi) && /\d/.test(this.sandi) },
                      get sama() { return this.ulang === '' || this.ulang === this.sandi } }">
                @csrf @method('PUT')
                <x-field name="current_password" bag="updatePassword" label="Kata sandi saat ini *" type="password" icon="lock" required autocomplete="current-password" />
                <x-field name="password" bag="updatePassword" label="Kata sandi baru *" type="password" icon="lock" required autocomplete="new-password" x-model="sandi" />
                <x-field name="password_confirmation" bag="updatePassword" label="Konfirmasi kata sandi baru *" type="password" icon="lock" required autocomplete="new-password" x-model="ulang" />
                <p x-bind:hidden="sama" hidden class="text-xs text-danger-text">Tidak sama dengan kata sandi baru.</p>

                <ul class="space-y-1.5 text-[13px]" aria-label="Syarat kata sandi">
                    @foreach (['panjang' => 'Minimal 8 karakter', 'hurufAngka' => 'Mengandung huruf dan angka'] as $kunci => $teks)
                        <li class="flex items-center gap-2" :class="{{ $kunci }} ? 'text-success-text' : 'text-ink-secondary'">
                            <x-icon name="circle-check" class="size-4" x-bind:hidden="! {{ $kunci }}" hidden />
                            <x-icon name="minus" class="size-4" x-bind:hidden="{{ $kunci }}" />
                            {{ $teks }}<span class="sr-only" x-text="{{ $kunci }} ? ' (terpenuhi)' : ' (belum terpenuhi)'"></span>
                        </li>
                    @endforeach
                </ul>

                <div class="flex justify-end pt-1"><x-button>Ubah kata sandi</x-button></div>
            </form>
        </section>
    </x-page>
</x-app-layout>
