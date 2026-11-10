@php
    use App\Support\Format as F;

    $edit = $recording->exists;
    $bacaSaja = $bacaSaja ?? false;
    $nilai = fn ($kolom, $bawaan = null) => old($kolom, $recording->{$kolom} ?? $bawaan);
    $lainnyaTerisi = $edit && ($recording->pakan_masuk_sak > 0 || $recording->pakan_sisa_sak !== null || $recording->bobot_aktual_gram !== null
        || $recording->suhu_pagi !== null || $recording->suhu_sore !== null || $recording->ovk_pagi || $recording->ovk_sore);
    $bukaLainnya = $lainnyaTerisi || $errors->hasAny(['pakan_masuk_sak', 'pakan_sisa_sak', 'bobot_aktual_gram', 'suhu_pagi', 'suhu_sore', 'ovk_pagi', 'ovk_sore']);
    $kelas = 'mt-1 block w-full min-h-11 text-base border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Hari ke-{{ $hariKe }} · {{ $tanggal->translatedFormat('l, j F Y') }}
        </h2>
        <p class="mt-1 text-base text-gray-700">{{ $siklus->kandang->nama_farm }} · populasi {{ F::angka($siklus->populasi, 0) }} ekor</p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @unless ($edit)
                <form method="GET" action="{{ route('recording.create', $siklus) }}" class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-wrap items-end gap-4">
                    <div>
                        <x-input-label for="tanggal_ganti" value="Isi untuk tanggal lain" class="text-base" />
                        <input id="tanggal_ganti" type="date" name="tanggal" value="{{ $tanggal->toDateString() }}"
                               min="{{ $siklus->tanggal_masuk->toDateString() }}" max="{{ now()->toDateString() }}"
                               class="{{ $kelas }}">
                    </div>
                    <x-secondary-button type="submit" class="min-h-11">Pilih tanggal</x-secondary-button>
                </form>
            @endunless

            @if ($standar || $target)
                <div class="bg-white shadow-sm sm:rounded-lg p-4 text-base text-gray-800">
                    @if ($standar)
                        <p>
                            <strong>Standar hari ke-{{ $hariKe }}:</strong>
                            pakan kumulatif {{ F::angka($standar->pakan_std_sak) }} sak,
                            bobot {{ F::angka($standar->bobot_std_gram) }} g,
                            FCR {{ F::angka($standar->fcr_std, 3) }},
                            feed intake {{ F::angka($standar->feed_intake_std_gram) }} g/ekor.
                        </p>
                    @endif
                    @if ($target)
                        <p class="{{ $standar ? 'mt-2' : '' }}">
                            <strong>Target umur ini:</strong>
                            @if ($target->suhu_min !== null && $target->suhu_maks !== null)suhu {{ F::angka($target->suhu_min, 1) }}–{{ F::angka($target->suhu_maks, 1) }} °C, @endif
                            @if ($target->frekuensi_pakan)pakan {{ $target->frekuensi_pakan }}x/hari, @endif
                            @if ($target->jumlah_tma)TMA {{ $target->jumlah_tma }}, @endif
                            @if ($target->jumlah_tra)TRA {{ $target->jumlah_tra }}@endif
                        </p>
                    @endif
                </div>
            @endif

            @if ($bacaSaja)
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-base text-gray-900">
                    <p>Siklus sudah selesai, jadi isi baris ini terkunci. Penandaan paraf PPL tetap bisa dilakukan.</p>
                    <dl class="mt-4 grid gap-2 sm:grid-cols-3">
                        <div><dt class="text-gray-600">Pakan terpakai</dt><dd>{{ F::angka($recording->pakan_pakai_sak) }} sak</dd></div>
                        <div><dt class="text-gray-600">Mati</dt><dd>{{ $recording->mati }} ekor</dd></div>
                        <div><dt class="text-gray-600">Afkir</dt><dd>{{ $recording->afkir }} ekor</dd></div>
                    </dl>
                </div>
            @else
            <form method="POST" action="{{ $edit ? route('recording.update', $recording) : route('recording.store', $siklus) }}"
                  class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6"
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
                <x-input-error :messages="$errors->get('tanggal')" />

                <div class="grid gap-6 sm:grid-cols-3">
                    <x-field name="pakan_pakai_sak" label="Pakan terpakai (sak)" type="number" step="0.01" min="0" inputmode="decimal"
                             :value="$recording->pakan_pakai_sak" x-model="pakai" required autofocus />
                    <x-field name="mati" label="Ayam mati (ekor)" type="number" step="1" min="0" inputmode="numeric"
                             :value="$recording->mati ?? 0" x-model="mati" required />
                    <x-field name="afkir" label="Ayam afkir / culling (ekor)" type="number" step="1" min="0" inputmode="numeric"
                             :value="$recording->afkir ?? 0" x-model="afkir" required />
                </div>

                <div :hidden="!(num(afkir) > 0)" @if ((int) $nilai('afkir', 0) <= 0) hidden @endif>
                    <x-input-label for="sebab_afkir" value="Sebab afkir" class="text-base" />
                    <select id="sebab_afkir" name="sebab_afkir" :required="num(afkir) > 0" class="{{ $kelas }}">
                        <option value="">Pilih sebab</option>
                        @foreach (\App\Models\RecordingHarian::SEBAB_AFKIR as $sebab)
                            <option value="{{ $sebab }}" @selected($nilai('sebab_afkir') === $sebab)>{{ ucfirst($sebab) }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('sebab_afkir')" />
                </div>
                <x-input-error :messages="$errors->get('mati')" />

                {{-- Umpan balik instan; angka yang disimpan selalu dihitung ulang oleh server. --}}
                <div class="rounded-md bg-indigo-50 border border-indigo-200 p-4 text-base text-indigo-950" aria-live="polite">
                    <p>Deplesi kumulatif setelah entri ini: <strong x-text="fmt(deplesi) + '%'"></strong></p>
                    <p>Stok gudang setelah entri ini: <strong x-text="fmt(stok) + ' sak'" :class="stok < 0 ? 'text-red-700' : ''"></strong></p>
                    <p :hidden="!(stok < 0)" hidden class="mt-1 text-red-800">Pakan terpakai melebihi stok. Mungkin ada pengiriman yang belum dicatat; entri tetap bisa disimpan.</p>
                </div>

                <details @if ($bukaLainnya) open @endif class="rounded-md border border-gray-200 p-4">
                    <summary class="cursor-pointer min-h-11 inline-flex items-center text-base font-medium text-gray-900">Isian lain (opsional)</summary>
                    <div class="mt-4 grid gap-6 sm:grid-cols-2">
                        <x-field name="pakan_masuk_sak" label="Pakan masuk dari inti (sak)" type="number" step="0.01" min="0" inputmode="decimal"
                                 :value="$recording->pakan_masuk_sak" x-model="masuk" hint="Isi pada hari pengiriman pakan; dasar tagihan sapronak." />
                        <x-field name="pakan_sisa_sak" label="Sisa pakan di gudang, hitungan fisik (sak)" type="number" step="0.01" min="0" inputmode="decimal" :value="$recording->pakan_sisa_sak" />
                        <x-field name="bobot_aktual_gram" label="Bobot aktual (g/ekor)" type="number" step="0.01" min="0" inputmode="decimal" :value="$recording->bobot_aktual_gram" hint="Diperlukan untuk FCR berjalan." />
                        <div></div>
                        <x-field name="suhu_pagi" label="Suhu pagi (°C)" type="number" step="0.1" min="0" inputmode="decimal" :value="$recording->suhu_pagi" />
                        <x-field name="suhu_sore" label="Suhu sore (°C)" type="number" step="0.1" min="0" inputmode="decimal" :value="$recording->suhu_sore" />
                        <x-field name="ovk_pagi" label="OVK pagi" :value="$recording->ovk_pagi" hint="Obat, vaksin, atau kimia yang diberikan pagi." />
                        <x-field name="ovk_sore" label="OVK sore" :value="$recording->ovk_sore" />
                    </div>
                </details>

                <div class="flex items-center gap-4">
                    <x-primary-button class="min-h-11 text-sm">Simpan</x-primary-button>
                    <a href="{{ route('recording.index', $siklus) }}" class="text-base text-gray-700 underline">Batal</a>
                </div>
            </form>
            @endif

            @if ($bisaParaf)
                <form method="POST" action="{{ route('recording.paraf', $recording) }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4"
                      onsubmit="return confirm('Tandai sudah diparaf PPL? Baris ini tidak bisa disunting lagi.')">
                    @csrf @method('PATCH')
                    <h3 class="text-lg font-semibold text-gray-900">Tandai sudah diparaf PPL</h3>
                    <p class="text-base text-gray-700">Paraf PPL berarti petugas inti sudah menyaksikan angka ini. Setelah ditandai, baris terkunci karena kekuatan buktinya ada di situ.</p>
                    <x-field name="tanggal_paraf" label="Tanggal paraf" type="date" :value="now()->toDateString()"
                             min="{{ $recording->tanggal->toDateString() }}" max="{{ now()->toDateString() }}" required />
                    <x-primary-button class="min-h-11 text-sm">Tandai diparaf</x-primary-button>
                </form>
            @endif

            @if ($edit && ! $bacaSaja && auth()->user()->isPeternak())
                <form method="POST" action="{{ route('recording.destroy', $recording) }}" onsubmit="return confirm('Hapus entri hari ini? Riwayat tetap tersimpan.')">
                    @csrf @method('DELETE')
                    <button class="min-h-11 text-base text-red-700 underline">Hapus entri</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
