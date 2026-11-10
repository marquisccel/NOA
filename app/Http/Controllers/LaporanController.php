<?php

namespace App\Http\Controllers;

use App\Models\Kandang;
use App\Models\Siklus;
use App\Services\LaporanSiklus;
use App\Services\RingkasanPerforma;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Laporan memuat biaya dan pendapatan, jadi hanya untuk peternak (R13).
 */
class LaporanController extends Controller
{
    public function __construct(
        private RingkasanPerforma $performa,
        private LaporanSiklus $laporan,
    ) {}

    /** F7.1, F7.2, F7.4: ringkasan satu siklus, grafik, dan tampilan cetak satu halaman. */
    public function siklus(Siklus $siklus): View
    {
        Gate::authorize('view', $siklus);

        $siklus->load(['kandang', 'kontrak']);
        $p = $this->performa->hitung($siklus);

        return view('laporan.siklus', [
            'siklus' => $siklus,
            'p' => $p,
            'biaya' => $this->laporan->biaya($siklus, $p),
            'grafik' => $this->laporan->grafik($siklus),
        ]);
    }

    /** F7.3: performa antar-siklus di kandang yang sama, terbaru di atas. */
    public function perbandingan(Kandang $kandang): View
    {
        Gate::authorize('view', $kandang);

        $baris = $kandang->siklus()->with('kontrak')->orderByDesc('tanggal_masuk')->get()
            ->map(function (Siklus $s) {
                $p = $this->performa->hitung($s);

                return ['siklus' => $s, 'p' => $p, 'biaya' => $this->laporan->biaya($s, $p)];
            });

        return view('laporan.perbandingan', ['kandang' => $kandang, 'baris' => $baris]);
    }
}
