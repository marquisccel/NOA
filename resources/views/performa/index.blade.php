@php
    use App\Support\Format as F;
    use App\Models\Siklus;

    $kelas = 'min-h-11 rounded-md border-line-control bg-surface px-3 text-sm font-medium focus:border-brand focus:ring-1 focus:ring-brand';
    $sb = $performaSebelumnya;
    $banding = function ($kunci, $d = 3, $akhiran = '') use ($sb) {
        if ($sb === null) {
            return 'Tanpa pembanding';
        }

        return 'Periode lalu: '.($sb[$kunci] === null ? '–' : F::angka($sb[$kunci], $d).$akhiran);
    };
@endphp

<x-app-layout title="Performa">
    <x-page>
        <x-page-header title="Performa" subtitle="Hasil siklus yang sudah dipanen: pantau FCR, IP, deplesi, dan biaya dari waktu ke waktu." />

        <form method="GET" action="{{ route('performa.index') }}" class="flex flex-wrap items-center gap-2.5" x-data>
            <label class="sr-only" for="f-periode">Periode</label>
            <select id="f-periode" name="periode" class="{{ $kelas }}" @change="$el.form.submit()">
                <optgroup label="Semua">
                    @foreach ($pilihanPeriode['semua'] as $nilai => $label)<option value="{{ $nilai }}" @selected($filter['periode'] === (string) $nilai)>{{ $label }}</option>@endforeach
                </optgroup>
                <optgroup label="Per bulan">
                    @foreach ($pilihanPeriode['bulan'] as $nilai => $label)<option value="{{ $nilai }}" @selected($filter['periode'] === (string) $nilai)>{{ $label }}</option>@endforeach
                </optgroup>
                <optgroup label="Per tahun">
                    @foreach ($pilihanPeriode['tahun'] as $nilai => $label)<option value="{{ $nilai }}" @selected($filter['periode'] === (string) $nilai)>{{ $label }}</option>@endforeach
                </optgroup>
            </select>
            <label class="sr-only" for="f-kandang">Kandang</label>
            <select id="f-kandang" name="kandang" class="{{ $kelas }}" @change="$el.form.submit()">
                <option value="">Semua kandang</option>
                @foreach ($kandang as $k)<option value="{{ $k->id }}" @selected($filter['kandang'] === $k->id)>{{ $k->nama_farm }}</option>@endforeach
            </select>
            <label class="sr-only" for="f-status">Status siklus</label>
            <select id="f-status" name="status" class="{{ $kelas }}" @change="$el.form.submit()">
                <option value="">Semua status</option>
                @foreach (Siklus::STATUS as $nilai => [$label])<option value="{{ $nilai }}" @selected($filter['status'] === $nilai)>{{ $label }}</option>@endforeach
            </select>
            <noscript><x-button variant="secondary">Tampilkan</x-button></noscript>
        </form>

        @if ($performa['jumlah_siklus'] === 0)
            <x-empty-state icon="chart-column" title="Belum ada siklus yang dipanen pada {{ $performa['label'] }}"
                text="FCR, IP, deplesi, dan biaya per kg muncul setelah ada siklus yang sudah ada catatan panennya pada periode ini. Coba pilih periode lain atau semua periode.">
                <x-button :href="route('performa.index', ['periode' => 'semua'])">Lihat semua periode</x-button>
            </x-empty-state>
        @else
            <section aria-labelledby="ringkasan-performa" class="space-y-3">
                <h2 id="ringkasan-performa" class="text-base font-bold">Ringkasan · {{ $performa['label'] }}</h2>
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-6 lg:gap-4">
                    <x-kpi label="Siklus dipanen" :value="F::angka($performa['jumlah_siklus'], 0)" caption="Dengan catatan panen" />
                    <x-kpi label="Total panen" :value="F::angka($performa['total_ekor'], 0).' ekor'" :caption="F::angka($performa['total_tonase'], 0).' kg'" />
                    <x-kpi label="FCR" istilah="fcr" :spark="$seri['fcr']" :naik-bagus="false" :value="F::angka($performa['fcr'], 3)" :caption="$banding('fcr').' · makin kecil makin baik'" />
                    <x-kpi label="IP rata-rata" istilah="ip" :spark="$seri['ip']" :value="F::angka($performa['ip'], 0)" :caption="$banding('ip', 0).' · makin besar makin baik'" />
                    <x-kpi label="Deplesi" istilah="deplesi" :spark="$seri['deplesi']" :naik-bagus="false" :value="F::angka($performa['deplesi_persen']).'%'" :caption="$banding('deplesi_persen', 2, '%')" />
                    <x-kpi label="Biaya per kg" :value="$performa['biaya_per_kg'] === null ? '–' : 'Rp '.F::angka($performa['biaya_per_kg'], 0)" :caption="$performa['biaya_per_kg'] === null ? 'Harga kontrak belum lengkap' : $banding('biaya_per_kg', 0)" />
                </div>
            </section>

            <section aria-labelledby="tabel-performa" class="space-y-3">
                <h2 id="tabel-performa" class="text-base font-bold">Siklus yang dipanen</h2>
                <div class="overflow-x-auto rounded-[16px] border border-line bg-surface">
                    <table class="tabel min-w-[900px]">
                        <caption class="sr-only">Perbandingan performa siklus yang sudah dipanen</caption>
                        <thead>
                            <tr>
                                <th scope="col">Kandang</th><th scope="col">Siklus</th><th scope="col">Status</th><th scope="col">DOC masuk</th>
                                <th scope="col" class="text-right">Deplesi</th><th scope="col" class="text-right">FCR</th><th scope="col" class="text-right">ABW</th>
                                <th scope="col" class="text-right">Umur panen</th><th scope="col" class="text-right">IP</th><th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($baris as $b)
                                @php $s = $b['siklus']; $p = $b['p']; @endphp
                                <tr>
                                    <td class="font-semibold">{{ $s->kandang->nama_farm }}</td>
                                    <td>Siklus {{ $b['nomor'] }}</td>
                                    <td><x-status-siklus :siklus="$s" /></td>
                                    <td class="whitespace-nowrap">{{ $s->tanggal_masuk->translatedFormat('j M Y') }}</td>
                                    <td class="text-right">{{ F::angka($p['deplesi_persen']) }}%</td>
                                    <td class="text-right">{{ F::angka($p['fcr'], 3) }}</td>
                                    <td class="text-right">{{ F::angka($p['abw'], 2) }} kg</td>
                                    <td class="text-right">{{ F::angka($p['umur_panen']) }} hari</td>
                                    <td class="text-right font-semibold">{{ F::angka($p['ip'], 0) }}</td>
                                    <td><a href="{{ route('laporan.siklus', $s) }}" class="inline-flex min-h-11 items-center font-semibold text-ink-brand hover:underline">Laporan</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($belumPanen > 0)
                    <p class="text-xs text-ink-muted">{{ $belumPanen }} siklus belum ada catatan panen sehingga belum masuk perhitungan.</p>
                @endif
            </section>

            <section aria-labelledby="tren-performa" class="space-y-3">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h2 id="tren-performa" class="text-base font-bold">Tren antar-siklus{{ $grafikKandang ? ' · '.$grafikKandang->nama_farm : '' }}</h2>
                        <p class="text-[13px] text-ink-secondary">Naik-turunnya hasil dari satu siklus ke siklus berikutnya di kandang ini.</p>
                    </div>
                    <x-button variant="secondary" :href="route('performa.bandingkan')"><x-icon name="columns-2" class="size-4" /> Bandingkan dua siklus</x-button>
                </div>
                @if (count($tren['labels']) >= 2)
                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="rounded-[16px] border border-line bg-surface p-5">
                            <h3 class="text-sm font-bold">IP per siklus</h3><p class="text-xs text-ink-secondary">Makin besar makin baik</p>
                            <div class="mt-2 h-56"><canvas id="grafik-ip" role="img" aria-label="Tren IP per siklus"></canvas></div>
                        </div>
                        <div class="rounded-[16px] border border-line bg-surface p-5">
                            <h3 class="text-sm font-bold">FCR per siklus</h3><p class="text-xs text-ink-secondary">Makin kecil makin baik</p>
                            <div class="mt-2 h-56"><canvas id="grafik-fcr" role="img" aria-label="Tren FCR per siklus"></canvas></div>
                        </div>
                    </div>
                    <script type="application/json" id="data-tren">@json($tren)</script>
                    @vite('resources/js/laporan.js')
                @else
                    <div class="grid gap-4 lg:grid-cols-2">
                        <x-bar-chart :judul="'IP per siklus'.($grafikKandang ? ' · '.$grafikKandang->nama_farm : '')" sub="Makin besar makin baik" :item="$grafikIp" :std-ada="false" kosong="Tren muncul setelah ada minimal dua siklus yang sudah dipanen di kandang ini." />
                        <x-bar-chart :judul="'FCR per siklus'.($grafikKandang ? ' · '.$grafikKandang->nama_farm : '')" sub="Makin kecil makin baik" :item="$grafikFcr" :std-ada="false" :desimal="3" kosong="Tren muncul setelah ada minimal dua siklus yang sudah dipanen di kandang ini." />
                    </div>
                @endif
            </section>
        @endif
    </x-page>
</x-app-layout>
