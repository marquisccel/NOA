@php
    use App\Support\Format as F;

    $ada = $sampling->exists;
    $nilai = fn ($kolom, $bawaan = null) => old($kolom, $sampling->{$kolom} ?? $bawaan);
    $tanggalAwal = old('tanggal', $sampling->tanggal?->toDateString() ?? now()->toDateString());
    $judul = $ada ? 'Ubah sampling' : 'Sampling baru';
    $peta = [
        ['Kandang & Siklus', route('kandang.index')],
        [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)],
        ['Sampling', route('sampling.index', $siklus)],
        [$judul],
    ];
    $tanpaStandar = $standarAda ? 'Tidak ada standar untuk hari ini' : 'Tabel standar belum diunggah';
@endphp

<x-app-layout title="Sampling bobot" :siklus="$siklus" :crumbs="$peta">
    <x-page>
        <x-page-header title="Sampling bobot" subtitle="Timbang minimal 5% populasi hidup agar representatif">
            <x-slot name="actions">
                <x-button variant="secondary" :href="route('sampling.index', $siklus)">Riwayat</x-button>
            </x-slot>
        </x-page-header>

        <form method="POST" action="{{ $ada ? route('sampling.update', $sampling) : route('sampling.store', $siklus) }}"
              class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)]"
              x-data="{
                  tanggal: @js($tanggalAwal),
                  masuk: @js($siklus->tanggal_masuk->toDateString()),
                  jumlah: @js($nilai('jumlah_sampel')),
                  total: @js($nilai('total_bobot_kg')),
                  hidup: @js($ayamHidup),
                  standar: @js($standarPerHari),
                  sebelumnya: @js($sebelumnya),
                  tanpaStandar: @js($tanpaStandar),
                  num(v) { const n = parseFloat(String(v ?? '').replace(',', '.')); return isNaN(n) ? 0 : n },
                  fmt(n, d = 0) { return n.toLocaleString('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d }) },
                  get hari() { const a = new Date(this.tanggal), b = new Date(this.masuk); return isNaN(a) ? null : Math.round((a - b) / 86400000) + 1 },
                  get abw() { return this.num(this.jumlah) > 0 && this.num(this.total) > 0 ? this.num(this.total) * 1000 / this.num(this.jumlah) : null },
                  get persen() { return this.hidup > 0 && this.num(this.jumlah) > 0 ? this.num(this.jumlah) / this.hidup * 100 : null },
                  get minimal() { return Math.ceil(this.hidup * 0.05) },
                  get kurang() { return this.persen !== null && this.persen < 5 },
                  get std() { return this.hari !== null ? (this.standar[this.hari] ?? null) : null },
                  get selisih() { return this.abw !== null && this.std ? (this.abw - this.std) / this.std * 100 : null },
                  get adg() {
                      if (this.abw === null || this.hari === null) return null;
                      const p = this.sebelumnya.filter(s => s.hari < this.hari).sort((a, b) => b.hari - a.hari)[0];
                      return p && p.abw !== null ? (this.abw - p.abw) / (this.hari - p.hari) : null;
                  },
              }">
            @csrf
            @if ($ada) @method('PUT') @endif

            <div class="space-y-5 rounded-[16px] border border-line bg-surface p-5 sm:p-6">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="font-heading text-lg font-semibold leading-snug"><span x-text="hari ? 'Hari ke-' + hari : 'Pilih tanggal'">Hari ini</span></p>
                        <p class="text-[13px] text-ink-secondary">Populasi hidup saat ini {{ F::angka($ayamHidup, 0) }} ekor</p>
                    </div>
                    <div>
                        <label for="tanggal" class="sr-only">Tanggal timbang</label>
                        <input id="tanggal" type="date" name="tanggal" x-model="tanggal" value="{{ $tanggalAwal }}"
                               min="{{ $siklus->tanggal_masuk->toDateString() }}" max="{{ now()->toDateString() }}" required
                               class="min-h-11 rounded-md border-line-control bg-surface px-3 text-base focus:border-brand focus:ring-1 focus:ring-brand">
                    </div>
                </div>
                <x-input-error :messages="$errors->get('tanggal')" />

                <x-input-satuan name="jumlah_sampel" label="Jumlah sampel *" satuan="ekor" step="1" min="1" inputmode="numeric" :value="$sampling->jumlah_sampel" x-model="jumlah" required autofocus />
                <p class="-mt-3 text-xs" :class="kurang ? 'font-semibold text-warning-text' : 'text-ink-muted'">
                    <span x-text="persen === null ? 'Min. ' + fmt(minimal) + ' ekor' : fmt(persen, 2) + '% dari populasi hidup' + (kurang ? ' — di bawah 5%' : '')">Min. {{ F::angka(ceil($ayamHidup * 0.05), 0) }} ekor</span>
                </p>
                <x-input-satuan name="total_bobot_kg" label="Total bobot *" satuan="kg" step="0.001" min="0" inputmode="decimal" :value="$sampling->total_bobot_kg" x-model="total" required
                    hint="Bobot seluruh sampel ditimbang" />

                <div x-cloak :class="kurang ? '' : 'hidden'">
                    <x-alert variant="warning">
                        <p class="font-semibold">Sampel kurang representatif</p>
                        <p><span x-text="fmt(num(jumlah))"></span> ekor = <span x-text="persen === null ? '' : fmt(persen, 2)"></span>% dari <span x-text="fmt(hidup)"></span> ekor hidup (minimal 5% ≈ <span x-text="fmt(minimal)"></span> ekor). Anda tetap bisa menyimpan; hasil ditandai “kurang representatif”.</p>
                    </x-alert>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 pt-1">
                    <x-button><span x-text="kurang ? 'Tetap simpan' : 'Simpan sampling'">Simpan sampling</span></x-button>
                    <x-button variant="secondary" :href="route('sampling.index', $siklus)">Batal</x-button>
                </div>
            </div>

            <aside class="space-y-3 rounded-[16px] border border-line bg-surface p-5" aria-live="polite">
                <p class="text-xs font-bold uppercase tracking-wide text-ink-muted">Hasil (dihitung otomatis)</p>
                <dl class="space-y-3 text-sm">
                    <div class="rounded-md border border-line-subtle p-3.5">
                        <dt class="text-xs text-ink-secondary">ABW</dt>
                        <dd class="font-heading text-2xl font-semibold"><span x-text="abw === null ? '–' : fmt(abw) + ' g'">–</span></dd>
                        <dd class="text-xs text-ink-muted"><span x-text="std ? 'Standar hari ke-' + hari + ': ' + fmt(std) + ' g' : tanpaStandar"></span></dd>
                    </div>
                    <div class="rounded-md border border-line-subtle p-3.5">
                        <dt class="text-xs text-ink-secondary">Selisih vs standar</dt>
                        <dd class="font-heading text-xl font-semibold" :class="selisih === null ? '' : (selisih >= 0 ? 'text-success-text' : 'text-warning-text')">
                            <span x-text="selisih === null ? '–' : (selisih >= 0 ? '+' : '−') + fmt(Math.abs(selisih), 1) + '%'">–</span>
                        </dd>
                        <dd class="text-xs text-ink-muted"><span x-text="selisih === null ? '' : (selisih >= 0 ? 'Di atas standar' : 'Di bawah standar')"></span></dd>
                    </div>
                    <div class="rounded-md border border-line-subtle p-3.5">
                        <dt class="text-xs text-ink-secondary">ADG</dt>
                        <dd class="font-heading text-xl font-semibold"><span x-text="adg === null ? '–' : fmt(adg) + ' g/hari'">–</span></dd>
                        <dd class="text-xs text-ink-muted">Dari sampling sebelumnya</dd>
                    </div>
                    <div class="rounded-md border border-line-subtle p-3.5">
                        <dt class="text-xs text-ink-secondary">Proporsi sampel</dt>
                        <dd class="font-heading text-xl font-semibold" :class="kurang ? 'text-warning-text' : ''"><span x-text="persen === null ? '–' : fmt(persen, 2) + '%'">–</span></dd>
                        <dd class="text-xs text-ink-muted"><span x-text="persen === null ? '' : (kurang ? 'Kurang representatif (< 5%)' : 'Representatif (≥ 5%)')"></span></dd>
                    </div>
                </dl>
                <p class="text-xs text-ink-muted">Petunjuk saja; angka yang disimpan dihitung ulang oleh server.</p>
            </aside>
        </form>

        @if ($ada)
            <form method="POST" action="{{ route('sampling.destroy', $sampling) }}" onsubmit="return confirm('Hapus sampling ini? Riwayat tetap tersimpan.')">
                @csrf @method('DELETE')
                <button class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-danger-text hover:underline"><x-icon name="trash-2" class="size-4" /> Hapus sampling</button>
            </form>
        @endif
    </x-page>
</x-app-layout>
