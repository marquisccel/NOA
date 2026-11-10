@php
    use App\Support\Format as F;

    $selisih = $p['selisih_populasi'];
    $tanda = $selisih === 0 ? '' : ($selisih > 0 ? '−' : '+');
    $baris = [
        ['Populasi awal (DOC)', F::angka($p['populasi'], 0).' ekor', false],
        ['Ayam mati (recording)', F::angka($p['mati'], 0).' ekor', false],
        ['Ayam afkir (recording)', F::angka($p['afkir'], 0).' ekor', false],
        ['Total ekor panen ('.$p['jumlah_tahap'].' tahap)', F::angka($p['total_ekor'], 0).' ekor', false],
        ['Panen + deplesi', F::angka($p['total_ekor'] + $p['deplesi'], 0).' ekor', true],
        ['Selisih', $tanda.F::angka(abs($selisih), 0).' ekor', true],
    ];
@endphp

<x-app-layout title="Selisih populasi" :siklus="$siklus"
    :crumbs="[['Kandang & Siklus', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['Panen', route('panen.index', $siklus)], ['Selisih populasi']]">
    <x-page lebar="max-w-3xl">
        <x-page-header title="Selisih populasi" subtitle="Panen + deplesi dibandingkan populasi awal" />

        @if ($selisih === 0)
            <x-alert variant="success"><p class="font-semibold">Populasi seimbang</p><p>Ekor panen ditambah deplesi sama dengan populasi awal.</p></x-alert>
        @elseif ($selisih > 0)
            <x-alert variant="warning"><p class="font-semibold">Selisih populasi: {{ F::angka($selisih, 0) }} ekor belum tercatat</p></x-alert>
        @else
            <x-alert variant="warning"><p class="font-semibold">Panen + deplesi melebihi populasi awal sebanyak {{ F::angka(abs($selisih), 0) }} ekor</p></x-alert>
        @endif

        <div class="overflow-hidden rounded-[16px] border border-line bg-surface">
            <dl class="divide-y divide-line-subtle text-sm">
                @foreach ($baris as [$label, $nilai, $tebal])
                    <div class="flex items-center justify-between gap-4 px-5 py-3.5 {{ $tebal ? 'bg-tint font-bold' : '' }}">
                        <dt class="{{ $tebal ? '' : 'text-ink-secondary' }}">{{ $label }}</dt><dd>{{ $nilai }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <p class="text-[13px] leading-[19px] text-ink-secondary">
            Selisih biasanya berarti ada ayam mati atau afkir yang belum dicatat, kesalahan hitung panen, atau ayam hilang.
            NOA tidak menyeimbangkan angka otomatis — selisih tetap terlihat dan ikut tampil saat rekonsiliasi RHPP.
        </p>
        <div class="flex flex-wrap gap-2.5">
            <x-button variant="secondary" :href="route('recording.index', $siklus)">Periksa recording</x-button>
            <x-button variant="secondary" :href="route('panen.index', $siklus)">Periksa tahap panen</x-button>
        </div>
    </x-page>
</x-app-layout>
