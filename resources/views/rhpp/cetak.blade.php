@php
    use App\Support\Format as F;

    $nomor = \App\Models\Siklus::nomorMap([$siklus->kandang_id])[$siklus->id] ?? 1;
    $cetak = true;
    $bisaCatat = false;
@endphp

<x-cetak-layout :judul="'Lembar perbandingan · '.$siklus->kandang->nama_farm.' · Siklus '.$nomor" :kembali="route('rhpp.show', $siklus)">
    <header class="border-b-2 border-black pb-3">
        <p class="text-[11px] font-semibold uppercase tracking-wide">NOA · catatan peternak</p>
        <h1 class="mt-1 text-xl font-bold">Lembar Perbandingan Catatan Peternak vs RHPP</h1>
        <p class="text-xs">{{ $siklus->kandang->nama_farm }} · Siklus {{ $nomor }} · dicetak {{ now()->translatedFormat('j M Y H.i') }} WIB</p>
        <dl class="mt-3 grid grid-cols-2 gap-x-6 gap-y-1 text-[12px]">
            <div><dt class="inline font-semibold">Peternak:</dt> <dd class="inline">{{ $siklus->kandang->user->name }}</dd></div>
            <div><dt class="inline font-semibold">Inti:</dt> <dd class="inline">{{ $siklus->nama_inti ?: '–' }}</dd></div>
            <div><dt class="inline font-semibold">Kandang:</dt> <dd class="inline">{{ $siklus->kandang->nama_farm }}@if ($siklus->kandang->labelTipe()) · {{ $siklus->kandang->labelTipe() }}@endif</dd></div>
            <div><dt class="inline font-semibold">PPL:</dt> <dd class="inline">{{ $siklus->nama_ppl ?: '–' }}</dd></div>
            <div><dt class="inline font-semibold">DOC masuk:</dt> <dd class="inline">{{ $siklus->tanggal_masuk->translatedFormat('j M Y') }} · {{ F::angka($siklus->populasi, 0) }} ekor</dd></div>
            <div><dt class="inline font-semibold">Panen terakhir:</dt> <dd class="inline">{{ $siklus->tanggal_panen?->translatedFormat('j M Y') ?? '–' }}</dd></div>
            <div class="col-span-2"><dt class="inline font-semibold">No. RHPP:</dt> <dd class="inline">{{ $rhpp ? $rhpp->nomor_rhpp.' · '.$rhpp->tanggal_terbit->translatedFormat('j M Y') : 'belum ada' }}</dd></div>
        </dl>
    </header>

    <section class="mt-4">
        @unless ($rhpp)<p class="mb-2 text-sm">RHPP belum dimasukkan, sehingga kolom RHPP dan selisih kosong.</p>@endunless
        @include('rhpp._tabel')
        <p class="mt-3 text-[10px] leading-snug">
            Selisih = RHPP − catatan peternak. “Beda” hanya menandai angka tidak sama; NOA tidak memakai ambang selisih dan tidak menghitung dampak rupiah bonus.
            Bonus dan hasil bersih hanya tercantum di RHPP.
        </p>
    </section>

    <section class="mt-8 grid grid-cols-3 gap-8 text-[12px]">
        <div><p>Peternak</p><div class="mt-14 border-t border-black pt-1">{{ $siklus->kandang->user->name }}</div></div>
        <div><p>PPL</p><div class="mt-14 border-t border-black pt-1">{{ $siklus->nama_ppl ?: ' ' }}</div></div>
        <div><p>Perwakilan inti{{ $siklus->nama_inti ? ' ('.$siklus->nama_inti.')' : '' }}</p><div class="mt-14 border-t border-black pt-1">&nbsp;</div></div>
    </section>
    <p class="mt-6 text-center text-[10px]">Halaman 1 dari 1 · Dokumen dibuat dari catatan peternak di NOA · aman dicetak hitam-putih</p>
</x-cetak-layout>
