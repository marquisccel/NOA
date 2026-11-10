@php
    $d = $draf['bonus'] ?? [];
    $bonus = ['fcr' => 'Bonus FCR', 'deplesi' => 'Bonus deplesi', 'ip' => 'Bonus IP', 'harga_pasar' => 'Bonus harga pasar'];
@endphp

<x-wizard-siklus :kandang="$kandang" :langkah="3" :konteks="$konteks">
    <h2 class="text-lg font-bold">Skema bonus</h2>
    <p class="mt-1 text-[13px] text-ink-secondary">Aktifkan bonus yang tercantum di kontrak. Semua boleh nonaktif. NOA hanya menyimpan nilainya untuk perbandingan; rupiah bonus tidak dihitung.</p>

    <form method="POST" action="{{ route('siklus.baru.simpan', [$kandang, 3]) }}" novalidate class="mt-5 space-y-4">
        @csrf
        @if ($errors->any())
            <x-alert variant="error">{{ $errors->count() }} isian perlu diperbaiki sebelum lanjut.</x-alert>
        @endif

        @foreach ($bonus as $k => $label)
            <div class="space-y-3 rounded-md border border-line-subtle p-4" x-data="{ aktif: @js((bool) old('bonus_'.$k, $d['bonus_'.$k] ?? false)) }">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-bold">{{ $label }}</p>
                    <label class="inline-flex min-h-11 items-center gap-2.5 text-[13px] font-semibold">
                        <input type="checkbox" name="bonus_{{ $k }}" value="1" x-model="aktif" class="size-5 rounded border-line-control text-brand focus:ring-brand">
                        <span x-text="aktif ? 'Aktif' : 'Nonaktif'"></span>
                    </label>
                </div>
                <div x-cloak :class="aktif ? 'sm:max-w-xs' : 'hidden'">
                    <x-field :name="'nilai_bonus_'.$k" :label="'Nilai bonus '.str_replace('_', ' ', $k).' (Rp / kg) *'" type="number" min="0" step="0.01" inputmode="decimal"
                             :value="$d['nilai_bonus_'.$k] ?? null" hint="Nilai per kg sesuai kontrak" />
                </div>
                <p :class="aktif ? 'hidden' : ''" class="text-xs text-ink-muted">Nonaktif — tidak dipakai di kontrak ini</p>
            </div>
        @endforeach

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line-subtle pt-4">
            <x-button variant="secondary" :href="route('siklus.baru.langkah', [$kandang, 2])">Kembali</x-button>
            <x-button>Lanjut: Standar harian <x-icon name="arrow-right" class="size-4" /></x-button>
        </div>
    </form>
</x-wizard-siklus>
