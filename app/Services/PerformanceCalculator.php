<?php

namespace App\Services;

/**
 * Rumus performa broiler (PRD "Rumus performa").
 *
 * Semua nilai dikembalikan tanpa pembulatan; pembulatan hanya saat ditampilkan.
 * Metrik yang belum bisa dihitung (R10) dikembalikan sebagai null, bukan nol.
 */
class PerformanceCalculator
{
    /** Ambang sampling minimum (R8 / F3.3): 5 persen populasi hidup. */
    public const SAMPLING_MIN_PERSEN = 5.0;

    public const RUMUS_IP_STANDAR = 'standar';

    /** Rumus IP yang tersedia: nama => keterangan untuk antarmuka. */
    public const RUMUS_IP = [
        self::RUMUS_IP_STANDAR => 'Standar PRD: (daya hidup % × ABW kg) / (FCR × umur panen) × 100',
    ];

    /** R4: pakan dicatat dalam sak, dikonversi memakai bobot_sak_kg milik siklus. */
    public function sakKeKg(float $sak, float $bobotSakKg): float
    {
        return $sak * $bobotSakKg;
    }

    /** R3: deplesi = (mati + culling/afkir) / jumlah DOC x 100. */
    public function deplesiPersen(int $mati, int $afkir, int $jumlahDoc): float
    {
        return $jumlahDoc > 0 ? ($mati + $afkir) / $jumlahDoc * 100 : 0.0;
    }

    public function liveability(float $deplesiPersen): float
    {
        return 100 - $deplesiPersen;
    }

    /** R10: FCR hanya dihitung setelah ada catatan panen (tonase > 0). */
    public function fcr(float $totalPakanKg, float $totalTonaseKg): ?float
    {
        return $totalTonaseKg > 0 ? $totalPakanKg / $totalTonaseKg : null;
    }

    public function abw(float $totalTonaseKg, int $totalEkor): ?float
    {
        return $totalEkor > 0 ? $totalTonaseKg / $totalEkor : null;
    }

    /**
     * R9: umur panen = sum(ekor_i x umur_i) / sum(ekor_i).
     *
     * @param  iterable<array{jumlah_ekor: int, umur_hari: int}>  $tahap
     */
    public function umurPanenRataRata(iterable $tahap): ?float
    {
        $totalEkor = 0;
        $totalEkorUmur = 0;

        foreach ($tahap as $t) {
            $totalEkor += $t['jumlah_ekor'];
            $totalEkorUmur += $t['jumlah_ekor'] * $t['umur_hari'];
        }

        return $totalEkor > 0 ? $totalEkorUmur / $totalEkor : null;
    }

    /**
     * IP = (liveability % x ABW kg) / (FCR x umur panen hari) x 100.
     *
     * Rumus IP berbeda antar perusahaan inti, jadi dipilih lewat nama (disimpan per siklus) dan
     * tidak ditanam sebagai satu-satunya konstanta. Rumus lain ditambahkan di RUMUS_IP dan sini.
     *
     * @throws \InvalidArgumentException bila nama rumus tidak dikenal
     */
    public function indeksPerforma(float $liveabilityPersen, ?float $abwKg, ?float $fcr, ?float $umurPanen, string $rumus = self::RUMUS_IP_STANDAR): ?float
    {
        if (! array_key_exists($rumus, self::RUMUS_IP)) {
            throw new \InvalidArgumentException("Rumus IP tidak dikenal: {$rumus}");
        }

        if ($abwKg === null || $fcr === null || $umurPanen === null || $fcr * $umurPanen <= 0) {
            return null;
        }

        return match ($rumus) {
            self::RUMUS_IP_STANDAR => ($liveabilityPersen * $abwKg) / ($fcr * $umurPanen) * 100,
        };
    }

    /** F2.7: feed intake kumulatif per ekor (gram) = total pakan terpakai / ayam hidup. */
    public function feedIntakeKumulatifGram(float $totalPakanTerpakaiKg, int $ayamHidup): ?float
    {
        return $ayamHidup > 0 ? $totalPakanTerpakaiKg * 1000 / $ayamHidup : null;
    }

    /** F3.2: ABW sampling = total bobot sampel / jumlah sampel. */
    public function abwSampling(float $totalBobotKg, int $jumlahSampel): ?float
    {
        return $jumlahSampel > 0 ? $totalBobotKg / $jumlahSampel : null;
    }

    /** F3.4: pertambahan bobot harian (gram/hari) antar dua sampling. */
    public function adgGram(float $abwSekarangKg, float $abwSebelumnyaKg, int $selisihHari): ?float
    {
        return $selisihHari > 0 ? ($abwSekarangKg - $abwSebelumnyaKg) * 1000 / $selisihHari : null;
    }

    /** Sampel sebagai persen populasi hidup. */
    public function samplingPersen(int $jumlahSampel, int $ayamHidup): ?float
    {
        return $ayamHidup > 0 ? $jumlahSampel / $ayamHidup * 100 : null;
    }

    /** R8: sampel di bawah 5 persen populasi hidup ditandai kurang representatif. */
    public function samplingRepresentatif(int $jumlahSampel, int $ayamHidup): bool
    {
        return $ayamHidup > 0 && ($jumlahSampel / $ayamHidup * 100) >= self::SAMPLING_MIN_PERSEN;
    }

    /** R5: stok gudang = akumulasi pakan masuk dikurangi pakan terpakai (boleh negatif). */
    public function stokGudangSak(float $totalMasukSak, float $totalPakaiSak): float
    {
        return $totalMasukSak - $totalPakaiSak;
    }

    /** R7: ekor panen + deplesi harus sama dengan populasi awal; kembalikan selisihnya. */
    public function selisihPopulasi(int $jumlahDoc, int $totalEkorPanen, int $totalDeplesi): int
    {
        return $jumlahDoc - ($totalEkorPanen + $totalDeplesi);
    }
}
