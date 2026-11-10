@php $terkirim = session('terkirim'); @endphp

<x-guest-layout title="Lupa kata sandi">
    @if ($terkirim)
        {{-- Pesan sama untuk email terdaftar maupun tidak, agar tidak membocorkan siapa saja yang punya akun. --}}
        <div class="space-y-2">
            <span class="grid size-11 place-items-center rounded-md bg-brand-soft text-ink-brand"><x-icon name="mail" /></span>
            <h1 class="pt-2 font-heading text-[26px] font-semibold tracking-[-0.4px]">Cek email Anda</h1>
            <p class="text-sm leading-[21px] text-ink-secondary">
                Jika email <strong class="font-semibold text-ink">{{ $terkirim }}</strong> terdaftar, instruksi reset kata sandi telah dikirim.
            </p>
        </div>

        <x-alert variant="info">Tidak menerima email? Periksa folder spam atau kirim ulang permintaan.</x-alert>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="email" value="{{ $terkirim }}">
            <x-button variant="secondary" class="w-full">Kirim ulang</x-button>
            <x-button variant="ghost" :href="route('login')" class="w-full">Kembali ke halaman masuk</x-button>
        </form>
    @else
        <div class="space-y-2">
            <h1 class="font-heading text-[26px] font-semibold tracking-[-0.4px]">Lupa kata sandi?</h1>
            <p class="text-sm leading-[21px] text-ink-secondary">Masukkan email terdaftar. Kami akan mengirim tautan untuk mengatur ulang kata sandi.</p>
        </div>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
            @csrf
            <x-field name="email" label="Email terdaftar" type="email" icon="mail" placeholder="nama@email.com" required autofocus />
            <x-button class="w-full">Kirim tautan reset</x-button>
        </form>

        <p class="text-center text-[13px]">
            <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center gap-1.5 font-semibold text-ink-brand hover:underline"><x-icon name="arrow-left" class="size-4" /> Kembali ke halaman masuk</a>
        </p>
    @endif
</x-guest-layout>
