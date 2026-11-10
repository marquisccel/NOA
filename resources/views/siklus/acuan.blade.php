@php
    $parameterAwal = old('parameter', $parameter->map->only(['umur_awal', 'umur_akhir', 'suhu_min', 'suhu_maks', 'kepadatan_ekor_m2', 'frekuensi_pakan', 'jumlah_tma', 'jumlah_tra'])->all());
    $vaksinAwal = old('vaksin', $vaksin->map->only(['hari_ke', 'nama_vaksin', 'keterangan'])->all());
    $kolom = [
        'umur_awal' => ['Umur awal (hari)', 'numeric'],
        'umur_akhir' => ['Umur akhir (hari)', 'numeric'],
        'suhu_min' => ['Suhu min (°C)', 'decimal'],
        'suhu_maks' => ['Suhu maks (°C)', 'decimal'],
        'kepadatan_ekor_m2' => ['Kepadatan (ekor/m²)', 'decimal'],
        'frekuensi_pakan' => ['Frekuensi pakan / hari', 'numeric'],
        'jumlah_tma' => ['Jumlah TMA', 'numeric'],
        'jumlah_tra' => ['Jumlah TRA', 'numeric'],
    ];
@endphp

<x-app-layout :siklus="$siklus">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Parameter umur dan jadwal vaksin · {{ $siklus->kandang->nama_farm }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('siklus.acuan.simpan', $siklus) }}" class="space-y-6"
                  x-data="{
                      parameter: @js(array_values($parameterAwal)),
                      vaksin: @js(array_values($vaksinAwal)),
                  }">
                @csrf @method('PUT')

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Parameter manajemen per rentang umur</h3>
                    <p class="mt-1 text-base text-gray-600">TMA = tempat minum, TRA = tempat ransum. Nilai berlaku untuk satu rentang umur, bukan per hari.</p>

                    <div class="mt-4 space-y-4">
                        <template x-for="(row, i) in parameter" :key="i">
                            <div class="rounded-md border border-gray-200 p-4">
                                <div class="grid gap-4 sm:grid-cols-4">
                                    @foreach ($kolom as $nama => [$teks, $mode])
                                        <div>
                                            <label class="block text-base text-gray-700" :for="'p_{{ $nama }}_' + i">{{ $teks }}</label>
                                            <input type="number" inputmode="{{ $mode }}" @if ($mode === 'decimal') step="0.1" @endif
                                                   :id="'p_{{ $nama }}_' + i" :name="'parameter[' + i + '][{{ $nama }}]'" x-model="row.{{ $nama }}"
                                                   @if (in_array($nama, ['umur_awal', 'umur_akhir'])) required @endif
                                                   class="mt-1 block w-full min-h-11 text-base border-gray-300 rounded-md shadow-sm">
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" @click="parameter.splice(i, 1)" class="mt-3 min-h-11 text-base text-red-700 underline">Hapus rentang</button>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="parameter.push({})" class="mt-4 min-h-11 text-base text-indigo-700 underline">+ Tambah rentang umur</button>
                    @foreach ($errors->get('parameter.*') as $pesan)
                        @foreach ((array) $pesan as $baris)
                            <p class="mt-2 text-sm text-red-600">{{ $baris }}</p>
                        @endforeach
                    @endforeach
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Jadwal vaksin</h3>
                    <div class="mt-4 space-y-4">
                        <template x-for="(row, i) in vaksin" :key="i">
                            <div class="rounded-md border border-gray-200 p-4">
                                <div class="grid gap-4 sm:grid-cols-3">
                                    <div>
                                        <label class="block text-base text-gray-700" :for="'v_hari_' + i">Hari ke</label>
                                        <input type="number" inputmode="numeric" required :id="'v_hari_' + i" :name="'vaksin[' + i + '][hari_ke]'" x-model="row.hari_ke"
                                               class="mt-1 block w-full min-h-11 text-base border-gray-300 rounded-md shadow-sm">
                                    </div>
                                    <div>
                                        <label class="block text-base text-gray-700" :for="'v_nama_' + i">Nama vaksin</label>
                                        <input type="text" required :id="'v_nama_' + i" :name="'vaksin[' + i + '][nama_vaksin]'" x-model="row.nama_vaksin"
                                               class="mt-1 block w-full min-h-11 text-base border-gray-300 rounded-md shadow-sm">
                                    </div>
                                    <div>
                                        <label class="block text-base text-gray-700" :for="'v_ket_' + i">Keterangan</label>
                                        <input type="text" :id="'v_ket_' + i" :name="'vaksin[' + i + '][keterangan]'" x-model="row.keterangan"
                                               class="mt-1 block w-full min-h-11 text-base border-gray-300 rounded-md shadow-sm">
                                    </div>
                                </div>
                                <button type="button" @click="vaksin.splice(i, 1)" class="mt-3 min-h-11 text-base text-red-700 underline">Hapus jadwal</button>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="vaksin.push({})" class="mt-4 min-h-11 text-base text-indigo-700 underline">+ Tambah jadwal vaksin</button>
                    @foreach ($errors->get('vaksin.*') as $pesan)
                        @foreach ((array) $pesan as $baris)
                            <p class="mt-2 text-sm text-red-600">{{ $baris }}</p>
                        @endforeach
                    @endforeach
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button class="min-h-11 text-sm">Simpan</x-primary-button>
                    <a href="{{ route('siklus.show', $siklus) }}" class="text-base text-gray-700 underline">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
