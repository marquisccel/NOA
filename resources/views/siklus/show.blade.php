@php
    use App\Support\Format as F;

    $tutup = $siklus->isTerkunci();
    $f = $r['final'];
    $bisaIsi = $siklus->status === \App\Models\Siklus::STATUS_BERJALAN;
    $bandingWarna = fn ($b) => $b === 'di atas standar' ? 'text-warning-text' : 'text-ink-muted';
@endphp

<x-app-layout title="Ringkasan Periode" :siklus="$siklus"
    :crumbs="[['Kandang', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['Periode '.$nomor]]">
    <x-page>
        @if ($siklus->isTerbuka() && ! $r['standar_ada'])
            <x-alert variant="info">
                <span class="flex flex-wrap items-center justify-between gap-2">
                    <span>Target harian dari inti belum diunggah (boleh menyusul). Tanpa itu, kolom target tampil “–” dan catatan Anda belum dibandingkan dengan target.</span>
                    <a href="{{ route('siklus.standar', $siklus) }}" class="inline-flex min-h-11 items-center font-bold underline">Unggah target harian</a>
                </span>
            </x-alert>
        @endif

        <x-langkah-berikutnya :siklus="$siklus" />

        <x-page-header title="Ringkasan periode"
            :subtitle="$tutup
                ? 'Angka final'.($siklus->ditutup_pada ? ' · ditutup '.$siklus->ditutup_pada->translatedFormat('j M Y') : '')
                : ($r['ada_recording']
                    ? 'Data s.d. hari ke-'.$r['hari_terakhir'].' ('.$r['tanggal_terakhir']->translatedFormat('j M Y').') · recording terakhir tersimpan'
                    : 'Belum ada recording tersimpan')">
            <x-slot name="actions">
                @if ($tutup)
                    <x-button variant="secondary" :href="route('laporan.siklus', $siklus)"><x-icon name="printer" class="size-4" /> Cetak laporan</x-button>
                @elseif ($bisaIsi)
                    <x-button :href="route('recording.create', $siklus)">{{ $r['ada_recording'] ? 'Isi recording hari ini' : 'Isi recording hari ke-1' }}</x-button>
                @endif
            </x-slot>
        </x-page-header>

        @if ($f && ($tutup || $f['jumlah_tahap'] > 0))
            {{-- Angka final periode (R10: kosong sampai ada panen) --}}
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 lg:gap-4">
                <x-kpi label="Total ekor panen" :value="$f['jumlah_tahap'] ? F::angka($f['total_ekor'], 0).' ekor' : '–'" :caption="'Dari '.F::angka($siklus->populasi, 0).' ekor DOC'" />
                <x-kpi label="Deplesi" istilah="deplesi" :value="F::angka($f['deplesi_persen']).'%'" :caption="F::angka($f['deplesi'], 0).' ekor · daya hidup '.F::angka($f['liveability']).'%'" />
                <x-kpi label="FCR" istilah="fcr" :value="F::angka($f['fcr'], 3)" :caption="$f['fcr'] !== null ? 'Pakan '.F::angka($f['pakan_bersih_kg'], 0).' kg ÷ tonase' : 'Muncul setelah ada panen'" />
                <x-kpi label="ABW panen" istilah="abw" :value="$f['abw'] !== null ? F::angka($f['abw'], 2).' kg' : '–'" :caption="$f['jumlah_tahap'] ? 'Tonase '.F::angka($f['total_tonase'], 0).' kg' : null" />
                <x-kpi label="Umur panen" :value="$f['umur_panen'] !== null ? F::angka($f['umur_panen'], 1).' hari' : '–'" :caption="$f['jumlah_tahap'] ? 'Rata-rata berbobot '.$f['jumlah_tahap'].' tahap' : null" />
                <x-kpi label="IP" istilah="ip" :value="F::angka($f['ip'], 0)" caption="Dihitung dari data final" />
            </div>
            <x-penjelasan-rumus :siklus="$siklus" />
        @else
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 lg:gap-4">
                <x-kpi label="Ayam hidup kini" istilah="populasi" :value="F::angka($r['populasi_kini'], 0).' ekor'" :caption="$r['ada_recording'] ? 'Dari '.F::angka($siklus->populasi, 0).' ekor DOC' : 'Jumlah ayam awal (DOC)'" />
                <x-kpi label="Deplesi kumulatif" istilah="deplesi" :value="$r['deplesi_persen'] !== null ? F::angka($r['deplesi_persen']).'%' : '–'">
                    <p class="text-xs {{ $bandingWarna($r['deplesi_banding']) }}">
                        @if ($r['deplesi_persen'] === null) Muncul setelah recording pertama
                        @elseif ($r['deplesi_std'] === null) Standar –
                        @else Standar {{ F::angka($r['deplesi_std']) }}% · {{ $r['deplesi_banding'] }} @endif
                    </p>
                </x-kpi>
                <x-kpi label="FCR berjalan" istilah="fcr" :value="$r['fcr'] ? F::angka($r['fcr']['nilai'], 3) : '–'"
                       :caption="$r['fcr'] ? 'Per hari ke-'.$r['fcr']['hari'].' · std '.($r['fcr']['std'] !== null ? F::angka($r['fcr']['std'], 3) : '–') : 'Butuh bobot (sampling)'" />
                <x-kpi label="Pakan per ekor" :value="$r['feed_intake'] !== null ? F::angka($r['feed_intake'], 0).' g/ekor' : '–'"
                       :caption="$r['feed_intake'] !== null ? 'Standar '.($r['feed_intake_std'] !== null ? F::angka($r['feed_intake_std'], 0).' g/ekor' : '–') : 'Belum ada pakan terpakai'" />
                <x-kpi label="Stok pakan" istilah="stok" :value="$r['stok_sak'] !== null ? F::angka($r['stok_sak'], 0).' sak' : '–'"
                       :caption="$r['stok_sak'] !== null ? 'Masuk '.F::angka($r['pakan_masuk_sak'], 0).' · terpakai '.F::angka($r['pakan_terpakai_sak'], 0).' sak' : 'Catat pakan masuk'" />
                <x-kpi label="ABW terakhir" istilah="abw" :value="$r['abw'] ? F::angka($r['abw']['gram'], 0).' g' : '–'"
                       :caption="$r['abw'] ? 'Hari ke-'.$r['abw']['hari'].' · std '.($r['abw']['std'] !== null ? F::angka($r['abw']['std'], 0).' g' : '–') : 'Belum ada sampling'" />
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-2">
            <x-bar-chart judul="Bobot rata-rata (g)"
                :sub="$r['standar_ada'] ? 'Hasil sampling vs standar' : 'Hasil sampling · standar belum ada'"
                :item="$r['grafik_bobot']" :std-ada="$r['standar_ada']" satuan=" g"
                :kosong="$r['standar_ada'] ? 'Grafik muncul setelah sampling bobot pertama.' : 'Grafik muncul setelah sampling bobot pertama. Standar belum diunggah.'">
                @if ($r['abw'] && $r['abw']['adg'] !== null)<p class="mt-3 text-xs text-ink-secondary">ADG sampling terakhir: {{ F::angka($r['abw']['adg'], 0) }} g/hari</p>@endif
            </x-bar-chart>

            <x-bar-chart judul="Deplesi kumulatif (%)" sub="Ayam mati + ayam afkir ÷ DOC"
                :item="$r['grafik_deplesi']" :std-ada="$r['standar_ada']" :desimal="2" satuan="%"
                kosong="Grafik muncul setelah recording harian pertama." />
        </div>

            @if (collect($kurva['labels'])->isNotEmpty())
                <section aria-labelledby="kurva-harian" class="space-y-3">
                    <div>
                        <h2 id="kurva-harian" class="text-base font-bold">Kurva harian</h2>
                        <p class="text-[13px] text-ink-secondary">Bandingkan bobot dan deplesi ayam Anda dengan target dari inti, hari demi hari.</p>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="kartu p-5">
                            <h3 class="text-sm font-bold">Bobot rata-rata</h3>
                            <p class="text-xs text-ink-secondary">Garis putus = target (standar)</p>
                            <div class="mt-2 h-64"><canvas id="grafik-bobot" role="img" aria-label="Kurva bobot rata-rata aktual dibandingkan standar menurut umur"></canvas></div>
                        </div>
                        <div class="kartu p-5">
                            <h3 class="text-sm font-bold">Deplesi kumulatif</h3>
                            <p class="text-xs text-ink-secondary">Ayam mati + afkir, dibagi jumlah ayam awal</p>
                            <div class="mt-2 h-64"><canvas id="grafik-deplesi" role="img" aria-label="Kurva deplesi kumulatif menurut umur"></canvas></div>
                        </div>
                    </div>
                    <script type="application/json" id="data-grafik">@json($kurva)</script>
                    @vite('resources/js/laporan.js')
                </section>
            @endif

        @if ($siklus->isTerbuka())
            <section class="kartu p-5" aria-labelledby="judul-perhatian">
                <h2 id="judul-perhatian" class="text-sm font-bold">Perlu perhatian</h2>
                @if (empty($r['perhatian']))
                    <p class="mt-3 flex items-center gap-2 text-[13px] text-success-text"><x-icon name="circle-check" class="size-4" /> Tidak ada yang perlu ditindaklanjuti saat ini.</p>
                @else
                    <ul class="mt-3 divide-y divide-line-subtle">
                        @foreach ($r['perhatian'] as $p)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold">{{ $p['judul'] }}</p>
                                    <p class="text-xs text-ink-secondary">{{ $p['teks'] }}</p>
                                </div>
                                <a href="{{ $p['tautan'] }}" class="inline-flex min-h-11 items-center whitespace-nowrap text-[13px] font-bold text-ink-brand hover:underline">{{ $p['label'] }} →</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif

        @if ($r['penutupan'])
            @php $t = $r['penutupan']; @endphp
            <section class="kartu p-5" aria-labelledby="judul-penutupan">
                <h2 id="judul-penutupan" class="text-sm font-bold">Penutupan periode</h2>
                <dl class="mt-3 grid gap-4 text-[13px] sm:grid-cols-2 lg:grid-cols-5">
                    <div><dt class="text-ink-secondary">Panen terakhir</dt><dd class="mt-0.5 font-bold">{{ $t['panen_terakhir']?->translatedFormat('j M Y') ?? '—' }}</dd></div>
                    <div><dt class="text-ink-secondary">Ditutup</dt><dd class="mt-0.5 font-bold">{{ $t['ditutup_pada']?->translatedFormat('j M Y') ?? '—' }}</dd></div>
                    <div><dt class="text-ink-secondary">RHPP</dt><dd class="mt-0.5 font-bold">{{ $t['rhpp_ada'] ? 'Diterima '.$t['rhpp']->translatedFormat('j M Y') : ($t['tanpa_rhpp'] ? 'Tidak diterbitkan' : 'Belum diterima') }}</dd></div>
                    <div><dt class="text-ink-secondary">Rekonsiliasi</dt><dd class="mt-0.5 font-bold">{{ $t['rekonsiliasi_selesai'] ? 'Selesai' : '—' }}</dd></div>
                    <div><dt class="text-ink-secondary">Re-chick-in</dt><dd class="mt-0.5 font-bold">{{ $t['rechick']?->translatedFormat('j M Y') ?? '—' }}</dd></div>
                </dl>
            </section>
        @endif
    </x-page>
</x-app-layout>
