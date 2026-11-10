@php
    use App\Support\Format as F;

    $rp = fn ($n) => $n === null ? 'belum lengkap' : 'Rp '.F::angka($n, 0);
    $adaGrafik = collect($grafik['labels'])->isNotEmpty();
    $sudahPanen = $p['jumlah_tahap'] > 0;
    $subtitle = 'Siklus '.$nomor.' · '.$siklus->kandang->nama_farm.($siklus->tanggal_panen ? ' · data s.d. panen terakhir '.$siklus->tanggal_panen->translatedFormat('j M Y') : '');
    $porsi = fn ($n) => $biaya['total'] > 0 && $n !== null ? F::angka($n / $biaya['total'] * 100, 1).'%' : '–';
    $sisaSak = $p['pakan_masuk_sak'] - $p['pakan_bersih_sak'];
@endphp

<x-app-layout title="Laporan siklus" :siklus="$siklus"
    :crumbs="[['Kandang & Siklus', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['Laporan']]">
    <x-page>
        <x-page-header title="Laporan siklus" :subtitle="$subtitle">
            <x-slot name="actions">
                <x-button variant="secondary" :href="route('laporan.csv', $siklus)"><x-icon name="download" class="size-4" /> Unduh CSV</x-button>
                <x-button :href="route('laporan.cetak', $siklus)" target="_blank" rel="noopener"><x-icon name="printer" class="size-4" /> Cetak laporan</x-button>
            </x-slot>
        </x-page-header>

        @if ($p['tidak_sebanding'])
            <x-alert variant="warning"><strong>Tidak sebanding:</strong> siklus dihentikan dini. Angka dihitung dari data yang ada dan tidak layak dibandingkan dengan siklus lengkap.</x-alert>
        @endif
        @if (! $sudahPanen)
            <x-alert variant="info">Belum ada catatan panen: FCR, ABW, umur panen, IP, dan margin belum bisa dihitung.</x-alert>
        @endif

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
            <x-kpi label="Deplesi" :value="F::angka($p['deplesi_persen']).'%'" :caption="F::angka($p['deplesi'], 0).' ekor · daya hidup '.F::angka($p['liveability']).'%'" />
            <x-kpi label="FCR" :value="F::angka($p['fcr'], 3)" :caption="$sudahPanen ? F::angka($p['pakan_bersih_kg'], 0).' kg ÷ '.F::angka($p['total_tonase'], 0).' kg' : null" />
            <x-kpi label="ABW panen" :value="F::angka($p['abw'], 3).' kg'" :caption="$sudahPanen ? F::angka($p['total_ekor'], 0).' ekor' : null" />
            <x-kpi label="IP" :value="F::angka($p['ip'], 0)" caption="Rumus kemitraan" />
            <x-kpi label="Umur panen" :value="F::angka($p['umur_panen']).' hari'" :caption="$sudahPanen ? 'Berbobot '.$p['jumlah_tahap'].' tahap' : null" />
            <x-kpi label="Biaya produksi" :value="$biaya['per_kg'] === null ? '–' : 'Rp '.F::angka($biaya['per_kg'], 0)" :caption="'per kg · total '.($biaya['lengkap'] ? $rp($biaya['total']) : $rp($biaya['total']).' (belum lengkap)')" />
        </div>

        @if ($p['selisih_populasi'] !== 0 && $sudahPanen)
            <x-alert variant="warning"><p class="font-semibold">Selisih populasi {{ F::angka(abs($p['selisih_populasi']), 0) }} ekor</p><p>Panen + deplesi ≠ populasi awal. <a href="{{ route('panen.selisih', $siklus) }}" class="font-semibold underline">Lihat rincian</a></p></x-alert>
        @endif

        <section class="space-y-3" aria-labelledby="biaya-judul">
            <h2 id="biaya-judul" class="text-base font-bold">Biaya produksi (catatan peternak)</h2>
            <div class="overflow-x-auto rounded-[16px] border border-line bg-surface">
                <table class="tabel min-w-[560px]">
                    <caption class="sr-only">Biaya produksi menurut komponen</caption>
                    <thead><tr><th scope="col">Komponen</th><th scope="col">Dasar</th><th scope="col" class="text-right">Biaya</th><th scope="col" class="text-right">Porsi</th></tr></thead>
                    <tbody>
                        <tr><th scope="row">DOC</th><td class="text-ink-secondary">{{ $biaya['doc_dasar'] ?? 'harga DOC belum diisi' }}</td><td class="text-right">{{ $rp($biaya['doc']) }}</td><td class="text-right">{{ $porsi($biaya['doc']) }}</td></tr>
                        <tr><th scope="row">Pakan</th><td class="text-ink-secondary">{{ $biaya['pakan_dasar'] ?? 'harga pakan belum diisi' }}</td><td class="text-right">{{ $rp($biaya['pakan']) }}</td><td class="text-right">{{ $porsi($biaya['pakan']) }}</td></tr>
                        <tr><th scope="row">OVK</th><td class="text-ink-secondary">{{ $jumlahOvk }} pemberian</td><td class="text-right">{{ $rp($biaya['ovk']) }}</td><td class="text-right">{{ $porsi($biaya['ovk']) }}</td></tr>
                        <tr class="!bg-tint font-bold"><th scope="row">Total biaya</th><td></td><td class="text-right">{{ $rp($biaya['total']) }}{{ $biaya['lengkap'] ? '' : '*' }}</td><td class="text-right">100%</td></tr>
                    </tbody>
                </table>
            </div>
            @unless ($biaya['lengkap'])<p class="text-xs text-ink-muted">* Belum lengkap: harga DOC atau pakan di kontrak belum diisi.</p>@endunless

            <div class="overflow-x-auto rounded-[16px] border border-line bg-surface">
                <table class="tabel min-w-[560px]">
                    <caption class="sr-only">Biaya per kilogram dan per ekor panen</caption>
                    <thead><tr><th scope="col">Ukuran</th><th scope="col">Dasar</th><th scope="col" class="text-right">Per kg</th><th scope="col" class="text-right">Per ekor panen</th></tr></thead>
                    <tbody><tr>
                        <th scope="row">Biaya produksi</th>
                        <td class="text-ink-secondary">{{ $sudahPanen ? $rp($biaya['total']).' ÷ '.F::angka($p['total_tonase'], 0).' kg · ÷ '.F::angka($p['total_ekor'], 0).' ekor' : 'menunggu panen' }}</td>
                        <td class="text-right">{{ $biaya['per_kg'] === null ? '–' : 'Rp '.F::angka($biaya['per_kg'], 0) }}</td>
                        <td class="text-right">{{ $biaya['per_ekor'] === null ? '–' : 'Rp '.F::angka($biaya['per_ekor'], 0) }}</td>
                    </tr></tbody>
                </table>
            </div>
        </section>

        <section class="space-y-3" aria-labelledby="produksi-judul">
            <h2 id="produksi-judul" class="text-base font-bold">Produksi</h2>
            <div class="overflow-hidden rounded-[16px] border border-line bg-surface">
                <dl class="divide-y divide-line-subtle text-sm">
                    @foreach ([
                        ['Populasi DOC', F::angka($p['populasi'], 0).' ekor'],
                        ['Ayam mati', F::angka($p['mati'], 0).' ekor'],
                        ['Ayam afkir', F::angka($p['afkir'], 0).' ekor'],
                        ['Ayam dipanen', F::angka($p['total_ekor'], 0).' ekor'],
                        ['Selisih populasi', F::angka(abs($p['selisih_populasi']), 0).' ekor'],
                        ['Tonase panen', F::angka($p['total_tonase'], 0).' kg'],
                        ['Pakan terpakai', F::angka($p['pakan_bersih_kg'], 0).' kg'],
                        ['Sisa pakan', F::angka($sisaSak, 1).' sak'],
                    ] as [$k, $v])
                        <div class="flex items-center justify-between gap-4 px-5 py-3"><dt class="text-ink-secondary">{{ $k }}</dt><dd class="font-semibold">{{ $v }}</dd></div>
                    @endforeach
                </dl>
            </div>
            <p class="text-xs text-ink-muted">Hasil bersih dan bonus tidak dihitung NOA (lihat RHPP &amp; Rekonsiliasi).</p>
        </section>

        <section class="space-y-3" aria-labelledby="grafik-judul">
            <h2 id="grafik-judul" class="text-base font-bold">Grafik siklus</h2>
            @if ($adaGrafik)
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="rounded-[16px] border border-line bg-surface p-5">
                        <h3 class="text-sm font-bold">Bobot rata-rata (g)</h3>
                        <p class="text-xs text-ink-secondary">Sampling vs standar</p>
                        <div class="mt-2 h-64"><canvas id="grafik-bobot" role="img" aria-label="Grafik bobot aktual dibandingkan standar menurut umur"></canvas></div>
                    </div>
                    <div class="rounded-[16px] border border-line bg-surface p-5">
                        <h3 class="text-sm font-bold">Deplesi kumulatif (%)</h3>
                        <p class="text-xs text-ink-secondary">Ayam mati + ayam afkir ÷ DOC</p>
                        <div class="mt-2 h-64"><canvas id="grafik-deplesi" role="img" aria-label="Grafik deplesi kumulatif menurut umur"></canvas></div>
                    </div>
                </div>
                <script type="application/json" id="data-grafik">@json($grafik)</script>
                @vite('resources/js/laporan.js')
            @else
                <x-empty-state icon="chart-column" title="Grafik muncul setelah ada recording" text="Bobot sampling, standar, dan deplesi kumulatif tergambar per hari umur." />
            @endif
        </section>
    </x-page>
</x-app-layout>
