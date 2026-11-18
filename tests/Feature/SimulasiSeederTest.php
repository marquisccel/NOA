<?php

namespace Tests\Feature;

use App\Models\Kandang;
use App\Models\RecordingHarian;
use App\Models\Siklus;
use App\Models\User;
use App\Services\PerformaPeriode;
use App\Services\Rekonsiliasi;
use Database\Seeders\SimulasiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Data simulasi: harus lengkap mencakup semua status, konsisten, dan aman dijalankan ulang. */
class SimulasiSeederTest extends TestCase
{
    use RefreshDatabase;

    private User $peternak;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SimulasiSeeder::class);
        $this->peternak = User::where('email', 'simulasi@noa.test')->firstOrFail();
    }

    public function test_simulasi_memuat_empat_kandang_dan_semua_status_siklus(): void
    {
        $this->assertSame(4, Kandang::where('user_id', $this->peternak->id)->count());
        $status = Siklus::whereIn('kandang_id', Kandang::where('user_id', $this->peternak->id)->pluck('id'))->pluck('status')->unique()->sort()->values()->all();
        $this->assertEquals(collect(Siklus::STATUSES)->reject(fn ($s) => $s === Siklus::STATUS_PANEN_SELESAI)->sort()->values()->all(), $status);
        $this->assertSame(0, Siklus::where('kandang_id', Kandang::where('nama_farm', 'like', 'Kandang D%')->value('id'))->count());
    }

    public function test_satu_siklus_terbuka_per_kandang_dan_abk_ditugaskan_ke_siklus_berjalan(): void
    {
        foreach (Kandang::where('user_id', $this->peternak->id)->get() as $k) {
            $this->assertLessThanOrEqual(1, $k->siklus()->whereIn('status', Siklus::TERBUKA)->count());
        }
        $abk = User::where('email', 'abk.simulasi@noa.test')->firstOrFail();
        $this->assertSame(Siklus::STATUS_BERJALAN, $abk->siklusDitugaskan->status);
        $this->assertSame($this->peternak->id, $abk->peternak_id);
    }

    public function test_angka_performa_realistis(): void
    {
        $h = app(PerformaPeriode::class)->hitung($this->peternak, 'semua');

        $this->assertSame(12, $h['jumlah_siklus']);
        $this->assertGreaterThan(1.5, $h['fcr']);
        $this->assertLessThan(1.8, $h['fcr']);
        $this->assertGreaterThan(250, $h['ip']);
        $this->assertLessThan(400, $h['ip']);
        $this->assertGreaterThan(2, $h['deplesi_persen']);
        $this->assertLessThan(7, $h['deplesi_persen']);
    }

    public function test_siklus_berjalan_punya_hari_terlewat_dan_rhpp_banyak_selisih_ada(): void
    {
        $berjalan = Siklus::where('status', Siklus::STATUS_BERJALAN)->whereIn('kandang_id', Kandang::where('user_id', $this->peternak->id)->pluck('id'))->firstOrFail();
        $hariAda = $berjalan->recordings()->pluck('hari_ke')->all();
        $this->assertNotContains(12, $hariAda);
        $this->assertNotContains($berjalan->umurHari(), $hariAda);
        $this->assertContains(11, $hariAda);

        $beda = Siklus::where('status', Siklus::STATUS_SELESAI)->get()->first(fn (Siklus $s) => app(Rekonsiliasi::class)->hitung($s, $s->rhpp()->first())['jumlah_berbeda'] >= 5);
        $this->assertNotNull($beda, 'Harus ada RHPP dengan banyak baris berbeda');
        $this->assertNotEmpty($beda->rhpp->catatan);
    }

    public function test_data_konsisten_satu_recording_per_hari_dan_panen_tidak_melebihi_populasi(): void
    {
        $this->assertSame(0, RecordingHarian::selectRaw('siklus_id, hari_ke')->groupBy('siklus_id', 'hari_ke')->havingRaw('count(*) > 1')->get()->count());
        foreach (Siklus::has('panen')->get() as $s) {
            $this->assertLessThanOrEqual($s->populasi, (int) $s->panen()->sum('jumlah_ekor') + (int) $s->recordings()->sum('deplesi_harian'));
        }
    }

    public function test_menjalankan_ulang_menggantikan_data_simulasi_tanpa_menyentuh_akun_lain(): void
    {
        $lain = User::factory()->create(['email' => 'lain@noa.test']);
        $siklusSebelum = Siklus::count();

        $this->seed(SimulasiSeeder::class);

        $this->assertSame(1, User::where('email', 'simulasi@noa.test')->count());
        $this->assertSame(1, User::where('email', 'abk.simulasi@noa.test')->count());
        $this->assertSame(4, Kandang::where('user_id', User::where('email', 'simulasi@noa.test')->value('id'))->count());
        $this->assertSame($siklusSebelum, Siklus::count());
        $this->assertNotNull($lain->fresh());
    }

    public function test_peternak_simulasi_dapat_membuka_semua_layar_utama(): void
    {
        $s = Siklus::where('status', Siklus::STATUS_BERJALAN)->whereIn('kandang_id', Kandang::where('user_id', $this->peternak->id)->pluck('id'))->firstOrFail();
        $rekon = Siklus::where('status', Siklus::STATUS_REKONSILIASI)->firstOrFail();

        foreach ([route('dashboard'), route('performa.index', ['periode' => 'semua']), route('aktivitas.index'), route('kandang.index'), route('siklus.show', $s), route('recording.index', $s),
            route('sampling.index', $s), route('ovk.index', $s), route('panen.index', $rekon), route('rhpp.show', $rekon), route('laporan.siklus', $rekon), route('rhpp.cetak', $rekon)] as $url) {
            $this->actingAs($this->peternak)->get($url)->assertOk();
        }
    }
}
