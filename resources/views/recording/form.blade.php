@php
    use App\Support\Format as F;

    $edit = $recording->exists;
    $bacaSaja = $bacaSaja ?? false;
    $nilai = fn ($kolom, $bawaan = null) => old($kolom, $recording->{$kolom} ?? $bawaan);
    $adaOvk = fn ($kolom) => (bool) old($kolom, $recording->{$kolom});
    $lainnyaTerisi = $edit && ($recording->pakan_masuk_sak > 0 || $recording->pakan_sisa_sak !== null || $recording->bobot_aktual_gram !== null
        || $recording->suhu_pagi !== null || $recording->suhu_sore !== null || $recording->ovk_pagi || $recording->ovk_sore);
    $bukaLainnya = $lainnyaTerisi || $errors->hasAny(['pakan_masuk_sak', 'pakan_sisa_sak', 'bobot_aktual_gram', 'suhu_pagi', 'suhu_sore', 'ovk_pagi', 'ovk_sore', 'sebab_afkir']);
    $peternak = auth()->user()->isPeternak();
    $hariIniTanggal = $hariKe === $hariIni;
    $penanda = $edit ? 'Ubah recording' : ($hariIniTanggal ? 'Hari ini' : 'Entri mundur');
    $judul = $edit ? 'Ubah recording' : ($hariIniTanggal ? 'Recording harian' : 'Entri mundur');
    $kelasPilih = 'mt-2 block min-h-[50px] w-full rounded-md border-line-control bg-surface px-3.5 text-base focus:border-brand focus:ring-1 focus:ring-brand';
@endphp

