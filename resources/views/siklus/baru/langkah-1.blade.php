@php $d = $draf['identitas'] ?? []; @endphp

<x-wizard-siklus :kandang="$kandang" :langkah="1" :konteks="$konteks">
    <h2 class="text-lg font-bold">Identitas &amp; DOC</h2>
    <p class="mt-1 text-[13px] text-ink-secondary">Data dasar siklus. Tanggal DOC masuk menjadi hari ke-1.</p>

    <form method="POST" action="{{ route('siklus.baru.simpan', [$kandang, 1]) }}" novalidate class="mt-5 space-y-5">
        @csrf
        @if ($errors->any())
            <x-alert variant="error">{{ $errors->count() }} isian perlu diperbaiki sebelum lanjut.</x-alert>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <x-field name="tanggal_masuk" label="Tanggal DOC masuk *" type="date" :value="$d['tanggal_masuk'] ?? null" required autofocus />
            <div>
                <x-field name="populasi" label="Populasi awal (ekor) *" type="number" min="1" step="1" inputmode="numeric" :value="$d['populasi'] ?? null" required
                         :hint="'Kapasitas kandang '.number_format($kandang->kapasitas, 0, ',', '.').' ekor.'" />
            </div>
            <x-field name="strain_doc" label="Strain DOC *" :value="$d['strain_doc'] ?? null" placeholder="CP 707" required />
            <x-field name="jenis_pakan" label="Jenis pakan *" :value="$d['jenis_pakan'] ?? null" placeholder="BR-1 (starter) · BR-2 (finisher)" required />
            <x-field name="nama_ppl" label="Nama PPL *" :value="$d['nama_ppl'] ?? null" required />
            <x-field name="nama_inti" label="Nama inti *" :value="$d['nama_inti'] ?? null" required />
            <x-field name="bobot_sak_kg" label="Bobot per sak (kg) *" type="number" min="0.01" step="0.01" inputmode="decimal" :value="$d['bobot_sak_kg'] ?? 50" required hint="Dipakai untuk konversi sak ↔ kg (R4)." />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line-subtle pt-4">
            <p class="text-xs text-ink-muted">* wajib diisi</p>
            <x-button>Lanjut: Kontrak <x-icon name="arrow-right" class="size-4" /></x-button>
        </div>
    </form>
</x-wizard-siklus>
