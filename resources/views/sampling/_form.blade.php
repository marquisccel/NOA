@php
    $ada = $sampling->exists;
    $nilai = fn ($kolom, $bawaan = null) => old($kolom, $sampling->{$kolom} ?? $bawaan);
@endphp

<form method="POST" action="{{ $ada ? route('sampling.update', $sampling) : route('sampling.store', $siklus) }}"
      class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6"
      x-data="{
          jumlah: @js($nilai('jumlah_sampel')),
          total: @js($nilai('total_bobot_kg')),
          hidup: @js($ayamHidup),
          num(v) { const n = parseFloat(String(v ?? '').replace(',', '.')); return isNaN(n) ? 0 : n },
          fmt(n, d = 0) { return n.toLocaleString('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d }) },
          get abw() { return this.num(this.jumlah) > 0 ? this.num(this.total) * 1000 / this.num(this.jumlah) : null },
          get persen() { return this.hidup > 0 ? this.num(this.jumlah) / this.hidup * 100 : null },
      }">
    @csrf
    @if ($ada) @method('PUT') @endif

    <div class="grid gap-6 sm:grid-cols-3">
        <x-field name="tanggal" label="Tanggal timbang" type="date" :value="$sampling->tanggal?->toDateString() ?? now()->toDateString()"
                 min="{{ $siklus->tanggal_masuk->toDateString() }}" max="{{ now()->toDateString() }}" required />
        <x-field name="jumlah_sampel" label="Jumlah ekor ditimbang" type="number" step="1" min="1" inputmode="numeric"
                 :value="$sampling->jumlah_sampel" x-model="jumlah" required />
        <x-field name="total_bobot_kg" label="Total bobot sampel (kg)" type="number" step="0.001" min="0" inputmode="decimal"
                 :value="$sampling->total_bobot_kg" x-model="total" required />
    </div>

    <div class="rounded-md bg-indigo-50 border border-indigo-200 p-4 text-base text-indigo-950" aria-live="polite">
        <p>ABW sampel: <strong x-text="abw === null ? '-' : fmt(abw) + ' g'"></strong></p>
        <p>Sampel: <strong x-text="persen === null ? '-' : fmt(persen, 2) + '% dari ' + fmt(hidup) + ' ayam hidup'"></strong>
            <span :hidden="!(persen !== null && persen < 5 && num(jumlah) > 0)" hidden class="text-amber-800 font-medium">· di bawah 5%, kurang representatif</span></p>
        <p class="text-sm text-indigo-900">Petunjuk saja; angka yang disimpan dihitung ulang oleh server.</p>
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button class="min-h-11 text-sm">Simpan sampling</x-primary-button>
        @if ($ada)<a href="{{ route('sampling.index', $siklus) }}" class="text-base text-gray-700 underline">Batal</a>@endif
    </div>
</form>
