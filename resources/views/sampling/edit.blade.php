<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ubah sampling hari ke-{{ $sampling->hari_ke }} · {{ $siklus->kandang->nama_farm }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @include('sampling._form', ['sampling' => $sampling, 'siklus' => $siklus, 'ayamHidup' => $ayamHidup])

            <form method="POST" action="{{ route('sampling.destroy', $sampling) }}" onsubmit="return confirm('Hapus sampling ini? Riwayat tetap tersimpan.')">
                @csrf @method('DELETE')
                <button class="min-h-11 text-base text-red-700 underline">Hapus sampling</button>
            </form>
        </div>
    </div>
</x-app-layout>
