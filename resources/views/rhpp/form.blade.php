@php
    use App\Http\Requests\RhppRequest;

    $kosong = collect($errors->messages())->filter(fn ($pesan) => in_array('Wajib diisi.', $pesan, true))->keys()
        ->map(fn ($k) => RhppRequest::WAJIB[$k] ?? $k)->values();
    $tanggal = old('tanggal_terbit', $rhpp?->tanggal_terbit?->toDateString() ?? now()->toDateString());
@endphp

<x-app-layout title="Input RHPP" :siklus="$siklus"
    :crumbs="[['Kandang & Siklus', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['RHPP', route('rhpp.show', $siklus)], ['Input RHPP']]">
    <x-page lebar="max-w-3xl">
        <x-page-header title="Input RHPP" subtitle="Salin angka persis dari dokumen RHPP inti. Satuan mengikuti dokumen." />

        @if ($kosong->isNotEmpty())
            <x-alert variant="error">{{ $kosong->count() }} isian wajib masih kosong: {{ $kosong->implode(', ') }}.</x-alert>
        @endif

        <form method="POST" action="{{ route('rhpp.simpan', $siklus) }}" enctype="multipart/form-data" novalidate class="space-y-5">
            @csrf @method('PUT')

            <section class="space-y-5 rounded-[16px] border border-line bg-surface p-5 sm:p-6">
                <h2 class="text-base font-bold">Dokumen RHPP</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="nomor_rhpp" value="Nomor RHPP *" />
                        <x-text-input id="nomor_rhpp" name="nomor_rhpp" :value="old('nomor_rhpp', $rhpp?->nomor_rhpp)" placeholder="RHPP/SU/2026/11/0457" class="mt-2 !min-h-[50px] text-lg" :invalid="$errors->has('nomor_rhpp')" />
                        <x-input-error class="mt-1.5" :messages="$errors->get('nomor_rhpp')" />
                    </div>
                    <div>
                        <x-input-label for="tanggal_terbit" value="Tanggal terbit *" />
                        <input id="tanggal_terbit" type="date" name="tanggal_terbit" value="{{ $tanggal }}" min="{{ $siklus->tanggal_masuk->toDateString() }}" max="{{ now()->toDateString() }}"
                               class="mt-2 block min-h-[50px] w-full rounded-md border-line-control bg-surface px-3.5 text-base focus:border-brand focus:ring-1 focus:ring-brand">
                        <x-input-error class="mt-1.5" :messages="$errors->get('tanggal_terbit')" />
                    </div>
                </div>
                <div>
                    <x-input-label for="lampiran" value="Lampiran (foto/PDF)" />
                    <input id="lampiran" name="lampiran" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="mt-2 block w-full rounded-md border border-dashed border-line-control bg-page p-3 text-base">
                    <p class="mt-1.5 text-xs text-ink-muted">JPG, PNG, atau PDF · maks. 5 MB. Disimpan privat.</p>
                    <x-input-error class="mt-1.5" :messages="$errors->get('lampiran')" />
                    @if ($rhpp?->lampiran)<p class="mt-2 text-sm"><a href="{{ route('rhpp.lampiran', $siklus) }}" target="_blank" rel="noopener" class="font-semibold text-ink-brand underline">Lihat lampiran saat ini</a></p>@endif
                </div>
            </section>

            <section class="space-y-5 rounded-[16px] border border-line bg-surface p-5 sm:p-6">
                <h2 class="text-base font-bold">Populasi &amp; produksi</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input-satuan name="populasi_doc" label="Populasi DOC *" satuan="ekor" step="1" min="0" inputmode="numeric" :value="$rhpp?->populasi_doc ?? $siklus->populasi" />
                    <x-input-satuan name="total_ekor" label="Ayam dipanen *" satuan="ekor" step="1" min="0" inputmode="numeric" :value="$rhpp?->total_ekor" />
                    <x-input-satuan name="deplesi_ekor" label="Deplesi *" satuan="ekor" step="1" min="0" inputmode="numeric" :value="$rhpp?->deplesi_ekor" />
                    <x-input-satuan name="total_tonase" label="Tonase *" satuan="kg" step="0.01" min="0" inputmode="decimal" :value="$rhpp?->total_tonase" />
                    <x-input-satuan name="pakan_terpakai_kg" label="Pakan terpakai *" satuan="kg" step="0.01" min="0" inputmode="decimal" :value="$rhpp?->pakan_terpakai_kg" />
                    <x-input-satuan name="pakan_retur_sak" label="Pakan retur" satuan="sak" step="0.01" min="0" inputmode="decimal" :value="$rhpp?->pakan_retur_sak" />
                    <x-input-satuan name="fcr" label="FCR *" step="0.001" min="0" inputmode="decimal" :value="$rhpp?->fcr" />
                    <x-input-satuan name="ip" label="IP *" step="0.01" min="0" inputmode="decimal" :value="$rhpp?->ip" />
                    <x-input-satuan name="abw" label="ABW *" satuan="kg" step="0.001" min="0" inputmode="decimal" :value="$rhpp?->abw" />
                    <x-input-satuan name="umur_panen" label="Umur panen *" satuan="hari" step="0.01" min="0" inputmode="decimal" :value="$rhpp?->umur_panen" />
                </div>
            </section>

            <section class="space-y-5 rounded-[16px] border border-line bg-surface p-5 sm:p-6">
                <h2 class="text-base font-bold">Biaya &amp; hasil (sesuai RHPP)</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input-satuan name="biaya_doc" label="Biaya DOC *" awalan="Rp" step="1" min="0" inputmode="numeric" :value="$rhpp?->biaya_doc" />
                    <x-input-satuan name="biaya_pakan" label="Biaya pakan *" awalan="Rp" step="1" min="0" inputmode="numeric" :value="$rhpp?->biaya_pakan" />
                    <x-input-satuan name="biaya_ovk" label="Biaya OVK *" awalan="Rp" step="1" min="0" inputmode="numeric" :value="$rhpp?->biaya_ovk" />
                    <x-input-satuan name="pendapatan" label="Pendapatan *" awalan="Rp" step="1" min="0" inputmode="numeric" :value="$rhpp?->pendapatan" />
                    <x-input-satuan name="bonus" label="Bonus" awalan="Rp" step="1" min="0" inputmode="numeric" :value="$rhpp?->bonus" hint="Total bonus tertulis di RHPP" />
                    <x-input-satuan name="hasil_bersih" label="Hasil bersih *" awalan="Rp" step="1" inputmode="numeric" :value="$rhpp?->hasil_bersih" />
                </div>
                <p class="text-xs text-ink-muted">Bonus dan hasil bersih hanya dicatat dari RHPP; NOA tidak menghitungnya.</p>
            </section>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-ink-secondary">{{ $siklus->status === \App\Models\Siklus::STATUS_MENUNGGU_RHPP ? 'Setelah disimpan, status siklus berubah menjadi Rekonsiliasi.' : 'Perubahan RHPP memperbarui tabel rekonsiliasi.' }}</p>
                <div class="flex gap-2.5">
                    <x-button variant="secondary" :href="route('rhpp.show', $siklus)">Batal</x-button>
                    <x-button>Simpan RHPP</x-button>
                </div>
            </div>
        </form>
    </x-page>
</x-app-layout>
