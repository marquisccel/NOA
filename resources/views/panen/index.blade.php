@php
    use App\Support\Format as F;

    $selisih = $p['selisih_populasi'];
    $adaTahap = $p['jumlah_tahap'] > 0;
    $kg = fn ($n) => F::angka($n, 0).' kg';
    $mulai = $siklus->status === \App\Models\Siklus::STATUS_BERJALAN;
    $nomor = \App\Models\Siklus::nomorMap([$siklus->kandang_id])[$siklus->id] ?? 1;
@endphp

<x-app-layout title="Panen & BTPA" :siklus="$siklus"
    :crumbs="[['Kandang & Siklus', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['Panen']]">
    <x-page>
        <x-page-header title="Panen & BTPA" subtitle="Setiap tahap dicatat dengan nomor BTPA. * umur panen rata-rata berbobot jumlah ekor.">
            <x-slot name="actions">
                @if ($adaTahap)<x-button variant="secondary" :href="route('panen.ringkasan', $siklus)">Ringkasan panen</x-button>@endif
                @if ($bisaCatat)<x-button variant="secondary" :href="route('panen.create', $siklus)"><x-icon name="plus" class="size-4" /> Tambah tahap panen</x-button>@endif
                @if ($bisaSelesai)<x-button type="button" x-data @click="$dispatch('open-modal', 'panen-selesai')">Tandai panen selesai</x-button>@endif
            </x-slot>
        </x-page-header>

        @if ($errors->has('siklus'))<x-alert variant="error">{{ $errors->first('siklus') }}</x-alert>@endif

        @if ($p['tidak_sebanding'])
            <x-alert variant="warning"><strong>Tidak sebanding:</strong> siklus dihentikan dini. FCR dan IP dihitung dari data yang ada dan tidak layak dibandingkan dengan siklus lengkap.</x-alert>
        @endif

        @if (! $adaTahap)
            <x-empty-state icon="truck" title="Belum ada panen"
                text="{{ $mulai ? 'Siklus masih berjalan (hari ke-'.$siklus->umurHari().'). ' : '' }}Catat setiap tahap panen dengan tanggal, jumlah ekor, tonase, dan nomor BTPA.">
                @if ($bisaCatat)<x-button :href="route('panen.create', $siklus)">Tambah tahap panen</x-button>@endif
            </x-empty-state>
            <x-alert variant="info">
                <p class="font-semibold">FCR final &amp; IP muncul setelah panen pertama</p>
                <p>Sebelum ada panen, Ringkasan hanya menampilkan FCR berjalan (estimasi dari sampling bobot). IP tampil “–”.</p>
            </x-alert>
        @else
            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <x-kpi label="Total ekor panen" icon="truck" :value="F::angka($p['total_ekor'], 0).' ekor'" :caption="$p['jumlah_tahap'].' tahap'" />
                <x-kpi label="Total tonase" icon="scale" :value="$kg($p['total_tonase'])" :caption="'Jumlah BTPA '.($p['jumlah_tahap'] - $p['tahap_tanpa_btpa'])" />
                <x-kpi label="Umur panen" icon="timer" :value="F::angka($p['umur_panen']).' hari'" caption="Rata-rata berbobot jumlah ekor" />
                <x-kpi label="ABW panen" icon="chart-column" :value="F::angka($p['abw'], 3).' kg'" caption="Tonase ÷ ekor" />
            </div>

            @if ($selisih !== 0)
                <x-alert variant="warning">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            @if ($selisih > 0)
                                <p class="font-semibold">Selisih populasi: {{ F::angka($selisih, 0) }} ekor belum tercatat</p>
                                <p>Panen {{ F::angka($p['total_ekor'], 0) }} + deplesi {{ F::angka($p['deplesi'], 0) }} = {{ F::angka($p['total_ekor'] + $p['deplesi'], 0) }} ekor, populasi awal {{ F::angka($p['populasi'], 0) }} ekor. NOA tidak menyeimbangkan angka otomatis.</p>
                            @else
                                <p class="font-semibold">Panen + deplesi melebihi populasi awal sebanyak {{ F::angka(abs($selisih), 0) }} ekor</p>
                                <p>Periksa jumlah ekor panen atau catatan deplesi. NOA tidak menyeimbangkan angka otomatis.</p>
                            @endif
                        </div>
                        <a href="{{ route('panen.selisih', $siklus) }}" class="inline-flex min-h-11 items-center font-semibold underline">Lihat rincian</a>
                    </div>
                </x-alert>
            @endif

            <div class="overflow-x-auto rounded-[16px] border border-line bg-surface">
                <table class="tabel min-w-[860px]">
                    <caption class="sr-only">Tahap panen dan nomor BTPA</caption>
                    <thead>
                        <tr>
                            <th scope="col">Tahap</th><th scope="col">Tanggal</th><th scope="col">Umur</th><th scope="col" class="text-right">Jumlah ekor</th>
                            <th scope="col" class="text-right">Tonase</th><th scope="col" class="text-right">ABW</th><th scope="col">No. BTPA</th><th scope="col">Foto BTPA</th>
                            @if ($bisaCatat)<th scope="col">Aksi</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($panen as $i => $t)
                            <tr>
                                <th scope="row">{{ $i + 1 }}</th>
                                <td class="whitespace-nowrap">{{ $t->tanggal->translatedFormat('j M Y') }}</td>
                                <td>{{ $t->umur_hari }} hari</td>
                                <td class="text-right">{{ F::angka($t->jumlah_ekor, 0) }} ekor</td>
                                <td class="text-right">{{ $kg($t->tonase_kg) }}</td>
                                <td class="text-right">{{ F::angka((float) $t->tonase_kg / $t->jumlah_ekor, 3) }} kg</td>
                                <td class="font-semibold">{{ filled($t->nomor_btpa) ? $t->nomor_btpa : '–' }}</td>
                                <td>
                                    @if ($t->foto_btpa)
                                        <a href="{{ route('panen.foto', $t) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-1.5 font-semibold text-ink-brand hover:underline"><x-icon name="file-text" class="size-4" /> Lihat foto</a>
                                    @else
                                        <span class="text-ink-muted">Belum diunggah</span>
                                    @endif
                                </td>
                                @if ($bisaCatat)
                                    <td><a href="{{ route('panen.edit', $t) }}" class="inline-flex min-h-11 items-center font-semibold text-ink-brand hover:underline">Ubah</a></td>
                                @endif
                            </tr>
                        @endforeach
                        <tr class="!bg-tint font-bold">
                            <td colspan="2">Total</td>
                            <td>{{ $p['jumlah_tahap'] }} tahap · {{ F::angka($p['umur_panen']) }} hari*</td>
                            <td class="text-right">{{ F::angka($p['total_ekor'], 0) }} ekor</td>
                            <td class="text-right">{{ $kg($p['total_tonase']) }}</td>
                            <td class="text-right">{{ F::angka($p['abw'], 3) }} kg</td>
                            <td colspan="{{ $bisaCatat ? 3 : 2 }}"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        @if ($bisaCatat)
            <section class="rounded-[16px] border border-line bg-surface p-5" aria-labelledby="retur-judul">
                <h2 id="retur-judul" class="text-base font-bold">Pakan sisa di akhir siklus</h2>
                <p class="mt-1 text-[13px] leading-[19px] text-ink-secondary">
                    Pakan masuk {{ F::angka($p['pakan_masuk_sak']) }} sak, terpakai {{ F::angka($p['pakan_terpakai_sak']) }} sak (stok gudang {{ F::angka($p['pakan_masuk_sak'] - $p['pakan_terpakai_sak']) }} sak).
                    Angka ini dikurangkan dari pakan terpakai agar FCR tidak melar, dan jadi dasar perbandingan retur di RHPP. Isi hanya bila catatan harian masih mencakup pakan yang akhirnya tersisa atau diretur.
                </p>
                <form method="POST" action="{{ route('panen.retur', $siklus) }}" class="mt-4 flex flex-wrap items-end gap-3">
                    @csrf @method('PATCH')
                    <div class="w-full sm:w-64">
                        <x-input-satuan name="pakan_retur_sak" label="Pakan sisa/retur" satuan="sak" step="0.01" min="0" inputmode="decimal" :value="$siklus->pakan_retur_sak" required />
                    </div>
                    <x-button variant="secondary">Simpan</x-button>
                </form>
            </section>
        @endif

        @if ($bisaSelesai)
            <x-modal name="panen-selesai">
                <form method="POST" action="{{ route('siklus.panen-selesai', $siklus) }}" class="space-y-4">
                    @csrf
                    <h2 id="panen-selesai-judul" class="font-heading text-lg font-semibold">Tandai panen Siklus {{ $nomor }} selesai?</h2>
                    <p class="text-sm leading-[21px] text-ink-secondary">Status berubah menjadi Menunggu RHPP. {{ $siklus->kandang->nama_farm }} tetap tertahan sampai siklus ditutup. Tahap panen masih dapat diubah sampai siklus ditutup.</p>
                    <div class="flex items-center gap-2 text-sm"><x-badge variant="success" :dot="true">Berjalan</x-badge><x-icon name="arrow-right" class="size-4 text-ink-muted" /><x-badge variant="warning" :dot="true">Menunggu RHPP</x-badge></div>
                    @if ($selisih !== 0)
                        <x-alert variant="warning">
                            <p class="font-semibold">{{ F::angka(abs($selisih), 0) }} ekor {{ $selisih > 0 ? 'belum tercatat' : 'melebihi populasi awal' }}</p>
                            <p>Panen {{ F::angka($p['total_ekor'], 0) }} + deplesi {{ F::angka($p['deplesi'], 0) }} ≠ {{ F::angka($p['populasi'], 0) }}. Anda tetap bisa melanjutkan; selisih tetap ditampilkan.</p>
                        </x-alert>
                    @endif
                    <x-field name="tanggal_panen" label="Tanggal panen terakhir *" type="date" :value="$tanggalTerakhir" max="{{ now()->toDateString() }}" required />
                    <div class="flex justify-end gap-2.5">
                        <x-button type="button" variant="secondary" @click="$dispatch('close-modal', 'panen-selesai')">Batal</x-button>
                        <x-button>Tandai selesai</x-button>
                    </div>
                </form>
            </x-modal>
        @endif
    </x-page>
</x-app-layout>
