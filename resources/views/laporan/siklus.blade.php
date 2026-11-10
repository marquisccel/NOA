@php
    use App\Support\Format as F;

    $rp = fn ($n) => $n === null ? 'belum lengkap' : 'Rp '.F::angka($n, 0);
    $status = ['berjalan' => 'Berjalan', 'selesai' => 'Selesai', 'dihentikan' => 'Dihentikan'][$siklus->status] ?? $siklus->status;
    $adaGrafik = collect($grafik['labels'])->isNotEmpty();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Laporan siklus · {{ $siklus->kandang->nama_farm }}</h2>
                <p class="mt-1 text-base text-gray-700">
                    Masuk {{ $siklus->tanggal_masuk->translatedFormat('j M Y') }} · {{ F::angka($siklus->populasi, 0) }} ekor · {{ $status }}
                    · <a href="{{ route('siklus.show', $siklus) }}" class="text-indigo-700 underline print:hidden">Detail siklus</a>
                </p>
            </div>
            <button type="button" onclick="window.print()" class="print:hidden min-h-11 px-4 py-2 bg-gray-800 rounded-md font-semibold text-sm text-white hover:bg-gray-700">Cetak laporan (1 halaman)</button>
        </div>
    </x-slot>

    <div class="py-8 print:py-2">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 print:space-y-3 print:max-w-none print:px-0">
            @if ($p['tidak_sebanding'])
                <p class="rounded-md bg-amber-50 border border-amber-400 p-4 text-base text-amber-900" role="alert">
                    <strong>Tidak sebanding:</strong> siklus dihentikan dini. Angka dihitung dari data yang ada dan tidak layak dibandingkan dengan siklus lengkap.
                </p>
            @endif
            @if ($p['jumlah_tahap'] === 0)
                <p class="rounded-md bg-gray-50 border border-gray-200 p-4 text-base text-gray-800">Belum ada catatan panen: FCR, ABW, umur panen, IP, dan margin belum bisa dihitung.</p>
            @endif

            <dl class="grid grid-cols-2 gap-4 lg:grid-cols-6 text-base print:grid-cols-6 print:gap-2">
                <div class="bg-white shadow-sm sm:rounded-lg p-4 print:shadow-none print:border print:p-2">
                    <dt class="text-gray-600">Deplesi</dt>
                    <dd class="text-2xl font-semibold print:text-lg">{{ F::angka($p['deplesi_persen']) }}%</dd>
                    <dd class="text-sm text-gray-600">{{ F::angka($p['deplesi'], 0) }} ekor</dd>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4 print:shadow-none print:border print:p-2">
                    <dt class="text-gray-600">FCR</dt>
                    <dd class="text-2xl font-semibold print:text-lg">{{ F::angka($p['fcr'], 3) }}</dd>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4 print:shadow-none print:border print:p-2">
                    <dt class="text-gray-600">ABW panen</dt>
                    <dd class="text-2xl font-semibold print:text-lg">{{ F::angka($p['abw'], 3) }} kg</dd>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4 print:shadow-none print:border print:p-2">
                    <dt class="text-gray-600">Umur panen</dt>
                    <dd class="text-2xl font-semibold print:text-lg">{{ F::angka($p['umur_panen'], 1) }} hari</dd>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4 print:shadow-none print:border print:p-2">
                    <dt class="text-gray-600">IP</dt>
                    <dd class="text-2xl font-semibold print:text-lg">{{ F::angka($p['ip'], 1) }}</dd>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4 print:shadow-none print:border print:p-2">
                    <dt class="text-gray-600">Panen</dt>
                    <dd class="text-2xl font-semibold print:text-lg">{{ F::angka($p['total_ekor'], 0) }}</dd>
                    <dd class="text-sm text-gray-600">ekor · {{ F::angka($p['total_tonase'], 0) }} kg</dd>
                </div>
            </dl>

            <section class="bg-white shadow-sm sm:rounded-lg p-4 sm:p-6 print:shadow-none print:border print:p-3 break-inside-avoid">
                <h3 class="text-lg font-semibold text-gray-900">Biaya produksi</h3>
                <table class="mt-3 min-w-full text-base text-left print:text-sm">
                    <caption class="sr-only">Rincian biaya produksi siklus</caption>
                    <tbody class="divide-y divide-gray-100 text-gray-900">
                        <tr>
                            <th scope="row" class="py-2 pe-4 font-normal">DOC ({{ F::angka($siklus->populasi, 0) }} ekor × harga DOC)</th>
                            <td class="py-2 text-right whitespace-nowrap">{{ $rp($biaya['doc']) }}</td>
                        </tr>
                        <tr>
                            <th scope="row" class="py-2 pe-4 font-normal">Pakan ({{ F::angka($p['pakan_bersih_sak']) }} sak tertagih × harga per sak)</th>
                            <td class="py-2 text-right whitespace-nowrap">{{ $rp($biaya['pakan']) }}</td>
                        </tr>
                        <tr>
                            <th scope="row" class="py-2 pe-4 font-normal">OVK (jumlah catatan pemberian)</th>
                            <td class="py-2 text-right whitespace-nowrap">{{ $rp($biaya['ovk']) }}</td>
                        </tr>
                        <tr class="font-semibold">
                            <th scope="row" class="py-2 pe-4">Total biaya produksi{{ $biaya['lengkap'] ? '' : ' (belum lengkap)' }}</th>
                            <td class="py-2 text-right whitespace-nowrap">{{ $rp($biaya['total']) }}</td>
                        </tr>
                        <tr>
                            <th scope="row" class="py-2 pe-4 font-normal">Biaya per kg panen / per ekor panen</th>
                            <td class="py-2 text-right whitespace-nowrap">
                                {{ $biaya['per_kg'] === null ? '-' : 'Rp '.F::angka($biaya['per_kg'], 0) }} / {{ $biaya['per_ekor'] === null ? '-' : 'Rp '.F::angka($biaya['per_ekor'], 0) }}
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" class="py-2 pe-4 font-normal">Pendapatan panen ({{ $p['jumlah_tahap'] }} tahap)</th>
                            <td class="py-2 text-right whitespace-nowrap">{{ $p['jumlah_tahap'] ? 'Rp '.F::angka($p['pendapatan'], 0) : '-' }}</td>
                        </tr>
                        <tr class="font-semibold">
                            <th scope="row" class="py-2 pe-4">Margin kasar</th>
                            <td class="py-2 text-right whitespace-nowrap">{{ $biaya['margin_kasar'] === null ? '-' : ($biaya['margin_kasar'] < 0 ? '−' : '').'Rp '.F::angka(abs($biaya['margin_kasar']), 0) }}</td>
                        </tr>
                    </tbody>
                </table>
                <p class="mt-3 text-sm text-gray-600">
                    Margin kasar = pendapatan panen dikurangi biaya produksi di atas; belum termasuk bonus, listrik, tenaga kerja, sekam, dan biaya operasional lain.
                    @unless ($biaya['lengkap']) Harga DOC atau harga pakan per sak belum diisi di kontrak, jadi total belum lengkap dan margin tidak dihitung. @endunless
                </p>
            </section>

            @if ($adaGrafik)
                <div class="grid gap-6 lg:grid-cols-2 print:grid-cols-2 print:gap-3">
                    <section class="bg-white shadow-sm sm:rounded-lg p-4 print:shadow-none print:border print:p-2 break-inside-avoid">
                        <h3 class="text-lg font-semibold text-gray-900 print:text-base">Bobot sepanjang siklus</h3>
                        <div class="mt-2 h-64 print:h-44"><canvas id="grafik-bobot" role="img" aria-label="Grafik bobot aktual dibandingkan standar menurut umur"></canvas></div>
                    </section>
                    <section class="bg-white shadow-sm sm:rounded-lg p-4 print:shadow-none print:border print:p-2 break-inside-avoid">
                        <h3 class="text-lg font-semibold text-gray-900 print:text-base">Deplesi kumulatif</h3>
                        <div class="mt-2 h-64 print:h-44"><canvas id="grafik-deplesi" role="img" aria-label="Grafik deplesi kumulatif menurut umur"></canvas></div>
                    </section>
                </div>
                <script type="application/json" id="data-grafik">@json($grafik)</script>
                @vite('resources/js/laporan.js')
            @else
                <p class="rounded-md bg-gray-50 border border-gray-200 p-4 text-base text-gray-800 print:hidden">Grafik muncul setelah ada recording harian atau tabel standar.</p>
            @endif

            <p class="hidden print:block text-xs text-gray-600">Dicetak {{ now()->translatedFormat('j F Y') }} dari NOA. Angka performa dihitung dari catatan peternak; bukan angka RHPP inti.</p>
        </div>
    </div>
</x-app-layout>
