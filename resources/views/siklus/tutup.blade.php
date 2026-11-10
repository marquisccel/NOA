@php
    use App\Support\Format as F;
    use App\Models\Siklus;

    $nomor = Siklus::nomorMap([$siklus->kandang_id])[$siklus->id] ?? 1;
    $rekon = $siklus->status === Siklus::STATUS_REKONSILIASI;
    $bisaTutup = $tanpaRhpp || $rekon;
    $panenTerakhir = $siklus->tanggal_panen ?? $siklus->panen()->max('tanggal');
    $panenTerakhir = $panenTerakhir ? \Illuminate\Support\Carbon::parse($panenTerakhir)->toDateString() : null;
    $aksi = $tanpaRhpp ? route('siklus.tutup-tanpa-rhpp.simpan', $siklus) : route('siklus.tutup.simpan', $siklus);
    $judul = $tanpaRhpp ? 'Tutup tanpa RHPP' : 'Tutup siklus';
    $nama = 'tutup-siklus';
@endphp

<x-app-layout :title="$judul" :siklus="$siklus"
    :crumbs="[['Kandang & Siklus', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['Siklus '.$nomor, route('siklus.show', $siklus)], [$judul]]">
    <x-page>
        <x-page-header :title="$judul" :subtitle="$tanpaRhpp ? 'Alternatif bila RHPP tidak diterbitkan inti. Data siklus akan dikunci.' : 'Langkah terakhir setelah rekonsiliasi. Data siklus akan dikunci.'" />

        @if ($errors->has('siklus'))<x-alert variant="error">{{ $errors->first('siklus') }}</x-alert>@endif

        <form method="POST" action="{{ $aksi }}" class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)]">
            @csrf
            <div class="space-y-5 rounded-[16px] border border-line bg-surface p-5 sm:p-6">
                @if ($tanpaRhpp)
                    <h2 class="text-base font-bold">Alasan</h2>
                    <div>
                        <x-input-label for="alasan_penutupan" value="Alasan *" />
                        <select id="alasan_penutupan" name="alasan_penutupan" required class="mt-2 block min-h-[50px] w-full rounded-md border-line-control bg-surface px-3.5 text-base focus:border-brand focus:ring-1 focus:ring-brand">
                            @foreach ($alasan as $a)<option value="{{ $a }}" @selected(old('alasan_penutupan', $alasan[0]) === $a)>{{ $a }}</option>@endforeach
                        </select>
                        <x-input-error class="mt-1.5" :messages="$errors->get('alasan_penutupan')" />
                    </div>
                    <div>
                        <x-input-label for="keterangan_penutupan" value="Keterangan" />
                        <textarea id="keterangan_penutupan" name="keterangan_penutupan" rows="3" maxlength="1000" class="mt-2 block w-full rounded-md border-line-control bg-surface px-3.5 py-2.5 text-base focus:border-brand focus:ring-1 focus:ring-brand">{{ old('keterangan_penutupan') }}</textarea>
                        <x-input-error class="mt-1.5" :messages="$errors->get('keterangan_penutupan')" />
                    </div>
                @endif

                <h2 class="text-base font-bold">Data penutupan</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="tanggal_panen" label="Tanggal panen terakhir *" type="date" :value="$panenTerakhir" required />
                    <x-field name="tanggal_rechick" label="Rencana re-chick-in" type="date" />
                </div>
                <p class="text-xs text-ink-muted">Re-chick-in = tanggal DOC berikutnya masuk ke {{ $siklus->kandang->nama_farm }}. Kandang dapat membuka siklus baru setelah siklus ini ditutup.</p>

                @if ($tanpaRhpp)
                    <x-alert variant="info"><p class="font-semibold">Rekonsiliasi tercatat kosong</p><p>Kolom RHPP dan selisih tetap “–”. Status menjadi Ditutup tanpa RHPP; data dikunci.</p></x-alert>
                @else
                    <ul class="space-y-2 text-[13px]">
                        <li class="flex items-center gap-2"><x-icon name="check" class="size-4 text-success-text" /> Panen ditandai selesai{{ $panenTerakhir ? ' ('.\Illuminate\Support\Carbon::parse($panenTerakhir)->translatedFormat('j M Y').')' : '' }}</li>
                        <li class="flex items-center gap-2">
                            @if ($rhpp)<x-icon name="check" class="size-4 text-success-text" /> RHPP diinput ({{ $rhpp->tanggal_terbit->translatedFormat('j M Y') }})
                            @else<x-icon name="info" class="size-4 text-warning" /> RHPP belum diinput — <a href="{{ route('rhpp.form', $siklus) }}" class="font-semibold text-ink-brand underline">input RHPP</a>@endif
                        </li>
                        <li class="flex items-center gap-2"><x-icon name="{{ $rhpp ? 'check' : 'minus' }}" class="size-4 {{ $rhpp ? 'text-success-text' : 'text-ink-muted' }}" /> Rekonsiliasi ditinjau</li>
                        @if ($p['selisih_populasi'] !== 0)<li class="flex items-center gap-2"><x-icon name="info" class="size-4 text-info" /> Selisih populasi {{ F::angka(abs($p['selisih_populasi']), 0) }} ekor tetap tercatat</li>@endif
                    </ul>
                @endif

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line-subtle pt-4">
                    @if ($tanpaRhpp)
                        <x-button variant="secondary" :href="route('rhpp.show', $siklus)">Batal</x-button>
                    @else
                        <x-button variant="secondary" :href="route('siklus.tutup-tanpa-rhpp', $siklus)">Tutup tanpa RHPP</x-button>
                    @endif
                    <x-button type="button" :disabled="! $bisaTutup" x-data @click="$dispatch('open-modal', '{{ $nama }}')">{{ $tanpaRhpp ? 'Tutup tanpa RHPP' : 'Tutup siklus' }}</x-button>
                </div>
            </div>

            <aside class="space-y-3 rounded-[16px] border border-line bg-surface p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-ink-muted">Ringkasan akhir</p>
                <dl class="space-y-2.5 text-sm">
                    @foreach ([
                        ['Ayam dipanen', F::angka($p['total_ekor'], 0).' ekor'],
                        ['Tonase', F::angka($p['total_tonase'], 0).' kg'],
                        ['Deplesi', F::angka($p['deplesi_persen']).'%'],
                        ['FCR', F::angka($p['fcr'], 3)],
                        ['IP', F::angka($p['ip'], 0)],
                        ['Selisih populasi', F::angka(abs($p['selisih_populasi']), 0).' ekor'],
                    ] as [$k, $v])
                        <div class="flex justify-between gap-3"><dt class="text-ink-secondary">{{ $k }}</dt><dd class="font-semibold">{{ $v }}</dd></div>
                    @endforeach
                    <div class="flex justify-between gap-3 border-t border-line-subtle pt-2.5"><dt class="text-ink-secondary">RHPP</dt><dd class="font-semibold">{{ $tanpaRhpp ? 'Tidak diterbitkan' : ($rhpp ? 'Diterima '.$rhpp->tanggal_terbit->translatedFormat('j M Y') : 'Belum diinput') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-ink-secondary">Rekonsiliasi</dt><dd class="font-semibold">{{ $tanpaRhpp || ! $rhpp ? 'Kosong' : $r['jumlah_berbeda'].' baris berbeda · '.$r['berbeda_bercatatan'].' bercatatan' }}</dd></div>
                </dl>
            </aside>

            <x-modal :name="$nama">
                <h2 id="{{ $nama }}-judul" class="font-heading text-lg font-semibold">{{ $tanpaRhpp ? 'Tutup Siklus '.$nomor.' tanpa RHPP?' : 'Tutup Siklus '.$nomor.'?' }}</h2>
                <p class="text-sm leading-[21px] text-ink-secondary">
                    @if ($tanpaRhpp)
                        Siklus ditutup dengan alasan yang Anda pilih. Rekonsiliasi tercatat kosong dan semua data dikunci.
                    @else
                        Semua data siklus akan dikunci (hanya-baca): recording, sampling, OVK, panen, dan rekonsiliasi. Laporan tetap bisa dicetak dan siklus tetap terlihat di riwayat {{ $siklus->kandang->nama_farm }}. {{ $siklus->kandang->nama_farm }} bisa membuka siklus baru.
                    @endif
                </p>
                <div class="flex items-center gap-2 text-sm">
                    <x-badge variant="{{ $tanpaRhpp ? 'warning' : 'info' }}" :dot="true">{{ $tanpaRhpp ? 'Menunggu RHPP' : 'Rekonsiliasi' }}</x-badge>
                    <x-icon name="arrow-right" class="size-4 text-ink-muted" />
                    <x-badge variant="neutral" :dot="true">{{ $tanpaRhpp ? 'Ditutup tanpa RHPP' : 'Ditutup' }}</x-badge>
                </div>
                <div class="flex justify-end gap-2.5">
                    <x-button type="button" variant="secondary" @click="$dispatch('close-modal', '{{ $nama }}')">Batal</x-button>
                    <x-button>{{ $tanpaRhpp ? 'Tutup tanpa RHPP' : 'Ya, tutup & kunci' }}</x-button>
                </div>
            </x-modal>
        </form>
    </x-page>
</x-app-layout>
