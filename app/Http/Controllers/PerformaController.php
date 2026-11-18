<?php

namespace App\Http\Controllers;

use App\Models\Kandang;
use App\Models\Siklus;
use App\Services\PerformaPeriode;
use App\Services\RingkasanPerforma;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Performa: hasil siklus yang sudah dipanen, dilihat per periode (bulan, tahun, atau semua), kandang, dan status.
 * Memuat biaya, jadi hanya untuk peternak (R13).
 */
class PerformaController extends Controller
{
    public function __invoke(Request $request, PerformaPeriode $periodeSvc, RingkasanPerforma $performa): View
    {
        $user = $request->user();
        abort_unless($user->isPeternak(), 403);

        $periode = $periodeSvc->normalisasi($request->query('periode'));
        $kandang = Kandang::where('user_id', $user->id)->orderBy('nama_farm')->get();
        $kandangId = $kandang->contains('id', $request->integer('kandang')) ? $request->integer('kandang') : null;
        $status = in_array($request->query('status'), Siklus::STATUSES, true) ? $request->query('status') : null;

        $daftar = $periodeSvc->siklusDipanen($user, $periode, $kandangId, $status);
        $nomor = Siklus::nomorMap($kandang->modelKeys());
        $baris = $daftar->map(fn (Siklus $s) => ['siklus' => $s, 'nomor' => $nomor[$s->id] ?? 1, 'p' => $performa->hitung($s)]);

        // Grafik per kandang: kandang terpilih, atau kandang dengan siklus berpanen terbanyak pada periode ini.
        $grafikKandang = $kandangId
            ? $kandang->firstWhere('id', $kandangId)
            : $kandang->sortByDesc(fn ($k) => $baris->filter(fn ($b) => $b['siklus']->kandang_id === $k->id)->count())->first();
        $untukGrafik = $baris->filter(fn ($b) => $grafikKandang && $b['siklus']->kandang_id === $grafikKandang->id)->sortBy(fn ($b) => $b['siklus']->tanggal_masuk)->values();

        $sebelumnya = $periodeSvc->sebelumnya($periode);

        return view('performa.index', [
            'performa' => $periodeSvc->hitung($user, $periode, $kandangId, $status),
            'performaSebelumnya' => $sebelumnya ? $periodeSvc->hitung($user, $sebelumnya, $kandangId, $status) : null,
            'pilihanPeriode' => $periodeSvc->pilihan($user),
            'kandang' => $kandang,
            'baris' => $baris,
            'filter' => ['periode' => $periode, 'kandang' => $kandangId, 'status' => $status],
            'belumPanen' => Siklus::whereIn('kandang_id', $kandang->modelKeys())->whereDoesntHave('panen')->count(),
            'grafikKandang' => $grafikKandang,
            'grafikIp' => $untukGrafik->map(fn ($b) => ['label' => 'Siklus '.$b['nomor'], 'aktual' => $b['p']['ip'], 'std' => null])->all(),
            'grafikFcr' => $untukGrafik->map(fn ($b) => ['label' => 'Siklus '.$b['nomor'], 'aktual' => $b['p']['fcr'], 'std' => null])->all(),
        ]);
    }
}
