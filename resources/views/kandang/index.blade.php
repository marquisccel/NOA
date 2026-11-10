@php use App\Support\Format as F; @endphp

<x-app-layout title="Kandang" :crumbs="[['Beranda', route('dashboard')], ['Kandang & Siklus']]">
    <x-page>
        <x-page-header title="Kandang" subtitle="Kelola kandang Anda. Setiap kandang hanya memiliki satu siklus terbuka pada satu waktu.">
            <x-slot name="actions">
                <x-button :href="route('kandang.create')"><x-icon name="plus" class="size-4" /> Tambah Kandang</x-button>
            </x-slot>
        </x-page-header>

        @if ($totalKandang === 0)
            <x-empty-state icon="warehouse" title="Belum ada kandang"
                text="Daftarkan kandang dengan nama, alamat, kapasitas, dan tipe kandang. Setelah tersimpan, siklus pemeliharaan dibuka terpisah dari halaman detail kandang.">
                <x-button :href="route('kandang.create')"><x-icon name="plus" class="size-4" /> Tambah Kandang</x-button>
            </x-empty-state>
        @else
            <section class="rounded-[16px] border border-line bg-surface" aria-label="Daftar kandang">
                <form method="GET" action="{{ route('kandang.index') }}" class="flex flex-wrap items-center gap-2.5 p-3 sm:p-4">
                    <div class="relative min-w-0 flex-1 sm:max-w-xs">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-muted" />
                        <input type="search" name="q" value="{{ $q }}" placeholder="Cari nama kandang" aria-label="Cari nama kandang"
                               class="block min-h-11 w-full rounded-md border-line-control bg-surface pl-9 text-base placeholder:text-ink-muted focus:border-brand focus:ring-1 focus:ring-brand">
                    </div>
                    <select name="status" aria-label="Status siklus" onchange="this.form.requestSubmit()"
                            class="min-h-11 rounded-md border-line-control bg-surface text-sm focus:border-brand focus:ring-1 focus:ring-brand">
                        <option value="">Status siklus: Semua</option>
                        <option value="berjalan" @selected($status === 'berjalan')>Punya siklus terbuka</option>
                        <option value="tanpa" @selected($status === 'tanpa')>Tanpa siklus</option>
                    </select>
                    <select name="tipe" aria-label="Tipe kandang" onchange="this.form.requestSubmit()"
                            class="min-h-11 rounded-md border-line-control bg-surface text-sm focus:border-brand focus:ring-1 focus:ring-brand">
                        <option value="">Tipe: Semua</option>
                        @foreach (\App\Models\Kandang::TIPE as $kunci => [$label])
                            <option value="{{ $kunci }}" @selected($tipe === $kunci)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-button variant="secondary" class="min-h-11">Cari</x-button>
                    <p class="ms-auto text-[13px] text-ink-secondary" role="status">
                        @if ($adaFilter)
                            {{ F::angka($kandang->count(), 0) }} hasil
                        @else
                            {{ F::angka($totalKandang, 0) }} kandang · {{ F::angka($aktif, 0) }} punya siklus terbuka
                        @endif
                    </p>
                </form>

                @if ($kandang->isEmpty())
                    <div class="flex flex-col items-center gap-2 border-t border-[#E1E6E2] px-6 py-12 text-center">
                        <span class="grid size-12 place-items-center rounded-full bg-tint text-ink-brand"><x-icon name="search-x" class="size-6" /></span>
                        <p class="font-heading text-lg font-semibold">
                            @if ($q !== '') Tidak ada kandang dengan nama “{{ $q }}” @else Tidak ada kandang yang cocok @endif
                        </p>
                        <p class="text-sm text-ink-secondary">Periksa ejaan atau hapus filter yang aktif.</p>
                        <x-button variant="secondary" :href="route('kandang.index')" class="mt-2">Hapus pencarian &amp; filter</x-button>
                    </div>
                @else
                    {{-- Tabel di layar lebar --}}
                    <div class="hidden overflow-x-auto md:block">
                        <table class="tabel">
                            <caption class="sr-only">Daftar kandang beserta siklus yang sedang terbuka</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Nama kandang · Alamat</th>
                                    <th scope="col">Kapasitas</th>
                                    <th scope="col">Tipe</th>
                                    <th scope="col">Siklus</th>
                                    <th scope="col">Status siklus</th>
                                    <th scope="col">DOC masuk</th>
                                    <th scope="col">Umur</th>
                                    <th scope="col"><span class="sr-only">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($kandang as $k)
                                    @php $s = $k->siklusBerjalan; @endphp
                                    <tr>
                                        <th scope="row" class="min-w-56">
                                            <a href="{{ route('kandang.show', $k) }}" class="font-bold text-ink hover:underline">{{ $k->nama_farm }}</a>
                                            <span class="mt-0.5 block text-xs text-ink-secondary">{{ $k->alamat ?: 'Alamat belum diisi' }}</span>
                                        </th>
                                        <td class="whitespace-nowrap">{{ F::angka($k->kapasitas, 0) }} ekor</td>
                                        <td>{{ $k->labelTipe() ?? '—' }}</td>
                                        <td>{{ $s ? 'Siklus '.($nomor[$s->id] ?? 1) : '—' }}</td>
                                        <td>@if ($s)<x-status-siklus :siklus="$s" />@else<x-badge variant="neutral">Tidak ada siklus</x-badge>@endif</td>
                                        <td class="whitespace-nowrap">{{ $s ? $s->tanggal_masuk->translatedFormat('j M Y') : '—' }}</td>
                                        <td class="whitespace-nowrap">{{ $s ? 'Hari ke-'.$s->umurHari() : '—' }}</td>
                                        <td class="text-right"><a href="{{ route('kandang.show', $k) }}" class="inline-flex min-h-11 items-center whitespace-nowrap text-[13px] font-semibold text-ink-brand hover:underline">Detail kandang</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Kartu di ponsel --}}
                    <ul class="divide-y divide-line-subtle border-t border-line-subtle md:hidden">
                        @foreach ($kandang as $k)
                            @php $s = $k->siklusBerjalan; @endphp
                            <li>
                                <a href="{{ route('kandang.show', $k) }}" class="flex flex-col gap-2 p-4 hover:bg-state-hover">
                                    <span class="flex items-start justify-between gap-2">
                                        <span class="font-heading text-base font-semibold">{{ $k->nama_farm }}</span>
                                        @if ($s)<x-status-siklus :siklus="$s" />@else<x-badge variant="neutral">Tidak ada siklus</x-badge>@endif
                                    </span>
                                    <span class="text-[13px] text-ink-secondary">{{ F::angka($k->kapasitas, 0) }} ekor · {{ $k->labelTipe() ?? 'Tipe belum diisi' }}</span>
                                    <span class="text-[13px] text-ink-secondary">
                                        @if ($s) Siklus {{ $nomor[$s->id] ?? 1 }} · DOC {{ $s->tanggal_masuk->translatedFormat('j M Y') }} · Hari ke-{{ $s->umurHari() }}
                                        @else Belum ada siklus terbuka @endif
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif
    </x-page>
</x-app-layout>
