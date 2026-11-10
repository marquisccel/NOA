@php use App\Support\Format as F; @endphp

<x-cetak-layout :judul="'Lembar perbandingan RHPP · '.$siklus->kandang->nama_farm" :kembali="route('rhpp.show', $siklus)">
    <header class="border-b border-gray-300 pb-3">
        <h1 class="text-xl font-semibold">Lembar perbandingan catatan peternak dengan RHPP</h1>
        <dl class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
            <div><dt class="inline text-gray-600">Kandang:</dt> <dd class="inline">{{ $siklus->kandang->nama_farm }}</dd></div>
            <div><dt class="inline text-gray-600">Inti:</dt> <dd class="inline">{{ $siklus->nama_inti ?: '-' }}</dd></div>
            <div><dt class="inline text-gray-600">Siklus masuk:</dt> <dd class="inline">{{ $siklus->tanggal_masuk->translatedFormat('j F Y') }} ({{ F::angka($siklus->populasi, 0) }} ekor)</dd></div>
            <div><dt class="inline text-gray-600">PPL:</dt> <dd class="inline">{{ $siklus->nama_ppl ?: '-' }}</dd></div>
            <div><dt class="inline text-gray-600">Tanggal panen:</dt> <dd class="inline">{{ $siklus->tanggal_panen?->translatedFormat('j F Y') ?? '-' }}</dd></div>
            <div><dt class="inline text-gray-600">RHPP terbit:</dt> <dd class="inline">{{ $rhpp?->tanggal_terbit?->translatedFormat('j F Y') ?? 'belum ada' }}</dd></div>
        </dl>
    </header>

    <section class="mt-4 text-sm">
        @if (! $rhpp)
            <p>RHPP belum dimasukkan, sehingga kolom perbandingan kosong.</p>
        @else
            <p class="mb-3">
                @if ($r['jumlah_melebihi'] === 0)
                    <strong>Tidak ada baris yang melebihi ambang selisih wajar.</strong>
                @else
                    <strong>{{ $r['jumlah_melebihi'] }} baris melebihi ambang selisih wajar</strong> (diberi latar kuning dan keterangan "Melebihi ambang").
                @endif
                @if ($r['total_dampak_rp'] !== null)
                    Dampak rupiah yang dapat dihitung dari kontrak: <strong>{{ ($r['total_dampak_rp'] < 0 ? '−' : '+').'Rp '.F::angka(abs($r['total_dampak_rp']), 0) }}</strong>
                    ({{ $r['total_dampak_rp'] < 0 ? 'merugikan peternak' : 'menguntungkan peternak' }}).
                @endif
            </p>
            <div class="[&_table]:text-sm [&_td]:py-1 [&_th]:py-1">
                @include('rhpp._tabel')
            </div>
            <p class="mt-3 text-xs text-gray-600">
                Selisih = angka RHPP dikurangi angka catatan peternak. Angka peternak dihitung dari recording harian dan catatan panen (BTPA).
                Bonus FCR, deplesi, dan IP tidak dikonversi ke rupiah karena bergantung pada tabel bonus inti.
            </p>
        @endif
    </section>

    <section class="mt-8 grid grid-cols-2 gap-8 text-sm">
        <div>
            <p class="text-gray-600">Peternak</p>
            <div class="mt-14 border-t border-gray-400 pt-1">{{ $siklus->kandang->user->name }}</div>
        </div>
        <div>
            <p class="text-gray-600">Perwakilan inti{{ $siklus->nama_inti ? ' ('.$siklus->nama_inti.')' : '' }}</p>
            <div class="mt-14 border-t border-gray-400 pt-1">&nbsp;</div>
        </div>
    </section>
</x-cetak-layout>
