@php
    $user = auth()->user();
    $pemilikId = $user->idPemilikData();
    $berjalan = ($user->isPeternak() || $user->isAbk())
        ? \App\Models\Siklus::where('status', 'berjalan')
            ->whereHas('kandang', fn ($q) => $q->where('user_id', $pemilikId))
            ->with('kandang')->orderBy('tanggal_masuk')->get()
        : collect();
@endphp

<x-app-layout title="Dashboard" :crumbs="[['Beranda', route('dashboard')], ['Dashboard']]">
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 text-base">
                <h3 class="text-lg font-semibold">Siklus berjalan</h3>

                @if ($user->isPeternak())
                    @if ($berjalan->isEmpty())
                        <p class="mt-2 text-gray-600">
                            Belum ada siklus berjalan.
                            <a href="{{ route('siklus.create') }}" class="text-indigo-700 underline">Buka siklus</a>
                        </p>
                    @else
                        @include('siklus.partials.daftar', ['siklus' => $berjalan, 'showKandang' => true])
                    @endif
                @elseif ($user->isAbk())
                    {{-- ABK hanya melihat daftar kandang dan pintu entri harian; tanpa kontrak, biaya, atau rekonsiliasi (R13). --}}
                    @if ($berjalan->isEmpty())
                        <p class="mt-2 text-gray-600">Belum ada siklus berjalan untuk dicatat.</p>
                    @else
                        <ul class="mt-4 divide-y divide-gray-200">
                            @foreach ($berjalan as $s)
                                <li class="py-3 flex flex-wrap items-center justify-between gap-2">
                                    <span>{{ $s->kandang->nama_farm }} · masuk {{ $s->tanggal_masuk->translatedFormat('j M Y') }}</span>
                                    <a href="{{ route('recording.index', $s) }}" class="inline-flex items-center min-h-11 px-4 py-2 bg-gray-800 rounded-md font-semibold text-sm text-white hover:bg-gray-700">Entri harian</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @else
                    <p class="mt-2 text-gray-600">Akun ini belum punya akses ke data kandang.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
