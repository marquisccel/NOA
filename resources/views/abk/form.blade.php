@php
    $ada = $abk->exists;
    $kandangAwal = old('kandang_id', $abk->siklusDitugaskan?->kandang_id);
    $selectKelas = 'mt-2 block min-h-[46px] w-full rounded-md bg-surface text-base text-ink focus:ring-1 border-line-control focus:border-brand focus:ring-brand';
@endphp

<x-app-layout :title="$ada ? 'Ubah akun ABK' : 'Tambah akun ABK'" :crumbs="[['Pengguna & Akses', route('abk.index')], [$ada ? 'Ubah akun' : 'Tambah akun ABK']]">
    <x-page>
        <div class="space-y-3">
            <a href="{{ route('abk.index') }}" class="inline-flex min-h-11 items-center gap-1.5 text-[13px] font-semibold text-ink-brand hover:underline">
                <x-icon name="arrow-left" class="size-4" /> Kembali ke daftar ABK
            </a>
            <x-page-header :title="$ada ? 'Ubah akun · '.$abk->name : 'Tambah akun ABK'"
                :subtitle="$ada ? 'Perbarui data, penugasan siklus, atau status akun.' : 'Buat akun dan tentukan siklus yang boleh diakses.'" />
        </div>

        @if (session('sandi_baru'))
            <x-alert variant="success">
                <strong>Kata sandi baru untuk {{ $abk->name }}:</strong>
                <code class="mx-1 rounded bg-surface px-2 py-0.5 text-sm font-bold text-ink">{{ session('sandi_baru') }}</code>
                Bagikan langsung ke ABK. Kata sandi ini hanya ditampilkan sekali; ABK bisa menggantinya di Pengaturan.
            </x-alert>
        @endif

        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
            <div class="space-y-5">
                <form method="POST" action="{{ $ada ? route('abk.update', $abk) : route('abk.store') }}" novalidate
                      class="space-y-6 rounded-[16px] border border-line bg-surface p-5 sm:p-6"
                      x-data="{
                          pilihan: @js($pilihan),
                          kandang: @js($kandangAwal ? (int) $kandangAwal : ''),
                          siklus: @js(old('siklus_id', $abk->siklus_id)),
                          get opsi() { return this.pilihan.filter(p => p.id === Number(this.kandang)) },
                          pilihKandang() { this.siklus = this.opsi.length ? String(this.opsi[0].siklus_id) : '' },
                      }">
                    @csrf
                    @if ($ada) @method('PUT') @endif

                    <fieldset class="space-y-4">
                        <legend class="mb-1 text-[11px] font-bold uppercase tracking-[1.2px] text-ink-muted">Data akun</legend>
                        <x-field name="name" label="Nama ABK" :value="$abk->name" placeholder="Nama lengkap ABK" required autofocus autocomplete="off" />
                        <x-field name="email" label="Email" type="email" :value="$abk->email" placeholder="nama@email.com" required autocomplete="off" />
                        @unless ($ada)
                            <x-field name="password" label="Kata sandi awal" type="password" required autocomplete="new-password"
                                     hint="Bagikan ke ABK secara langsung. Minimal 8 karakter, memuat huruf dan angka." />
                        @endunless
                    </fieldset>

                    <fieldset class="space-y-4">
                        <legend class="mb-1 text-[11px] font-bold uppercase tracking-[1.2px] text-ink-muted">Penugasan siklus</legend>
                        @if (empty($pilihan))
                            <x-alert variant="info">Belum ada siklus berjalan untuk ditugaskan. Akun tetap bisa dibuat dan ditugaskan nanti, setelah Anda membuka siklus.</x-alert>
                        @else
                            <div>
                                <x-input-label for="kandang_id" value="Kandang" />
                                <select id="kandang_id" name="kandang_id" x-model="kandang" @change="pilihKandang()" class="{{ $selectKelas }}">
                                    <option value="">— Belum ditugaskan</option>
                                    <template x-for="p in pilihan" :key="p.id"><option :value="p.id" x-text="p.nama" :selected="p.id === Number(kandang)"></option></template>
                                </select>
                            </div>
                            <div>
                                <x-input-label for="siklus_id" value="Siklus" />
                                <select id="siklus_id" name="siklus_id" x-model="siklus" :disabled="! opsi.length" class="{{ $selectKelas }} disabled:bg-state-hover">
                                    <option value="">Pilih kandang dulu</option>
                                    <template x-for="o in opsi" :key="o.siklus_id"><option :value="o.siklus_id" x-text="o.siklus" :selected="String(o.siklus_id) === String(siklus)"></option></template>
                                </select>
                                <x-input-error class="mt-1.5" :messages="$errors->get('siklus_id')" />
                                <p class="mt-1.5 text-xs text-ink-muted">ABK hanya dapat membuka siklus ini. Penugasan dapat diubah kapan saja.</p>
                            </div>
                        @endif
                    </fieldset>

                    <div class="flex flex-wrap items-center justify-end gap-2.5 border-t border-line-subtle pt-4">
                        <x-button variant="secondary" :href="route('abk.index')">Batal</x-button>
                        <x-button>{{ $ada ? 'Simpan perubahan' : 'Buat akun ABK' }}</x-button>
                    </div>
                </form>

                @if ($ada)
                    <section class="space-y-4 rounded-[16px] border border-line bg-surface p-5 sm:p-6" aria-labelledby="judul-keamanan">
                        <h2 id="judul-keamanan" class="text-[11px] font-bold uppercase tracking-[1.2px] text-ink-muted">Kata sandi</h2>
                        <p class="text-[13px] leading-[19px] text-ink-secondary">Atur ulang kata sandi awal ABK jika lupa. Kata sandi baru dibuat otomatis dan ditampilkan sekali.</p>
                        <form method="POST" action="{{ route('abk.reset', $abk) }}">
                            @csrf
                            <x-button variant="secondary"><x-icon name="key-round" class="size-4" /> Atur ulang kata sandi</x-button>
                        </form>
                    </section>

                    <section class="space-y-3 rounded-[16px] border border-danger-border bg-surface p-5 sm:p-6" aria-labelledby="judul-status">
                        <h2 id="judul-status" class="text-[11px] font-bold uppercase tracking-[1.2px] text-ink-muted">Status akun</h2>
                        @if ($abk->aktif)
                            <p class="text-sm font-semibold">Nonaktifkan akun</p>
                            <p class="text-[13px] leading-[19px] text-ink-secondary">ABK tidak dapat masuk lagi. Riwayat recording yang sudah dicatat tetap tersimpan, dan akun bisa diaktifkan kembali.</p>
                            <x-button type="button" variant="secondary" class="!text-danger-text" x-data x-on:click="$dispatch('open-modal', 'nonaktif-{{ $abk->id }}')">
                                <x-icon name="ban" class="size-4" /> Nonaktifkan
                            </x-button>
                        @else
                            <p class="text-sm font-semibold">Akun nonaktif</p>
                            <p class="text-[13px] leading-[19px] text-ink-secondary">ABK tidak bisa masuk. Aktifkan kembali lalu tugaskan ke siklus.</p>
                            <form method="POST" action="{{ route('abk.status', $abk) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="aktif" value="1">
                                <x-button variant="secondary">Aktifkan akun</x-button>
                            </form>
                        @endif
                    </section>
                    @if ($abk->aktif)
                        @include('abk.partials.dialog-nonaktif', ['a' => $abk])
                    @endif
                @endif
            </div>

            <aside class="rounded-[16px] border border-line bg-surface p-5">
                <h2 class="text-sm font-bold">Hak akses ABK</h2>
                <p class="mt-1 text-xs text-ink-muted">Tetap untuk semua akun ABK dan tidak dapat diubah.</p>

                <p class="mt-4 text-[11px] font-bold uppercase tracking-[1.2px] text-success-text">Dapat</p>
                <ul class="mt-2 space-y-2 text-[13px]">
                    @foreach (['Melihat konteks siklus yang ditugaskan', 'Membuka Recording Harian', 'Mencatat konsumsi pakan', 'Mencatat ayam mati', 'Mencatat ayam afkir'] as $h)
                        <li class="flex gap-2"><x-icon name="circle-check" class="mt-0.5 size-4 text-success" /> {{ $h }}</li>
                    @endforeach
                </ul>

                <p class="mt-5 text-[11px] font-bold uppercase tracking-[1.2px] text-danger-text">Tidak dapat</p>
                <ul class="mt-2 space-y-2 text-[13px]">
                    @foreach (['Kontrak & harga kontrak', 'Informasi keuangan & bonus', 'RHPP & rekonsiliasi', 'Sampling bobot, OVK, panen', 'Manajemen akun pemilik'] as $h)
                        <li class="flex gap-2"><x-icon name="shield-x" class="mt-0.5 size-4 text-danger" /> {{ $h }}</li>
                    @endforeach
                </ul>
            </aside>
        </div>
    </x-page>
</x-app-layout>
