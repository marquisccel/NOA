@php
    use App\Support\Format as F;

    $ada = $ovk->exists;
    $judul = $ada ? 'Ubah pemberian OVK' : 'Pemberian OVK baru';
    $tanggal = old('tanggal', $ovk->tanggal?->toDateString() ?? now()->toDateString());
    $jenisDipilih = old('jenis', $ovk->jenis ?? 'obat');
    // "Kimia" hanya tersedia bila data lama memakainya; desain membedakan Obat, Vitamin, Vaksin.
    $pilihan = collect(['obat', 'vitamin', 'vaksin'])->when($jenisDipilih === 'kimia', fn ($c) => $c->push('kimia'));
    $hariKe = max(1, (int) $siklus->tanggal_masuk->copy()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($tanggal)->startOfDay()) + 1);
@endphp

<x-app-layout title="OVK" :siklus="$siklus"
    :crumbs="[['Kandang & Siklus', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['OVK', route('ovk.index', $siklus)], [$judul]]">
    <x-page>
        <x-page-header title="Pemberian OVK" subtitle="Obat, vitamin, dan vaksin beserta biayanya">
            <x-slot name="actions"><x-button variant="secondary" :href="route('ovk.index', $siklus)">Riwayat</x-button></x-slot>
        </x-page-header>

        <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)]">
            <form method="POST" action="{{ $ada ? route('ovk.update', $ovk) : route('ovk.store', $siklus) }}" class="space-y-5 rounded-[16px] border border-line bg-surface p-5 sm:p-6"
                  x-data="{ tanggal: @js($tanggal), masuk: @js($siklus->tanggal_masuk->toDateString()), get hari() { const a = new Date(this.tanggal), b = new Date(this.masuk); return isNaN(a) ? null : Math.round((a - b) / 86400000) + 1 } }">
                @csrf
                @if ($ada) @method('PUT') @endif

                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="font-heading text-lg font-semibold leading-snug"><span x-text="hari ? 'Hari ke-' + hari : 'Pilih tanggal'">Hari ke-{{ $hariKe }}</span></p>
                        <p class="text-[13px] text-ink-secondary">Tanggal pemberian</p>
                    </div>
                    <div>
                        <label for="tanggal" class="sr-only">Tanggal pemberian</label>
                        <input id="tanggal" type="date" name="tanggal" x-model="tanggal" value="{{ $tanggal }}" required
                               min="{{ $siklus->tanggal_masuk->toDateString() }}" max="{{ now()->toDateString() }}"
                               class="min-h-11 rounded-md border-line-control bg-surface px-3 text-base focus:border-brand focus:ring-1 focus:ring-brand">
                    </div>
                </div>
                <x-input-error :messages="$errors->get('tanggal')" />

                <fieldset>
                    <legend class="text-sm font-semibold">Jenis *</legend>
                    <div class="mt-2 grid grid-cols-3 gap-2.5">
                        @foreach ($pilihan as $jenis)
                            <label class="flex min-h-12 cursor-pointer items-center justify-center rounded-md border border-line-control px-3 text-sm font-semibold has-[:checked]:border-brand has-[:checked]:bg-brand-soft has-[:checked]:text-ink-brand has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand">
                                <input type="radio" name="jenis" value="{{ $jenis }}" @checked($jenisDipilih === $jenis) required class="sr-only">
                                {{ ucfirst($jenis) }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error class="mt-1.5" :messages="$errors->get('jenis')" />
                </fieldset>

                <div>
                    <x-input-label for="nama_produk" value="Nama produk *" />
                    <x-text-input id="nama_produk" name="nama_produk" :value="old('nama_produk', $ovk->nama_produk)" placeholder="Contoh: Elektrolit Plus" required class="mt-2 !min-h-[50px] text-lg" :invalid="$errors->has('nama_produk')" />
                    <x-input-error class="mt-1.5" :messages="$errors->get('nama_produk')" />
                </div>
                <div>
                    <x-input-label for="dosis" value="Dosis *" />
                    <x-text-input id="dosis" name="dosis" :value="old('dosis', $ovk->dosis)" placeholder="Contoh: 1 g/L air minum" required class="mt-2 !min-h-[50px] text-lg" :invalid="$errors->has('dosis')" />
                    <p class="mt-1.5 text-xs text-ink-muted">Contoh: 1 g/L air minum, 1 dosis/ekor.</p>
                    <x-input-error class="mt-1.5" :messages="$errors->get('dosis')" />
                </div>
                <x-input-satuan name="biaya" label="Biaya" awalan="Rp" step="1" min="0" inputmode="numeric" :value="$ovk->biaya !== null ? (float) $ovk->biaya : null" hint="Masuk ke biaya produksi siklus" />

                <x-alert variant="info">Centang OVK pagi/sore di Recording hanya penanda pemberian. Detail produk dan biaya dicatat di sini.</x-alert>

                <div class="flex flex-wrap items-center gap-2.5">
                    <x-button>Simpan OVK</x-button>
                    <x-button variant="secondary" :href="route('ovk.index', $siklus)">Batal</x-button>
                </div>
            </form>

            <aside class="space-y-3 rounded-[16px] border border-line bg-surface p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-ink-muted">Siklus sejauh ini</p>
                <dl class="space-y-3 text-sm">
                    <div class="rounded-md border border-line-subtle p-3.5">
                        <dt class="text-xs text-ink-secondary">Total biaya OVK</dt>
                        <dd class="font-heading text-2xl font-semibold">Rp {{ F::angka($ringkas['total'], 0) }}</dd>
                    </div>
                    <div class="rounded-md border border-line-subtle p-3.5">
                        <dt class="text-xs text-ink-secondary">Pemberian</dt>
                        <dd class="font-heading text-xl font-semibold">{{ $ringkas['jumlah'] }} kali</dd>
                    </div>
                    <div class="rounded-md border border-line-subtle p-3.5">
                        <dt class="text-xs text-ink-secondary">Vaksin</dt>
                        <dd class="font-heading text-xl font-semibold">{{ $ringkas['vaksin'] }}</dd>
                        <dd class="text-xs text-ink-muted">{{ $ringkas['vaksin_terakhir_hari'] ? 'terakhir hari ke-'.$ringkas['vaksin_terakhir_hari'] : 'belum ada' }}</dd>
                    </div>
                    <div class="rounded-md border border-line-subtle p-3.5">
                        <dt class="text-xs text-ink-secondary">Biaya per ekor awal</dt>
                        <dd class="font-heading text-xl font-semibold">{{ $ringkas['per_ekor'] === null ? '–' : 'Rp '.F::angka($ringkas['per_ekor'], 0).' / ekor' }}</dd>
                    </div>
                </dl>
            </aside>
        </div>

        @if ($ada)
            <form method="POST" action="{{ route('ovk.destroy', $ovk) }}" onsubmit="return confirm('Hapus pemberian OVK ini? Riwayat tetap tersimpan.')">
                @csrf @method('DELETE')
                <button class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-danger-text hover:underline"><x-icon name="trash-2" class="size-4" /> Hapus pemberian</button>
            </form>
        @endif
    </x-page>
</x-app-layout>
