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

            @php
                $langkahSelesai = collect($langkahAwal)->where('selesai', true)->count();
                $langkahBerikut = collect($langkahAwal)->firstWhere('selesai', false);
            @endphp
            @if ($langkahBerikut)
                <section aria-labelledby="mulai-di-sini" class="rounded-[16px] border border-brand-border bg-tint p-5" data-tur="Mulai di sini|Daftar langkah dari kandang sampai RHPP. Centang otomatis saat Anda menyelesaikannya." data-tur-urut="2">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 id="mulai-di-sini" class="flex items-center gap-2 font-heading text-lg font-semibold"><x-icon name="list-checks" class="size-5 text-ink-brand" /> Mulai di sini</h2>
                            <p class="text-[13px] text-ink-secondary">{{ $langkahSelesai }} dari {{ count($langkahAwal) }} langkah selesai · berikutnya: <strong>{{ $langkahBerikut['judul'] }}</strong></p>
                        </div>
                        <x-button :href="$langkahBerikut['tautan']">{{ $langkahBerikut['label'] }}</x-button>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-surface" role="progressbar" aria-valuemin="0" aria-valuemax="{{ count($langkahAwal) }}" aria-valuenow="{{ $langkahSelesai }}" aria-label="Kemajuan langkah awal">
                        <div class="h-full rounded-full bg-brand transition-all" style="width: {{ round($langkahSelesai / count($langkahAwal) * 100) }}%"></div>
                    </div>
                    <ol class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($langkahAwal as $l)
                            <li class="flex items-start gap-2.5 rounded-md bg-surface p-3 text-[13px]">
                                <span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full {{ $l['selesai'] ? 'bg-brand text-white' : 'border border-line-control text-transparent' }}">
                                    <x-icon name="check" class="size-3.5" />
                                </span>
                                <span><span class="block font-semibold {{ $l['selesai'] ? 'text-ink-secondary line-through' : '' }}">{{ $l['judul'] }}</span><span class="block text-xs text-ink-muted">{{ $l['teks'] }}</span></span>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4" data-tur="Ringkasan peternakan|Empat angka utama: kandang, siklus yang sedang berjalan, dan jumlah ayam saat ini." data-tur-urut="3">
                <x-kpi label="Total Kandang" :value="F::angka($ringkasan['total_kandang'], 0)" caption="Terdaftar atas nama Anda" icon="warehouse" />
                <x-kpi label="Kandang Aktif" :value="F::angka($ringkasan['kandang_aktif'], 0)"
                       :caption="$ringkasan['kandang_aktif'] ? 'Punya siklus yang belum ditutup' : 'Tidak ada siklus terbuka'" icon="house" />
                <x-kpi label="Siklus Berjalan" :value="F::angka($ringkasan['siklus_berjalan'], 0)"
                       :caption="$ringkasan['siklus_berjalan'] ? 'Masih dalam pemeliharaan' : '—'" icon="refresh-cw" />
                <x-kpi label="Populasi Ayam Aktif" :value="$ringkasan['siklus_berjalan'] ? F::angka($ringkasan['populasi_aktif'], 0) : '—'"
                       :caption="$ringkasan['siklus_berjalan'] ? 'Ekor, dari siklus berjalan' : 'Belum ada siklus berjalan'" icon="feather" />
            </div>

            @if ($perhatian->isNotEmpty())
                <section aria-labelledby="perlu-perhatian" class="space-y-3" data-tur="Perlu perhatian|Hal yang sebaiknya segera Anda urus di semua siklus: catatan yang terlewat, stok pakan menipis, dan baris yang belum diparaf." data-tur-urut="4">
                    <div>
                        <h2 id="perlu-perhatian" class="text-base font-bold text-ink">Perlu perhatian</h2>
                        <p class="text-[13px] text-ink-secondary">Hal yang sebaiknya diurus dulu di siklus yang sedang berjalan.</p>
                    </div>
                    <ul class="grid gap-2.5 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($perhatian as $h)
                            <li class="flex items-start gap-3 rounded-[16px] border {{ $h['tingkat'] === 'penting' ? 'border-warning-border bg-warning-soft' : 'border-line bg-surface' }} p-4">
                                <span class="mt-0.5 shrink-0 {{ $h['tingkat'] === 'penting' ? 'text-warning-text' : 'text-ink-brand' }}"><x-icon :name="$h['tingkat'] === 'penting' ? 'triangle-alert' : 'info'" class="size-5" /></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold">{{ $h['judul'] }}</p>
                                    <p class="text-xs text-ink-secondary">{{ $h['siklus'] }} · {{ $h['teks'] }}</p>
                                    <a href="{{ $h['tautan'] }}" class="mt-1 inline-flex min-h-11 items-center text-[13px] font-bold text-ink-brand hover:underline">{{ $h['label'] }} →</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

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
            </div>
        @endif
    </x-page>
</x-app-layout>
