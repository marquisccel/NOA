@php
    $label = ['berjalan' => 'Berjalan', 'selesai' => 'Selesai', 'dihentikan' => 'Dihentikan'];
    $rp = fn ($n) => $n === null ? '-' : 'Rp '.number_format((float) $n, 0, ',', '.');
    $k = $siklus->kontrak;
    $bonusAktif = collect(['bonus_fcr' => 'FCR', 'bonus_deplesi' => 'Deplesi', 'bonus_ip' => 'IP', 'bonus_harga_pasar' => 'Harga pasar'])
        ->filter(fn ($teks, $kolom) => $k?->{$kolom})->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Siklus {{ $siklus->kandang->nama_farm }} · masuk {{ $siklus->tanggal_masuk->translatedFormat('j M Y') }}
                </h2>
                <p class="mt-1 text-base text-gray-700">Status: <strong>{{ $label[$siklus->status] ?? $siklus->status }}</strong></p>
            </div>
            <a href="{{ route('siklus.edit', $siklus) }}" class="text-base text-indigo-700 underline">Ubah</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Data siklus</h3>
                <dl class="mt-4 grid gap-4 sm:grid-cols-3 text-base">
                    <div><dt class="text-gray-600">Populasi</dt><dd class="text-gray-900">{{ number_format($siklus->populasi, 0, ',', '.') }} ekor</dd></div>
                    <div><dt class="text-gray-600">Strain DOC</dt><dd class="text-gray-900">{{ $siklus->strain_doc ?: '-' }}</dd></div>
                    <div><dt class="text-gray-600">Jenis pakan</dt><dd class="text-gray-900">{{ $siklus->jenis_pakan ?: '-' }}</dd></div>
                    <div><dt class="text-gray-600">PPL</dt><dd class="text-gray-900">{{ $siklus->nama_ppl ?: '-' }}</dd></div>
                    <div><dt class="text-gray-600">Inti</dt><dd class="text-gray-900">{{ $siklus->nama_inti ?: '-' }}</dd></div>
                    <div><dt class="text-gray-600">Bobot per sak</dt><dd class="text-gray-900">{{ rtrim(rtrim(number_format((float) $siklus->bobot_sak_kg, 2, ',', '.'), '0'), ',') }} kg</dd></div>
                    <div><dt class="text-gray-600">Tanggal panen</dt><dd class="text-gray-900">{{ $siklus->tanggal_panen?->translatedFormat('j M Y') ?? '-' }}</dd></div>
                    <div><dt class="text-gray-600">Re-chick in</dt><dd class="text-gray-900">{{ $siklus->tanggal_rechick?->translatedFormat('j M Y') ?? '-' }}</dd></div>
                </dl>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Kontrak</h3>
                <dl class="mt-4 grid gap-4 sm:grid-cols-4 text-base">
                    <div><dt class="text-gray-600">Harga DOC</dt><dd class="text-gray-900">{{ $rp($k?->harga_doc) }}</dd></div>
                    <div><dt class="text-gray-600">Pakan per sak</dt><dd class="text-gray-900">{{ $rp($k?->harga_pakan_sak) }}</dd></div>
                    <div><dt class="text-gray-600">OVK</dt><dd class="text-gray-900">{{ $rp($k?->harga_ovk) }}</dd></div>
                    <div><dt class="text-gray-600">Kontrak ayam / kg</dt><dd class="text-gray-900">{{ $rp($k?->harga_kontrak_ayam) }}</dd></div>
                </dl>
                <p class="mt-4 text-base text-gray-900">
                    <span class="text-gray-600">Skema bonus:</span>
                    {{ $bonusAktif->isEmpty() ? 'tidak ada yang diaktifkan' : $bonusAktif->implode(', ') }}
                </p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Recording harian</h3>
                <p class="mt-2 text-base text-gray-700">Pakan terpakai, ayam mati, dan afkir tiap hari; angka berjalan dihitung tiap entri disimpan.</p>
                <a href="{{ route('recording.index', $siklus) }}" class="mt-3 inline-flex items-center min-h-11 text-base text-indigo-700 underline">Buka recording harian</a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Panen dan performa</h3>
                <p class="mt-2 text-base text-gray-700">Panen bertahap dengan nomor BTPA, lalu FCR, IP, umur panen berbobot, dan selisih populasi.</p>
                <a href="{{ route('panen.index', $siklus) }}" class="mt-3 inline-flex items-center min-h-11 text-base text-indigo-700 underline">Buka panen dan performa</a>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Sampling bobot</h3>
                    <p class="mt-2 text-base text-gray-700">Timbang sampel berkala; ABW dan pertambahan harian dibandingkan dengan standar.</p>
                    <a href="{{ route('sampling.index', $siklus) }}" class="mt-3 inline-flex items-center min-h-11 text-base text-indigo-700 underline">Buka sampling bobot</a>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900">OVK</h3>
                    <p class="mt-2 text-base text-gray-700">Obat, vaksin, dan kimia beserta biayanya.</p>
                    <a href="{{ route('ovk.index', $siklus) }}" class="mt-3 inline-flex items-center min-h-11 text-base text-indigo-700 underline">Buka catatan OVK</a>
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Tabel standar harian</h3>
                    <p class="mt-2 text-base text-gray-700">
                        @if ($standarJumlah > 0)
                            {{ $standarJumlah }} baris hari umur sudah diunggah.
                        @else
                            Belum diunggah. Tanpa tabel ini, kolom standar kosong dan perbandingan harian tidak berjalan.
                        @endif
                    </p>
                    <a href="{{ route('siklus.standar', $siklus) }}" class="mt-3 inline-flex items-center min-h-11 text-base text-indigo-700 underline">
                        {{ $standarJumlah > 0 ? 'Lihat / ganti tabel' : 'Unggah tabel standar' }}
                    </a>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Parameter umur dan vaksin</h3>
                    <p class="mt-2 text-base text-gray-700">
                        {{ $siklus->parameterUmur->count() }} rentang umur, {{ $siklus->jadwalVaksin->count() }} jadwal vaksin.
                    </p>
                    <a href="{{ route('siklus.acuan', $siklus) }}" class="mt-3 inline-flex items-center min-h-11 text-base text-indigo-700 underline">Atur parameter dan vaksin</a>
                </div>
            </div>

            @if ($siklus->status === 'berjalan')
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Akhiri siklus</h3>
                    <p class="mt-1 text-base text-gray-600">Siklus selesai dikunci dari penyuntingan recording.</p>
                    <form method="POST" action="{{ route('siklus.selesai', $siklus) }}" class="mt-4 grid gap-6 sm:grid-cols-3"
                          onsubmit="return confirm('Akhiri siklus ini? Recording akan terkunci.')">
                        @csrf @method('PATCH')
                        <div>
                            <x-input-label for="status" value="Status akhir" class="text-base" />
                            <select id="status" name="status" class="mt-1 block w-full min-h-11 text-base border-gray-300 rounded-md shadow-sm">
                                <option value="selesai" @selected(old('status') === 'selesai')>Selesai</option>
                                <option value="dihentikan" @selected(old('status') === 'dihentikan')>Dihentikan dini (misal wabah)</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('status')" />
                        </div>
                        <x-field name="tanggal_panen" label="Tanggal panen" type="date" required />
                        <x-field name="tanggal_rechick" label="Tanggal re-chick in" type="date" />
                        <div class="sm:col-span-3">
                            <x-primary-button class="min-h-11 text-sm">Akhiri siklus</x-primary-button>
                        </div>
                    </form>
                </div>
            @endif

            <form method="POST" action="{{ route('siklus.destroy', $siklus) }}"
                  onsubmit="return confirm('Hapus siklus ini? Riwayat tetap tersimpan, tetapi siklus tidak muncul lagi.')">
                @csrf @method('DELETE')
                <button class="min-h-11 text-base text-red-700 underline">Hapus siklus</button>
            </form>
        </div>
    </div>
</x-app-layout>
