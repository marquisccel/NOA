@php
    use App\Support\Format as F;

    $rp = fn ($n) => $n === null ? 'belum lengkap' : 'Rp '.F::angka($n, 0);
    $inti = $siklus->nama_inti ?: '–';
    $rentangPanen = $panen->isEmpty() ? '–' : ($panen->first()->tanggal->format('j').'–'.$panen->last()->tanggal->translatedFormat('j M Y').' · '.$panen->count().' tahap');
@endphp

<x-cetak-layout :judul="'Laporan Siklus '.$nomor.' · '.$siklus->kandang->nama_farm" :kembali="route('laporan.siklus', $siklus)">
    <header class="border-b-2 border-black pb-3">
        <p class="text-[11px] font-semibold uppercase tracking-wide">NOA · catatan peternak</p>
        <h1 class="mt-1 text-xl font-bold">Laporan Siklus {{ $nomor }} · {{ $siklus->kandang->nama_farm }}</h1>
        <p class="text-xs">Dicetak {{ now()->translatedFormat('j M Y') }} · status {{ $siklus->labelStatus() }}</p>
        <dl class="mt-3 grid grid-cols-2 gap-x-6 gap-y-1 text-[12px]">
            <div><dt class="inline font-semibold">Peternak:</dt> <dd class="inline">{{ $siklus->kandang->user->name }}</dd></div>
            <div><dt class="inline font-semibold">Inti / PPL:</dt> <dd class="inline">{{ $inti }} / {{ $siklus->nama_ppl ?: '–' }}</dd></div>
            <div><dt class="inline font-semibold">Kandang:</dt> <dd class="inline">{{ $siklus->kandang->nama_farm }}@if ($siklus->kandang->labelTipe()) · {{ $siklus->kandang->labelTipe() }}@endif</dd></div>
            <div><dt class="inline font-semibold">Strain:</dt> <dd class="inline">{{ $siklus->strain_doc ?: '–' }}</dd></div>
            <div><dt class="inline font-semibold">Alamat:</dt> <dd class="inline">{{ $siklus->kandang->alamat ?: '–' }}</dd></div>
            <div><dt class="inline font-semibold">DOC masuk:</dt> <dd class="inline">{{ $siklus->tanggal_masuk->translatedFormat('j M Y') }}</dd></div>
            <div><dt class="inline font-semibold">Panen:</dt> <dd class="inline">{{ $rentangPanen }}</dd></div>
            <div><dt class="inline font-semibold">Populasi awal:</dt> <dd class="inline">{{ F::angka($siklus->populasi, 0) }} ekor</dd></div>
        </dl>
    </header>

    @if ($p['tidak_sebanding'])<p class="mt-3 border border-black p-2 text-[12px] font-semibold">Tidak sebanding: siklus dihentikan dini{{ $siklus->alasan_penutupan ? ' ('.mb_strtolower($siklus->alasan_penutupan).')' : '' }}.</p>@endif

    <table class="mt-4 w-full border-collapse text-[12px]">
        <thead><tr class="border-b border-black text-left"><th class="py-1">Performa</th><th class="py-1">Nilai</th><th class="py-1">Keterangan</th></tr></thead>
        <tbody class="[&_td]:border-b [&_td]:border-gray-400 [&_td]:py-1 [&_th]:border-b [&_th]:border-gray-400 [&_th]:py-1 [&_th]:text-left [&_th]:font-semibold">
            <tr><th>Deplesi</th><td>{{ F::angka($p['deplesi_persen']) }}%</td><td>{{ F::angka($p['deplesi'], 0) }} ekor (mati {{ F::angka($p['mati'], 0) }}, afkir {{ F::angka($p['afkir'], 0) }})</td></tr>
            <tr><th>Daya hidup</th><td>{{ F::angka($p['liveability']) }}%</td><td></td></tr>
            <tr><th>FCR</th><td>{{ F::angka($p['fcr'], 3) }}</td><td>{{ $p['jumlah_tahap'] ? F::angka($p['pakan_bersih_kg'], 0).' kg ÷ '.F::angka($p['total_tonase'], 0).' kg' : '' }}</td></tr>
            <tr><th>ABW panen</th><td>{{ F::angka($p['abw'], 3) }} kg</td><td>{{ F::angka($p['total_ekor'], 0) }} ekor</td></tr>
            <tr><th>Umur panen</th><td>{{ F::angka($p['umur_panen']) }} hari</td><td>Rata-rata berbobot</td></tr>
            <tr><th>IP</th><td>{{ F::angka($p['ip'], 0) }}</td><td>Rumus kemitraan</td></tr>
            <tr><th>Selisih populasi</th><td>{{ F::angka(abs($p['selisih_populasi']), 0) }} ekor</td><td>Tidak diseimbangkan</td></tr>
        </tbody>
    </table>

    <table class="mt-4 w-full border-collapse text-[12px]">
        <thead><tr class="border-b border-black text-left"><th class="py-1">Biaya</th><th class="py-1">Nilai</th><th class="py-1">Dasar</th></tr></thead>
        <tbody class="[&_td]:border-b [&_td]:border-gray-400 [&_td]:py-1 [&_th]:border-b [&_th]:border-gray-400 [&_th]:py-1 [&_th]:text-left [&_th]:font-semibold">
            <tr><th>DOC</th><td>{{ $rp($biaya['doc']) }}</td><td>{{ $biaya['doc_dasar'] ?? '–' }}</td></tr>
            <tr><th>Pakan</th><td>{{ $rp($biaya['pakan']) }}</td><td>{{ $biaya['pakan_dasar'] ?? '–' }}</td></tr>
            <tr><th>OVK</th><td>{{ $rp($biaya['ovk']) }}</td><td>{{ $jumlahOvk }} pemberian</td></tr>
            <tr class="font-bold"><th>Total biaya</th><td>{{ $rp($biaya['total']) }}</td><td></td></tr>
            <tr><th>Biaya per kg</th><td>{{ $biaya['per_kg'] === null ? '–' : 'Rp '.F::angka($biaya['per_kg'], 0) }}</td><td>{{ $p['jumlah_tahap'] ? '÷ '.F::angka($p['total_tonase'], 0).' kg' : '' }}</td></tr>
        </tbody>
    </table>

    @if ($panen->isNotEmpty())
        <table class="mt-4 w-full border-collapse text-[12px]">
            <thead><tr class="border-b border-black text-left"><th class="py-1">Panen</th><th class="py-1">Tanggal</th><th class="py-1">Umur</th><th class="py-1 text-right">Ekor</th><th class="py-1 text-right">Tonase</th><th class="py-1">No. BTPA</th></tr></thead>
            <tbody class="[&_td]:border-b [&_td]:border-gray-400 [&_td]:py-1 [&_th]:border-b [&_th]:border-gray-400 [&_th]:py-1 [&_th]:text-left">
                @foreach ($panen as $i => $t)
                    <tr><th>{{ $i + 1 }}</th><td>{{ $t->tanggal->translatedFormat('j M Y') }}</td><td>{{ $t->umur_hari }} hr</td><td class="text-right">{{ F::angka($t->jumlah_ekor, 0) }}</td><td class="text-right">{{ F::angka($t->tonase_kg, 0) }} kg</td><td>{{ $t->nomor_btpa ?: '–' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="mt-4 text-[10px] leading-snug">Hasil bersih dan bonus tidak dihitung NOA (lihat RHPP &amp; Rekonsiliasi).</p>
    <p class="mt-6 text-center text-[10px]">Halaman 1 dari 1 · NOA · aman dicetak hitam-putih</p>
</x-cetak-layout>
