@php
    use App\Support\Format as F;

    $namaSiklus = fn ($s) => 'Siklus '.($nomor[$s->id] ?? 1);
@endphp

<x-app-layout :title="$kandang->nama_farm" :crumbs="[['Kandang & Siklus', route('kandang.index')], [$kandang->nama_farm]]">
    <x-page>
        <div class="space-y-3">
            <a href="{{ route('kandang.index') }}" class="inline-flex min-h-11 items-center gap-1.5 text-[13px] font-semibold text-ink-brand hover:underline">
                <x-icon name="arrow-left" class="size-4" /> Kembali ke daftar kandang
            </a>
            <x-page-header :title="$kandang->nama_farm">
                <x-slot name="actions">
                    <x-button variant="secondary" :href="route('laporan.perbandingan', $kandang)">Bandingkan siklus</x-button>
                </x-slot>
            </x-page-header>
        </div>

        <div class="grid items-start gap-5 xl:grid-cols-2">
            <section class="rounded-[16px] border border-line bg-surface p-5" aria-labelledby="judul-info">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="judul-info" class="text-base font-bold">Informasi kandang</h2>
                    <a href="{{ route('kandang.edit', $kandang) }}" class="inline-flex min-h-11 items-center gap-1.5 text-[13px] font-semibold text-ink-brand hover:underline"><x-icon name="pencil" class="size-4" /> Ubah</a>
                </div>
                <dl class="mt-3 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div><dt class="text-xs text-ink-muted">Nama kandang</dt><dd class="mt-0.5 text-sm font-semibold">{{ $kandang->nama_farm }}</dd></div>
                    <div><dt class="text-xs text-ink-muted">Kapasitas</dt><dd class="mt-0.5 text-sm font-semibold">{{ F::angka($kandang->kapasitas, 0) }} ekor</dd></div>
                    <div><dt class="text-xs text-ink-muted">Tipe kandang</dt><dd class="mt-0.5 text-sm font-semibold">{{ $kandang->labelTipe() ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-ink-muted">Pemilik</dt><dd class="mt-0.5 text-sm font-semibold">{{ $kandang->user->name }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-ink-muted">Alamat</dt><dd class="mt-0.5 text-sm font-semibold">{{ $kandang->alamat ?: 'Belum diisi' }}</dd></div>
                </dl>
            </section>

            @if ($berjalan)
                <section class="rounded-[16px] border border-line bg-surface p-5" aria-labelledby="judul-saat-ini">
                    <p class="text-[11px] font-bold uppercase tracking-[1.2px] text-ink-muted">Siklus saat ini</p>
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <h2 id="judul-saat-ini" class="font-heading text-xl font-semibold">{{ $namaSiklus($berjalan) }}</h2>
                            <x-status-siklus :siklus="$berjalan" />
                        </div>
                        <x-button :href="route('siklus.show', $berjalan)" variant="secondary" class="min-h-11">Buka Detail Siklus</x-button>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-4 rounded-md bg-page p-3.5 text-[13px]">
                        <div><dt class="text-ink-secondary">DOC masuk</dt><dd class="mt-0.5 font-bold">{{ $berjalan->tanggal_masuk->translatedFormat('j M Y') }}</dd></div>
                        <div><dt class="text-ink-secondary">Umur ayam</dt><dd class="mt-0.5 font-bold">Hari ke-{{ $berjalan->umurHari() }}</dd></div>
                        <div><dt class="text-ink-secondary">Populasi awal</dt><dd class="mt-0.5 font-bold">{{ F::angka($berjalan->populasi, 0) }} ekor</dd></div>
                        <div><dt class="text-ink-secondary">Status</dt><dd class="mt-0.5 font-bold">{{ $berjalan->labelStatus() }}</dd></div>
                    </dl>
                    <p class="mt-3 text-xs text-ink-muted">Siklus baru dapat dibuka setelah {{ $namaSiklus($berjalan) }} ditutup.</p>
                </section>
            @else
                <section class="rounded-[16px] border border-gold-border bg-gold-tint p-5" aria-labelledby="judul-saat-ini">
                    <div class="flex items-start gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-md bg-gold-soft text-ink-gold"><x-icon name="calendar-plus" /></span>
                        <div class="min-w-0 flex-1 space-y-1.5">
                            <h2 id="judul-saat-ini" class="text-base font-bold">Tidak ada siklus terbuka</h2>
                            <p class="text-[13px] leading-[19px] text-ink-secondary">
                                @if ($terakhir)
                                    {{ $namaSiklus($terakhir) }} sudah {{ $terakhir->status === 'dihentikan' ? 'dihentikan' : 'ditutup' }}@if ($terakhir->tanggal_rechick) (rencana re-chick-in {{ $terakhir->tanggal_rechick->translatedFormat('j M Y') }})@endif.
                                    Buka siklus baru untuk mulai mencatat.
                                @else
                                    Kandang ini belum pernah dipakai. Buka siklus pertama untuk mulai mencatat.
                                @endif
                            </p>
                        </div>
                    </div>
                    <x-button type="button" class="mt-4" x-data x-on:click="$dispatch('open-modal', 'buka-siklus')">Buka Siklus Baru</x-button>
                </section>
            @endif
        </div>

        <section class="rounded-[16px] border border-line bg-surface" aria-labelledby="judul-riwayat">
            <div class="flex flex-wrap items-center justify-between gap-3 p-4 sm:p-5">
                <div>
                    <h2 id="judul-riwayat" class="text-base font-bold">Riwayat siklus</h2>
                    <p class="text-[13px] text-ink-secondary">{{ $totalRiwayat }} siklus tercatat untuk kandang ini</p>
                </div>
                @if ($totalRiwayat > 0)
                    <a href="{{ route('kandang.riwayat', $kandang) }}" class="inline-flex min-h-11 items-center text-[13px] font-semibold text-ink-brand hover:underline">Lihat semua riwayat</a>
                @endif
            </div>

            @if ($riwayat->isEmpty())
                <p class="border-t border-line-subtle px-5 py-8 text-center text-sm text-ink-secondary">Belum ada siklus yang selesai di kandang ini.</p>
            @else
                @include('kandang.partials.tabel-riwayat', ['daftar' => $riwayat])
            @endif
        </section>

        {{-- Dialog titik masuk "Buka siklus baru" --}}
        @unless ($berjalan)
            <x-modal name="buka-siklus" maxWidth="lg">
                <h2 id="buka-siklus-judul" class="font-heading text-lg font-semibold">Buka siklus baru</h2>
                <p class="text-sm leading-[21px] text-ink-secondary">Siklus baru akan dibuat untuk kandang berikut. Setelah dibuka, kandang ini tidak dapat membuka siklus lain sampai siklusnya ditutup.</p>
                <div class="rounded-md border border-line bg-page p-3.5">
                    <p class="text-sm font-bold">{{ $kandang->nama_farm }}</p>
                    <p class="mt-0.5 text-[13px] text-ink-secondary">{{ F::angka($kandang->kapasitas, 0) }} ekor · {{ $kandang->labelTipe() ?? 'Tipe belum diisi' }} · Siklus berikutnya: Siklus {{ $nomorBerikutnya }}</p>
                </div>
                <p class="text-[13px] leading-[19px] text-ink-secondary">Langkah berikutnya adalah inisialisasi siklus: data DOC, kontrak, skema bonus, dan tabel standar harian.</p>
                <div class="flex justify-end gap-2.5 pt-2">
                    <x-button type="button" variant="secondary" x-on:click="$dispatch('close-modal', 'buka-siklus')">Batal</x-button>
                    <x-button :href="route('siklus.create', ['kandang_id' => $kandang->id])" autofocus>Lanjut ke inisialisasi siklus</x-button>
                </div>
            </x-modal>
        @endunless
    </x-page>
</x-app-layout>
