<?php

namespace Tests\Feature;

use App\Models\Kandang;
use App\Models\RecordingHarian;
use App\Models\Siklus;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function siklus(?Kandang $kandang = null, string $status = Siklus::STATUS_BERJALAN): Siklus
    {
        $kandang ??= Kandang::create([
            'user_id' => User::factory()->create()->id,
            'nama_farm' => 'Uji',
            'kapasitas' => 4000,
        ]);

        return Siklus::create([
            'kandang_id' => $kandang->id,
            'tanggal_masuk' => '2026-09-20',
            'populasi' => 3000,
            'status' => $status,
        ]);
    }

    public function test_deplesi_harian_adalah_kolom_turunan_mati_tambah_afkir(): void
    {
        $siklus = $this->siklus();

        $rec = RecordingHarian::create([
            'siklus_id' => $siklus->id,
            'tanggal' => '2026-09-20',
            'hari_ke' => 1,
            'mati' => 7,
            'afkir' => 2,
        ]);

        $this->assertSame(9, (int) $rec->fresh()->deplesi_harian);
    }

    public function test_r1_satu_entri_per_hari_umur(): void
    {
        $siklus = $this->siklus();
        $data = ['siklus_id' => $siklus->id, 'tanggal' => '2026-09-20', 'hari_ke' => 1];

        RecordingHarian::create($data);

        $this->expectException(QueryException::class);
        RecordingHarian::create($data);
    }

    public function test_r1_entri_yang_dihapus_lunak_tidak_memblokir_entri_ulang(): void
    {
        $siklus = $this->siklus();
        $data = ['siklus_id' => $siklus->id, 'tanggal' => '2026-09-20', 'hari_ke' => 1];

        RecordingHarian::create($data)->delete();
        RecordingHarian::create($data);

        $this->assertSame(1, RecordingHarian::count());
        $this->assertSame(2, RecordingHarian::withTrashed()->count());
    }

    public function test_r2_satu_kandang_hanya_satu_siklus_berjalan(): void
    {
        $siklus = $this->siklus();

        $this->expectException(QueryException::class);
        $this->siklus($siklus->kandang);
    }

    public function test_r2_kandang_boleh_punya_siklus_selesai_lain(): void
    {
        $siklus = $this->siklus();
        $this->siklus($siklus->kandang, Siklus::STATUS_SELESAI);

        $this->assertSame(2, $siklus->kandang->siklus()->count());
    }

    public function test_status_siklus_di_luar_daftar_ditolak(): void
    {
        $this->expectException(QueryException::class);
        $this->siklus(null, 'tidak-ada');
    }

    public function test_pakan_negatif_ditolak(): void
    {
        $siklus = $this->siklus();

        $this->expectException(QueryException::class);
        RecordingHarian::create([
            'siklus_id' => $siklus->id,
            'tanggal' => '2026-09-20',
            'hari_ke' => 1,
            'pakan_pakai_sak' => -1,
        ]);
    }
}
