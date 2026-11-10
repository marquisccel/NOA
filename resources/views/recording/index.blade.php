@php
    use App\Support\Format as F;

    /** Sel "aktual di atas, standar di bawah" (F2.6). */
    $sel = function ($aktual, $std, int $d = 2) {
        return ['a' => $aktual === null ? '-' : F::angka($aktual, $d), 's' => $std === null ? null : F::angka($std, $d)];
    };
@endphp

<x-app-layout :siklus="$siklus">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Recording harian · {{ $siklus->kandang->nama_farm }}
                </h2>
                <p class="mt-1 text-base text-gray-700">
                    Masuk {{ $siklus->tanggal_masuk->translatedFormat('j M Y') }} · {{ F::angka($siklus->populasi, 0) }} ekor
                    @if (auth()->user()->isPeternak())
                        · <a href="{{ route('siklus.show', $siklus) }}" class="text-indigo-700 underline">Detail siklus</a>
                    @endif
                </p>
            </div>
            @if ($bisaIsi)
                <a href="{{ route('recording.create', $siklus) }}" class="inline-flex items-center min-h-11 px-4 py-2 bg-gray-800 rounded-md font-semibold text-sm text-white hover:bg-gray-700">Isi entri harian</a>
            @elseif ($siklus->isTerkunci())
                <span class="px-3 py-2 rounded-md bg-gray-100 text-base text-gray-800">Siklus selesai: recording dikunci</span>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($terakhir)
                @php $t = $terakhir; @endphp
                <dl class="grid grid-cols-2 gap-4 lg:grid-cols-5 text-base">
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <dt class="text-gray-600">Deplesi kumulatif</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ F::angka($t['deplesi_kum_persen']) }}%</dd>
                        <dd class="text-sm text-gray-600">{{ F::angka($t['deplesi_kum'], 0) }} ekor</dd>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <dt class="text-gray-600">FCR berjalan</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ F::angka($t['fcr_berjalan'], 3) }}</dd>
                        @if ($t['standar'])<dd class="text-sm text-gray-600">standar {{ F::angka($t['standar']->fcr_std, 3) }}</dd>@endif
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <dt class="text-gray-600">Feed intake kumulatif</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ F::angka($t['feed_intake_gram'], 0) }} g</dd>
                        @if ($t['standar'])<dd class="text-sm text-gray-600">standar {{ F::angka($t['standar']->feed_intake_std_gram, 0) }} g</dd>@endif
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <dt class="text-gray-600">Stok gudang</dt>
                        <dd class="text-2xl font-semibold {{ $t['stok_sak'] < 0 ? 'text-red-700' : 'text-gray-900' }}">{{ F::angka($t['stok_sak']) }} sak</dd>
                        @if ($t['stok_sak'] < 0)<dd class="text-sm text-red-700">Negatif: cek pengiriman yang belum dicatat</dd>@endif
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <dt class="text-gray-600">Ayam hidup</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ F::angka($t['ayam_hidup'], 0) }}</dd>
                        <dd class="text-sm text-gray-600">per hari ke-{{ $t['hari_ke'] }}</dd>
                    </div>
                </dl>
            @endif

            @unless ($standarAda)
                <p class="rounded-md bg-gray-50 border border-gray-200 p-4 text-base text-gray-800">
                    Tabel standar belum diunggah, jadi kolom standar kosong.
                    @if (auth()->user()->isPeternak())
                        <a href="{{ route('siklus.standar', $siklus) }}" class="text-indigo-700 underline">Unggah tabel standar</a>
                    @else
                        Minta peternak mengunggahnya.
                    @endif
                </p>
            @endunless

            <div class="bg-white shadow-sm sm:rounded-lg p-4 sm:p-6">
                @if (empty($daftar))
                    <p class="text-base text-gray-700">Belum ada entri. Mulai dari hari pertama dengan “Isi entri harian”.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-base text-left align-top">
                            <caption class="sr-only">Recording harian: nilai aktual dengan standar di bawahnya</caption>
                            <thead class="text-gray-600 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="py-2 pe-4">Hari</th>
                                    <th scope="col" class="py-2 pe-4">Pakan masuk (sak)</th>
                                    <th scope="col" class="py-2 pe-4">Pakan terpakai (sak)</th>
                                    <th scope="col" class="py-2 pe-4">Stok (sak)</th>
                                    <th scope="col" class="py-2 pe-4">Mati / afkir</th>
                                    <th scope="col" class="py-2 pe-4">Deplesi kum.</th>
                                    <th scope="col" class="py-2 pe-4">Pakan kum. (sak)</th>
                                    <th scope="col" class="py-2 pe-4">Bobot (g)</th>
                                    <th scope="col" class="py-2 pe-4">FCR</th>
                                    <th scope="col" class="py-2 pe-4">Feed intake (g)</th>
                                    <th scope="col" class="py-2 pe-4">Suhu</th>
                                    <th scope="col" class="py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-900">
                                @foreach ($daftar as $d)
                                    @if ($d['jenis'] === 'minggu')
                                        <tr class="bg-gray-50 font-medium">
                                            <td class="py-2 pe-4" colspan="2">Subtotal minggu {{ $d['minggu'] }}</td>
                                            <td class="py-2 pe-4">{{ F::angka($d['pakan_pakai_sak']) }}</td>
                                            <td class="py-2 pe-4"></td>
                                            <td class="py-2 pe-4" colspan="2">Deplesi {{ F::angka($d['deplesi'], 0) }} ekor</td>
                                            <td class="py-2" colspan="6"></td>
                                        </tr>
                                    @elseif ($d['jenis'] === 'kosong')
                                        <tr>
                                            <td class="py-2 pe-4 whitespace-nowrap">{{ $d['hari_ke'] }}<div class="text-sm text-gray-600">{{ $d['tanggal']->translatedFormat('j M') }}</div></td>
                                            <td class="py-2" colspan="10">
                                                <span class="text-gray-600">Belum diisi.</span>
                                                @if ($bisaIsi)
                                                    <a href="{{ route('recording.create', [$siklus, 'tanggal' => $d['tanggal']->toDateString()]) }}" class="ms-2 text-indigo-700 underline">Isi sekarang</a>
                                                @endif
                                            </td>
                                            <td class="py-2"></td>
                                        </tr>
                                    @else
                                        @php
                                            $b = $d['baris'];
                                            $r = $b['recording'];
                                            $std = $b['standar'];
                                            $pakan = $sel($b['pakan_kum_sak'], $std?->pakan_std_sak);
                                            $bobot = $sel($b['bobot_gram'], $std?->bobot_std_gram);
                                            $fcr = $sel($b['fcr_berjalan'], $std?->fcr_std, 3);
                                            $fi = $sel($b['feed_intake_gram'], $std?->feed_intake_std_gram, 0);
                                        @endphp
                                        <tr>
                                            <td class="py-2 pe-4 whitespace-nowrap">{{ $b['hari_ke'] }}<div class="text-sm text-gray-600">{{ $b['tanggal']->translatedFormat('j M') }}</div></td>
                                            <td class="py-2 pe-4">{{ F::angka($b['pakan_masuk_sak']) }}</td>
                                            <td class="py-2 pe-4">{{ F::angka($b['pakan_pakai_sak']) }}</td>
                                            <td class="py-2 pe-4 {{ $b['stok_sak'] < 0 ? 'text-red-700 font-medium' : '' }}">
                                                {{ F::angka($b['stok_sak']) }}
                                                @if ($b['stok_sak'] < 0)<div class="text-sm">Stok negatif</div>@endif
                                            </td>
                                            <td class="py-2 pe-4 whitespace-nowrap">{{ $r->mati }} / {{ $r->afkir }}@if ($r->afkir)<div class="text-sm text-gray-600">{{ $r->sebab_afkir }}</div>@endif</td>
                                            <td class="py-2 pe-4">{{ F::angka($b['deplesi_kum_persen']) }}%</td>
                                            <td class="py-2 pe-4"><div>{{ $pakan['a'] }}</div>@if ($pakan['s'] !== null)<div class="text-sm text-gray-600">std {{ $pakan['s'] }}</div>@endif</td>
                                            <td class="py-2 pe-4"><div>{{ $bobot['a'] }}</div>@if ($bobot['s'] !== null)<div class="text-sm text-gray-600">std {{ $bobot['s'] }}</div>@endif</td>
                                            <td class="py-2 pe-4"><div>{{ $fcr['a'] }}</div>@if ($fcr['s'] !== null)<div class="text-sm text-gray-600">std {{ $fcr['s'] }}</div>@endif</td>
                                            <td class="py-2 pe-4"><div>{{ $fi['a'] }}</div>@if ($fi['s'] !== null)<div class="text-sm text-gray-600">std {{ $fi['s'] }}</div>@endif</td>
                                            <td class="py-2 pe-4 text-sm">
                                                @foreach (['suhu_pagi' => 'Pagi', 'suhu_sore' => 'Sore'] as $kolom => $teks)
                                                    @if ($r->{$kolom} !== null)
                                                        <div>{{ $teks }} {{ F::angka($r->{$kolom}, 1) }}°
                                                            @if ($b[$kolom])<span class="{{ $b[$kolom]['status'] === 'sesuai target' ? 'text-gray-600' : 'text-amber-800 font-medium' }}">({{ $b[$kolom]['status'] }} {{ $b[$kolom]['target'] }})</span>@endif
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </td>
                                            <td class="py-2 whitespace-nowrap">
                                                @if ($r->diparaf_ppl)
                                                    <span class="px-2 py-1 rounded-md bg-gray-100 text-sm text-gray-800">Diparaf PPL {{ $r->tanggal_paraf?->translatedFormat('j M') }}</span>
                                                @elseif ($siklus->isTerkunci())
                                                    <a href="{{ route('recording.edit', $r) }}" class="text-indigo-700 underline">Paraf</a>
                                                @else
                                                    <a href="{{ route('recording.edit', $r) }}" class="text-indigo-700 underline min-h-11 inline-flex items-center">Ubah</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
