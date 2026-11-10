@php
    $label = ['berjalan' => 'Berjalan', 'selesai' => 'Selesai', 'dihentikan' => 'Dihentikan'];
@endphp

@if ($siklus->isEmpty())
    <p class="mt-4 text-base text-gray-600">Belum ada siklus.</p>
@else
    <ul class="mt-4 divide-y divide-gray-200">
        @foreach ($siklus as $s)
            <li class="py-3 flex flex-wrap items-center justify-between gap-2 text-base">
                <a href="{{ route('siklus.show', $s) }}" class="text-indigo-700 underline min-h-11 inline-flex items-center">
                    @isset($showKandang){{ $s->kandang->nama_farm }} · @endisset
                    Masuk {{ $s->tanggal_masuk->translatedFormat('j M Y') }} · {{ number_format($s->populasi, 0, ',', '.') }} ekor
                </a>
                <span class="px-2 py-1 rounded-md bg-gray-100 text-gray-800 text-sm">{{ $label[$s->status] ?? $s->status }}</span>
            </li>
        @endforeach
    </ul>
@endif
