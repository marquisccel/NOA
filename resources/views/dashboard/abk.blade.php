@php use App\Support\Format as F; @endphp

<x-app-layout title="Siklus Ditugaskan" :crumbs="[['Beranda', route('dashboard')], ['Siklus Ditugaskan']]">
    <x-page>
        <x-page-header title="Siklus Ditugaskan" subtitle="Akses ABK: pakan, ayam mati, dan ayam afkir pada siklus yang ditugaskan." />

        @if (! $siklus)
            <x-empty-state icon="clipboard-list" title="Belum ada siklus yang ditugaskan"
                text="Pemilik kandang belum menugaskan siklus untuk akun Anda, atau siklusnya sudah ditutup. Hubungi pemilik untuk meminta penugasan." />
        @else
            {{-- Hanya konteks siklus; tanpa kontrak, biaya, atau rekonsiliasi (R13). --}}
            <article class="max-w-xl space-y-4 rounded-[16px] border border-line bg-surface p-5">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h2 class="font-heading text-lg font-semibold">Siklus {{ $nomor }}</h2>
                        <p class="mt-1 flex items-center gap-1.5 text-[13px] text-ink-secondary"><x-icon name="warehouse" class="size-3.5" /> {{ $siklus->kandang->nama_farm }}</p>
                    </div>
                    <x-status-siklus :siklus="$siklus" />
                </div>
                <dl class="space-y-2.5 rounded-md bg-page p-3.5 text-[13px]">
                    <div class="flex justify-between gap-3"><dt class="text-ink-secondary">DOC masuk</dt><dd class="font-bold">{{ $siklus->tanggal_masuk->translatedFormat('j M Y') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-ink-secondary">Umur ayam</dt><dd class="font-bold">Hari ke-{{ $siklus->umurHari() }}</dd></div>
                </dl>
                <x-button :href="route('recording.index', $siklus)" class="w-full">Buka Recording Harian <x-icon name="arrow-right" class="size-4" /></x-button>
            </article>
        @endif
    </x-page>
</x-app-layout>
