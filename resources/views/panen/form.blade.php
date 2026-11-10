@php
    use App\Support\Format as F;

    $ada = $panen->exists;
    $nilai = fn ($kolom) => old($kolom, $panen->{$kolom});
    $tanggalAwal = old('tanggal', $panen->tanggal?->toDateString() ?? now()->toDateString());
    $judul = $ada ? 'Ubah tahap panen' : 'Tambah tahap panen';
@endphp

<x-app-layout title="Panen & BTPA" :siklus="$siklus"
    :crumbs="[['Kandang & Siklus', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['Panen', route('panen.index', $siklus)], [$judul]]">
    <x-page>
        <x-page-header :title="$judul" :subtitle="'Tahap '.$nomorTahap" />

        <form method="POST" action="{{ $ada ? route('panen.update', $panen) : route('panen.store', $siklus) }}" enctype="multipart/form-data"
              class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)]"
              x-data="{
                  tanggal: @js($tanggalAwal),
                  masuk: @js($siklus->tanggal_masuk->toDateString()),
                  ekor: @js($nilai('jumlah_ekor')),
                  tonase: @js($nilai('tonase_kg')),
                  populasi: @js($siklus->populasi),
                  deplesi: @js($deplesi),
                  lainEkor: @js($lainEkor),
                  lainTonase: @js($lainTonase),
                  num(v) { const n = parseFloat(String(v ?? '').replace(',', '.')); return isNaN(n) ? 0 : n },
                  fmt(n, d = 0) { return n.toLocaleString('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d }) },
                  get umur() { const a = new Date(this.tanggal), b = new Date(this.masuk); return isNaN(a) ? null : Math.round((a - b) / 86400000) + 1 },
                  get abw() { return this.num(this.ekor) > 0 ? this.num(this.tonase) / this.num(this.ekor) : null },
                  get totalEkor() { return this.lainEkor + this.num(this.ekor) },
                  get totalTonase() { return this.lainTonase + this.num(this.tonase) },
                  get sisa() { return this.populasi - this.deplesi - this.totalEkor },
              }">
            @csrf
            @if ($ada) @method('PUT') @endif

            <div class="space-y-5 rounded-[16px] border border-line bg-surface p-5 sm:p-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="tanggal" value="Tanggal panen *" />
                        <input id="tanggal" type="date" name="tanggal" x-model="tanggal" value="{{ $tanggalAwal }}" required
                               min="{{ $siklus->tanggal_masuk->toDateString() }}" max="{{ now()->toDateString() }}"
                               class="mt-2 block min-h-[50px] w-full rounded-md border-line-control bg-surface px-3.5 text-base focus:border-brand focus:ring-1 focus:ring-brand">
                        <x-input-error class="mt-1.5" :messages="$errors->get('tanggal')" />
                    </div>
                    <div>
                        <x-input-label for="umur_tampil" value="Umur panen" />
                        <div class="mt-2 flex min-h-[50px] items-center rounded-md border border-line bg-page px-3.5 text-lg font-semibold">
                            <span x-text="umur ?? '–'"></span><span class="ml-2 text-base font-normal text-ink-secondary">hari</span>
                        </div>
                        <p class="mt-1.5 text-xs text-ink-muted">Dihitung dari DOC masuk</p>
                    </div>
                </div>

                <x-input-satuan name="jumlah_ekor" label="Jumlah ekor *" satuan="ekor" step="1" min="1" inputmode="numeric" :value="$panen->jumlah_ekor" x-model="ekor" required autofocus
                    :hint="'Sisa ayam hidup tercatat: '.F::angka($sisaTercatat, 0).' ekor'" />
                <x-input-satuan name="tonase_kg" label="Tonase *" satuan="kg" step="0.01" min="0" inputmode="decimal" :value="$panen->tonase_kg" x-model="tonase" required hint="Sesuai timbangan BTPA" />
                <div>
                    <x-input-label for="nomor_btpa" value="Nomor BTPA *" />
                    <x-text-input id="nomor_btpa" name="nomor_btpa" :value="old('nomor_btpa', $panen->nomor_btpa)" placeholder="Contoh: SU-1030-091" required class="mt-2 !min-h-[50px] text-lg" :invalid="$errors->has('nomor_btpa')" />
                    <p class="mt-1.5 text-xs text-ink-muted">Berita acara timbang panen dari petugas inti.</p>
                    <x-input-error class="mt-1.5" :messages="$errors->get('nomor_btpa')" />
                </div>

                <div>
                    <x-input-label for="foto_btpa" value="Foto BTPA (opsional)" />
                    <input id="foto_btpa" name="foto_btpa" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full rounded-md border border-dashed border-line-control bg-page p-3 text-base">
                    <p class="mt-1.5 text-xs text-ink-muted">Ambil foto atau pilih file · JPG/PNG maks. 5 MB. Disimpan privat sebagai bukti.</p>
                    <x-input-error class="mt-1.5" :messages="$errors->get('foto_btpa')" />
                    @if ($ada && $panen->foto_btpa)
                        <p class="mt-2 text-sm"><a href="{{ route('panen.foto', $panen) }}" target="_blank" rel="noopener" class="font-semibold text-ink-brand underline">Lihat foto saat ini</a></p>
                        <label class="mt-1 inline-flex min-h-11 items-center gap-3 text-sm">
                            <input type="checkbox" name="hapus_foto" value="1" class="size-5 rounded border-line-control text-brand focus:ring-brand"> Hapus foto ini
                        </label>
                    @endif
                </div>

                @if ($errors->has('tonase_kg') || $errors->has('jumlah_ekor'))<x-alert variant="error">Periksa jumlah ekor dan tonase.</x-alert>@endif

                <div class="flex flex-wrap items-center gap-2.5 pt-1">
                    <x-button>Simpan tahap</x-button>
                    <x-button variant="secondary" :href="route('panen.index', $siklus)">Batal</x-button>
                </div>
            </div>

            <aside class="space-y-3 rounded-[16px] border border-line bg-surface p-5" aria-live="polite">
                <p class="text-xs font-bold uppercase tracking-wide text-ink-muted">Pratinjau</p>
                <dl class="space-y-3 text-sm">
                    <div class="rounded-md border border-line-subtle p-3.5">
                        <dt class="text-xs text-ink-secondary">ABW tahap ini</dt>
                        <dd class="font-heading text-2xl font-semibold"><span x-text="abw === null ? '–' : fmt(abw, 3) + ' kg'">–</span></dd>
                        <dd class="text-xs text-ink-muted"><span x-text="fmt(num(tonase)) + ' kg ÷ ' + fmt(num(ekor)) + ' ekor'"></span></dd>
                    </div>
                    <div class="rounded-md border border-line-subtle p-3.5">
                        <dt class="text-xs text-ink-secondary">Total setelah disimpan</dt>
                        <dd class="font-heading text-xl font-semibold"><span x-text="fmt(totalEkor) + ' ekor'"></span></dd>
                        <dd class="text-xs text-ink-muted"><span x-text="fmt(totalTonase) + ' kg'"></span></dd>
                    </div>
                    <div class="rounded-md border border-line-subtle p-3.5">
                        <dt class="text-xs text-ink-secondary">Sisa ayam hidup tercatat</dt>
                        <dd class="font-heading text-xl font-semibold" :class="sisa !== 0 ? 'text-warning-text' : ''"><span x-text="fmt(sisa) + ' ekor'"></span></dd>
                        <dd class="text-xs text-ink-muted"><span x-text="sisa === 0 ? 'Seimbang dengan populasi awal' : (sisa > 0 ? 'Akan muncul sebagai selisih populasi' : 'Melebihi populasi awal')"></span></dd>
                    </div>
                </dl>
                <p class="text-xs text-ink-muted">Petunjuk saja; angka yang disimpan dihitung ulang oleh server.</p>
            </aside>
        </form>

        @if ($ada)
            <form method="POST" action="{{ route('panen.destroy', $panen) }}" onsubmit="return confirm('Hapus tahap panen ini? Riwayat tetap tersimpan.')">
                @csrf @method('DELETE')
                <button class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-danger-text hover:underline"><x-icon name="trash-2" class="size-4" /> Hapus tahap</button>
            </form>
        @endif
    </x-page>
</x-app-layout>
