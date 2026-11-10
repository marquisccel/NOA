@php
    $bonus = [
        'bonus_fcr' => 'Bonus FCR',
        'bonus_deplesi' => 'Bonus deplesi',
        'bonus_ip' => 'Bonus IP',
        'bonus_harga_pasar' => 'Bonus harga pasar',
    ];
    $nilaiKontrak = fn ($k) => $kontrak?->{$k};
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $siklus->exists ? 'Ubah siklus' : 'Buka siklus' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($kandang->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-base text-gray-900">
                    Daftarkan kandang dulu sebelum membuka siklus.
                    <a href="{{ route('kandang.create') }}" class="text-indigo-700 underline">Tambah kandang</a>
                </div>
            @else
            <form method="POST" action="{{ $siklus->exists ? route('siklus.update', $siklus) : route('siklus.store') }}" class="space-y-6">
                @csrf
                @if ($siklus->exists) @method('PUT') @endif

                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900">Data siklus</h3>

                    <div>
                        <x-input-label for="kandang_id" value="Kandang" class="text-base" />
                        <select id="kandang_id" name="kandang_id" @disabled($siklus->exists) required
                                class="mt-1 block w-full min-h-11 text-base border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach ($kandang as $k)
                                <option value="{{ $k->id }}" @selected((int) old('kandang_id', $kandangDipilih) === $k->id)>{{ $k->nama_farm }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('kandang_id')" />
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <x-field name="tanggal_masuk" label="Tanggal masuk DOC" type="date" :value="$siklus->tanggal_masuk?->toDateString()" required />
                        <x-field name="populasi" label="Populasi (ekor DOC)" type="number" :value="$siklus->populasi" min="1" inputmode="numeric" required />
                        <x-field name="strain_doc" label="Jenis DOC / strain" :value="$siklus->strain_doc" hint="Contoh: Cobb 500, Ross 308." />
                        <x-field name="jenis_pakan" label="Jenis pakan" :value="$siklus->jenis_pakan" />
                        <x-field name="nama_ppl" label="Nama PPL" :value="$siklus->nama_ppl" />
                        <x-field name="nama_inti" label="Nama inti" :value="$siklus->nama_inti" />
                        <x-field name="bobot_sak_kg" label="Bobot per sak (kg)" type="number" step="0.01" min="0.01" :value="$siklus->bobot_sak_kg" inputmode="decimal" required hint="Dipakai mengonversi sak ke kilogram." />
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Kontrak siklus</h3>
                        <p class="mt-1 text-base text-gray-600">Isi sesuai salinan kontrak. Kolom yang belum diketahui boleh dikosongkan.</p>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <x-field name="kontrak[harga_doc]" label="Harga DOC (Rp)" type="number" step="0.01" min="0" inputmode="decimal" :value="$nilaiKontrak('harga_doc')" />
                        <x-field name="kontrak[harga_pakan_sak]" label="Harga pakan per sak (Rp)" type="number" step="0.01" min="0" inputmode="decimal" :value="$nilaiKontrak('harga_pakan_sak')" />
                        <x-field name="kontrak[harga_ovk]" label="Harga OVK (Rp)" type="number" step="0.01" min="0" inputmode="decimal" :value="$nilaiKontrak('harga_ovk')" />
                        <x-field name="kontrak[harga_kontrak_ayam]" label="Harga kontrak ayam (Rp/kg)" type="number" step="0.01" min="0" inputmode="decimal" :value="$nilaiKontrak('harga_kontrak_ayam')" />
                    </div>

                    <fieldset>
                        <legend class="text-base font-medium text-gray-900">Skema bonus yang berlaku</legend>
                        <p class="mt-1 text-sm text-gray-600">Skema bonus berbeda antar inti; aktifkan hanya yang ada di kontrak.</p>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach ($bonus as $kolom => $teks)
                                <label class="flex items-center gap-3 min-h-11 text-base text-gray-900">
                                    <input type="checkbox" name="kontrak[{{ $kolom }}]" value="1"
                                           class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                           @checked(old('kontrak.'.$kolom, $nilaiKontrak($kolom)))>
                                    {{ $teks }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button class="min-h-11 text-sm">Simpan</x-primary-button>
                    <a href="{{ $siklus->exists ? route('siklus.show', $siklus) : route('siklus.index') }}" class="text-base text-gray-700 underline">Batal</a>
                </div>
            </form>
            @endif
        </div>
    </div>
</x-app-layout>
