@php
    use App\Support\Format as F;
    use App\Models\Siklus;

    $menunggu = $siklus->status === Siklus::STATUS_MENUNGGU_RHPP;
    $rekon = $siklus->status === Siklus::STATUS_REKONSILIASI;
    $tanpa = $siklus->status === Siklus::STATUS_DITUTUP_TANPA_RHPP;
    $berjalan = $siklus->status === Siklus::STATUS_BERJALAN;
    $terkunci = $siklus->isTerkunci();
    $inti = $siklus->nama_inti ?: 'inti';
    $hariSejak = $siklus->tanggal_panen ? (int) $siklus->tanggal_panen->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null;
    $nomor = Siklus::nomorMap([$siklus->kandang_id])[$siklus->id] ?? 1;
@endphp

<x-app-layout title="RHPP & Rekonsiliasi" :siklus="$siklus"
    :crumbs="[['Kandang & Siklus', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['RHPP']]">
    <x-page>
        <x-page-header :title="$rhpp ? 'Rekonsiliasi RHPP' : 'RHPP & Rekonsiliasi'"
            :subtitle="$rhpp ? $rhpp->nomor_rhpp.' · terbit '.$rhpp->tanggal_terbit->translatedFormat('j M Y').' · '.$r['jumlah_berbeda'].' dari '.$r['jumlah_dibandingkan'].' baris berbeda' : 'Hanya terlihat oleh peternak'">
            <x-slot name="actions">
                @if ($rhpp)
                    @if ($bisaInput)<x-button variant="secondary" :href="route('rhpp.form', $siklus)">Ubah RHPP</x-button>@endif
                    <x-button variant="secondary" :href="route('rhpp.cetak', $siklus)" target="_blank" rel="noopener"><x-icon name="printer" class="size-4" /> Cetak lembar</x-button>
                    @if ($rekon)<x-button :href="route('siklus.tutup', $siklus)">Lanjut tutup siklus</x-button>@endif
                @elseif ($menunggu)
                    <x-button :href="route('rhpp.form', $siklus)">Input RHPP</x-button>
                @endif
            </x-slot>
        </x-page-header>

        @if ($errors->has('rhpp'))<x-alert variant="error">{{ $errors->first('rhpp') }}</x-alert>@endif

        @if ($berjalan)
            <x-empty-state icon="file-text" title="RHPP belum waktunya diinput"
                text="Siklus masih berjalan. RHPP diinput setelah panen ditandai selesai; sampai saat itu kolom RHPP kosong.">
                <x-button :href="route('panen.index', $siklus)">Ke Panen</x-button>
            </x-empty-state>
        @else
            @if ($menunggu)
                <x-alert variant="warning">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-semibold">Menunggu RHPP dari {{ $inti }}</p>
                            <p>RHPP biasanya terbit 3–7 hari setelah panen terakhir.
                                @if ($siklus->tanggal_panen)Panen terakhir {{ $siklus->tanggal_panen->translatedFormat('j M Y') }} — sudah {{ $hariSejak }} hari.@endif
                                Kolom RHPP kosong sampai Anda menginput angka dari dokumen RHPP. Bila RHPP tidak pernah terbit, siklus tetap bisa ditutup tanpa RHPP.</p>
                        </div>
                        <x-button variant="secondary" :href="route('siklus.tutup-tanpa-rhpp', $siklus)">Tutup tanpa RHPP</x-button>
                    </div>
                </x-alert>
            @elseif ($tanpa)
                <x-alert variant="info">Siklus ditutup tanpa RHPP{{ $siklus->alasan_penutupan ? ' ('.mb_strtolower($siklus->alasan_penutupan).')' : '' }}. Kolom RHPP dan selisih tetap “–”.</x-alert>
            @endif

            @if ($rhpp)
                <x-alert variant="info">Penanda “Berbeda” hanya menunjukkan angka tidak sama — tanpa ambang wajar dan tanpa dampak rupiah bonus (belum ditetapkan).</x-alert>
            @endif

            <form method="POST" action="{{ route('rhpp.catatan', $siklus) }}" x-data="{ ubah: false }">
                @csrf @method('PATCH')
                @include('rhpp._tabel', ['bisaCatat' => $rhpp && ! $terkunci])
                @if ($rhpp && ! $terkunci)
                    <div x-cloak :class="ubah ? 'flex' : 'hidden'" class="mt-4 items-center justify-end gap-2.5">
                        <x-button type="button" variant="secondary" @click="ubah = false">Batal</x-button>
                        <x-button>Simpan catatan</x-button>
                    </div>
                @endif
            </form>

            @if ($rekon && $rhpp)
                @php $tanpaCatatan = $r['jumlah_berbeda'] - $r['berbeda_bercatatan']; @endphp
                <section class="rounded-[16px] border border-line bg-surface p-5" aria-labelledby="siap-tutup">
                    <div class="flex items-center gap-2.5"><x-icon name="circle-check" class="size-5 text-success-text" /><h2 id="siap-tutup" class="text-base font-bold">Rekonsiliasi siap ditutup</h2></div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm lg:grid-cols-4">
                        <div class="rounded-md border border-line-subtle p-3.5"><dt class="text-xs text-ink-secondary">Baris dibandingkan</dt><dd class="font-heading text-xl font-semibold">{{ $r['jumlah_dibandingkan'] }}</dd></div>
                        <div class="rounded-md border border-line-subtle p-3.5"><dt class="text-xs text-ink-secondary">Sama</dt><dd class="font-heading text-xl font-semibold text-success-text">{{ $r['jumlah_sama'] }}</dd></div>
                        <div class="rounded-md border border-line-subtle p-3.5"><dt class="text-xs text-ink-secondary">Berbeda</dt><dd class="font-heading text-xl font-semibold text-warning-text">{{ $r['jumlah_berbeda'] }}</dd></div>
                        <div class="rounded-md border border-line-subtle p-3.5"><dt class="text-xs text-ink-secondary">Berbeda + catatan</dt><dd class="font-heading text-xl font-semibold">{{ $r['berbeda_bercatatan'] }} dari {{ $r['jumlah_berbeda'] }}</dd></div>
                    </dl>
                    @if ($tanpaCatatan > 0)
                        <x-alert variant="info" class="mt-4"><p class="font-semibold">{{ $tanpaCatatan }} baris berbeda belum diberi catatan</p><p>Catatan opsional, tetapi membantu saat musyawarah dengan inti.</p></x-alert>
                    @endif
                    <ul class="mt-4 space-y-1.5 text-[13px] text-ink-secondary">
                        <li class="flex items-center gap-2"><x-icon name="check" class="size-4 text-success-text" /> RHPP tersimpan ({{ $rhpp->tanggal_terbit->translatedFormat('j M Y') }})</li>
                        <li class="flex items-center gap-2"><x-icon name="check" class="size-4 text-success-text" /> Lembar perbandingan dapat dicetak</li>
                        <li class="flex items-center gap-2"><x-icon name="info" class="size-4 text-info" /> Langkah berikutnya: tutup siklus — data akan dikunci</li>
                    </ul>
                    <div class="mt-4 flex justify-end"><x-button :href="route('siklus.tutup', $siklus)">Lanjut tutup siklus</x-button></div>
                </section>
            @endif
        @endif
    </x-page>
</x-app-layout>
