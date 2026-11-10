<?php

namespace App\Services;

use App\Models\ParameterUmur;
use App\Models\RecordingHarian;
use App\Models\Siklus;
use App\Models\StandarHarian;
use Illuminate\Support\Collection;

/**
 * Angka berjalan per hari umur (F2.3, F2.6-F2.9, F2.12) dihitung ulang dari seluruh recording.
 *
 * Stok gudang tidak disimpan sebagai kolom (R5): akumulasi pakan masuk dikurangi pakan terpakai,
 * dihitung di sini setiap kali dibutuhkan, sehingga entri mundur tidak melahirkan dua sumber kebenaran.
 */
class RingkasanRecording
{
    public function __construct(private PerformanceCalculator $calc) {}

    /**
     * @param  Collection<int, RecordingHarian>  $recordings
     * @param  Collection<int, StandarHarian>  $standar
     * @param  Collection<int, ParameterUmur>  $parameter
     * @param  Collection<int, \App\Models\SamplingBobot>|null  $samplings
     * @return array<int, array<string, mixed>> baris per hari umur, berurutan, kunci = hari_ke
     */
    public function baris(Siklus $siklus, Collection $recordings, Collection $standar, Collection $parameter, ?Collection $samplings = null): array
    {
        $standarPerHari = $standar->keyBy('hari_ke');
        // Bobot dari sampling pada hari yang sama dipakai bila recording hari itu tidak mencatat bobot aktual.
        $abwSampling = ($samplings ?? collect())
            ->sortBy('id')
            ->mapWithKeys(fn ($s) => [$s->hari_ke => $this->calc->abwSampling((float) $s->total_bobot_kg, $s->jumlah_sampel) * 1000]);
        $bobotBkg = (float) $siklus->bobot_sak_kg;

        $kumMasuk = 0.0;
        $kumPakai = 0.0;
        $kumDeplesi = 0;
        $baris = [];

        foreach ($recordings->sortBy('hari_ke') as $r) {
            $kumMasuk += (float) $r->pakan_masuk_sak;
            $kumPakai += (float) $r->pakan_pakai_sak;
            $kumDeplesi += $r->mati + $r->afkir;

            $hidup = $siklus->populasi - $kumDeplesi;
            $kumKg = $this->calc->sakKeKg($kumPakai, $bobotBkg);
            $bobot = $r->bobot_aktual_gram !== null ? (float) $r->bobot_aktual_gram : $abwSampling->get($r->hari_ke);
            $std = $standarPerHari->get($r->hari_ke);
            $target = $parameter->first(fn (ParameterUmur $p) => $r->hari_ke >= $p->umur_awal && $r->hari_ke <= $p->umur_akhir);

            $baris[$r->hari_ke] = [
                'recording' => $r,
                'hari_ke' => $r->hari_ke,
                'tanggal' => $r->tanggal,
                'pakan_masuk_sak' => (float) $r->pakan_masuk_sak,
                'pakan_pakai_sak' => (float) $r->pakan_pakai_sak,
                'stok_sak' => $this->calc->stokGudangSak($kumMasuk, $kumPakai),
                'deplesi_hari' => $r->mati + $r->afkir,
                'deplesi_kum' => $kumDeplesi,
                'deplesi_kum_persen' => $this->calc->deplesiPersen($kumDeplesi, 0, $siklus->populasi),
                'ayam_hidup' => $hidup,
                'pakan_kum_sak' => $kumPakai,
                'bobot_gram' => $bobot,
                'feed_intake_gram' => $this->calc->feedIntakeKumulatifGram($kumKg, $hidup),
                // FCR berjalan memakai bobot hari itu; tanpa bobot aktual tidak bisa dihitung.
                'fcr_berjalan' => $bobot && $hidup > 0 ? $this->calc->fcr($kumKg, $hidup * $bobot / 1000) : null,
                'standar' => $std,
                'suhu_pagi' => $this->statusSuhu($r->suhu_pagi, $target),
                'suhu_sore' => $this->statusSuhu($r->suhu_sore, $target),
            ];
        }

        return $baris;
    }

    /**
     * Subtotal mingguan pakan terpakai dan deplesi (F2.12).
     *
     * @param  array<int, array<string, mixed>>  $baris
     * @return array<int, array{minggu: int, pakan_pakai_sak: float, deplesi: int}>
     */
    public function mingguan(array $baris): array
    {
        $hasil = [];

        foreach ($baris as $hariKe => $b) {
            $minggu = intdiv($hariKe - 1, 7) + 1;
            $hasil[$minggu] ??= ['minggu' => $minggu, 'pakan_pakai_sak' => 0.0, 'deplesi' => 0];
            $hasil[$minggu]['pakan_pakai_sak'] += $b['pakan_pakai_sak'];
            $hasil[$minggu]['deplesi'] += $b['deplesi_hari'];
        }

        return $hasil;
    }

    /**
     * Total sebelum hari tertentu, untuk umpan balik pada form entri.
     *
     * @return array{masuk: float, pakai: float, deplesi: int}
     */
    public function totalSebelum(Siklus $siklus, int $hariKe, ?int $kecualiId = null): array
    {
        $q = $siklus->recordings()->where('hari_ke', '<', $hariKe)
            ->when($kecualiId, fn ($q) => $q->whereKeyNot($kecualiId));

        return [
            'masuk' => (float) (clone $q)->sum('pakan_masuk_sak'),
            'pakai' => (float) (clone $q)->sum('pakan_pakai_sak'),
            'deplesi' => (int) (clone $q)->sum('deplesi_harian'),
        ];
    }

    /** Total deplesi seluruh recording selain satu baris; untuk menjaga deplesi tidak melebihi populasi. */
    public function deplesiLain(Siklus $siklus, ?int $kecualiId = null): int
    {
        return (int) $siklus->recordings()
            ->when($kecualiId, fn ($q) => $q->whereKeyNot($kecualiId))
            ->sum('deplesi_harian');
    }

    /**
     * @return array{status: string, target: string}|null null bila suhu tidak dicatat atau tidak ada target
     */
    private function statusSuhu(mixed $suhu, ?ParameterUmur $target): ?array
    {
        if ($suhu === null || $target === null || $target->suhu_min === null || $target->suhu_maks === null) {
            return null;
        }

        $suhu = (float) $suhu;
        $min = (float) $target->suhu_min;
        $maks = (float) $target->suhu_maks;

        return [
            'status' => $suhu < $min ? 'di bawah target' : ($suhu > $maks ? 'di atas target' : 'sesuai target'),
            'target' => rtrim(rtrim(number_format($min, 1, ',', ''), '0'), ',').'–'.rtrim(rtrim(number_format($maks, 1, ',', ''), '0'), ','),
        ];
    }
}
