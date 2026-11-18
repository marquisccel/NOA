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

    /** Tautan lama "Bandingkan siklus" per kandang kini memakai halaman perbandingan global dengan filter kandang. */
    public function perbandingan(Kandang $kandang)
    {
        Gate::authorize('view', $kandang);

        return redirect()->route('performa.index', ['periode' => 'semua', 'kandang' => $kandang->id]);
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
