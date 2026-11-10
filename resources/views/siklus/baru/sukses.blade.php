@php use App\Support\Format as F; @endphp

<x-app-layout title="Siklus dibuka" :crumbs="[['Kandang & Siklus', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['Siklus '.$nomor]]">
    <x-page lebar="max-w-xl">
        <div class="space-y-5 rounded-[16px] border border-line bg-surface p-6 text-center sm:p-8">
            <span class="mx-auto grid size-14 place-items-center rounded-full bg-success-soft text-success-text"><x-icon name="circle-check" class="size-7" /></span>
            <div class="space-y-1.5">
                <h1 class="font-heading text-xl font-semibold">Siklus {{ $nomor }} di {{ $siklus->kandang->nama_farm }} berhasil dibuka</h1>
                <p class="text-sm leading-[21px] text-ink-secondary">DOC masuk {{ $siklus->tanggal_masuk->translatedFormat('j M Y') }} menjadi hari ke-1. Recording harian pertama dapat diisi mulai tanggal itu.</p>
            </div>

            <dl class="grid grid-cols-2 gap-3 rounded-md bg-page p-4 text-left text-[13px]">
                <div><dt class="text-ink-muted">Status</dt><dd class="mt-0.5"><x-status-siklus :siklus="$siklus" /></dd></div>
                <div><dt class="text-ink-muted">Populasi awal</dt><dd class="mt-0.5 font-semibold">{{ F::angka($siklus->populasi, 0) }} ekor</dd></div>
                <div><dt class="text-ink-muted">Strain</dt><dd class="mt-0.5 font-semibold">{{ $siklus->strain_doc ?: '–' }}</dd></div>
                <div><dt class="text-ink-muted">Standar</dt><dd class="mt-0.5 font-semibold">{{ $standar ? $standar.' baris' : 'Belum ada' }}</dd></div>
            </dl>

            <div class="flex flex-col gap-2.5 sm:flex-row sm:justify-center">
                @unless ($standar)<x-button variant="secondary" :href="route('siklus.standar', $siklus)">Unggah standar</x-button>@endunless
                <x-button :href="route('siklus.show', $siklus)" id="ke-ringkasan">Ke Ringkasan Siklus</x-button>
            </div>
            <p class="text-xs text-ink-muted">Anda akan diarahkan ke Ringkasan Siklus {{ $nomor }} dalam 5 detik.</p>
        </div>
    </x-page>
    <meta http-equiv="refresh" content="5;url={{ route('siklus.show', $siklus) }}">
</x-app-layout>
