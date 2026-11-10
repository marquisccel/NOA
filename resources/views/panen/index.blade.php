@php
    use App\Support\Format as F;
    $rp = fn ($n) => 'Rp '.F::angka($n, 0);
    $selisih = $p['selisih_populasi'];
@endphp

<x-app-layout :siklus="$siklus">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Panen dan performa · {{ $siklus->kandang->nama_farm }}</h2>
        <p class="mt-1 text-base text-gray-700">
            Masuk {{ $siklus->tanggal_masuk->translatedFormat('j M Y') }} · {{ F::angka($siklus->populasi, 0) }} ekor
            · <a href="{{ route('siklus.show', $siklus) }}" class="text-indigo-700 underline">Detail siklus</a>
        </p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($p['tidak_sebanding'])
                <p class="rounded-md bg-amber-50 border border-amber-400 p-4 text-base text-amber-900" role="alert">
                    <strong>Tidak sebanding:</strong> siklus dihentikan dini. FCR dan IP dihitung dari data yang ada dan tidak layak dibandingkan dengan siklus lengkap.
                </p>
            @endif

            @if ($p['jumlah_tahap'] === 0)
                <p class="rounded-md bg-gray-50 border border-gray-200 p-4 text-base text-gray-800">
                    Belum ada catatan panen, jadi FCR, ABW panen, umur panen, dan IP belum bisa dihitung.
                </p>
            @endif

            <dl class="grid grid-cols-2 gap-4 lg:grid-cols-4 text-base">
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <dt class="text-gray-600">FCR</dt>
                    <dd class="text-2xl font-semibold text-gray-900">{{ F::angka($p['fcr'], 3) }}</dd>
                    <dd class="text-sm text-gray-600">{{ F::angka($p['pakan_bersih_kg'], 0) }} kg pakan / {{ F::angka($p['total_tonase'], 0) }} kg</dd>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <dt class="text-gray-600">Indeks Performa (IP)</dt>
                    <dd class="text-2xl font-semibold text-gray-900">{{ F::angka($p['ip'], 1) }}</dd>
                    <dd class="text-sm text-gray-600">rumus {{ $siklus->rumus_ip }}</dd>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <dt class="text-gray-600">Deplesi</dt>
                    <dd class="text-2xl font-semibold text-gray-900">{{ F::angka($p['deplesi_persen']) }}%</dd>
                    <dd class="text-sm text-gray-600">{{ F::angka($p['deplesi'], 0) }} ekor ({{ F::angka($p['mati'], 0) }} mati + {{ F::angka($p['afkir'], 0) }} afkir)</dd>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <dt class="text-gray-600">Daya hidup</dt>
                    <dd class="text-2xl font-semibold text-gray-900">{{ F::angka($p['liveability']) }}%</dd>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <dt class="text-gray-600">Total panen</dt>
                    <dd class="text-2xl font-semibold text-gray-900">{{ F::angka($p['total_ekor'], 0) }} ekor</dd>
                    <dd class="text-sm text-gray-600">{{ F::angka($p['total_tonase']) }} kg · {{ $p['jumlah_tahap'] }} tahap</dd>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <dt class="text-gray-600">ABW panen</dt>
                    <dd class="text-2xl font-semibold text-gray-900">{{ F::angka($p['abw'], 3) }} kg</dd>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <dt class="text-gray-600">Umur panen rata-rata</dt>
                    <dd class="text-2xl font-semibold text-gray-900">{{ F::angka($p['umur_panen'], 1) }} hari</dd>
                    <dd class="text-sm text-gray-600">berbobot jumlah ekor tiap tahap</dd>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <dt class="text-gray-600">Pendapatan panen</dt>
                    <dd class="text-2xl font-semibold text-gray-900">{{ $rp($p['pendapatan']) }}</dd>
                    @if ($p['tahap_tanpa_harga'] > 0)<dd class="text-sm text-amber-800">{{ $p['tahap_tanpa_harga'] }} tahap belum ada harga</dd>@endif
                </div>
            </dl>

            <div class="rounded-md border p-4 text-base {{ $selisih === 0 ? 'bg-green-50 border-green-300 text-green-900' : 'bg-amber-50 border-amber-400 text-amber-900' }}" role="status">
                <strong>Selisih populasi:</strong>
                @if ($selisih === 0)
                    seimbang. Ekor panen {{ F::angka($p['total_ekor'], 0) }} + deplesi {{ F::angka($p['deplesi'], 0) }} = populasi awal {{ F::angka($p['populasi'], 0) }}.
                @elseif ($selisih > 0)
                    {{ F::angka($selisih, 0) }} ekor belum terhitung. Panen belum lengkap, atau ada ayam hilang tanpa catatan mati; dibiarkan apa adanya, tidak dipaksa seimbang.
                @else
                    ekor panen ditambah deplesi melebihi populasi awal sebanyak {{ F::angka(abs($selisih), 0) }} ekor. Periksa jumlah ekor panen atau catatan deplesi.
                @endif
            </div>

            <section>
                <h3 class="mb-3 text-lg font-semibold text-gray-900">Catat tahap panen</h3>
                @include('panen._form', ['panen' => new \App\Models\Panen, 'siklus' => $siklus])
            </section>

            <section class="bg-white shadow-sm sm:rounded-lg p-4 sm:p-6">
                <h3 class="text-lg font-semibold text-gray-900">Tahap panen</h3>
                @if ($panen->isEmpty())
                    <p class="mt-2 text-base text-gray-600">Belum ada tahap panen.</p>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-base text-left align-top">
                            <caption class="sr-only">Tahap panen berurutan menurut tanggal</caption>
                            <thead class="text-gray-600 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="py-2 pe-4">Tahap</th>
                                    <th scope="col" class="py-2 pe-4">Umur</th>
                                    <th scope="col" class="py-2 pe-4">Ekor</th>
                                    <th scope="col" class="py-2 pe-4">Tonase (kg)</th>
                                    <th scope="col" class="py-2 pe-4">ABW (kg)</th>
                                    <th scope="col" class="py-2 pe-4">Harga/kg</th>
                                    <th scope="col" class="py-2 pe-4">BTPA</th>
                                    <th scope="col" class="py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-900">
                                @foreach ($panen as $i => $t)
                                    <tr>
                                        <td class="py-2 pe-4 whitespace-nowrap">{{ $i + 1 }}<div class="text-sm text-gray-600">{{ $t->tanggal->translatedFormat('j M Y') }}</div></td>
                                        <td class="py-2 pe-4">{{ $t->umur_hari }} hari</td>
                                        <td class="py-2 pe-4">{{ F::angka($t->jumlah_ekor, 0) }}</td>
                                        <td class="py-2 pe-4">{{ F::angka($t->tonase_kg) }}</td>
                                        <td class="py-2 pe-4">{{ F::angka((float) $t->tonase_kg / $t->jumlah_ekor, 3) }}</td>
                                        <td class="py-2 pe-4">{{ $t->harga_per_kg === null ? 'belum diisi' : $rp($t->harga_per_kg) }}</td>
                                        <td class="py-2 pe-4">
                                            {{ filled($t->nomor_btpa) ? $t->nomor_btpa : 'belum ada nomor' }}
                                            @if ($t->foto_btpa)<div><a href="{{ route('panen.foto', $t) }}" target="_blank" rel="noopener" class="text-sm text-indigo-700 underline">Lihat foto</a></div>@endif
                                        </td>
                                        <td class="py-2"><a href="{{ route('panen.edit', $t) }}" class="text-indigo-700 underline min-h-11 inline-flex items-center">Ubah</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Pakan sisa di akhir siklus</h3>
                <p class="mt-1 text-base text-gray-700">
                    Pakan masuk {{ F::angka($p['pakan_masuk_sak']) }} sak, terpakai {{ F::angka($p['pakan_terpakai_sak']) }} sak
                    (stok gudang {{ F::angka($p['pakan_masuk_sak'] - $p['pakan_terpakai_sak']) }} sak).
                    Angka ini dikurangkan dari pakan terpakai agar FCR tidak melar, dan jadi dasar perbandingan retur di RHPP.
                </p>
                <p class="mt-2 text-sm text-gray-600">
                    Isi hanya bila pakan terpakai harian masih mencakup pakan yang akhirnya tersisa atau diretur ke inti.
                    Bila catatan harian sudah tidak menghitungnya, biarkan 0 agar tidak dihitung dua kali.
                </p>
                <form method="POST" action="{{ route('panen.retur', $siklus) }}" class="mt-4 flex flex-wrap items-end gap-4">
                    @csrf @method('PATCH')
                    <div class="w-full sm:w-64">
                        <x-field name="pakan_retur_sak" label="Pakan sisa/retur (sak)" type="number" step="0.01" min="0" inputmode="decimal" :value="$siklus->pakan_retur_sak" required />
                    </div>
                    <x-secondary-button type="submit" class="min-h-11">Simpan</x-secondary-button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
