@php
    $user = auth()->user();
    $abk = $user?->isAbk();
    $pengawas = $user?->isPengawas();
    $judul = $pengawas ? 'Halaman ini tidak tersedia untuk Pengawas Inti' : ($abk ? 'Halaman ini tidak tersedia untuk akun ABK' : 'Halaman ini tidak tersedia untuk akun Anda');
    $teks = $pengawas
        ? 'Akun Pengawas hanya dapat melihat recording, sampling, OVK, dan panen pada siklus yang dibagikan peternak. Hubungi peternak bila membutuhkan akses lain.'
        : ($abk
        ? 'Akun Anda hanya dapat membuka siklus yang ditugaskan dan Recording Harian. Hubungi pemilik kandang bila membutuhkan akses lain.'
        : 'Anda tidak punya izin untuk membuka halaman ini. Kembali ke dashboard atau hubungi pemilik akun.');
    $tujuan = $abk && $user->siklus_id ? route('recording.index', $user->siklus_id) : route('dashboard');
    $tombol = $abk && $user->siklus_id ? 'Ke Recording Harian' : 'Ke Dashboard';
@endphp

@if ($user)
    <x-app-layout title="Akses ditolak" :crumbs="[['Beranda', route('dashboard')], ['Akses ditolak']]">
        <x-page lebar="max-w-xl">
            <div class="flex flex-col items-center gap-3 rounded-[16px] border border-line bg-surface px-6 py-14 text-center">
                <span class="grid size-12 place-items-center rounded-full bg-danger-soft text-danger-text"><x-icon name="shield-alert" class="size-6" /></span>
                <p class="text-[11px] font-bold uppercase tracking-[1.4px] text-danger-text">Kode 403</p>
                <h1 class="font-heading text-xl font-semibold">{{ $judul }}</h1>
                <p class="max-w-md text-sm leading-[21px] text-ink-secondary">{{ $teks }}</p>
                <x-button :href="$tujuan" class="mt-2">{{ $tombol }}</x-button>
            </div>
        </x-page>
    </x-app-layout>
@else
    <x-guest-layout title="Akses ditolak">
        <div class="space-y-2">
            <p class="text-[11px] font-bold uppercase tracking-[1.4px] text-danger-text">Kode 403</p>
            <h1 class="font-heading text-[26px] font-semibold tracking-[-0.4px]">Akses ditolak</h1>
            <p class="text-sm leading-[21px] text-ink-secondary">Anda tidak punya izin untuk membuka halaman ini.</p>
        </div>
        <x-button :href="route('login')" class="w-full">Ke halaman masuk</x-button>
    </x-guest-layout>
@endif
