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

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
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
                                        <div class="flex justify-between gap-3"><dt class="text-ink-secondary">Populasi awal</dt><dd class="font-bold">{{ F::angka($s->populasi, 0) }} ekor</dd></div>
                                        <div class="flex justify-between gap-3"><dt class="text-ink-secondary">Populasi kini</dt><dd class="font-bold">{{ F::angka($k['populasi_kini'], 0) }} ekor</dd></div>
                                    </dl>
                                    <a href="{{ route('siklus.show', $s) }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md border border-brand-border bg-tint text-[13px] font-bold text-ink-brand hover:bg-state-selected">
                                        Buka detail siklus <x-icon name="arrow-right" class="size-4" />
                                    </a>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section aria-labelledby="judul-aktivitas" class="rounded-[16px] border border-line bg-surface p-5">
                    <h2 id="judul-aktivitas" class="text-base font-bold text-ink">Aktivitas terbaru</h2>
                    @if ($aktivitas->isEmpty())
                        <div class="flex flex-col items-center gap-2 py-8 text-center">
                            <span class="grid size-11 place-items-center rounded-full bg-tint text-ink-brand"><x-icon name="history" /></span>
                            <p class="text-sm font-semibold">Belum ada aktivitas</p>
                            <p class="text-[13px] leading-[19px] text-ink-secondary">Recording, sampling, OVK, dan panen akan tercatat di sini setelah siklus berjalan.</p>
                        </div>
                    @else
                        <ul class="mt-3 divide-y divide-line-subtle">
                            @foreach ($aktivitas as $a)
                                <li class="flex items-center gap-3 py-3">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-md bg-tint text-ink-brand"><x-icon :name="$a['ikon']" class="size-[18px]" /></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold">{{ $a['jenis'] }}</p>
                                        <p class="truncate text-xs text-ink-secondary">{{ $a['keterangan'] }}</p>
                                    </div>
                                    <time class="shrink-0 text-xs text-ink-muted" datetime="{{ $a['tanggal']->toDateString() }}">{{ $a['tanggal']->translatedFormat('j M Y') }}</time>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>
        @endif
    </x-page>
</x-app-layout>
