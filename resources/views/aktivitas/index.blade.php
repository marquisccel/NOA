@php
    $kelas = 'min-h-11 rounded-md border-line-control bg-surface px-3 text-sm font-medium focus:border-brand focus:ring-1 focus:ring-brand';
    $hariIni = now()->toDateString();
    $kemarin = now()->subDay()->toDateString();
    $label = function ($tanggal) use ($hariIni, $kemarin) {
        return match ($tanggal->toDateString()) {
            $hariIni => 'Hari ini',
            $kemarin => 'Kemarin',
            default => $tanggal->translatedFormat('l, j F Y'),
        };
    };
    $kelompok = $aktivitas->getCollection()->groupBy(fn ($a) => $a['tanggal']->toDateString());
@endphp

<x-app-layout title="Aktivitas">
    <x-page lebar="max-w-3xl">
        <x-page-header title="Aktivitas" subtitle="Catatan terbaru dari semua kandang Anda: harian, timbang bobot, obat/vaksin, dan panen." />

        <form method="GET" action="{{ route('aktivitas.index') }}" class="flex flex-wrap items-center gap-2.5" x-data>
            <label class="sr-only" for="f-kandang">Kandang</label>
            <select id="f-kandang" name="kandang" class="{{ $kelas }}" @change="$el.form.submit()">
                <option value="">Semua kandang</option>
                @foreach ($kandang as $k)<option value="{{ $k->id }}" @selected($filter['kandang'] === $k->id)>{{ $k->nama_farm }}</option>@endforeach
            </select>
            <label class="sr-only" for="f-jenis">Jenis catatan</label>
            <select id="f-jenis" name="jenis" class="{{ $kelas }}" @change="$el.form.submit()">
                <option value="">Semua jenis catatan</option>
                @foreach ($jenis as $j)<option value="{{ $j }}" @selected($filter['jenis'] === $j)>{{ $j }}</option>@endforeach
            </select>
            <noscript><x-button variant="secondary">Terapkan</x-button></noscript>
        </form>

        @if ($aktivitas->total() === 0)
            <x-empty-state icon="history" title="Belum ada aktivitas"
                text="Catatan harian, timbang bobot, obat/vaksin, dan panen akan muncul di sini setelah Anda mulai mencatat di periode yang berjalan." />
        @else
            @foreach ($kelompok as $tanggal => $daftar)
                <section aria-labelledby="h-{{ $tanggal }}" class="space-y-2">
                    <h2 id="h-{{ $tanggal }}" class="text-[13px] font-bold uppercase tracking-wide text-ink-muted">{{ $label($daftar->first()['tanggal']) }}</h2>
                    <ul class="divide-y divide-line-subtle overflow-hidden rounded-[16px] border border-line bg-surface">
                        @foreach ($daftar as $a)
                            <li>
                                <a href="{{ $a['url'] }}" class="flex items-center gap-3 px-4 py-3.5 hover:bg-state-hover">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-md bg-tint text-ink-brand"><x-icon :name="$a['ikon']" class="size-5" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold">{{ $a['jenis'] }} <span class="font-normal text-ink-secondary">· {{ $a['ringkas'] }}</span></span>
                                        <span class="block truncate text-xs text-ink-secondary">{{ $a['keterangan'] }}</span>
                                    </span>
                                    <x-icon name="chevron-right" class="size-4 shrink-0 text-ink-muted" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach

            @if ($aktivitas->hasPages())
                <nav aria-label="Halaman aktivitas" class="flex items-center justify-between gap-3 pt-1">
                    @if ($aktivitas->onFirstPage())<span></span>@else<x-button variant="secondary" :href="$aktivitas->previousPageUrl()">Lebih baru</x-button>@endif
                    <span class="text-[13px] text-ink-secondary">Halaman {{ $aktivitas->currentPage() }} dari {{ $aktivitas->lastPage() }}</span>
                    @if ($aktivitas->hasMorePages())<x-button variant="secondary" :href="$aktivitas->nextPageUrl()">Lebih lama</x-button>@else<span></span>@endif
                </nav>
            @endif
        @endif
    </x-page>
</x-app-layout>
