<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $kandang->exists ? 'Ubah kandang' : 'Tambah kandang' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ $kandang->exists ? route('kandang.update', $kandang) : route('kandang.store') }}"
                  class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                @csrf
                @if ($kandang->exists) @method('PUT') @endif

                <x-field name="nama_farm" label="Nama farm" :value="$kandang->nama_farm" required autofocus />
                <div>
                    <x-input-label for="alamat" value="Alamat" class="text-base" />
                    <textarea id="alamat" name="alamat" rows="2" class="mt-1 block w-full text-base border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('alamat', $kandang->alamat) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('alamat')" />
                </div>
                <x-field name="kapasitas" label="Kapasitas (ekor)" type="number" :value="$kandang->kapasitas" min="1" inputmode="numeric" required />
                <x-field name="tipe" label="Tipe kandang" :value="$kandang->tipe" hint="Contoh: closed house, semi closed, panggung." />

                <div class="flex items-center gap-4">
                    <x-primary-button class="min-h-11 text-sm">Simpan</x-primary-button>
                    <a href="{{ $kandang->exists ? route('kandang.show', $kandang) : route('kandang.index') }}" class="text-base text-gray-700 underline">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
