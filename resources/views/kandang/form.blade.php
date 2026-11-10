@php
    $ada = $kandang->exists;
    $tipeTerpilih = old('tipe', $kandang->tipe);
    $judul = $ada ? 'Ubah Kandang · '.$kandang->nama_farm : 'Tambah Kandang';
    $banyakGalat = $errors->count();
    $kelasArea = 'mt-2 block w-full rounded-md bg-surface px-3.5 py-3 text-base text-ink placeholder:text-ink-muted focus:ring-1 ';
@endphp

<x-app-layout :title="$ada ? 'Ubah Kandang' : 'Tambah Kandang'"
    :crumbs="[['Kandang & Siklus', route('kandang.index')], [$ada ? 'Ubah Kandang' : 'Tambah Kandang']]">
    <x-page>
        <div class="space-y-3">
            <a href="{{ $ada ? route('kandang.show', $kandang) : route('kandang.index') }}" class="inline-flex min-h-11 items-center gap-1.5 text-[13px] font-semibold text-ink-brand hover:underline">
                <x-icon name="arrow-left" class="size-4" /> {{ $ada ? 'Kembali ke detail kandang' : 'Kembali ke daftar kandang' }}
            </a>
            <x-page-header :title="$judul" :subtitle="$ada ? 'Perbarui nama, alamat, kapasitas, atau tipe kandang.' : 'Daftarkan kandang baru. Siklus pemeliharaan dibuka terpisah.'" />
        </div>

        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
            <form method="POST" action="{{ $ada ? route('kandang.update', $kandang) : route('kandang.store') }}" novalidate
                  class="space-y-6 rounded-[16px] border border-line bg-surface p-5 sm:p-6">
                @csrf
                @if ($ada) @method('PUT') @endif

                @if ($banyakGalat)
                    <x-alert variant="error">Periksa kembali {{ $banyakGalat }} isian yang ditandai sebelum menyimpan.</x-alert>
                @endif

                <x-field name="nama_farm" label="Nama farm / kandang *" :value="$kandang->nama_farm" placeholder="Kandang 4" required autofocus
                         hint="Gunakan nama yang mudah dikenali, misalnya nama desa atau lokasi." />

                <div>
                    <x-input-label for="alamat" value="Alamat kandang *" />
                    <textarea id="alamat" name="alamat" rows="3" required placeholder="Desa, kecamatan, kabupaten"
                              aria-describedby="alamat-hint"
                              class="{{ $kelasArea }}{{ $errors->has('alamat') ? 'border-danger focus:border-danger focus:ring-danger' : 'border-line-control focus:border-brand focus:ring-brand' }}">{{ old('alamat', $kandang->alamat) }}</textarea>
                    <div id="alamat-hint">
                        @unless ($errors->has('alamat'))<p class="mt-1.5 text-xs text-ink-muted">Desa, kecamatan, dan kabupaten. Dipakai pada laporan dan lembar cetak.</p>@endunless
                        <x-input-error class="mt-1.5" :messages="$errors->get('alamat')" />
                    </div>
                </div>

                <x-field name="kapasitas" label="Kapasitas (ekor) *" type="number" min="1" step="1" inputmode="numeric" :value="$kandang->kapasitas" placeholder="4000" required
                         hint="Jumlah maksimal ayam yang dapat ditampung kandang." />

                <fieldset>
                    <legend class="block text-sm font-semibold text-ink">Tipe kandang *</legend>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        @foreach (\App\Models\Kandang::TIPE as $kunci => [$label, $ket])
                            <label class="flex min-h-11 cursor-pointer items-start gap-3 rounded-md border p-3.5 transition-colors has-[:checked]:border-brand has-[:checked]:bg-tint {{ $errors->has('tipe') ? 'border-danger' : 'border-line-control' }}">
                                <input type="radio" name="tipe" value="{{ $kunci }}" @checked($tipeTerpilih === $kunci)
                                       class="mt-0.5 size-[18px] border-line-control text-brand focus:ring-brand" @if ($errors->has('tipe')) aria-invalid="true" @endif>
                                <span>
                                    <span class="block text-sm font-semibold text-ink">{{ $label }}</span>
                                    <span class="block text-xs text-ink-secondary">{{ $ket }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error class="mt-1.5" :messages="$errors->get('tipe')" />
                </fieldset>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line-subtle pt-4">
                    <p class="text-xs text-ink-muted">* wajib diisi</p>
                    <div class="flex items-center gap-2.5">
                        <x-button variant="secondary" :href="$ada ? route('kandang.show', $kandang) : route('kandang.index')">Batal</x-button>
                        <x-button>{{ $ada ? 'Simpan perubahan' : 'Simpan kandang' }}</x-button>
                    </div>
                </div>
            </form>

            <aside class="space-y-4">
                <section class="rounded-[16px] border border-line bg-surface p-5">
                    <h2 class="text-sm font-bold text-ink">{{ $ada ? 'Tentang perubahan' : 'Setelah kandang tersimpan' }}</h2>
                    <ol class="mt-3 space-y-3 text-[13px] leading-[19px] text-ink-secondary">
                        @foreach ($ada
                            ? ['Nama, alamat, kapasitas, dan tipe dapat diubah kapan saja.', 'Riwayat siklus tetap tersimpan di halaman detail kandang.', 'Kandang tetap terhubung dengan akun pemilik Anda.']
                            : ['Kandang otomatis terhubung dengan akun Anda sebagai pemilik.', 'Siklus pemeliharaan tidak dibuat otomatis.', 'Buka siklus baru dari halaman detail kandang.'] as $i => $teks)
                            <li class="flex gap-3">
                                <span class="grid size-5 shrink-0 place-items-center rounded-full bg-tint text-[11px] font-bold text-ink-brand">{{ $i + 1 }}</span>
                                <span>{{ $teks }}</span>
                            </li>
                        @endforeach
                    </ol>
                </section>

                @if ($ada)
                    <section class="rounded-[16px] border border-line bg-surface p-5">
                        <h2 class="text-sm font-bold text-ink">Hapus kandang</h2>
                        <p class="mt-1 text-[13px] leading-[19px] text-ink-secondary">Kandang disembunyikan dari daftar; riwayatnya tetap tersimpan. Tidak bisa selama masih ada siklus berjalan.</p>
                        @error('kandang')<p class="mt-2 text-xs text-danger-text" role="alert">{{ $message }}</p>@enderror
                        <x-button type="button" variant="secondary" class="mt-3 !text-danger-text" x-data x-on:click="$dispatch('open-modal', 'hapus-kandang')">
                            <x-icon name="trash-2" class="size-4" /> Hapus kandang
                        </x-button>
                    </section>

                    <x-modal name="hapus-kandang">
                        <h2 id="hapus-kandang-judul" class="font-heading text-lg font-semibold">Hapus {{ $kandang->nama_farm }}?</h2>
                        <p class="text-sm leading-[21px] text-ink-secondary">Kandang tidak muncul lagi di daftar. Riwayat siklus tetap tersimpan.</p>
                        <form method="POST" action="{{ route('kandang.destroy', $kandang) }}" class="flex justify-end gap-2.5 pt-2">
                            @csrf @method('DELETE')
                            <x-button type="button" variant="secondary" x-on:click="$dispatch('close-modal', 'hapus-kandang')">Batal</x-button>
                            <x-button variant="danger">Hapus</x-button>
                        </form>
                    </x-modal>
                @endif
            </aside>
        </div>
    </x-page>
</x-app-layout>
