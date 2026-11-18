@php use App\Support\Format as F; @endphp

<x-app-layout title="Dashboard" :crumbs="[['Beranda', route('dashboard')], ['Dashboard']]">
    <x-page>
        @if ($ringkasan['total_kandang'] === 0)
            <x-page-header title="Selamat datang di NOA" subtitle="Mulai dengan mendaftarkan kandang pertama Anda." />

            <x-empty-state icon="warehouse" title="Belum ada kandang terdaftar"
                text="Ringkasan dan siklus akan muncul setelah Anda menambahkan kandang. Siklus pemeliharaan dibuka terpisah, dari halaman detail kandang.">
                <x-button :href="route('kandang.create')"><x-icon name="plus" class="size-4" /> Tambah Kandang</x-button>
            </x-empty-state>
        @else
            <x-page-header :title="$sapaan.', '.auth()->user()->name" :subtitle="'Ringkasan peternakan per '.now()->translatedFormat('j M Y').'.'">
                <x-slot name="actions">
                    <x-button :href="route('kandang.create')"><x-icon name="plus" class="size-4" /> Tambah Kandang</x-button>
                </x-slot>
            </x-page-header>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
                <x-kpi label="Total Kandang" :value="F::angka($ringkasan['total_kandang'], 0)" caption="Terdaftar atas nama Anda" icon="warehouse" />
                <x-kpi label="Kandang Aktif" :value="F::angka($ringkasan['kandang_aktif'], 0)"
                       :caption="$ringkasan['kandang_aktif'] ? 'Punya siklus yang belum ditutup' : 'Tidak ada siklus terbuka'" icon="house" />
                <x-kpi label="Siklus Berjalan" :value="F::angka($ringkasan['siklus_berjalan'], 0)"
                       :caption="$ringkasan['siklus_berjalan'] ? 'Masih dalam pemeliharaan' : '—'" icon="refresh-cw" />
                <x-kpi label="Populasi Ayam Aktif" :value="$ringkasan['siklus_berjalan'] ? F::angka($ringkasan['populasi_aktif'], 0) : '—'"
                       :caption="$ringkasan['siklus_berjalan'] ? 'Ekor, dari siklus berjalan' : 'Belum ada siklus berjalan'" icon="feather" />
            </div>

            <div class="space-y-5">
                <section aria-labelledby="judul-siklus-aktif" class="space-y-3.5">
                    <div class="flex items-end justify-between gap-3">
                        <div>
                            <h2 id="judul-siklus-aktif" class="text-base font-bold text-ink">Siklus aktif</h2>
                            <p class="text-[13px] text-ink-secondary">Siklus yang belum ditutup, per kandang.</p>
                        </div>
                        <a href="{{ route('kandang.index') }}" class="inline-flex min-h-11 items-center text-[13px] font-semibold text-ink-brand hover:underline">Lihat semua kandang</a>
                    </div>

                    @if ($ringkasan['siklus']->isEmpty())
                        <x-empty-state icon="calendar-plus" title="Belum ada siklus berjalan"
                            text="Kandang Anda sudah terdaftar, tetapi belum ada siklus pemeliharaan yang dibuka. Buka siklus dari halaman detail kandang.">
                            <x-button :href="route('kandang.index')">Buka Siklus</x-button>
                        </x-empty-state>
                    @else
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-3">
                            @foreach ($ringkasan['siklus'] as $k)
                                @php $s = $k['siklus']; @endphp
                                <article class="flex flex-col gap-4 rounded-[16px] border border-line bg-surface p-5">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <h3 class="font-heading text-lg font-semibold leading-snug">Siklus {{ $k['nomor'] }}</h3>
                                            <p class="mt-1 flex items-center gap-1.5 truncate text-[13px] text-ink-secondary"><x-icon name="warehouse" class="size-3.5" /> {{ $s->kandang->nama_farm }}</p>
                                        </div>
                                        <x-status-siklus :siklus="$s" />
                                    </div>
                                    <dl class="space-y-2.5 rounded-md bg-page p-3.5 text-[13px]">
                                        <div class="flex justify-between gap-3"><dt class="text-ink-secondary">DOC masuk</dt><dd class="font-bold">{{ $s->tanggal_masuk->translatedFormat('j M Y') }}</dd></div>
                                        <div class="flex justify-between gap-3"><dt class="text-ink-secondary">Umur ayam</dt><dd class="font-bold">Hari ke-{{ $k['umur'] }}</dd></div>
                                        <div class="flex justify-between gap-3"><dt class="text-ink-secondary">Jumlah ayam awal</dt><dd class="font-bold">{{ F::angka($s->populasi, 0) }} ekor</dd></div>
                                        <div class="flex justify-between gap-3"><dt class="text-ink-secondary">Ayam hidup kini</dt><dd class="font-bold">{{ F::angka($k['populasi_kini'], 0) }} ekor</dd></div>
                                    </dl>
                                    <a href="{{ route('siklus.show', $s) }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md border border-brand-border bg-tint text-[13px] font-bold text-ink-brand hover:bg-state-selected">
                                        Buka detail siklus <x-icon name="arrow-right" class="size-4" />
                                    </a>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>

                @php
                    $sb = $performaSebelumnya;
                    $banding = fn ($sekarang, $lalu, $d = 3) => $lalu === null ? 'Periode lalu: –' : 'Periode lalu: '.F::angka($lalu, $d);
                    $tahunLaporan = strlen($performa['periode']) === 4 ? $performa['periode'] : substr($performa['periode'], 0, 4);
                @endphp
                <section id="performa" aria-labelledby="judul-performa" class="space-y-3.5">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h2 id="judul-performa" class="text-base font-bold text-ink">Performa</h2>
                            <p class="text-[13px] text-ink-secondary">Hasil siklus yang selesai dipanen pada <strong>{{ $performa['label'] }}</strong>.</p>
                        </div>
                        <form method="GET" action="{{ route('dashboard') }}#performa" x-data>
                            <label for="periode" class="sr-only">Pilih periode</label>
                            <select id="periode" name="periode" class="min-h-11 rounded-md border-line-control bg-surface px-3 text-sm font-medium focus:border-brand focus:ring-1 focus:ring-brand" @change="$el.form.submit()">
                                <optgroup label="Per bulan">
                                    @foreach ($pilihanPeriode['bulan'] as $nilai => $label)<option value="{{ $nilai }}" @selected($performa['periode'] === (string) $nilai)>{{ $label }}</option>@endforeach
                                </optgroup>
                                <optgroup label="Per tahun">
                                    @foreach ($pilihanPeriode['tahun'] as $nilai => $label)<option value="{{ $nilai }}" @selected($performa['periode'] === (string) $nilai)>{{ $label }}</option>@endforeach
                                </optgroup>
                            </select>
                            <noscript><x-button variant="secondary">Tampilkan</x-button></noscript>
                        </form>
                    </div>

                    @if ($performa['jumlah_siklus'] === 0)
                        <div class="rounded-[16px] border border-line bg-surface px-5 py-8 text-center">
                            <p class="text-sm font-semibold">Belum ada siklus yang dipanen pada {{ $performa['label'] }}</p>
                            <p class="mx-auto mt-1 max-w-md text-[13px] leading-[19px] text-ink-secondary">FCR, IP, deplesi, dan biaya per kg muncul setelah ada siklus yang sudah ada catatan panennya pada periode ini. Coba pilih periode lain.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-6 lg:gap-4">
                            <x-kpi label="Siklus dipanen" :value="F::angka($performa['jumlah_siklus'], 0)" caption="Dengan catatan panen" />
                            <x-kpi label="Total panen" :value="F::angka($performa['total_ekor'], 0).' ekor'" :caption="F::angka($performa['total_tonase'], 0).' kg'" />
                            <x-kpi label="FCR" istilah="fcr" :value="F::angka($performa['fcr'], 3)" :caption="$banding($performa['fcr'], $sb['fcr']).' · makin kecil makin baik'" />
                            <x-kpi label="IP rata-rata" istilah="ip" :value="F::angka($performa['ip'], 0)" :caption="$banding($performa['ip'], $sb['ip'], 0).' · makin besar makin baik'" />
                            <x-kpi label="Deplesi" istilah="deplesi" :value="F::angka($performa['deplesi_persen']).'%'" :caption="$banding($performa['deplesi_persen'], $sb['deplesi_persen'], 2).($sb['deplesi_persen'] === null ? '' : '%')" />
                            <x-kpi label="Biaya per kg" :value="$performa['biaya_per_kg'] === null ? '–' : 'Rp '.F::angka($performa['biaya_per_kg'], 0)" :caption="$performa['biaya_per_kg'] === null ? 'Harga kontrak belum lengkap' : $banding(null, $sb['biaya_per_kg'], 0)" />
                        </div>
                        <a href="{{ route('laporan.index', ['tahun' => $tahunLaporan]) }}" class="inline-flex min-h-11 items-center text-[13px] font-semibold text-ink-brand hover:underline">Lihat perbandingan lengkap per siklus →</a>
                    @endif
                </section>
            </div>
        @endif
    </x-page>
</x-app-layout>
