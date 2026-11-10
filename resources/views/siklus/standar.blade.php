<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tabel standar harian · {{ $siklus->kandang->nama_farm }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Unggah CSV</h3>
                <p class="mt-2 text-base text-gray-700">
                    Satu baris per hari umur, dari tabel standar kemitraan Anda. Kolom:
                    <code class="text-sm">hari_ke, pakan_std_sak, bobot_std_gram, fcr_std, feed_intake_std_gram</code>.
                    Pemisah koma atau titik koma. Unggahan baru menggantikan seluruh tabel lama.
                </p>
                <p class="mt-2 text-base"><a href="{{ route('siklus.standar.template') }}" class="text-indigo-700 underline">Unduh contoh format</a></p>

                <form method="POST" action="{{ route('siklus.standar.unggah', $siklus) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="file" value="File CSV" class="text-base" />
                        <input id="file" name="file" type="file" accept=".csv,text/csv,text/plain" required class="mt-1 block w-full text-base">
                        @foreach ($errors->get('file') as $pesan)
                            @foreach ((array) $pesan as $baris)
                                <p class="mt-2 text-sm text-red-600">{{ $baris }}</p>
                            @endforeach
                        @endforeach
                    </div>
                    <x-primary-button class="min-h-11 text-sm">Unggah</x-primary-button>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Isi tabel ({{ $standar->count() }} baris)</h3>
                @if ($standar->isEmpty())
                    <p class="mt-2 text-base text-gray-600">Belum ada data.</p>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-base text-left">
                            <thead class="text-gray-600">
                                <tr>
                                    <th class="py-2 pe-4">Hari</th>
                                    <th class="py-2 pe-4">Pakan kumulatif (sak)</th>
                                    <th class="py-2 pe-4">Bobot (g)</th>
                                    <th class="py-2 pe-4">FCR</th>
                                    <th class="py-2">Feed intake (g/ekor)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-900">
                                @foreach ($standar as $s)
                                    <tr>
                                        <td class="py-2 pe-4">{{ $s->hari_ke }}</td>
                                        <td class="py-2 pe-4">{{ number_format((float) $s->pakan_std_sak, 2, ',', '.') }}</td>
                                        <td class="py-2 pe-4">{{ number_format((float) $s->bobot_std_gram, 2, ',', '.') }}</td>
                                        <td class="py-2 pe-4">{{ number_format((float) $s->fcr_std, 3, ',', '.') }}</td>
                                        <td class="py-2">{{ number_format((float) $s->feed_intake_std_gram, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <a href="{{ route('siklus.show', $siklus) }}" class="inline-flex items-center min-h-11 text-base text-gray-700 underline">Kembali ke siklus</a>
        </div>
    </div>
</x-app-layout>
