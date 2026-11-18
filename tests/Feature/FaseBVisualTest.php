<?php

namespace Tests\Feature;

use App\Models\Siklus;
use App\Models\User;
use App\Services\LaporanSiklus;
use Database\Seeders\SimulasiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Fase B: grafik garis, sparkline, perbandingan dua siklus, dan tema gelap. */
class FaseBVisualTest extends TestCase
{
    use RefreshDatabase;

    private User $peternak;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SimulasiSeeder::class);
        $this->peternak = User::where('email', 'simulasi@noa.test')->firstOrFail();
    }

    private function siklusSelesai(int $urut = 0): Siklus
    {
        return Siklus::where('status', Siklus::STATUS_SELESAI)->whereHas('kandang', fn ($k) => $k->where('user_id', $this->peternak->id))->orderBy('tanggal_masuk')->get()[$urut];
    }

    public function test_kurva_harian_ada_di_ringkasan_siklus_dengan_data_standar(): void
    {
        $s = $this->siklusSelesai();

        $this->actingAs($this->peternak)->get(route('siklus.show', $s))->assertOk()
            ->assertSee('Kurva harian')->assertSee('id="grafik-bobot"', false)->assertSee('id="grafik-deplesi"', false)->assertSee('id="data-grafik"', false)->assertSee('Garis putus = target');

        $g = app(LaporanSiklus::class)->grafik($s);
        $this->assertArrayHasKey('deplesi_standar', $g);
        $this->assertCount(count($g['labels']), $g['deplesi_standar']);
        $this->assertNotNull($g['deplesi_standar'][0]);
    }

    public function test_performa_memakai_grafik_garis_tren_dan_sparkline(): void
    {
        $kandang = $this->siklusSelesai()->kandang_id;

        $r = $this->actingAs($this->peternak)->get(route('performa.index', ['periode' => 'semua', 'kandang' => $kandang]))->assertOk()
            ->assertSee('Tren antar-siklus')->assertSee('id="grafik-ip"', false)->assertSee('id="grafik-fcr"', false)->assertSee('id="data-tren"', false)
            ->assertSee('Bandingkan dua siklus')->assertSee('<polyline', false)->assertSee('Tren FCR: ', false);
        $this->assertGreaterThanOrEqual(3, substr_count($r->getContent(), '<polyline'));
        $r->assertViewHas('tren', fn ($t) => count($t['labels']) >= 2 && count($t['ip']) === count($t['labels']));
    }

    public function test_tren_butuh_dua_siklus_bila_kurang_tampil_penjelasan(): void
    {
        $kosong = User::factory()->create();

        $this->actingAs($kosong)->get(route('performa.index', ['periode' => 'semua']))->assertOk()->assertSee('Belum ada siklus yang dipanen')->assertDontSee('id="data-tren"', false);
    }

    public function test_bandingkan_dua_siklus_menandai_yang_lebih_baik(): void
    {
        $a = $this->siklusSelesai(0);   // kandang A siklus 1 (FCR tinggi)
        $b = Siklus::where('status', Siklus::STATUS_SELESAI)->where('kandang_id', $a->kandang_id)->orderBy('tanggal_masuk')->get()[2]; // siklus terbaik

        $r = $this->actingAs($this->peternak)->get(route('performa.bandingkan', ['a' => $a->id, 'b' => $b->id]))->assertOk()
            ->assertSee('Bandingkan dua siklus')->assertSee('Konversi pakan')->assertSee('Yang lebih baik')->assertSee('Siklus kedua')->assertSee('unggul di')
            ->assertSee('Hijau menandai nilai yang lebih baik');
        $this->assertStringContainsString('text-success-text', $r->getContent());
    }

    public function test_bandingkan_kasus_tepi(): void
    {
        $a = $this->siklusSelesai(0);

        $this->actingAs($this->peternak)->get(route('performa.bandingkan'))->assertOk()->assertSee('Pilih dua siklus lalu tekan');
        $this->actingAs($this->peternak)->get(route('performa.bandingkan', ['a' => $a->id, 'b' => $a->id]))->assertOk()->assertSee('Pilih dua siklus yang berbeda');
        $this->actingAs($this->peternak)->get(route('performa.bandingkan', ['a' => $a->id, 'b' => 999999]))->assertOk()->assertSee('Pilih dua siklus lalu tekan');
    }

    public function test_bandingkan_tidak_memuat_siklus_peternak_lain_dan_abk_ditolak(): void
    {
        $lain = User::factory()->create();
        $abk = User::factory()->abk($this->peternak)->create();
        $a = $this->siklusSelesai(0);

        $this->actingAs($lain)->get(route('performa.bandingkan', ['a' => $a->id, 'b' => $a->id]))->assertOk()->assertSee('Belum cukup siklus')->assertDontSee('Kandang A');
        $this->actingAs($abk)->get(route('performa.bandingkan'))->assertForbidden();
    }

    public function test_tema_gelap_tombol_skrip_awal_dan_token_lengkap(): void
    {
        $html = $this->actingAs($this->peternak)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString("localStorage.getItem('noa.tema')", $html);
        $this->assertStringContainsString('x-data="tema()"', $html);
        $this->assertStringContainsString('Ganti ke tema gelap', $html);
        auth()->logout();
        $this->get(route('login'))->assertSee("localStorage.getItem('noa.tema')", false);

        $css = file_get_contents(resource_path('css/app.css'));
        $gelap = substr($css, strpos($css, "[data-theme='dark']"), 3000);
        foreach (['--status-success-text', '--status-warning-text', '--status-danger-text', '--status-info-text', '--status-neutral-soft', '--state-hover', '--border-control', 'color-scheme: dark'] as $kunci) {
            $this->assertStringContainsString($kunci, $gelap, "Token gelap hilang: {$kunci}");
        }
    }

    public function test_halaman_ringan_grafik_hanya_dimuat_di_halaman_yang_butuh(): void
    {
        $s = $this->siklusSelesai();

        $this->actingAs($this->peternak)->get(route('recording.index', $s))->assertDontSee('data-grafik', false)->assertDontSee('data-tren', false);
        $this->actingAs($this->peternak)->get(route('dashboard'))->assertDontSee('laporan-', false);
    }
}
