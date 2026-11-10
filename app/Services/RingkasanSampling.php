<?php

namespace App\Services;

use App\Models\SamplingBobot;
use App\Models\Siklus;
use App\Models\StandarHarian;
use Illuminate\Support\Collection;

/**
 * ABW, pertambahan bobot harian (ADG), kelayakan sampel, dan selisih terhadap standar (F3.2-F3.5).
 * Semua turunan dihitung ulang dari data sampling dan recording; tidak ada yang disimpan.
 */
class RingkasanSampling
{
    public function __construct(private PerformanceCalculator $calc) {}

    /** Ayam hidup pada akhir hari umur tertentu: populasi awal dikurangi deplesi sampai hari itu. */
    public function ayamHidup(Siklus $siklus, int $hariKe): int
    {
        return $siklus->populasi - (int) $siklus->recordings()->where('hari_ke', '<=', $hariKe)->sum('deplesi_harian');
    }

    /**
     * @param  Collection<int, SamplingBobot>  $samplings
     * @param  Collection<int, StandarHarian>  $standar
     * @return list<array<string, mixed>> berurutan menurut hari umur
     */
    public function baris(Siklus $siklus, Collection $samplings, Collection $standar): array
    {
        $deplesi = $siklus->recordings()->pluck('deplesi_harian', 'hari_ke');
        $standarPerHari = $standar->keyBy('hari_ke');

        $hasil = [];
        $sebelumnya = null;

        foreach ($samplings->sortBy([['hari_ke', 'asc'], ['id', 'asc']]) as $s) {
            $hidup = $siklus->populasi - (int) $deplesi->filter(fn ($d, $hari) => $hari <= $s->hari_ke)->sum();
            $abwKg = $this->calc->abwSampling((float) $s->total_bobot_kg, $s->jumlah_sampel);
            $abwGram = $abwKg === null ? null : $abwKg * 1000;
            $std = $standarPerHari->get($s->hari_ke);
            $stdGram = $std ? (float) $std->bobot_std_gram : null;

            $hasil[] = [
                'sampling' => $s,
                'hari_ke' => $s->hari_ke,
                'ayam_hidup' => $hidup,
                'persen_populasi' => $this->calc->samplingPersen($s->jumlah_sampel, $hidup),
                'representatif' => $this->calc->samplingRepresentatif($s->jumlah_sampel, $hidup),
                'abw_gram' => $abwGram,
                'std_gram' => $stdGram,
                'selisih_std_gram' => $abwGram !== null && $stdGram !== null ? $abwGram - $stdGram : null,
                'selisih_std_persen' => $abwGram !== null && $stdGram ? ($abwGram - $stdGram) / $stdGram * 100 : null,
                'adg_gram' => $sebelumnya && $abwKg !== null
                    ? $this->calc->adgGram($abwKg, $sebelumnya['abw_kg'], $s->hari_ke - $sebelumnya['hari_ke'])
                    : null,
            ];

            $sebelumnya = ['abw_kg' => $abwKg, 'hari_ke' => $s->hari_ke];
        }

        return $hasil;
    }
}
