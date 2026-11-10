@php
    $filter = ['' => 'Semua', 'berjalan' => 'Berjalan', 'selesai' => 'Ditutup', 'dihentikan' => 'Dihentikan'];
@endphp

<x-app-layout title="Riwayat siklus" :crumbs="[['Kandang & Siklus', route('kandang.index')], [$kandang->nama_farm, route('kandang.show', $kandang)], ['Riwayat siklus']]">
    <x-page>
        <div class="space-y-3">
            <a href="{{ route('kandang.show', $kandang) }}" class="inline-flex min-h-11 items-center gap-1.5 text-[13px] font-semibold text-ink-brand hover:underline">
                <x-icon name="arrow-left" class="size-4" /> Kembali ke detail kandang
            </a>
            <x-page-header :title="'Riwayat siklus · '.$kandang->nama_farm" subtitle="Semua siklus pemeliharaan milik kandang ini. Siklus yang sudah ditutup tetap tersimpan dan dapat dibuka kembali." />
        </div>

        <section class="rounded-[16px] border border-line bg-surface">
            <div class="flex flex-wrap items-center gap-2 p-4 sm:p-5">
                <span class="text-[13px] font-semibold text-ink-secondary">Status:</span>
                @foreach ($filter as $kunci => $label)
                    <a href="{{ $kunci === '' ? route('kandang.riwayat', $kandang) : route('kandang.riwayat', [$kandang, 'status' => $kunci]) }}"
                       @if (($status ?? '') === $kunci) aria-current="true" @endif
                       class="inline-flex min-h-11 items-center rounded-full border px-3.5 text-[13px] font-semibold {{ ($status ?? '') === $kunci ? 'border-brand bg-brand text-white' : 'border-line-control text-ink hover:bg-state-hover' }}">{{ $label }}</a>
                @endforeach
            </div>

            <div class="border-t border-line-subtle px-4 pb-1 pt-4 sm:px-5">
                <h2 class="text-base font-bold">Riwayat siklus</h2>
                <p class="text-[13px] text-ink-secondary">{{ $total }} siklus tercatat untuk kandang ini{{ $status ? ' · '.$siklus->count().' sesuai filter' : '' }}</p>
            </div>

            @if ($siklus->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-ink-secondary">Tidak ada siklus dengan status ini.</p>
            @else
                <div class="mt-3">@include('kandang.partials.tabel-riwayat', ['daftar' => $siklus])</div>
            @endif
        </section>
    </x-page>
</x-app-layout>
