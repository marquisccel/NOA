<?php

namespace Tests\Unit;

use App\Services\PerformanceCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Angka mengikuti "Contoh perhitungan" di PRD: 3.000 DOC, mati 78, culling 18,
 * panen 3 tahap total 2.904 ekor / 5.750 kg.
 *
 * Catatan: PRD menulis total pakan 420 sak (21.000 kg) tetapi FCR 1,652 dan IP 339,2.
 * 21.000 / 5.750 = 3,652, jadi dua angka itu tidak konsisten. FCR 1,652 dan IP 339,2
 * hanya cocok dengan total pakan sekitar 9.500 kg (190 sak); tes memakai angka itu.
 */
class PerformanceCalculatorTest extends TestCase
{
    private PerformanceCalculator $calc;

    protected function setUp(): void
    {
        $this->calc = new PerformanceCalculator;
    }

    public function test_deplesi_dan_liveability(): void
    {
        $deplesi = $this->calc->deplesiPersen(78, 18, 3000);

        $this->assertEqualsWithDelta(3.20, $deplesi, 0.0001);
        $this->assertEqualsWithDelta(96.80, $this->calc->liveability($deplesi), 0.0001);
    }

    public function test_abw_panen(): void
    {
        $this->assertEqualsWithDelta(1.98, $this->calc->abw(5750, 2904), 0.001);
    }

    public function test_fcr(): void
    {
        $this->assertEqualsWithDelta(1.652, $this->calc->fcr(9500, 5750), 0.001);
    }

    public function test_fcr_null_sebelum_ada_panen(): void
    {
        $this->assertNull($this->calc->fcr(9500, 0));
    }

    public function test_umur_panen_rata_rata_berbobot(): void
    {
        $umur = $this->calc->umurPanenRataRata([
            ['jumlah_ekor' => 400, 'umur_hari' => 33],
            ['jumlah_ekor' => 1523, 'umur_hari' => 34],
            ['jumlah_ekor' => 981, 'umur_hari' => 35],
        ]);

        $this->assertEqualsWithDelta(34.2, $umur, 0.001);
    }

    public function test_umur_panen_null_tanpa_panen(): void
    {
        $this->assertNull($this->calc->umurPanenRataRata([]));
    }

    public function test_indeks_performa_sesuai_contoh_prd(): void
    {
        $abw = $this->calc->abw(5750, 2904);
        $fcr = $this->calc->fcr(9500, 5750);

        $ip = $this->calc->indeksPerforma(96.80, $abw, $fcr, 34.2);

        $this->assertEqualsWithDelta(339.2, $ip, 0.05);
    }

    public function test_indeks_performa_null_bila_fcr_belum_ada(): void
    {
        $this->assertNull($this->calc->indeksPerforma(96.8, 1.98, null, 34.2));
    }

    public function test_konversi_sak_ke_kg(): void
    {
        $this->assertEqualsWithDelta(21000, $this->calc->sakKeKg(420, 50), 0.0001);
    }

    public function test_feed_intake_kumulatif_gram(): void
    {
        // 9.500 kg untuk 2.904 ayam hidup.
        $this->assertEqualsWithDelta(3271.35, $this->calc->feedIntakeKumulatifGram(9500, 2904), 0.01);
    }

    public function test_sampling_bobot_dan_representatif(): void
    {
        $this->assertEqualsWithDelta(1.0, $this->calc->abwSampling(150, 150), 0.0001);
        $this->assertTrue($this->calc->samplingRepresentatif(150, 3000));
        $this->assertFalse($this->calc->samplingRepresentatif(149, 3000));
    }

    public function test_sampling_persen(): void
    {
        $this->assertEqualsWithDelta(5.0, $this->calc->samplingPersen(150, 3000), 0.0001);
        $this->assertNull($this->calc->samplingPersen(10, 0));
    }

    public function test_adg(): void
    {
        $this->assertEqualsWithDelta(45.0, $this->calc->adgGram(0.9, 0.45, 10), 0.0001);
    }

    public function test_stok_gudang_boleh_negatif(): void
    {
        $this->assertEqualsWithDelta(-2.0, $this->calc->stokGudangSak(100, 102), 0.0001);
    }

    public function test_selisih_populasi(): void
    {
        $this->assertSame(0, $this->calc->selisihPopulasi(3000, 2904, 96));
        $this->assertSame(5, $this->calc->selisihPopulasi(3000, 2899, 96));
    }
}
