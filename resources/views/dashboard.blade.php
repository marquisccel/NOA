@php
    $berjalan = auth()->user()->isPeternak()
        ? \App\Models\Siklus::where('status', 'berjalan')
            ->whereHas('kandang', fn ($q) => $q->where('user_id', auth()->id()))
            ->with('kandang')->orderBy('tanggal_masuk')->get()
        : collect();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 text-base">
                @if (! auth()->user()->isPeternak())
                    Anda masuk sebagai {{ auth()->user()->role }}. Entri harian tersedia di fase berikutnya.
                @else
                    <h3 class="text-lg font-semibold">Siklus berjalan</h3>
                    @if ($berjalan->isEmpty())
                        <p class="mt-2 text-gray-600">
                            Belum ada siklus berjalan.
                            <a href="{{ route('siklus.create') }}" class="text-indigo-700 underline">Buka siklus</a>
                        </p>
                    @else
                        @include('siklus.partials.daftar', ['siklus' => $berjalan, 'showKandang' => true])
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
