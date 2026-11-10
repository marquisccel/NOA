@php
    use App\Support\Format as F;

    $selisih = $p['selisih_populasi'];
    $umurRumus = $panen->map(fn ($t) => F::angka($t->jumlah_ekor, 0).'×'.$t->umur_hari)->implode(' + ');
@endphp

<x-app-layout title="Ringkasan panen" :siklus="$siklus"
    :crumbs="[['Kandang & Siklus', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['Panen', route('panen.index', $siklus)], ['Ringkasan']]">
    <x-page>
        <x-page-header title="Ringkasan panen" subtitle="FCR dan IP baru dihitung setelah ada catatan panen (R10)">
            <x-slot name="actions"><x-button variant="secondary" :href="route('panen.index', $siklus)">Lihat tahap</x-button></x-slot>
        </x-page-header>

        @if ($p['jumlah_tahap'] === 0)
            <x-empty-state icon="truck" title="Belum ada catatan panen" text="FCR, ABW panen, umur panen, dan IP belum bisa dihitung.">
                @if (! $siklus->isTerkunci())<x-button :href="route('panen.create', $siklus)">Tambah tahap panen</x-button>@endif
            </x-empty-state>
        @else
            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <x-kpi label="Total ekor panen" icon="truck" :value="F::angka($p['total_ekor'], 0).' ekor'" :caption="$p['jumlah_tahap'].' tahap'" />
                <x-kpi label="Total tonase" icon="scale" :value="F::angka($p['total_tonase'], 0).' kg'" :caption="'Jumlah BTPA '.($p['jumlah_tahap'] - $p['tahap_tanpa_btpa'])" />
                <x-kpi label="Umur panen" icon="timer" :value="F::angka($p['umur_panen']).' hari'" caption="Rata-rata berbobot jumlah ekor" />
                <x-kpi label="ABW panen" icon="chart-column" :value="F::angka($p['abw'], 3).' kg'" caption="Tonase ÷ ekor" />
                <x-kpi label="Deplesi" :value="F::angka($p['deplesi_persen']).'%'" :caption="F::angka($p['deplesi'], 0).' ekor · daya hidup '.F::angka($p['liveability']).'%'" />
                <x-kpi label="FCR" :value="F::angka($p['fcr'], 3)" :caption="'Pakan '.F::angka($p['pakan_bersih_kg'], 0).' kg ÷ '.F::angka($p['total_tonase'], 0).' kg'" />
                <x-kpi label="IP" :value="F::angka($p['ip'], 0)" caption="Rumus kemitraan" />
                <x-kpi label="Selisih populasi" :value="F::angka(abs($selisih), 0).' ekor'" :caption="$selisih === 0 ? 'Seimbang' : 'Belum tercatat — lihat rincian'">
                    @if ($selisih !== 0)<a href="{{ route('panen.selisih', $siklus) }}" class="inline-flex min-h-11 items-center text-[13px] font-semibold text-ink-brand hover:underline">Lihat rincian</a>@endif
                </x-kpi>
            </div>

            <section class="rounded-[16px] border border-line bg-surface p-5" aria-labelledby="cara-hitung">
                <h2 id="cara-hitung" class="text-base font-bold">Cara hitung</h2>
                <ul class="mt-3 space-y-2.5 text-[13px] leading-[19px] text-ink-secondary">
                    <li><strong class="text-ink">Umur panen</strong> = Σ(ekor × umur) ÷ Σ ekor = ({{ $umurRumus }}) ÷ {{ F::angka($p['total_ekor'], 0) }} = {{ F::angka($p['umur_panen']) }} hari</li>
                    <li><strong class="text-ink">ABW panen</strong> = total tonase ÷ total ekor = {{ F::angka($p['total_tonase'], 0) }} ÷ {{ F::angka($p['total_ekor'], 0) }} = {{ F::angka($p['abw'], 3) }} kg</li>
                    <li><strong class="text-ink">FCR</strong> = total pakan terpakai (kg) ÷ total tonase panen (kg)</li>
                    <li><strong class="text-ink">IP</strong> = (daya hidup % × ABW kg) ÷ (FCR × umur panen) × 100 — rumus mengikuti konfigurasi kemitraan</li>
                </ul>
            </section>
        @endif
    </x-page>
</x-app-layout>
