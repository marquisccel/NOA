<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kandang</h2>
            <a href="{{ route('kandang.create') }}" class="inline-flex items-center min-h-11 px-4 py-2 bg-gray-800 rounded-md font-semibold text-sm text-white hover:bg-gray-700">Tambah kandang</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($kandang->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-base text-gray-900">
                    Belum ada kandang. Daftarkan kandang dulu, lalu buka siklus pemeliharaan.
                </div>
            @else
                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($kandang as $k)
                        @php $berjalan = $k->siklus->first(); @endphp
                        <li class="bg-white shadow-sm sm:rounded-lg p-6">
                            <a href="{{ route('kandang.show', $k) }}" class="text-lg font-semibold text-gray-900 hover:underline">{{ $k->nama_farm }}</a>
                            <p class="mt-1 text-base text-gray-600">Kapasitas {{ number_format($k->kapasitas, 0, ',', '.') }} ekor{{ $k->tipe ? ' · '.$k->tipe : '' }}</p>
                            <p class="mt-3 text-base">
                                @if ($berjalan)
                                    <a href="{{ route('siklus.show', $berjalan) }}" class="text-indigo-700 underline">Siklus berjalan, masuk {{ $berjalan->tanggal_masuk->translatedFormat('j M Y') }}</a>
                                @else
                                    <span class="text-gray-600">Tidak ada siklus berjalan</span>
                                @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>
