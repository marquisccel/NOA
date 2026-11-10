<?php

namespace App\Http\Controllers;

use App\Models\Kandang;
use App\Models\Siklus;
use App\Services\LaporanSiklus;
use App\Services\RingkasanPerforma;
use App\Support\Format as F;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Laporan memuat biaya dan pendapatan, jadi hanya untuk peternak (R13).
 */
class LaporanController extends Controller
{
    public function __construct(
        private RingkasanPerforma $performa,
        private LaporanSiklus $laporan,
    ) {}

    /** Perbandingan antar-siklus lintas kandang; siklus yang belum panen tampil "–". */
    public function index(Request $request): View
    {
        abort_unless($request->user()->isPeternak(), 403);

        $kandang = Kandang::where('user_id', $request->user()->id)->orderBy('nama_farm')->get();
        $siklus = Siklus::whereIn('kandang_id', $kandang->modelKeys())->with('kandang')->orderByDesc('tanggal_masuk')->get();
        $nomor = Siklus::nomorMap($kandang->modelKeys());

        $tahun = $siklus->map(fn (Siklus $s) => $s->tanggal_masuk->year)->unique()->sortDesc()->values();
        $filter = [
            'kandang' => $request->integer('kandang') ?: null,
            'status' => in_array($request->query('status'), Siklus::STATUSES, true) ? $request->query('status') : null,
            'tahun' => $request->integer('tahun') ?: null,
        ];

        $baris = $siklus
            ->when($filter['kandang'], fn ($c) => $c->where('kandang_id', $filter['kandang']))
            ->when($filter['status'], fn ($c) => $c->where('status', $filter['status']))
            ->when($filter['tahun'], fn ($c) => $c->filter(fn (Siklus $s) => $s->tanggal_masuk->year === $filter['tahun']))
            ->map(function (Siklus $s) use ($nomor) {
                $p = $this->performa->hitung($s);

                return ['siklus' => $s, 'nomor' => $nomor[$s->id] ?? 1, 'p' => $p, 'sudahPanen' => $p['jumlah_tahap'] > 0];
            })->values();

        // Grafik per kandang: kandang terpilih, atau kandang dengan siklus berpanen terbanyak.
        $grafikKandang = $filter['kandang']
            ? $kandang->firstWhere('id', $filter['kandang'])
            : $kandang->sortByDesc(fn ($k) => $baris->filter(fn ($b) => $b['siklus']->kandang_id === $k->id && $b['sudahPanen'])->count())->first();
        $untukGrafik = $baris->filter(fn ($b) => $grafikKandang && $b['siklus']->kandang_id === $grafikKandang->id && $b['sudahPanen'])->sortBy(fn ($b) => $b['siklus']->tanggal_masuk)->values();

        return view('laporan.index', [
            'kandang' => $kandang,
            'baris' => $baris,
            'tahun' => $tahun,
            'filter' => $filter,
            'grafikKandang' => $grafikKandang,
            'grafikIp' => $untukGrafik->map(fn ($b) => ['label' => 'Siklus '.$b['nomor'], 'aktual' => $b['p']['ip'], 'std' => null])->all(),
            'grafikFcr' => $untukGrafik->map(fn ($b) => ['label' => 'Siklus '.$b['nomor'], 'aktual' => $b['p']['fcr'], 'std' => null])->all(),
        ]);
    }

    /** Tautan lama "Bandingkan siklus" per kandang kini memakai halaman perbandingan global dengan filter kandang. */
    public function perbandingan(Kandang $kandang)
    {
        Gate::authorize('view', $kandang);

        return redirect()->route('laporan.index', ['kandang' => $kandang->id]);
    }

    /** Laporan satu siklus: performa, biaya produksi, produksi, dan grafik. */
    public function siklus(Siklus $siklus): View
    {
        Gate::authorize('view', $siklus);

        return view('laporan.siklus', $this->dataSiklus($siklus) + ['grafik' => $this->laporan->grafik($siklus)]);
    }

    /** Lembar cetak A4 satu halaman (hitam-putih aman). */
    public function cetak(Siklus $siklus): View
    {
        Gate::authorize('view', $siklus);

        return view('laporan.cetak', $this->dataSiklus($siklus) + ['panen' => $siklus->panen()->orderBy('tanggal')->orderBy('id')->get()]);
    }

    /** Unduh ringkasan laporan sebagai CSV (UTF-8 dengan BOM agar terbaca di Excel). */
    public function csv(Siklus $siklus): StreamedResponse
    {
        Gate::authorize('view', $siklus);

        $d = $this->dataSiklus($siklus);
        $p = $d['p'];
        $biaya = $d['biaya'];

        $baris = [
            ['Ukuran', 'Nilai', 'Keterangan'],
            ['Kandang', $siklus->kandang->nama_farm, ''],
            ['Siklus', $d['nomor'], $siklus->labelStatus()],
            ['DOC masuk', $siklus->tanggal_masuk->toDateString(), F::angka($siklus->populasi, 0).' ekor'],
            ['Deplesi (%)', round($p['deplesi_persen'], 2), $p['deplesi'].' ekor'],
            ['Daya hidup (%)', $p['liveability'] === null ? '' : round($p['liveability'], 2), ''],
            ['FCR', $p['fcr'] === null ? '' : round($p['fcr'], 3), ''],
            ['ABW panen (kg)', $p['abw'] === null ? '' : round($p['abw'], 3), ''],
            ['Umur panen (hari)', $p['umur_panen'] === null ? '' : round($p['umur_panen'], 2), ''],
            ['IP', $p['ip'] === null ? '' : round($p['ip'], 1), ''],
            ['Ayam dipanen (ekor)', $p['total_ekor'], ''],
            ['Tonase panen (kg)', round($p['total_tonase'], 2), ''],
            ['Selisih populasi (ekor)', $p['selisih_populasi'], ''],
            ['Biaya DOC (Rp)', $biaya['doc'] ?? '', $biaya['doc_dasar'] ?? ''],
            ['Biaya pakan (Rp)', $biaya['pakan'] ?? '', $biaya['pakan_dasar'] ?? ''],
            ['Biaya OVK (Rp)', $biaya['ovk'], ''],
            ['Total biaya (Rp)', $biaya['total'], $biaya['lengkap'] ? '' : 'belum lengkap'],
            ['Biaya per kg (Rp)', $biaya['per_kg'] === null ? '' : round($biaya['per_kg']), ''],
            ['Biaya per ekor panen (Rp)', $biaya['per_ekor'] === null ? '' : round($biaya['per_ekor']), ''],
        ];

        return $this->unduhCsv($baris, 'laporan-siklus-'.$d['nomor'].'-'.str($siklus->kandang->nama_farm)->slug().'.csv');
    }

    /**
     * @param  list<list<mixed>>  $baris
     */
    public static function unduhCsv(array $baris, string $nama): StreamedResponse
    {
        return response()->streamDownload(function () use ($baris) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($baris as $b) {
                fputcsv($out, $b);
            }
            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array<string, mixed> */
    private function dataSiklus(Siklus $siklus): array
    {
        $siklus->load(['kandang', 'kontrak']);
        $p = $this->performa->hitung($siklus);

        return [
            'siklus' => $siklus,
            'nomor' => Siklus::nomorMap([$siklus->kandang_id])[$siklus->id] ?? 1,
            'p' => $p,
            'biaya' => $this->laporan->biaya($siklus, $p),
            'jumlahOvk' => $siklus->pemberianOvk()->count(),
        ];
    }
}
