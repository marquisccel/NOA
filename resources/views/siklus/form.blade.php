@php
    $nomor = \App\Models\Siklus::nomorMap([$siklus->kandang_id])[$siklus->id] ?? 1;
    $nilai = fn ($k) => $kontrak?->{$k};
    $bonus = ['fcr' => 'Bonus FCR', 'deplesi' => 'Bonus deplesi', 'ip' => 'Bonus IP', 'harga_pasar' => 'Bonus harga pasar'];
    $kelasSelect = 'mt-2 block min-h-[46px] w-full rounded-md border-line-control bg-surface text-base focus:border-brand focus:ring-1 focus:ring-brand';
@endphp

<x-app-layout title="Ubah Pengaturan Siklus" :siklus="$siklus"
    :crumbs="[['Kandang & Siklus', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['Siklus '.$nomor, route('siklus.show', $siklus)], ['Ubah pengaturan']]">
    <x-page lebar="max-w-3xl">
        <x-page-header title="Ubah pengaturan siklus" subtitle="Data kontrak dan skema bonus hanya terlihat oleh peternak." />

        <form method="POST" action="{{ route('siklus.update', $siklus) }}" novalidate class="space-y-5">
            @csrf @method('PUT')

            @if ($errors->any())
                <x-alert variant="error">Periksa kembali {{ $errors->count() }} isian yang ditandai sebelum menyimpan.</x-alert>
            @endif

            <section id="identitas" class="scroll-mt-24 space-y-4 rounded-[16px] border border-line bg-surface p-5 sm:p-6">
                <h2 class="text-base font-bold">Identitas &amp; DOC</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="tanggal_masuk" label="Tanggal DOC masuk *" type="date" :value="$siklus->tanggal_masuk?->toDateString()" required />
                    <x-field name="populasi" label="Populasi awal (ekor) *" type="number" min="1" step="1" inputmode="numeric" :value="$siklus->populasi" required />
                    <x-field name="strain_doc" label="Strain DOC" :value="$siklus->strain_doc" />
                    <x-field name="jenis_pakan" label="Jenis pakan" :value="$siklus->jenis_pakan" />
                    <x-field name="nama_ppl" label="Nama PPL" :value="$siklus->nama_ppl" />
                    <x-field name="nama_inti" label="Nama inti" :value="$siklus->nama_inti" />
                    <x-field name="bobot_sak_kg" label="Bobot per sak (kg) *" type="number" min="0.01" step="0.01" inputmode="decimal" :value="$siklus->bobot_sak_kg" required hint="Dipakai untuk konversi sak ↔ kg (R4)." />
                    <div>
                        <x-input-label for="rumus_ip" value="Rumus Indeks Performa (IP)" />
                        <select id="rumus_ip" name="rumus_ip" class="{{ $kelasSelect }}">
                            @foreach (\App\Services\PerformanceCalculator::RUMUS_IP as $nama => $ket)
                                <option value="{{ $nama }}" @selected(old('rumus_ip', $siklus->rumus_ip ?? 'standar') === $nama)>{{ $ket }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-1.5" :messages="$errors->get('rumus_ip')" />
                    </div>
                </div>
            </section>

            <section id="kontrak" class="scroll-mt-24 space-y-4 rounded-[16px] border border-line bg-surface p-5 sm:p-6">
                <div class="flex items-center gap-2.5"><h2 class="text-base font-bold">Kontrak</h2><x-badge variant="brand">Hanya peternak</x-badge></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="kontrak[harga_doc]" label="Harga DOC (Rp / ekor)" type="number" min="0" step="0.01" inputmode="decimal" :value="$nilai('harga_doc')" />
                    <x-field name="kontrak[harga_pakan_sak]" label="Harga pakan (Rp / sak)" type="number" min="0" step="0.01" inputmode="decimal" :value="$nilai('harga_pakan_sak')" />
                    <x-field name="kontrak[harga_ovk]" label="Harga OVK (Rp, opsional)" type="number" min="0" step="0.01" inputmode="decimal" :value="$nilai('harga_ovk')" />
                    <x-field name="kontrak[harga_kontrak_ayam]" label="Harga kontrak ayam (Rp / kg)" type="number" min="0" step="0.01" inputmode="decimal" :value="$nilai('harga_kontrak_ayam')" />
                </div>
            </section>

            <section id="bonus" class="scroll-mt-24 space-y-4 rounded-[16px] border border-line bg-surface p-5 sm:p-6">
                <div class="flex items-center gap-2.5"><h2 class="text-base font-bold">Skema bonus</h2><x-badge variant="brand">Hanya peternak</x-badge></div>
                <p class="text-[13px] text-ink-secondary">Aktifkan bonus yang tercantum di kontrak. Semua boleh nonaktif. NOA hanya menyimpan nilainya; rupiah bonus tidak dihitung.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($bonus as $k => $label)
                        <div class="space-y-2 rounded-md border border-line-subtle p-3.5" x-data="{ aktif: @js((bool) old('kontrak.bonus_'.$k, $nilai('bonus_'.$k))) }">
                            <label class="flex min-h-11 items-center gap-3 text-sm font-semibold">
                                <input type="checkbox" name="kontrak[bonus_{{ $k }}]" value="1" x-model="aktif" class="size-5 rounded border-line-control text-brand focus:ring-brand">
                                {{ $label }}
                            </label>
                            <div x-cloak :class="aktif ? '' : 'hidden'">
                                <x-field name="kontrak[nilai_bonus_{{ $k }}]" label="Nilai (Rp / kg)" type="number" min="0" step="0.01" inputmode="decimal" :value="$nilai('nilai_bonus_'.$k)" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="flex justify-end gap-2.5">
                <x-button variant="secondary" :href="route('siklus.pengaturan', $siklus)">Batal</x-button>
                <x-button>Simpan perubahan</x-button>
            </div>
        </form>
    </x-page>
</x-app-layout>
