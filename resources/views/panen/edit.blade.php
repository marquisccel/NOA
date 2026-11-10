<x-app-layout :siklus="$siklus">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ubah panen hari ke-{{ $panen->umur_hari }} · {{ $siklus->kandang->nama_farm }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @include('panen._form', ['panen' => $panen, 'siklus' => $siklus])

            <form method="POST" action="{{ route('panen.destroy', $panen) }}" onsubmit="return confirm('Hapus tahap panen ini? Riwayat tetap tersimpan.')">
                @csrf @method('DELETE')
                <button class="min-h-11 text-base text-red-700 underline">Hapus tahap panen</button>
            </form>
        </div>
    </div>
</x-app-layout>
