@php
    use App\Support\Format as F;

    $kelas = 'min-h-11 w-full rounded-md border-line-control bg-surface px-3 text-sm font-medium focus:border-brand focus:ring-1 focus:ring-brand';
    $nama = fn (\App\Models\Siklus $s) => $s->kandang->nama_farm.' · Periode '.($nomor[$s->id] ?? 1).' ('.$s->tanggal_masuk->translatedFormat('M Y').')';

    // [label, nilai A, nilai B, tampilan, "lebih kecil lebih baik"?, istilah]
    $ukuran = [];
    if ($a && $b) {
        $pa = $a['p']; $pb = $b['p'];
        $ukuran = [
            ['FCR (konversi pakan)', $pa['fcr'], $pb['fcr'], fn ($n) => F::angka($n, 3), true, 'fcr'],
            ['IP (indeks performa)', $pa['ip'], $pb['ip'], fn ($n) => F::angka($n, 0), false, 'ip'],
            ['Deplesi', $pa['deplesi_persen'], $pb['deplesi_persen'], fn ($n) => F::angka($n).'%', true, 'deplesi'],
            ['Bobot rata-rata panen (ABW)', $pa['abw'], $pb['abw'], fn ($n) => F::angka($n, 3).' kg', false, 'abw'],
            ['Umur panen', $pa['umur_panen'], $pb['umur_panen'], fn ($n) => F::angka($n).' hari', true, null],
            ['Total panen', $pa['total_tonase'], $pb['total_tonase'], fn ($n) => F::angka($n, 0).' kg', false, null],
            ['Ayam dipanen', $pa['total_ekor'], $pb['total_ekor'], fn ($n) => F::angka($n, 0).' ekor', false, null],
            ['Biaya per kg', $a['biaya']['per_kg'], $b['biaya']['per_kg'], fn ($n) => 'Rp '.F::angka($n, 0), true, null],
            ['Total pakan terpakai', $pa['pakan_bersih_kg'], $pb['pakan_bersih_kg'], fn ($n) => F::angka($n, 0).' kg', true, null],
        ];
    }
@endphp

<x-app-layout title="Bandingkan periode">
    <x-page lebar="max-w-5xl">
        <x-page-header title="Bandingkan dua periode" subtitle="Pilih dua periode yang sudah dipanen untuk melihat mana yang lebih baik, ukuran demi ukuran.">
            <x-slot name="actions"><x-button variant="secondary" :href="route('performa.index')">Kembali ke Performa</x-button></x-slot>
        </x-page-header>

        @if ($pilihan->count() < 2)
            <x-empty-state icon="columns-2" title="Belum cukup periode untuk dibandingkan" text="Perbandingan butuh minimal dua periode yang sudah ada catatan panennya." />
        @else
            <form method="GET" action="{{ route('performa.bandingkan') }}" class="grid gap-3 rounded-[16px] border border-line bg-surface p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                <div>
                    <label for="a" class="block text-sm font-semibold">Periode pertama</label>
                    <select id="a" name="a" class="mt-1.5 {{ $kelas }}">
                        <option value="">Pilih periode…</option>
                        @foreach ($pilihan as $s)<option value="{{ $s->id }}" @selected($idA === $s->id)>{{ $nama($s) }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="b" class="block text-sm font-semibold">Periode kedua</label>
                    <select id="b" name="b" class="mt-1.5 {{ $kelas }}">
                        <option value="">Pilih periode…</option>
                        @foreach ($pilihan as $s)<option value="{{ $s->id }}" @selected($idB === $s->id)>{{ $nama($s) }}</option>@endforeach
                    </select>
                </div>
                <x-button>Bandingkan</x-button>
            </form>

            @if (! $a || ! $b)
                <x-alert variant="info">Pilih dua periode lalu tekan <strong>Bandingkan</strong>.</x-alert>
            @elseif ($a['siklus']->id === $b['siklus']->id)
                <x-alert variant="warning">Pilih dua periode yang berbeda.</x-alert>
            @else
                @php $menang = ['a' => 0, 'b' => 0]; @endphp
                <div class="overflow-x-auto rounded-[16px] border border-line bg-surface">
                    <table class="tabel min-w-[640px]">
                        <caption class="sr-only">Perbandingan dua periode</caption>
                        <thead>
                            <tr>
                                <th scope="col">Ukuran</th>
                                <th scope="col">{{ $nama($a['siklus']) }}</th>
                                <th scope="col">{{ $nama($b['siklus']) }}</th>
                                <th scope="col">Yang lebih baik</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ukuran as [$label, $va, $vb, $fmt, $kecilBaik, $istilah])
                                @php
                                    $adaBanding = $va !== null && $vb !== null && round((float) $va, 6) !== round((float) $vb, 6);
                                    $pemenang = ! $adaBanding ? null : ((($va < $vb) === $kecilBaik) ? 'a' : 'b');
                                    if ($pemenang) { $menang[$pemenang]++; }
                                @endphp
                                <tr>
                                    <th scope="row">@if ($istilah)<x-istilah :k="$istilah">{{ $label }}</x-istilah>@else{{ $label }}@endif</th>
                                    <td class="{{ $pemenang === 'a' ? 'font-bold text-success-text' : '' }}">{{ $va === null ? '–' : $fmt($va) }}</td>
                                    <td class="{{ $pemenang === 'b' ? 'font-bold text-success-text' : '' }}">{{ $vb === null ? '–' : $fmt($vb) }}</td>
                                    <td>
                                        @if ($pemenang)<x-badge variant="success">{{ $pemenang === 'a' ? 'Periode pertama' : 'Periode kedua' }}</x-badge>@else<span class="text-ink-muted">–</span>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-alert variant="{{ $menang['a'] === $menang['b'] ? 'info' : 'success' }}">
                    @if ($menang['a'] === $menang['b'])
                        <p class="font-semibold">Seimbang</p><p>Kedua periode unggul di jumlah ukuran yang sama ({{ $menang['a'] }} dari {{ count($ukuran) }}).</p>
                    @else
                        @php $juara = $menang['a'] > $menang['b'] ? 'a' : 'b'; @endphp
                        <p class="font-semibold">{{ $nama(${$juara}['siklus']) }} unggul di {{ $menang[$juara] }} dari {{ count($ukuran) }} ukuran.</p>
                        <p>Hijau menandai nilai yang lebih baik: FCR, deplesi, umur panen, biaya, dan pakan makin kecil makin baik; IP, bobot, dan panen makin besar makin baik.</p>
                    @endif
                </x-alert>
            @endif
        @endif
    </x-page>
</x-app-layout>
