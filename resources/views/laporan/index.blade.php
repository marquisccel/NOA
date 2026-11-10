@php
    use App\Support\Format as F;
    use App\Models\Siklus;

    $selectClass = 'min-h-11 rounded-md border-line-control bg-surface px-3 text-sm font-medium focus:border-brand focus:ring-1 focus:ring-brand';
    $tahunAwal = $tahun->min();
    $tahunAkhir = $tahun->max();
@endphp

<x-app-layout title="Laporan" :crumbs="[['Beranda', route('dashboard')], ['Laporan']]">
    <x-page>
        <x-page-header title="Perbandingan antar-siklus" subtitle="Bandingkan performa siklus yang sudah panen. Siklus berjalan tampil “–”." />

        <form method="GET" action="{{ route('laporan.index') }}" class="flex flex-wrap items-center gap-2.5" x-data>
            <label class="sr-only" for="f-kandang">Kandang</label>
            <select id="f-kandang" name="kandang" class="{{ $selectClass }}" @change="$el.form.submit()">
                <option value="">Kandang: Semua</option>
                @foreach ($kandang as $k)<option value="{{ $k->id }}" @selected($filter['kandang'] === $k->id)>{{ $k->nama_farm }}</option>@endforeach
            </select>
            <label class="sr-only" for="f-status">Status</label>
            <select id="f-status" name="status" class="{{ $selectClass }}" @change="$el.form.submit()">
                <option value="">Status: Semua</option>
                @foreach (Siklus::STATUS as $nilai => [$label])<option value="{{ $nilai }}" @selected($filter['status'] === $nilai)>{{ $label }}</option>@endforeach
            </select>
            <label class="sr-only" for="f-tahun">Tahun</label>
            <select id="f-tahun" name="tahun" class="{{ $selectClass }}" @change="$el.form.submit()">
                <option value="">Tahun: {{ $tahun->isEmpty() ? 'Semua' : ($tahunAwal === $tahunAkhir ? $tahunAkhir : $tahunAwal.'–'.$tahunAkhir) }}</option>
                @foreach ($tahun as $t)<option value="{{ $t }}" @selected($filter['tahun'] === $t)>{{ $t }}</option>@endforeach
            </select>
            <noscript><x-button variant="secondary">Terapkan</x-button></noscript>
        </form>

        @if ($baris->isEmpty())
            <x-empty-state icon="chart-column" title="Belum ada siklus untuk dibandingkan" text="Perbandingan muncul setelah siklus dibuka. Siklus yang sudah panen menampilkan deplesi, FCR, ABW, umur panen, dan IP.">
                <x-button :href="route('kandang.index')">Ke Kandang &amp; Siklus</x-button>
            </x-empty-state>
        @else
            <div class="overflow-x-auto rounded-[16px] border border-line bg-surface">
                <table class="tabel min-w-[900px]">
                    <caption class="sr-only">Perbandingan performa siklus lintas kandang</caption>
                    <thead>
                        <tr>
                            <th scope="col">Kandang</th><th scope="col">Siklus</th><th scope="col">Status</th><th scope="col">DOC masuk</th>
                            <th scope="col" class="text-right">Deplesi</th><th scope="col" class="text-right">FCR</th><th scope="col" class="text-right">ABW</th>
                            <th scope="col" class="text-right">Umur panen</th><th scope="col" class="text-right">IP</th><th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($baris as $b)
                            @php $s = $b['siklus']; $p = $b['p']; $ada = $b['sudahPanen']; @endphp
                            <tr>
                                <td class="font-semibold">{{ $s->kandang->nama_farm }}</td>
                                <td>Siklus {{ $b['nomor'] }}</td>
                                <td><x-status-siklus :siklus="$s" /></td>
                                <td class="whitespace-nowrap">{{ $s->tanggal_masuk->translatedFormat('j M Y') }}</td>
                                <td class="text-right">{{ $ada ? F::angka($p['deplesi_persen']).'%' : '–' }}</td>
                                <td class="text-right">{{ $ada ? F::angka($p['fcr'], 3) : '–' }}</td>
                                <td class="text-right">{{ $ada ? F::angka($p['abw'], 2).' kg' : '–' }}</td>
                                <td class="text-right">{{ $ada ? F::angka($p['umur_panen']).' hari' : '–' }}</td>
                                <td class="text-right font-semibold">{{ $ada ? F::angka($p['ip'], 0) : '–' }}</td>
                                <td><a href="{{ $ada ? route('laporan.siklus', $s) : route('siklus.show', $s) }}" class="inline-flex min-h-11 items-center font-semibold text-ink-brand hover:underline">{{ $ada ? 'Laporan' : 'Buka' }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <x-bar-chart :judul="'IP per siklus'.($grafikKandang ? ' · '.$grafikKandang->nama_farm : '')" sub="Siklus yang sudah panen" :item="$grafikIp" :std-ada="false" kosong="Grafik muncul setelah ada siklus yang sudah panen." />
                <x-bar-chart :judul="'FCR per siklus'.($grafikKandang ? ' · '.$grafikKandang->nama_farm : '')" sub="Makin rendah makin baik" :item="$grafikFcr" :std-ada="false" :desimal="3" kosong="Grafik muncul setelah ada siklus yang sudah panen." />
            </div>
        @endif
    </x-page>
</x-app-layout>
