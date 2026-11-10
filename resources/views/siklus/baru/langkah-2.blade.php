@php $d = $draf['kontrak'] ?? []; @endphp

<x-wizard-siklus :kandang="$kandang" :langkah="2" :konteks="$konteks">
    <h2 class="text-lg font-bold">Kontrak siklus</h2>
    <p class="mt-1 text-[13px] text-ink-secondary">Harga sesuai kontrak dengan inti. Hanya terlihat oleh peternak (tidak oleh ABK atau Pengawas Inti).</p>

    <form method="POST" action="{{ route('siklus.baru.simpan', [$kandang, 2]) }}" novalidate class="mt-5 space-y-5">
        @csrf
        @if ($errors->any())
            <x-alert variant="error">{{ $errors->count() }} isian perlu diperbaiki sebelum lanjut.</x-alert>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <x-field name="harga_doc" label="Harga DOC (Rp / ekor) *" type="number" min="0" step="0.01" inputmode="decimal" :value="$d['harga_doc'] ?? null" placeholder="6500" required autofocus />
            <x-field name="harga_pakan_sak" label="Harga pakan (Rp / sak) *" type="number" min="0" step="0.01" inputmode="decimal" :value="$d['harga_pakan_sak'] ?? null" placeholder="412500" required />
            <x-field name="harga_ovk" label="Harga OVK (Rp)" type="number" min="0" step="0.01" inputmode="decimal" :value="$d['harga_ovk'] ?? null" hint="Opsional. Biaya OVK aktual dicatat di tab OVK." />
            <x-field name="harga_kontrak_ayam" label="Harga kontrak ayam (Rp / kg) *" type="number" min="0" step="0.01" inputmode="decimal" :value="$d['harga_kontrak_ayam'] ?? null" placeholder="19500" required />
        </div>

        <p class="flex gap-2 text-xs text-ink-muted"><x-icon name="lock" class="mt-0.5 size-3.5" /> Data kontrak, biaya, dan bonus disembunyikan dari akun ABK dan Pengawas Inti.</p>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line-subtle pt-4">
            <x-button variant="secondary" :href="route('siklus.baru.langkah', [$kandang, 1])">Kembali</x-button>
            <x-button>Lanjut: Skema bonus <x-icon name="arrow-right" class="size-4" /></x-button>
        </div>
    </form>
</x-wizard-siklus>
