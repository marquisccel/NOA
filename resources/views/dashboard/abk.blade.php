@php use App\Support\Format as F; @endphp

<x-app-layout title="Periode Ditugaskan" :crumbs="[['Beranda', route('dashboard')], ['Periode Ditugaskan']]">
    <x-page>
        {{-- Sapaan ABK: ringkas, satu tujuan (isi catatan harian). Maskot menemani. --}}
        <section class="kartu-hero mt-2 flex flex-col justify-center gap-5 p-6 sm:p-8 lg:mt-12 lg:min-h-[250px] lg:pr-[300px]">
            <div class="space-y-2.5">
                <p class="text-[11px] font-bold uppercase tracking-[1.4px] text-ink-brand">Akses ABK</p>
                <h1 class="font-heading text-[clamp(1.8rem,3.2vw,2.6rem)] font-semibold leading-[1.06] tracking-[-0.035em]">Periode <span class="text-ink-brand">Ditugaskan</span></h1>
                <p class="max-w-lg text-[15px] leading-[24px] text-ink-secondary">Akses ABK: pakan, ayam mati, dan ayam afkir pada periode yang ditugaskan.</p>
            </div>
            @if ($siklus)
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="kaca-chip inline-flex items-center gap-2 rounded-full px-3.5 py-2 text-[13px] font-semibold"><x-icon name="warehouse" class="size-4 text-ink-brand" /> {{ $siklus->kandang->nama_farm }}</span>
                    <span class="kaca-chip inline-flex items-center gap-2 rounded-full px-3.5 py-2 text-[13px] font-semibold"><x-icon name="calendar-plus" class="size-4 text-ink-brand" /> Hari ke-{{ $siklus->umurHari() }}</span>
                </div>
            @endif
            <div class="sorot-ayam absolute -top-12 bottom-0 right-6 hidden w-[240px] lg:block"><x-ayam class="inset-y-0" /></div>
        </section>

        @if (! $siklus)
            <x-empty-state icon="clipboard-list" title="Belum ada periode yang ditugaskan"
                text="Pemilik kandang belum menugaskan periode untuk akun Anda, atau periodenya sudah ditutup. Hubungi pemilik untuk meminta penugasan." />
        @else
            {{-- Hanya konteks periode; tanpa kontrak, biaya, atau rekonsiliasi (R13). --}}
            <article class="kartu max-w-xl space-y-4 p-5 sm:p-6">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="chip-ikon size-12 shrink-0" data-nada="merek"><x-icon name="warehouse" class="size-6" /></span>
                        <div class="min-w-0">
                            <h2 class="font-heading text-xl font-semibold">Periode {{ $nomor }}</h2>
                            <p class="truncate text-[13px] text-ink-secondary">{{ $siklus->kandang->nama_farm }}</p>
                        </div>
                    </div>
                    <x-status-siklus :siklus="$siklus" />
                </div>
                <dl class="space-y-2.5 rounded-[16px] bg-page p-4 text-[13px]">
                    <div class="flex justify-between gap-3"><dt class="text-ink-secondary">DOC masuk</dt><dd class="font-bold">{{ $siklus->tanggal_masuk->translatedFormat('j M Y') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-ink-secondary">Umur ayam</dt><dd class="font-bold">Hari ke-{{ $siklus->umurHari() }}</dd></div>
                </dl>
                <x-button :href="route('recording.index', $siklus)" class="w-full">Buka Recording Harian <x-icon name="arrow-right" class="size-4" /></x-button>
            </article>
        @endif
    </x-page>
</x-app-layout>
