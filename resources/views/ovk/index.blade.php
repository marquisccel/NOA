@php
    use App\Support\Format as F;
    $rp = fn ($n) => 'Rp '.F::angka($n, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">OVK · {{ $siklus->kandang->nama_farm }}</h2>
        <p class="mt-1 text-base text-gray-700">Obat, vaksin, dan kimia selama siklus · <a href="{{ route('siklus.show', $siklus) }}" class="text-indigo-700 underline">Detail siklus</a></p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <dl class="grid grid-cols-2 gap-4 lg:grid-cols-4 text-base">
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <dt class="text-gray-600">Total biaya OVK</dt>
                    <dd class="text-2xl font-semibold text-gray-900">{{ $rp($totalBiaya) }}</dd>
                </div>
                @foreach (\App\Models\PemberianOvk::JENIS as $jenis)
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <dt class="text-gray-600">{{ ucfirst($jenis) }}</dt>
                        <dd class="text-xl font-semibold text-gray-900">{{ $rp($biayaPerJenis->get($jenis, 0)) }}</dd>
                    </div>
                @endforeach
            </dl>

            <section>
                <h3 class="mb-3 text-lg font-semibold text-gray-900">Catat pemberian</h3>
                @include('ovk._form', ['ovk' => new \App\Models\PemberianOvk, 'siklus' => $siklus])
            </section>

            <section class="bg-white shadow-sm sm:rounded-lg p-4 sm:p-6">
                <h3 class="text-lg font-semibold text-gray-900">Riwayat OVK</h3>
                @if ($riwayat->isEmpty())
                    <p class="mt-2 text-base text-gray-600">Belum ada pemberian OVK.</p>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-base text-left align-top">
                            <caption class="sr-only">Riwayat pemberian OVK, terbaru di atas</caption>
                            <thead class="text-gray-600 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="py-2 pe-4">Tanggal</th>
                                    <th scope="col" class="py-2 pe-4">Jenis</th>
                                    <th scope="col" class="py-2 pe-4">Produk</th>
                                    <th scope="col" class="py-2 pe-4">Dosis</th>
                                    <th scope="col" class="py-2 pe-4">Biaya</th>
                                    <th scope="col" class="py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-900">
                                @foreach ($riwayat as $o)
                                    <tr>
                                        <td class="py-2 pe-4 whitespace-nowrap">{{ $o->tanggal->translatedFormat('j M Y') }}</td>
                                        <td class="py-2 pe-4">{{ ucfirst($o->jenis) }}</td>
                                        <td class="py-2 pe-4">{{ $o->nama_produk }}</td>
                                        <td class="py-2 pe-4">{{ $o->dosis ?: '-' }}</td>
                                        <td class="py-2 pe-4 whitespace-nowrap">{{ $rp($o->biaya) }}</td>
                                        <td class="py-2"><a href="{{ route('ovk.edit', $o) }}" class="text-indigo-700 underline min-h-11 inline-flex items-center">Ubah</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
