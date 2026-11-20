@php use App\Support\Format as F; @endphp

<x-app-layout title="Dashboard" :crumbs="[['Beranda', route('dashboard')], ['Dashboard']]">
    <x-page>
        @if ($ringkasan['total_kandang'] === 0)
            {{-- Sapaan pertama: maskot menyambut, lalu ajakan mendaftarkan kandang. --}}
            <section class="kartu-hero mt-2 flex flex-wrap items-center gap-6 p-6 sm:p-8 lg:mt-12 lg:min-h-[250px] lg:pr-[300px]">
                <div class="min-w-0 flex-1 basis-72 space-y-3">
                    <h1 class="font-heading text-[clamp(1.8rem,3.2vw,2.6rem)] font-semibold leading-[1.06] tracking-[-0.035em]">Selamat datang di NOA</h1>
                    <p class="max-w-md text-[15px] leading-[24px] text-ink-secondary">Mulai dengan mendaftarkan kandang pertama Anda.</p>
                </div>
                <div class="sorot-ayam absolute -top-12 bottom-0 right-6 hidden w-[240px] lg:block"><x-ayam class="inset-y-0" /></div>
            </section>

            <x-empty-state icon="warehouse" title="Belum ada kandang terdaftar"
                text="Ringkasan dan periode akan muncul setelah Anda menambahkan kandang. Periode pemeliharaan dibuka terpisah, dari halaman detail kandang.">
                <x-button :href="route('kandang.create')"><x-icon name="plus" class="size-4" /> Tambah Kandang</x-button>
            </x-empty-state>
        @else
            <section class="kartu-hero mt-2 flex flex-col justify-center gap-5 p-6 sm:p-8 lg:mt-12 lg:min-h-[250px] lg:pr-[300px]">
                <div class="space-y-2.5">
                    <p class="text-[11px] font-bold uppercase tracking-[1.4px] text-ink-brand">Dashboard peternak</p>
                    <h1 class="font-heading text-[clamp(1.8rem,3.2vw,2.6rem)] font-semibold leading-[1.06] tracking-[-0.035em]">{{ $sapaan }}, {{ auth()->user()->name }}</h1>
                    <p class="max-w-lg text-[15px] leading-[24px] text-ink-secondary">{{ 'Ringkasan peternakan per '.now()->translatedFormat('j M Y').'.' }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="kaca-chip inline-flex items-center gap-2 rounded-full px-3.5 py-2 text-[13px] font-semibold"><x-icon name="house" class="size-4 text-ink-brand" /> {{ F::angka($ringkasan['kandang_aktif'], 0) }} kandang aktif</span>
                    @if ($perhatian->isNotEmpty())
                        <span class="kaca-chip inline-flex items-center gap-2 rounded-full px-3.5 py-2 text-[13px] font-semibold"><x-icon name="triangle-alert" class="size-4 text-warning-text" /> {{ $perhatian->count() }} hal yang perlu diurus</span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <x-button :href="route('kandang.create')"><x-icon name="plus" class="size-4" /> Tambah Kandang</x-button>
                    <x-button variant="secondary" :href="route('kandang.index')">Lihat semua kandang</x-button>
                </div>
                <div class="sorot-ayam absolute -top-12 bottom-0 right-6 hidden w-[240px] lg:block"><x-ayam class="inset-y-0" /></div>
            </section>

            @php
                $langkahSelesai = collect($langkahAwal)->where('selesai', true)->count();
                $langkahBerikut = collect($langkahAwal)->firstWhere('selesai', false);
            @endphp
            @if ($langkahBerikut)
                <section aria-labelledby="mulai-di-sini" class="kartu-merek p-5 sm:p-6" data-tur="Mulai di sini|Daftar langkah dari kandang sampai RHPP. Centang otomatis saat Anda menyelesaikannya." data-tur-urut="2">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="chip-ikon size-11 shrink-0" data-nada="merek"><x-icon name="list-checks" class="size-5" /></span>
                            <div>
                                <h2 id="mulai-di-sini" class="font-heading text-lg font-semibold">Mulai di sini</h2>
                                <p class="text-[13px] text-ink-secondary">{{ $langkahSelesai }} dari {{ count($langkahAwal) }} langkah selesai · berikutnya: <strong>{{ $langkahBerikut['judul'] }}</strong></p>
                            </div>
                        </div>
                        <x-button :href="$langkahBerikut['tautan']">{{ $langkahBerikut['label'] }}</x-button>
                    </div>
                    <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-surface" role="progressbar" aria-valuemin="0" aria-valuemax="{{ count($langkahAwal) }}" aria-valuenow="{{ $langkahSelesai }}" aria-label="Kemajuan langkah awal">
                        <div class="h-full rounded-full bg-gradient-to-r from-brand-mint to-brand transition-all" style="width: {{ round($langkahSelesai / count($langkahAwal) * 100) }}%"></div>
                    </div>
                    <ol class="mt-4 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($langkahAwal as $l)
                            <li class="flex items-start gap-2.5 rounded-[16px] bg-surface p-3.5 text-[13px]">
                                <span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full {{ $l['selesai'] ? 'bg-brand text-white' : 'border border-line-control text-transparent' }}">
                                    <x-icon name="check" class="size-3.5" />
                                </span>
                                <span><span class="block font-semibold {{ $l['selesai'] ? 'text-ink-secondary line-through' : '' }}">{{ $l['judul'] }}</span><span class="block text-xs text-ink-muted">{{ $l['teks'] }}</span></span>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4" data-tur="Ringkasan peternakan|Empat angka utama: kandang, periode yang sedang berjalan, dan jumlah ayam saat ini." data-tur-urut="3">
                <x-kpi label="Total Kandang" :value="F::angka($ringkasan['total_kandang'], 0)" caption="Terdaftar atas nama Anda" icon="warehouse" nada="merek" />
                <x-kpi label="Kandang Aktif" :value="F::angka($ringkasan['kandang_aktif'], 0)"
                       :caption="$ringkasan['kandang_aktif'] ? 'Punya periode yang belum ditutup' : 'Tidak ada periode terbuka'" icon="house" nada="emas" />
                <x-kpi label="Periode Berjalan" :value="F::angka($ringkasan['siklus_berjalan'], 0)"
                       :caption="$ringkasan['siklus_berjalan'] ? 'Masih dalam pemeliharaan' : '—'" icon="refresh-cw" nada="persik" />
                <x-kpi label="Populasi Ayam Aktif" :value="$ringkasan['siklus_berjalan'] ? F::angka($ringkasan['populasi_aktif'], 0) : '—'"
                       :caption="$ringkasan['siklus_berjalan'] ? 'Ekor, dari periode berjalan' : 'Belum ada periode berjalan'" icon="feather" nada="biru" />
            </div>

            @if ($perhatian->isNotEmpty())
                <section aria-labelledby="perlu-perhatian" class="space-y-3" data-tur="Perlu perhatian|Hal yang sebaiknya segera Anda urus di semua periode: catatan yang terlewat, stok pakan menipis, dan baris yang belum diparaf." data-tur-urut="4">
                    <div>
                        <h2 id="perlu-perhatian" class="font-heading text-lg font-semibold text-ink">Perlu perhatian</h2>
                        <p class="text-[13px] text-ink-secondary">Hal yang sebaiknya diurus dulu. Ketuk untuk membukanya.</p>
                    </div>
                    <ul class="kartu divide-y divide-line-subtle overflow-hidden !rounded-[22px]">
                        @foreach ($perhatian as $h)
                            <li>
                                <a href="{{ $h['tautan'] }}" class="group flex min-h-[64px] items-center gap-3.5 px-4 py-3 transition-colors hover:bg-state-hover">
                                    <span class="chip-ikon size-10 shrink-0" data-nada="emas"><x-icon name="triangle-alert" class="size-5" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-ink">{{ $h['judul'] }}</span>
                                        <span class="block truncate text-[13px] text-ink-muted">{{ $h['siklus'] }}</span>
                                    </span>
                                    <span class="hidden shrink-0 text-[13px] font-semibold text-ink-brand sm:inline">{{ $h['label'] }}</span>
                                    <x-icon name="chevron-right" class="size-4 shrink-0 text-ink-muted transition-transform group-hover:translate-x-0.5" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <div class="space-y-5">
                <section aria-labelledby="judul-siklus-aktif" class="space-y-3.5">
                    <div class="flex items-end justify-between gap-3">
                        <div>
                            <h2 id="judul-siklus-aktif" class="font-heading text-lg font-semibold text-ink">Periode aktif</h2>
                            <p class="text-[13px] text-ink-secondary">Periode yang belum ditutup, per kandang.</p>
                        </div>
                    </div>

                    @if ($ringkasan['siklus']->isEmpty())
                        <x-empty-state icon="calendar-plus" title="Belum ada periode berjalan"
                            text="Kandang Anda sudah terdaftar, tetapi belum ada periode pemeliharaan yang dibuka. Buka periode dari halaman detail kandang.">
                            <x-button :href="route('kandang.index')">Buka Periode</x-button>
                        </x-empty-state>
                    @else
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-3">
                            @foreach ($ringkasan['siklus'] as $k)
                                @php
                                    $s = $k['siklus'];
                                    $hidup = $s->populasi > 0 ? max(0, min(100, round($k['populasi_kini'] / $s->populasi * 100))) : 0;
                                @endphp
                                <article class="kartu flex flex-col gap-4 p-5">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <span class="chip-ikon size-11 shrink-0" data-nada="merek"><x-icon name="warehouse" class="size-5" /></span>
                                            <div class="min-w-0">
                                                <h3 class="font-heading text-lg font-semibold leading-snug">Periode {{ $k['nomor'] }}</h3>
                                                <p class="truncate text-[13px] text-ink-secondary">{{ $s->kandang->nama_farm }}</p>
                                            </div>
                                        </div>
                                        <x-status-siklus :siklus="$s" />
                                    </div>
                                    <div class="space-y-1.5">
                                        <div class="flex items-center justify-between text-[12px] font-semibold text-ink-secondary"><span>Ayam hidup</span><span>{{ $hidup }}%</span></div>
                                        <div class="h-2 overflow-hidden rounded-full bg-page" role="img" aria-label="Ayam hidup {{ $hidup }} persen dari jumlah awal"><div class="h-full rounded-full bg-gradient-to-r from-brand-mint to-brand" style="width: {{ $hidup }}%"></div></div>
                                    </div>
                                    <dl class="space-y-2.5 rounded-[16px] bg-page p-3.5 text-[13px]">
                                        <div class="flex justify-between gap-3"><dt class="text-ink-secondary">DOC masuk</dt><dd class="font-bold">{{ $s->tanggal_masuk->translatedFormat('j M Y') }}</dd></div>
                                        <div class="flex justify-between gap-3"><dt class="text-ink-secondary">Umur ayam</dt><dd class="font-bold">Hari ke-{{ $k['umur'] }}</dd></div>
                                        <div class="flex justify-between gap-3"><dt class="text-ink-secondary">Jumlah ayam awal</dt><dd class="font-bold">{{ F::angka($s->populasi, 0) }} ekor</dd></div>
                                        <div class="flex justify-between gap-3"><dt class="text-ink-secondary">Ayam hidup kini</dt><dd class="font-bold">{{ F::angka($k['populasi_kini'], 0) }} ekor</dd></div>
                                    </dl>
                                    <a href="{{ route('siklus.show', $s) }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-[14px] border border-brand-border bg-brand-soft text-[13px] font-bold text-ink-brand transition hover:bg-state-selected">
                                        Buka detail periode <x-icon name="arrow-right" class="size-4" />
                                    </a>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        @endif
    </x-page>
</x-app-layout>
