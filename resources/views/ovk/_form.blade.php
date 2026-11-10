@php $ada = $ovk->exists; @endphp

<form method="POST" action="{{ $ada ? route('ovk.update', $ovk) : route('ovk.store', $siklus) }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
    @csrf
    @if ($ada) @method('PUT') @endif

    <div class="grid gap-6 sm:grid-cols-2">
        <x-field name="tanggal" label="Tanggal pemberian" type="date" :value="$ovk->tanggal?->toDateString() ?? now()->toDateString()"
                 min="{{ $siklus->tanggal_masuk->toDateString() }}" max="{{ now()->toDateString() }}" required />
        <div>
            <x-input-label for="jenis" value="Jenis" class="text-base" />
            <select id="jenis" name="jenis" required class="mt-1 block w-full min-h-11 text-base border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                @foreach (\App\Models\PemberianOvk::JENIS as $jenis)
                    <option value="{{ $jenis }}" @selected(old('jenis', $ovk->jenis) === $jenis)>{{ ucfirst($jenis) }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('jenis')" />
        </div>
        <x-field name="nama_produk" label="Nama produk" :value="$ovk->nama_produk" required />
        <x-field name="dosis" label="Dosis" :value="$ovk->dosis" hint="Contoh: 1 g/L air minum, 1 dosis/ekor." />
        <x-field name="biaya" label="Biaya (Rp)" type="number" step="0.01" min="0" inputmode="decimal" :value="$ovk->biaya"
                 hint="Masuk hitungan biaya produksi siklus." />
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button class="min-h-11 text-sm">Simpan</x-primary-button>
        @if ($ada)<a href="{{ route('ovk.index', $siklus) }}" class="text-base text-gray-700 underline">Batal</a>@endif
    </div>
</form>
