@php
    use App\Support\Format as F;

    $angka = fn ($n, $d) => $n === null ? '-' : F::angka($n, $d);
    $bertanda = function ($n, $d, $nol) {
        if ($n === null) return '-';
        if ($nol) return F::angka(0, $d);
        return ($n > 0 ? '+' : '−').F::angka(abs($n), $d);
    };
    $rp = fn ($n) => $n === null ? null : (round($n) == 0 ? 'Rp 0' : ($n < 0 ? '−' : '+').'Rp '.F::angka(abs($n), 0));
@endphp

<table class="min-w-full text-base text-left align-top">
    <caption class="sr-only">Perbandingan catatan peternak dengan RHPP baris demi baris</caption>
    <thead class="text-gray-600 border-b border-gray-300">
        <tr>
            <th scope="col" class="py-2 pe-4">Baris</th>
            <th scope="col" class="py-2 pe-4 text-right">Versi peternak</th>
            <th scope="col" class="py-2 pe-4 text-right">Versi RHPP</th>
            <th scope="col" class="py-2 pe-4 text-right">Selisih (RHPP − peternak)</th>
            <th scope="col" class="py-2 pe-4">Status</th>
            <th scope="col" class="py-2 text-right">Dampak rupiah</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 text-gray-900">
        @foreach ($r['baris'] as $b)
            <tr class="{{ $b['melebihi'] ? 'bg-amber-50' : '' }}">
                <th scope="row" class="py-2 pe-4 font-medium">{{ $b['label'] }}@if ($b['satuan'])<span class="text-gray-600 font-normal"> ({{ $b['satuan'] }})</span>@endif</th>
                <td class="py-2 pe-4 text-right whitespace-nowrap">{{ $angka($b['peternak'], $b['desimal']) }}</td>
                <td class="py-2 pe-4 text-right whitespace-nowrap">{{ $angka($b['rhpp'], $b['desimal']) }}</td>
                <td class="py-2 pe-4 text-right whitespace-nowrap font-medium">{{ $bertanda($b['selisih'], $b['desimal'], $b['nol']) }}</td>
                <td class="py-2 pe-4">
                    @if ($b['selisih'] === null)
                        <span class="text-gray-600">Belum bisa dibandingkan</span>
                    @elseif ($b['nol'])
                        Sama
                    @elseif ($b['melebihi'])
                        <strong class="text-amber-900">Melebihi ambang</strong> <span class="text-sm text-gray-700">({{ $b['arah'] }}, ambang {{ F::angka($b['ambang'], $b['desimal']) }})</span>
                    @else
                        Wajar <span class="text-sm text-gray-700">({{ $b['arah'] }}, dalam ambang {{ F::angka($b['ambang'], $b['desimal']) }})</span>
                    @endif
                </td>
                <td class="py-2 text-right whitespace-nowrap">
                    @if ($b['dampak_rp'] !== null)
                        {{ $rp($b['dampak_rp']) }}
                        <div class="text-sm text-gray-600">{{ $b['dampak_rp'] < 0 ? 'merugikan Anda' : 'menguntungkan Anda' }}</div>
                    @elseif ($b['bonus_terkait'])
                        <span class="text-sm text-gray-700">memengaruhi bonus; nilai rupiah butuh tabel bonus inti</span>
                    @else
                        -
                    @endif
                </td>
            </tr>
        @endforeach
        <tr class="text-gray-700">
            <th scope="row" class="py-2 pe-4 font-medium">Pendapatan panen (rupiah, informatif)</th>
            <td class="py-2 pe-4 text-right whitespace-nowrap">{{ $r['pendapatan']['peternak'] === null ? '-' : 'Rp '.F::angka($r['pendapatan']['peternak'], 0) }}</td>
            <td class="py-2 pe-4 text-right whitespace-nowrap">{{ $r['pendapatan']['rhpp'] === null ? '-' : 'Rp '.F::angka($r['pendapatan']['rhpp'], 0) }}</td>
            <td class="py-2 pe-4 text-right whitespace-nowrap">{{ $r['pendapatan']['selisih'] === null ? '-' : $rp($r['pendapatan']['selisih']) }}</td>
            <td class="py-2 pe-4 text-sm" colspan="2">RHPP dapat memuat bonus dan harga yang tidak tercatat di NOA, jadi tidak ditandai.</td>
        </tr>
    </tbody>
</table>
