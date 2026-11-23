@props(['siklus'])

@php
    use App\Support\Format as F;

    $user = auth()->user();
    $siklus->loadMissing('kandang');
    $nomor = \App\Models\Siklus::nomorMap([$siklus->kandang_id])[$siklus->id] ?? 1;
    $tab = [
        ['Ringkasan', 'siklus.show', ['siklus.show']],
        ['Recording', 'recording.index', ['recording.*']],
        ['Sampling', 'sampling.index', ['sampling.*']],
        ['OVK', 'ovk.index', ['ovk.*']],
        ['Panen', 'panen.index', ['panen.*']],
        ['RHPP', 'rhpp.show', ['rhpp.*']],
        ['Laporan', 'laporan.siklus', ['laporan.siklus']],
        ['Pengaturan Periode', 'siklus.pengaturan', ['siklus.pengaturan', 'siklus.edit', 'siklus.standar*', 'siklus.acuan*']],
    ];
@endphp

{{-- Konteks periode dan tab. ABK hanya melihat konteks; tab menuju halaman komersial hanya untuk peternak (R13). --}}
<div class="space-y-3" data-siklus-header>
    <div class="flex flex-wrap items-center gap-3.5 kartu px-4 py-3 sm:px-5">
        <span class="chip-ikon size-11 shrink-0" data-nada="merek"><x-icon name="refresh-cw" class="size-5" /></span>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="font-heading text-lg font-semibold leading-snug">Periode {{ $nomor }}</h1>
                <x-status-siklus :siklus="$siklus" />
            </div>
            <p class="mt-0.5 text-[13px] text-ink-secondary">
                {{ $siklus->kandang->nama_farm }}
                · DOC masuk {{ $siklus->tanggal_masuk->translatedFormat('j M Y') }}
                @if ($siklus->isTerbuka()) · Hari ke-{{ $siklus->umurHari() }} @elseif ($siklus->tanggal_panen) · Panen terakhir {{ $siklus->tanggal_panen->translatedFormat('j M Y') }} @endif
                · Jumlah ayam awal {{ F::angka($siklus->populasi, 0) }} ekor
            </p>
        </div>
    </div>

    @if ($siklus->isTerkunci())
        <x-alert variant="info">Periode {{ $nomor }} sudah {{ $siklus->status === \App\Models\Siklus::STATUS_DIHENTIKAN ? 'dihentikan' : 'ditutup' }}. Semua data hanya dapat dilihat.</x-alert>
    @endif

    @if ($user->isPeternak())
        <nav aria-label="Tab periode" class="-mx-4 -mb-3 -mt-1 overflow-x-auto px-4 pb-4 pt-1 md:mx-0 md:px-1">
            <ul class="flex gap-1.5 whitespace-nowrap">
                @foreach ($tab as [$label, $rute, $pola])
                    @php $aktif = request()->routeIs(...$pola); @endphp
                    <li>
                        <a href="{{ route($rute, $siklus) }}" @if ($aktif) aria-current="page" @endif
                           class="inline-flex min-h-11 items-center rounded-full border px-4 text-[13px] transition {{ $aktif ? 'kaca-chip border-brand-border font-bold text-ink-brand shadow-sm' : 'border-transparent font-medium text-ink-secondary hover:bg-state-hover' }}">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif
</div>
