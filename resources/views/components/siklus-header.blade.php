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
        ['Pengaturan Siklus', 'siklus.pengaturan', ['siklus.pengaturan', 'siklus.edit', 'siklus.standar*', 'siklus.acuan*']],
    ];
@endphp

{{-- Konteks siklus dan tab. ABK hanya melihat konteks; tab menuju halaman komersial hanya untuk peternak (R13). --}}
<div class="space-y-3" data-siklus-header>
    <div class="flex flex-wrap items-center gap-3.5 rounded-[16px] border border-line bg-surface px-4 py-3 sm:px-5">
        <span class="grid size-10 shrink-0 place-items-center rounded-[11px] border border-brand-border bg-tint text-ink-brand"><x-icon name="refresh-cw" class="size-5" /></span>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="font-heading text-lg font-semibold leading-snug">Siklus {{ $nomor }}</h1>
                <x-status-siklus :siklus="$siklus" />
            </div>
            <p class="mt-0.5 text-[13px] text-ink-secondary">
                {{ $siklus->kandang->nama_farm }}@if ($siklus->kandang->labelTipe()) · {{ $siklus->kandang->labelTipe() }}@endif
                · DOC masuk {{ $siklus->tanggal_masuk->translatedFormat('j M Y') }}
                @if ($siklus->isTerbuka()) · Hari ke-{{ $siklus->umurHari() }} @elseif ($siklus->tanggal_panen) · Panen terakhir {{ $siklus->tanggal_panen->translatedFormat('j M Y') }} @endif
                · Populasi awal {{ F::angka($siklus->populasi, 0) }} ekor
            </p>
        </div>
    </div>

    @if ($siklus->isTerkunci())
        <x-alert variant="info">Siklus {{ $nomor }} sudah {{ $siklus->status === \App\Models\Siklus::STATUS_DIHENTIKAN ? 'dihentikan' : 'ditutup' }}. Semua data hanya dapat dilihat.</x-alert>
    @endif

    @if ($user->isPeternak())
        <nav aria-label="Tab siklus" class="-mx-4 overflow-x-auto px-4 md:mx-0 md:px-0">
            <ul class="flex gap-1.5 whitespace-nowrap">
                @foreach ($tab as [$label, $rute, $pola])
                    @php $aktif = request()->routeIs(...$pola); @endphp
                    <li>
                        <a href="{{ route($rute, $siklus) }}" @if ($aktif) aria-current="page" @endif
                           class="inline-flex min-h-11 items-center rounded-[10px] border px-3.5 text-[13px] {{ $aktif ? 'border-line bg-surface font-bold text-ink-brand' : 'border-line-subtle font-medium text-ink-secondary hover:bg-state-hover' }}">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif
</div>
