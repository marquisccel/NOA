@php
    use App\Support\Format as F;

    $peternak = auth()->user()->isPeternak();
    $bacaSaja = auth()->user()->isPengawas();
    $ppl = $siklus->nama_ppl ?: 'PPL';
    $angka = fn ($n, $d = 1) => $n === null ? '–' : F::angka($n, $d);
    $persen = fn ($n) => $n === null ? '–' : F::angka($n, 2).'%';
    $terparaf = collect($daftar)->where('jenis', 'hari')->filter(fn ($d) => $d['baris']['recording']->diparaf_ppl);
@endphp

<x-app-layout title="Recording harian" :siklus="$siklus"
    :crumbs="[['Kandang & Siklus', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['Recording']]">
    <x-page>
        <x-page-header title="Recording harian"
            :subtitle="$siklus->isTerkunci() ? 'Siklus ditutup · semua baris hanya dapat dilihat' : 'Kolom STD dari tabel standar kemitraan · angka oranye = di atas standar'">
            <x-slot name="actions">
                @if (! empty($daftar) && ! auth()->user()->isPengawas())<x-button variant="secondary" :href="route('recording.csv', $siklus)"><x-icon name="download" class="size-4" /> Unduh CSV</x-button>@endif
                @if ($bisaIsi)
                    <x-button :href="route('recording.create', $siklus)"><x-icon name="plus" class="size-4" /> Isi recording hari ini</x-button>
                @endif
            </x-slot>
        </x-page-header>

        @if (empty($daftar))
            <x-empty-state icon="clipboard-list" title="Belum ada recording"
                text="Recording hari ke-1 ({{ $siklus->tanggal_masuk->translatedFormat('j M Y') }}) dapat diisi mulai {{ $siklus->tanggal_masuk->isFuture() ? 'tanggal itu' : 'hari ini' }}. Isi pakan terpakai, ayam mati, dan ayam afkir — selesai kurang dari satu menit.">
                @if ($bisaIsi)<x-button :href="route('recording.create', $siklus)">Isi recording hari ke-1</x-button>@endif
            </x-empty-state>
        @else
            @if ($belumDiisi && $bisaIsi)
                @php $tglBelum = $siklus->tanggal_masuk->copy()->addDays($belumDiisi - 1); @endphp
                <x-alert variant="warning">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="font-semibold">Hari ke-{{ $belumDiisi }} ({{ $tglBelum->translatedFormat('j M Y') }}) belum diisi</p>
                            <p>Entri mundur diizinkan. Angka berjalan dihitung ulang setelah disimpan.</p>
                        </div>
                        <a href="{{ route('recording.create', [$siklus, 'tanggal' => $tglBelum->toDateString()]) }}" class="inline-flex min-h-11 items-center font-semibold underline">Isi hari ke-{{ $belumDiisi }}</a>
                    </div>
                </x-alert>
            @endif

            @if ($terparaf->isNotEmpty() && ! $siklus->isTerkunci())
                <x-alert variant="info">
                    <p class="font-semibold">Baris terparaf terkunci</p>
                    <p>Baris yang sudah diparaf oleh {{ $ppl }} tidak dapat diubah agar tetap sah sebagai bukti saat rekonsiliasi (R11). Bila ada kesalahan, catat koreksi di hari berikutnya dan konfirmasi dengan PPL.</p>
                </x-alert>
            @endif

            @unless ($standarAda)
                <x-alert variant="info">
                    Tabel standar belum diunggah, jadi kolom standar kosong.
                    @if ($peternak)<a href="{{ route('siklus.standar', $siklus) }}" class="font-semibold underline">Unggah tabel standar</a>@else Minta peternak mengunggahnya.@endif
                </x-alert>
            @endunless

            <div class="overflow-x-auto rounded-[16px] border border-line bg-surface">
                <table class="tabel min-w-[960px]">
                    <caption class="sr-only">Recording harian: nilai aktual dan standar kemitraan</caption>
                    <thead>
                        <tr>
                            <th scope="col">Hari</th><th scope="col">Tanggal</th><th scope="col" class="text-right">Pakan std</th><th scope="col" class="text-right">Pakan akt.</th>
                            <th scope="col" class="text-right">Ayam mati</th><th scope="col" class="text-right">Ayam afkir</th><th scope="col" class="text-right">Depl. std</th>
                            <th scope="col" class="text-right">Depl. akt.</th><th scope="col" class="text-right">Stok pakan</th><th scope="col">Paraf PPL</th>@unless ($bacaSaja)<th scope="col">Aksi</th>@endunless
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daftar as $d)
                            @if ($d['jenis'] === 'minggu')
                                <tr class="!bg-tint font-semibold">
                                    <th scope="row" class="!font-bold text-ink-brand">W</th>
                                    <td>Minggu {{ $d['minggu'] }}</td>
                                    <td></td>
                                    <td class="text-right">{{ $angka($d['pakan_pakai_sak']) }}</td>
                                    <td class="text-right" colspan="2">{{ F::angka($d['deplesi'], 0) }} ekor</td>
                                    <td colspan="5"></td>
                                </tr>
                            @elseif ($d['jenis'] === 'kosong')
                                @php $hariIniBaris = $d['hari_ke'] === $hariIni; @endphp
                                <tr>
                                    <th scope="row">{{ $d['hari_ke'] }}</th>
                                    <td class="whitespace-nowrap">{{ $d['tanggal']->translatedFormat('j M') }}</td>
                                    <td class="text-right text-ink-secondary">{{ $angka($stdHari[$d['hari_ke']] ?? null) }}</td>
                                    <td class="text-right text-ink-muted">–</td><td class="text-right text-ink-muted">–</td><td class="text-right text-ink-muted">–</td>
                                    <td class="text-right text-ink-secondary">{{ $persen($stdPersen[$d['hari_ke']] ?? null) }}</td>
                                    <td class="text-right text-ink-muted">–</td><td class="text-right text-ink-muted">–</td>
                                    <td>
                                        @if ($hariIniBaris)<x-badge variant="info">Hari ini</x-badge>
                                        @else<x-badge variant="warning">Belum diisi</x-badge>@endif
                                    </td>
                                    @unless ($bacaSaja)<td>
                                        @if ($bisaIsi)
                                            <a href="{{ route('recording.create', [$siklus, 'tanggal' => $d['tanggal']->toDateString()]) }}" class="inline-flex min-h-11 items-center font-semibold text-ink-brand hover:underline">Isi</a>
                                        @endif
                                    </td>@endunless
                                </tr>
                            @else
                                @php
                                    $b = $d['baris'];
                                    $r = $b['recording'];
                                    $stdP = $b['standar']?->deplesi_std_persen;
                                    $deplDiAtas = $stdP !== null && $b['deplesi_kum_persen'] > (float) $stdP;
                                    $pakanDiAtas = $b['pakan_std_hari_sak'] !== null && $b['pakan_pakai_sak'] > $b['pakan_std_hari_sak'] + 0.0001;
                                @endphp
                                <tr>
                                    <th scope="row">{{ $b['hari_ke'] }}</th>
                                    <td class="whitespace-nowrap">{{ $b['tanggal']->translatedFormat('j M') }}</td>
                                    <td class="text-right text-ink-secondary">{{ $angka($b['pakan_std_hari_sak']) }}</td>
                                    <td class="text-right font-semibold {{ $pakanDiAtas ? 'text-warning-text' : '' }}">{{ $angka($b['pakan_pakai_sak']) }}@if ($pakanDiAtas)<span class="sr-only"> (di atas standar)</span>@endif</td>
                                    <td class="text-right">{{ $r->mati }}</td>
                                    <td class="text-right" @if ($r->afkir && $r->sebab_afkir) title="{{ ucfirst($r->sebab_afkir) }}" @endif>{{ $r->afkir }}</td>
                                    <td class="text-right text-ink-secondary">{{ $persen($stdP !== null ? (float) $stdP : null) }}</td>
                                    <td class="text-right font-semibold {{ $deplDiAtas ? 'text-warning-text' : '' }}">{{ F::angka($b['deplesi_kum_persen']) }}%@if ($deplDiAtas)<span class="sr-only"> (di atas standar)</span>@endif</td>
                                    <td class="text-right font-semibold {{ $b['stok_sak'] < 0 ? 'text-danger-text' : '' }}">
                                        {{ F::angka($b['stok_sak']) }}
                                        @if ($b['pakan_masuk_sak'] > 0)<span class="ml-1 text-xs font-semibold text-success-text">↑{{ F::angka($b['pakan_masuk_sak'], 0) }}</span>@endif
                                        @if ($b['stok_sak'] < 0)<span class="block text-xs font-medium">Stok negatif</span>@endif
                                    </td>
                                    <td>
                                        @if ($r->diparaf_ppl)
                                            <span class="inline-flex items-center gap-1.5 text-[13px] font-medium text-ink-secondary" title="Diparaf {{ $r->tanggal_paraf?->translatedFormat('j M Y') }}">
                                                <x-icon name="lock" class="size-3.5" /> {{ $ppl }}
                                            </span>
                                        @elseif ($peternak && Gate::allows('paraf', $r))
                                            <form method="POST" action="{{ route('recording.paraf', $r) }}" class="relative" x-data="{ buka: false }" @keydown.escape="buka = false" @click.outside="buka = false">
                                                @csrf @method('PATCH')
                                                <button type="button" @click="buka = ! buka" :aria-expanded="buka.toString()" class="inline-flex min-h-11 items-center rounded-md border border-line-control px-3 text-[13px] font-semibold text-ink hover:bg-state-hover">Tandai paraf</button>
                                                <div x-cloak :class="buka ? 'block' : 'hidden'" class="absolute left-0 top-full z-20 mt-1 w-60 space-y-2.5 rounded-md border border-line bg-surface p-3 shadow-lg">
                                                    <label for="paraf-{{ $r->id }}" class="block text-xs font-semibold">Tanggal paraf PPL</label>
                                                    <input id="paraf-{{ $r->id }}" type="date" name="tanggal_paraf" value="{{ now()->toDateString() }}" min="{{ $r->tanggal->toDateString() }}" max="{{ now()->toDateString() }}" required class="block min-h-11 w-full rounded-md border border-line-control bg-surface px-3 text-base">
                                                    <p class="text-xs text-ink-muted">Setelah ditandai, baris ini terkunci (R11).</p>
                                                    <x-button class="w-full">Tandai diparaf</x-button>
                                                </div>
                                            </form>
                                        @else
                                            <x-badge variant="neutral">Belum diparaf</x-badge>
                                        @endif
                                    </td>
                                    @unless ($bacaSaja)<td>
                                        @if ($r->diparaf_ppl)
                                            <span class="inline-flex items-center gap-1 text-[13px] text-ink-muted"><x-icon name="lock" class="size-3.5" /> Terkunci</span>
                                        @elseif (Gate::allows('update', $r))
                                            <a href="{{ route('recording.edit', $r) }}" class="inline-flex min-h-11 items-center font-semibold text-ink-brand hover:underline">Ubah</a>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[13px] text-ink-muted"><x-icon name="lock" class="size-3.5" /> Terkunci</span>
                                        @endif
                                    </td>@endunless
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-ink-muted">Pakan dalam sak. Stok pakan = pakan masuk − pakan terpakai, kumulatif; tanda ↑ menunjukkan pakan masuk pada hari itu.</p>
        @endif
    </x-page>
</x-app-layout>
