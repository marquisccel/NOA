@props(['siklus'])

@php
    $b = app(\App\Services\PenjelasRumus::class)->bahanSimulasi($siklus);
@endphp

@if ($b)
    {{-- Kalkulator "bagaimana kalau": hitung penghematan atau tambahan hasil bila FCR atau deplesi berbeda. Hanya perkiraan; tidak disimpan. --}}
    <section aria-labelledby="judul-simulasi" class="space-y-4 kartu p-5"
        x-data="{
            d: @js($b),
            fcr: @js($b['fcr']),
            deplesi: @js($b['deplesi']),
            rp(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID') },
            kg(n) { return Math.round(n).toLocaleString('id-ID') + ' kg' },
            get hematPakanKg() { return (this.d.fcr - this.fcr) * this.d.tonase },
            get hematRp() { return this.hematPakanKg * this.d.hargaPakanKg },
            get ayamTambah() { return (this.d.deplesi - this.deplesi) / 100 * this.d.populasi },
            get tambahRp() { return this.ayamTambah * this.d.abw * this.d.hargaAyamKg },
            get total() { return this.hematRp + this.tambahRp },
            reset() { this.fcr = this.d.fcr; this.deplesi = this.d.deplesi },
        }">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="grid size-9 place-items-center rounded-md bg-tint text-ink-brand"><x-icon name="sparkles" class="size-5" /></span>
                <div>
                    <h2 id="judul-simulasi" class="text-sm font-bold">Bagaimana kalau hasilnya lebih baik?</h2>
                    <p class="text-xs text-ink-secondary">Geser untuk melihat perkiraan keuntungan bila FCR atau deplesi periode ini berbeda.</p>
                </div>
            </div>
            <button type="button" @click="reset()" class="min-h-11 text-[13px] font-semibold text-ink-brand hover:underline">Kembalikan</button>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label for="sim-fcr" class="flex items-baseline justify-between text-sm font-semibold"><span>FCR (konversi pakan)</span><span class="font-heading text-lg" x-text="fcr.toFixed(3).replace('.', ',')"></span></label>
                <input id="sim-fcr" type="range" min="1.3" max="2.0" step="0.005" x-model.number="fcr" class="mt-2 w-full accent-[var(--brand-primary)]">
                <p class="text-xs text-ink-muted">Sekarang <span x-text="d.fcr.toFixed(3).replace('.', ',')"></span>. Makin kecil makin hemat pakan.</p>
            </div>
            <div>
                <label for="sim-depl" class="flex items-baseline justify-between text-sm font-semibold"><span>Deplesi</span><span class="font-heading text-lg" x-text="deplesi.toFixed(2).replace('.', ',') + '%'"></span></label>
                <input id="sim-depl" type="range" min="1" max="10" step="0.05" x-model.number="deplesi" class="mt-2 w-full accent-[var(--brand-primary)]">
                <p class="text-xs text-ink-muted">Sekarang <span x-text="d.deplesi.toFixed(2).replace('.', ',') + '%'"></span>. Makin kecil makin banyak ayam terjual.</p>
            </div>
        </div>

        <dl class="grid gap-3 sm:grid-cols-3" aria-live="polite">
            <div class="rounded-md border border-line-subtle p-3.5">
                <dt class="text-xs text-ink-secondary">Pakan yang dihemat</dt>
                <dd class="font-heading text-lg font-semibold" :class="hematPakanKg < 0 ? 'text-danger-text' : ''" x-text="(hematPakanKg >= 0 ? '+' : '−') + kg(Math.abs(hematPakanKg))"></dd>
                <dd class="text-xs text-ink-muted" x-text="(hematRp >= 0 ? '≈ +' : '≈ −') + rp(Math.abs(hematRp))"></dd>
            </div>
            <div class="rounded-md border border-line-subtle p-3.5">
                <dt class="text-xs text-ink-secondary">Ayam tambahan terjual</dt>
                <dd class="font-heading text-lg font-semibold" :class="ayamTambah < 0 ? 'text-danger-text' : ''" x-text="(ayamTambah >= 0 ? '+' : '−') + Math.round(Math.abs(ayamTambah)).toLocaleString('id-ID') + ' ekor'"></dd>
                <dd class="text-xs text-ink-muted" x-text="(tambahRp >= 0 ? '≈ +' : '≈ −') + rp(Math.abs(tambahRp))"></dd>
            </div>
            <div class="rounded-md border border-brand-border bg-tint p-3.5">
                <dt class="text-xs text-ink-secondary">Perkiraan selisih</dt>
                <dd class="font-heading text-lg font-semibold" :class="total < 0 ? 'text-danger-text' : 'text-ink-brand'" x-text="(total >= 0 ? '+' : '−') + rp(Math.abs(total))"></dd>
                <dd class="text-xs text-ink-muted">dari satu periode</dd>
            </div>
        </dl>
        <p class="text-xs text-ink-muted">Perkiraan kasar: pakan dihitung dengan harga di kontrak (Rp {{ \App\Support\Format::angka($b['hargaPakanKg'], 0) }}/kg), ayam tambahan dengan bobot rata-rata dan harga ayam kontrak. Bonus dan potongan dari inti tidak ikut dihitung.</p>
    </section>
@endif
