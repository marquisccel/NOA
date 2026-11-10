@php
    use App\Support\Format as F;

    $status = ['berjalan' => 'Berjalan', 'selesai' => 'Selesai', 'dihentikan' => 'Dihentikan'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Perbandingan siklus · {{ $kandang->nama_farm }}</h2>
        <p class="mt-1 text-base text-gray-700">Performa tiap siklus di kandang ini, terbaru di atas · <a href="{{ route('kandang.show', $kandang) }}" class="text-indigo-700 underline">Detail kandang</a></p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-4 sm:p-6">
                @if ($baris->isEmpty())
                    <p class="text-base text-gray-600">Belum ada siklus di kandang ini.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-base text-left align-top">
                            <caption class="sr-only">Perbandingan performa antar-siklus di satu kandang</caption>
                            <thead class="text-gray-600 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="py-2 pe-4">Siklus</th>
                                    <th scope="col" class="py-2 pe-4 text-right">Deplesi</th>
                                    <th scope="col" class="py-2 pe-4 text-right">FCR</th>
                                    <th scope="col" class="py-2 pe-4 text-right">ABW (kg)</th>
                                    <th scope="col" class="py-2 pe-4 text-right">Umur panen</th>
                                    <th scope="col" class="py-2 pe-4 text-right">IP</th>
                                    <th scope="col" class="py-2 pe-4 text-right">Biaya / kg</th>
                                    <th scope="col" class="py-2">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-900">
                                @foreach ($baris as $b)
                                    @php $s = $b['siklus']; $p = $b['p']; @endphp
                                    <tr>
                                        <th scope="row" class="py-2 pe-4 font-normal whitespace-nowrap">
                                            <a href="{{ route('laporan.siklus', $s) }}" class="text-indigo-700 underline">{{ $s->tanggal_masuk->translatedFormat('j M Y') }}</a>
                                            <div class="text-sm text-gray-600">{{ F::angka($s->populasi, 0) }} ekor · {{ $status[$s->status] ?? $s->status }}</div>
                                        </th>
                                        <td class="py-2 pe-4 text-right">{{ F::angka($p['deplesi_persen']) }}%</td>
                                        <td class="py-2 pe-4 text-right">{{ F::angka($p['fcr'], 3) }}</td>
                                        <td class="py-2 pe-4 text-right">{{ F::angka($p['abw'], 3) }}</td>
                                        <td class="py-2 pe-4 text-right">{{ $p['umur_panen'] === null ? '-' : F::angka($p['umur_panen'], 1).' hari' }}</td>
                                        <td class="py-2 pe-4 text-right">{{ F::angka($p['ip'], 1) }}</td>
                                        <td class="py-2 pe-4 text-right whitespace-nowrap">{{ $b['biaya']['per_kg'] === null ? '-' : 'Rp '.F::angka($b['biaya']['per_kg'], 0) }}</td>
                                        <td class="py-2 text-sm">
                                            @if ($p['tidak_sebanding'])<span class="text-amber-900 font-medium">Dihentikan dini, tidak sebanding</span>
                                            @elseif ($p['jumlah_tahap'] === 0)<span class="text-gray-600">Belum panen</span>
                                            @elseif (! $b['biaya']['lengkap'])<span class="text-gray-600">Biaya belum lengkap</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-4 text-sm text-gray-600">Siklus yang dihentikan dini atau belum panen tidak layak dibandingkan langsung dengan siklus lengkap.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
