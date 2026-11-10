<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $kandang->nama_farm }}</h2>
            <a href="{{ route('kandang.edit', $kandang) }}" class="text-base text-indigo-700 underline">Ubah</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid gap-4 sm:grid-cols-3 text-base">
                    <div><dt class="text-gray-600">Alamat</dt><dd class="text-gray-900">{{ $kandang->alamat ?: '-' }}</dd></div>
                    <div><dt class="text-gray-600">Kapasitas</dt><dd class="text-gray-900">{{ number_format($kandang->kapasitas, 0, ',', '.') }} ekor</dd></div>
                    <div><dt class="text-gray-600">Tipe</dt><dd class="text-gray-900">{{ $kandang->tipe ?: '-' }}</dd></div>
                </dl>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="text-lg font-semibold text-gray-900">Siklus</h3>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('laporan.perbandingan', $kandang) }}" class="text-base text-indigo-700 underline">Bandingkan siklus</a>
                        <a href="{{ route('siklus.create', ['kandang_id' => $kandang->id]) }}" class="inline-flex items-center min-h-11 px-4 py-2 bg-gray-800 rounded-md font-semibold text-sm text-white hover:bg-gray-700">Buka siklus</a>
                    </div>
                </div>
                @include('siklus.partials.daftar', ['siklus' => $siklus])
            </div>

            @error('kandang')
                <p class="text-base text-red-700" role="alert">{{ $message }}</p>
            @enderror

            <form method="POST" action="{{ route('kandang.destroy', $kandang) }}"
                  onsubmit="return confirm('Hapus kandang ini? Riwayat tetap tersimpan, tetapi kandang tidak muncul lagi.')">
                @csrf @method('DELETE')
                <button class="min-h-11 text-base text-red-700 underline">Hapus kandang</button>
            </form>
        </div>
    </div>
</x-app-layout>
