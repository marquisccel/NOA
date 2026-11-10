@php
    $ada = $panen->exists;
    $nilai = fn ($kolom) => old($kolom, $panen->{$kolom});
@endphp

<form method="POST" action="{{ $ada ? route('panen.update', $panen) : route('panen.store', $siklus) }}" enctype="multipart/form-data"
      class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6"
      x-data="{
          ekor: @js($nilai('jumlah_ekor')),
          tonase: @js($nilai('tonase_kg')),
          num(v) { const n = parseFloat(String(v ?? '').replace(',', '.')); return isNaN(n) ? 0 : n },
          fmt(n, d = 2) { return n.toLocaleString('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d }) },
          get abw() { return this.num(this.ekor) > 0 ? this.num(this.tonase) / this.num(this.ekor) : null },
      }">
    @csrf
    @if ($ada) @method('PUT') @endif

    <div class="grid gap-6 sm:grid-cols-2">
        <x-field name="tanggal" label="Tanggal panen" type="date" :value="$panen->tanggal?->toDateString() ?? now()->toDateString()"
                 min="{{ $siklus->tanggal_masuk->toDateString() }}" max="{{ now()->toDateString() }}" required />
        <x-field name="nomor_btpa" label="Nomor BTPA" :value="$panen->nomor_btpa" hint="Berita acara timbang panen dari petugas inti." />
        <x-field name="jumlah_ekor" label="Jumlah ekor dipanen" type="number" step="1" min="1" inputmode="numeric" :value="$panen->jumlah_ekor" x-model="ekor" required />
        <x-field name="tonase_kg" label="Tonase hasil timbang (kg)" type="number" step="0.01" min="0" inputmode="decimal" :value="$panen->tonase_kg" x-model="tonase" required />
        <x-field name="harga_per_kg" label="Harga per kg tahap ini (Rp)" type="number" step="0.01" min="0" inputmode="decimal" :value="$panen->harga_per_kg"
                 hint="Tiap tahap menyimpan harganya sendiri; pendapatan dijumlahkan per tahap." />
        <div>
            <x-input-label for="foto_btpa" value="Foto BTPA (opsional)" class="text-base" />
            <input id="foto_btpa" name="foto_btpa" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-base">
            <p class="mt-1 text-sm text-gray-600">JPG, PNG, atau WebP, maksimal 4 MB. Disimpan privat sebagai bukti.</p>
            <x-input-error class="mt-2" :messages="$errors->get('foto_btpa')" />
            @if ($ada && $panen->foto_btpa)
                <p class="mt-2 text-base">
                    <a href="{{ route('panen.foto', $panen) }}" target="_blank" rel="noopener" class="text-indigo-700 underline">Lihat foto saat ini</a>
                </p>
                <label class="mt-1 flex items-center gap-3 min-h-11 text-base text-gray-900">
                    <input type="checkbox" name="hapus_foto" value="1" class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Hapus foto ini
                </label>
            @endif
        </div>
    </div>

    <div class="rounded-md bg-indigo-50 border border-indigo-200 p-4 text-base text-indigo-950" aria-live="polite">
        <p>Bobot rata-rata tahap ini: <strong x-text="abw === null ? '-' : fmt(abw, 3) + ' kg/ekor'"></strong></p>
        <p class="text-sm text-indigo-900">Petunjuk saja; angka yang disimpan dihitung ulang oleh server.</p>
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button class="min-h-11 text-sm">Simpan tahap panen</x-primary-button>
        @if ($ada)<a href="{{ route('panen.index', $siklus) }}" class="text-base text-gray-700 underline">Batal</a>@endif
    </div>
</form>