<x-app-layout :title="$judul" :siklus="$siklus"
    :crumbs="[['Kandang & Siklus', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['Recording', route('recording.index', $siklus)], [$judul]]">
    <x-page lebar="max-w-xl">
        {{-- Pilih tanggal (entri mundur); formulir terpisah agar tidak bersarang dengan formulir simpan. --}}
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-[16px] border border-line bg-surface px-4 py-3.5">
            <div>
                <p class="font-heading text-lg font-semibold leading-snug">Hari ke-{{ $hariKe }} · {{ $tanggal->translatedFormat('D, j M Y') }}</p>
                <p class="text-[13px] text-ink-secondary">{{ $edit ? 'Ubah recording' : ($hariIniTanggal ? 'Hari ini · ubah tanggal untuk entri mundur' : 'Tanggal lampau · entri mundur') }}</p>
            </div>
            @unless ($edit)
                <form method="GET" action="{{ route('recording.create', $siklus) }}" class="flex items-center gap-2" x-data>
                    <label for="tanggal_ganti" class="sr-only">Tanggal recording</label>
                    <input id="tanggal_ganti" type="date" name="tanggal" value="{{ $tanggal->toDateString() }}"
                           min="{{ $siklus->tanggal_masuk->toDateString() }}" max="{{ now()->toDateString() }}"
                           @change="$el.form.submit()" class="min-h-11 rounded-md border-line-control bg-surface px-3 text-base focus:border-brand focus:ring-1 focus:ring-brand">
                    <noscript><x-button variant="secondary">Pilih</x-button></noscript>
                </form>
            @endunless
        </div>

        @unless ($edit)
            @if (! $hariIniTanggal)
                <x-alert variant="info">
                    <p class="font-semibold">Mengisi entri yang terlewat</p>
                    <p>Angka berjalan dihitung ulang untuk hari ke-{{ $hariKe }} dan sesudahnya.</p>
                </x-alert>
            @endif
        @endunless

        @if ($bacaSaja)
            <div class="space-y-4 rounded-[16px] border border-line bg-surface p-5">
                <x-alert variant="info">Siklus sudah ditutup, jadi isi baris ini terkunci. Penandaan paraf PPL tetap bisa dilakukan.</x-alert>
                <dl class="grid gap-3 text-sm sm:grid-cols-3">
                    <div><dt class="text-ink-muted">Pakan terpakai</dt><dd class="mt-0.5 font-semibold">{{ F::angka($recording->pakan_pakai_sak) }} sak</dd></div>
                    <div><dt class="text-ink-muted">Ayam mati</dt><dd class="mt-0.5 font-semibold">{{ $recording->mati }} ekor</dd></div>
                    <div><dt class="text-ink-muted">Ayam afkir</dt><dd class="mt-0.5 font-semibold">{{ $recording->afkir }} ekor</dd></div>
                </dl>
            </div>
        @else
            <form method="POST" action="{{ $edit ? route('recording.update', $recording) : route('recording.store', $siklus) }}"
                  class="space-y-5 rounded-[16px] border border-line bg-surface p-5 sm:p-6"
                  x-data="{
                      masuk: @js($nilai('pakan_masuk_sak', 0)),
                      pakai: @js($nilai('pakan_pakai_sak')),
                      mati: @js($nilai('mati', 0)),
                      afkir: @js($nilai('afkir', 0)),
                      populasi: @js($siklus->populasi),
                      sebelum: @js($sebelum),
                      num(v) { const n = parseFloat(String(v ?? '').replace(',', '.')); return isNaN(n) ? 0 : n },
                      fmt(n, d = 2) { return n.toLocaleString('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d }) },
                      get stok() { return this.sebelum.masuk + this.num(this.masuk) - this.sebelum.pakai - this.num(this.pakai) },
                      get deplesi() { return (this.sebelum.deplesi + this.num(this.mati) + this.num(this.afkir)) / this.populasi * 100 },
                  }">
                @csrf
                @if ($edit) @method('PUT') @endif
                @if (! $edit)<input type="hidden" name="tanggal" value="{{ $tanggal->toDateString() }}">@endif
                @if ($errors->has('tanggal'))
                    <x-alert variant="error">
                        <p class="font-semibold">Hari ke-{{ $hariKe }} sudah punya recording</p>
                        <p>{{ $errors->first('tanggal') }}</p>
                        @php $ada = $siklus->recordings()->where('hari_ke', $hariKe)->first(); @endphp
                        <div class="mt-2 flex flex-wrap gap-4 font-semibold">
                            @if ($ada)<a href="{{ route('recording.edit', $ada) }}" class="inline-flex min-h-11 items-center underline">Ubah entri yang ada</a>@endif
                            <a href="{{ route('recording.create', $siklus) }}" class="inline-flex min-h-11 items-center underline">Pilih tanggal lain</a>
                        </div>
                    </x-alert>
                @endif

                <x-input-satuan name="pakan_pakai_sak" label="Pakan terpakai *" satuan="sak" step="0.01" min="0" inputmode="decimal"
                    :value="$recording->pakan_pakai_sak" x-model="pakai" required autofocus
                    :catatan="$stdHariSak !== null ? 'Standar '.F::angka($stdHariSak, 1).' sak' : null" />
                <x-input-satuan name="mati" label="Ayam mati *" satuan="ekor" step="1" min="0" inputmode="numeric" :value="$recording->mati ?? 0" x-model="mati" required />
                <x-input-satuan name="afkir" label="Ayam afkir *" satuan="ekor" step="1" min="0" inputmode="numeric" :value="$recording->afkir ?? 0" x-model="afkir" required />

                <div x-cloak :class="num(afkir) > 0 ? '' : 'hidden'">
                    <x-input-label for="sebab_afkir" value="Sebab afkir *" />
                    <select id="sebab_afkir" name="sebab_afkir" :required="num(afkir) > 0" class="{{ $kelasPilih }}">
                        <option value="">Pilih sebab</option>
                        @foreach (\App\Models\RecordingHarian::SEBAB_AFKIR as $sebab)
                            <option value="{{ $sebab }}" @selected($nilai('sebab_afkir') === $sebab)>{{ ucfirst($sebab) }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-1.5" :messages="$errors->get('sebab_afkir')" />
                </div>
                <x-input-error :messages="$errors->get('mati')" />

                {{-- Umpan balik instan; angka yang disimpan selalu dihitung ulang oleh server. --}}
                <div class="rounded-md border border-brand-border bg-tint p-4 text-[13px]" aria-live="polite">
                    <p>Deplesi kumulatif setelah entri ini: <strong x-text="fmt(deplesi) + '%'"></strong></p>
                    <p>Stok pakan setelah entri ini: <strong x-text="fmt(stok) + ' sak'" :class="stok < 0 ? 'text-danger-text' : ''"></strong></p>
                </div>

                <div x-cloak :class="stok < 0 ? '' : 'hidden'">
                    <x-alert variant="warning">
                        <p class="font-semibold">Stok pakan akan menjadi <span x-text="fmt(stok)"></span> sak</p>
                        <p>Biasanya ada pengiriman pakan yang belum dicatat. Anda tetap bisa menyimpan; catat pakan masuk di detail opsional.</p>
                    </x-alert>
                </div>

                <details @if ($bukaLainnya) open @endif class="group rounded-md border border-line-subtle">
                    <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 px-4 py-2.5">
                        <span>
                            <span class="block text-sm font-bold">Detail opsional</span>
                            <span class="block text-xs text-ink-secondary">Pakan masuk, sisa pakan, sebab afkir, bobot, suhu, OVK</span>
                        </span>
                        <x-icon name="chevron-down" class="size-5 text-ink-muted transition group-open:rotate-180" />
                    </summary>
                    <div class="space-y-5 border-t border-line-subtle p-4">
                        @unless ($peternak)
                            <p class="text-xs text-ink-secondary">Detail opsional untuk ABK: pakan masuk, sisa pakan, sebab afkir, bobot, suhu, penanda OVK — tanpa biaya.</p>
                        @endunless
                        <x-input-satuan name="pakan_masuk_sak" label="Pakan masuk" satuan="sak" step="0.01" min="0" inputmode="decimal" :value="$recording->pakan_masuk_sak" x-model="masuk"
                            hint="Isi pada hari pengiriman pakan dari inti." />
                        <x-input-satuan name="pakan_sisa_sak" label="Sisa pakan (hitungan fisik)" satuan="sak" step="0.01" min="0" inputmode="decimal" :value="$recording->pakan_sisa_sak"
                            hint="Hasil hitung stok saat ini ada di kotak di atas." />
                        <x-input-satuan name="bobot_aktual_gram" label="Bobot sampel cepat" satuan="g" step="0.01" min="0" inputmode="decimal" :value="$recording->bobot_aktual_gram"
                            hint="Bobot rata-rata per ekor; diperlukan untuk FCR berjalan." />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-input-satuan name="suhu_pagi" label="Suhu pagi" satuan="°C" step="0.1" min="0" inputmode="decimal" :value="$recording->suhu_pagi" />
                            <x-input-satuan name="suhu_sore" label="Suhu sore" satuan="°C" step="0.1" min="0" inputmode="decimal" :value="$recording->suhu_sore" />
                        </div>
                        @if ($target)
                            <p class="-mt-2 text-xs text-ink-muted">
                                Target umur ini:
                                @if ($target->suhu_min !== null && $target->suhu_maks !== null){{ F::angka($target->suhu_min, 1) }}–{{ F::angka($target->suhu_maks, 1) }} °C @endif
                                @if ($target->frekuensi_pakan)· pakan {{ $target->frekuensi_pakan }}x/hari @endif
                            </p>
                        @endif

                        <fieldset>
                            <legend class="text-sm font-semibold">OVK diberikan (penanda)</legend>
                            <div class="mt-2 flex flex-wrap gap-2.5">
                                @foreach (['ovk_pagi' => 'Pagi', 'ovk_sore' => 'Sore'] as $kolom => $teks)
                                    <label class="inline-flex min-h-11 cursor-pointer items-center gap-2.5 rounded-md border border-line-control px-4 text-sm font-semibold has-[:checked]:border-brand has-[:checked]:bg-brand-soft has-[:checked]:text-ink-brand">
                                        <input type="checkbox" name="{{ $kolom }}" value="{{ old($kolom, $recording->{$kolom}) ?: 'Diberikan' }}" @checked($adaOvk($kolom)) class="size-5 rounded border-line-control text-brand focus:ring-brand">
                                        {{ $teks }}
                                    </label>
                                @endforeach
                            </div>
                            @if ($peternak)
                                <p class="mt-2 text-xs text-ink-muted">Detail produk dan biaya dicatat di <a href="{{ route('ovk.index', $siklus) }}" class="font-semibold text-ink-brand underline">tab OVK</a>.</p>
                            @endif
                        </fieldset>
                    </div>
                </details>

                <p class="text-xs text-ink-secondary">
                    Stok pakan {{ $edit ? 'sebelum entri ini' : 'saat ini' }} <strong class="text-ink" x-text="fmt(sebelum.masuk - sebelum.pakai, 1) + ' sak'"></strong>
                    @if ($belumDiisi && $belumDiisi !== $hariKe) · recording hari ke-{{ $belumDiisi }} belum diisi @endif
                </p>

                <div class="sticky bottom-20 z-10 -mx-1 flex flex-wrap items-center gap-2.5 bg-surface px-1 pt-1 md:static md:bottom-auto">
                    <x-button class="min-w-32 flex-1 sm:flex-none"><span x-text="stok < 0 ? 'Tetap simpan' : 'Simpan'">Simpan</span></x-button>
                    <x-button variant="secondary" :href="route('recording.index', $siklus)">Batal</x-button>
                </div>
            </form>
        @endif

        @if ($bisaParaf)
            <form method="POST" action="{{ route('recording.paraf', $recording) }}" class="space-y-4 rounded-[16px] border border-line bg-surface p-5"
                  onsubmit="return confirm('Tandai sudah diparaf PPL? Baris ini tidak bisa disunting lagi.')">
                @csrf @method('PATCH')
                <h2 class="font-heading text-base font-semibold">Tandai sudah diparaf PPL</h2>
                <p class="text-[13px] text-ink-secondary">Paraf PPL berarti petugas inti sudah menyaksikan angka ini. Setelah ditandai, baris terkunci karena kekuatan buktinya ada di situ (R11).</p>
                <x-field name="tanggal_paraf" label="Tanggal paraf" type="date" :value="now()->toDateString()"
                         min="{{ $recording->tanggal->toDateString() }}" max="{{ now()->toDateString() }}" required />
                <x-button>Tandai diparaf</x-button>
            </form>
        @endif

        @if ($edit && ! $bacaSaja && $peternak)
            <form method="POST" action="{{ route('recording.destroy', $recording) }}" onsubmit="return confirm('Hapus entri hari ini? Riwayat tetap tersimpan.')">
                @csrf @method('DELETE')
                <button class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-danger-text hover:underline"><x-icon name="trash-2" class="size-4" /> Hapus entri</button>
            </form>
        @endif
    </x-page>
</x-app-layout>
