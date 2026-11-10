@php $nama = $terbuka->kandang_id === $kandang->id ? 'Siklus '.$nomor.' · '.$kandang->nama_farm : ''; @endphp

<x-app-layout title="Tidak bisa membuka siklus" :crumbs="[['Kandang & Siklus', route('kandang.index')], [$kandang->nama_farm, route('kandang.show', $kandang)], ['Buka siklus baru']]">
    <x-page lebar="max-w-xl">
        <div class="space-y-5 rounded-[16px] border border-warning-border bg-surface p-6 sm:p-8">
            <span class="grid size-12 place-items-center rounded-full bg-warning-soft text-warning-text"><x-icon name="triangle-alert" class="size-6" /></span>
            <div class="space-y-1.5">
                <h1 class="font-heading text-xl font-semibold">{{ $kandang->nama_farm }} masih punya siklus yang belum ditutup</h1>
                <p class="text-sm leading-[21px] text-ink-secondary">Satu kandang hanya boleh memiliki satu siklus terbuka. Siklus berstatus Berjalan, Panen selesai, Menunggu RHPP, atau Rekonsiliasi harus ditutup dulu sebelum siklus baru dibuka.</p>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-line bg-page p-4">
                <div>
                    <p class="text-sm font-bold">{{ $nama }}</p>
                    <p class="mt-0.5 text-[13px] text-ink-secondary">
                        @if ($terbuka->status === \App\Models\Siklus::STATUS_MENUNGGU_RHPP && $terbuka->tanggal_panen)
                            Panen selesai {{ $terbuka->tanggal_panen->translatedFormat('j M Y') }}@if ($terbuka->nama_inti) · menunggu RHPP dari {{ $terbuka->nama_inti }}@endif
                        @else DOC masuk {{ $terbuka->tanggal_masuk->translatedFormat('j M Y') }} · Hari ke-{{ $terbuka->umurHari() }} @endif
                    </p>
                </div>
                <x-status-siklus :siklus="$terbuka" />
            </div>

            <div class="flex flex-wrap justify-end gap-2.5">
                <x-button variant="secondary" :href="route('kandang.show', $kandang)">Kembali ke kandang</x-button>
                <x-button :href="route('siklus.show', $terbuka)">Buka Siklus {{ $nomor }}</x-button>
            </div>
        </div>
    </x-page>
</x-app-layout>
