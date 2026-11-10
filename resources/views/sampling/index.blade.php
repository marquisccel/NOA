@php use App\Support\Format as F; @endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sampling bobot · {{ $siklus->kandang->nama_farm }}</h2>
        <p class="mt-1 text-base text-gray-700">
            Ayam hidup saat ini {{ F::angka($ayamHidup, 0) }} ekor · sampel minimal 5% ({{ F::angka(ceil($ayamHidup * 0.05), 0) }} ekor)
            · <a href="{{ route('siklus.show', $siklus) }}" class="text-indigo-700 underline">Detail siklus</a>
        </p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($terakhir)
                <dl class="grid grid-cols-2 gap-4 lg:grid-cols-4 text-base">
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <dt class="text-gray-600">ABW terakhir (hari {{ $terakhir['hari_ke'] }})</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ F::angka($terakhir['abw_gram'], 0) }} g</dd>
                        @if ($terakhir['std_gram'] !== null)<dd class="text-sm text-gray-600">standar {{ F::angka($terakhir['std_gram'], 0) }} g</dd>@endif
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <dt class="text-gray-600">Pertambahan harian</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ $terakhir['adg_gram'] === null ? '-' : F::angka($terakhir['adg_gram'], 1).' g/hari' }}</dd>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <dt class="text-gray-600">Selisih dengan standar</dt>
                        <dd class="text-2xl font-semibold text-gray-900">
                            {{ $terakhir['selisih_std_gram'] === null ? '-' : ($terakhir['selisih_std_gram'] >= 0 ? '+' : '').F::angka($terakhir['selisih_std_gram'], 0).' g' }}
                        </dd>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <dt class="text-gray-600">Jumlah sampling</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ count($baris) }}</dd>
                    </div>
                </dl>
            @endif

            @unless ($standarAda)
                <p class="rounded-md bg-gray-50 border border-gray-200 p-4 text-base text-gray-800">
                    Tabel standar belum diunggah, jadi perbandingan ABW dengan standar kosong.
                    <a href="{{ route('siklus.standar', $siklus) }}" class="text-indigo-700 underline">Unggah tabel standar</a>
                </p>
            @endunless

            <section>
                <h3 class="mb-3 text-lg font-semibold text-gray-900">Catat sampling</h3>
                @include('sampling._form', ['sampling' => new \App\Models\SamplingBobot, 'siklus' => $siklus, 'ayamHidup' => $ayamHidup])
            </section>

            <section class="bg-white shadow-sm sm:rounded-lg p-4 sm:p-6">
                <h3 class="text-lg font-semibold text-gray-900">Riwayat sampling</h3>
                @if (empty($baris))
                    <p class="mt-2 text-base text-gray-600">Belum ada sampling.</p>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-base text-left align-top">
                            <caption class="sr-only">Riwayat sampling bobot, terbaru di atas</caption>
                            <thead class="text-gray-600 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="py-2 pe-4">Hari</th>
                                    <th scope="col" class="py-2 pe-4">Sampel</th>
                                    <th scope="col" class="py-2 pe-4">Total (kg)</th>
                                    <th scope="col" class="py-2 pe-4">ABW (g)</th>
                                    <th scope="col" class="py-2 pe-4">Selisih standar</th>
                                    <th scope="col" class="py-2 pe-4">ADG (g/hari)</th>
                                    <th scope="col" class="py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-900">
                                @foreach ($baris as $b)
                                    @php $s = $b['sampling']; @endphp
                                    <tr>
                                        <td class="py-2 pe-4 whitespace-nowrap">{{ $b['hari_ke'] }}<div class="text-sm text-gray-600">{{ $s->tanggal->translatedFormat('j M Y') }}</div></td>
                                        <td class="py-2 pe-4">
                                            {{ F::angka($s->jumlah_sampel, 0) }}
                                            <div class="text-sm {{ $b['representatif'] ? 'text-gray-600' : 'text-amber-800 font-medium' }}">
                                                {{ F::angka($b['persen_populasi']) }}%{{ $b['representatif'] ? '' : ' · kurang representatif' }}
                                            </div>
                                        </td>
                                        <td class="py-2 pe-4">{{ F::angka($s->total_bobot_kg, 3) }}</td>
                                        <td class="py-2 pe-4">{{ F::angka($b['abw_gram'], 0) }}@if ($b['std_gram'] !== null)<div class="text-sm text-gray-600">std {{ F::angka($b['std_gram'], 0) }}</div>@endif</td>
                                        <td class="py-2 pe-4">
                                            @if ($b['selisih_std_gram'] === null)
                                                -
                                            @else
                                                {{ $b['selisih_std_gram'] >= 0 ? '+' : '' }}{{ F::angka($b['selisih_std_gram'], 0) }} g
                                                <div class="text-sm text-gray-600">{{ $b['selisih_std_persen'] >= 0 ? '+' : '' }}{{ F::angka($b['selisih_std_persen'], 1) }}%</div>
                                            @endif
                                        </td>
                                        <td class="py-2 pe-4">{{ $b['adg_gram'] === null ? '-' : F::angka($b['adg_gram'], 1) }}</td>
                                        <td class="py-2"><a href="{{ route('sampling.edit', $s) }}" class="text-indigo-700 underline min-h-11 inline-flex items-center">Ubah</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
