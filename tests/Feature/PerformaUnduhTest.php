<?php

namespace Tests\Feature;

use App\Models\Kandang;
use App\Models\Siklus;
use App\Models\User;
use App\Services\PerformaPeriode;
use Database\Seeders\SiklusContohSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Bagian Performa di Dashboard (pilihan periode) dan unduhan CSV daftar BTPA dan rekap pembanding RHPP. */
class PerformaUnduhTest extends TestCase
{
    use RefreshDatabase;

    private Siklus $siklus;

    private User $peternak;

    private string $bulanPanen;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 08:00:00');
        $this->seed(SiklusContohSeeder::class);
        $this->siklus = Siklus::where('nama_inti', 'PT Inti Contoh')->firstOrFail();
        $this->peternak = $this->siklus->kandang->user;
        $this->bulanPanen = Carbon::parse($this->siklus->panen()->max('tanggal'))->format('Y-m');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ---- Performa per periode ----

    public function test_performa_tahun_ini_menghitung_siklus_yang_dipanen_dengan_fcr_gabungan(): void
    {
        $h = app(PerformaPeriode::class)->hitung($this->peternak, '2026');

        $this->assertSame(1, $h['jumlah_siklus']);
        $this->assertSame(2904, $h['total_ekor']);
        $this->assertEqualsWithDelta(5750.0, $h['total_tonase'], 0.01);
        $this->assertEqualsWithDelta(9500 / 5750, $h['fcr'], 0.001);
        $this->assertEqualsWithDelta(3.2, $h['deplesi_persen'], 0.001);
        $this->assertNotNull($h['ip']);
        $this->assertEqualsWithDelta((21000000 + 83850000 + 565000) / 5750, $h['biaya_per_kg'], 0.5);
    }

    public function test_periode_tanpa_panen_kosong_dan_periode_tidak_valid_jatuh_ke_tahun_ini(): void
    {
        $svc = app(PerformaPeriode::class);

        $h = $svc->hitung($this->peternak, '2025');
        $this->assertSame(0, $h['jumlah_siklus']);
        $this->assertNull($h['fcr']);
        $this->assertNull($h['ip']);
        $this->assertNull($h['deplesi_persen']);
        $this->assertSame('2026', $svc->normalisasi('abc'));
        $this->assertSame('2026', $svc->normalisasi('2026-13'));
        $this->assertSame('2026-11', $svc->normalisasi('2026-11'));
        $this->assertSame('2026-10', $svc->sebelumnya('2026-11'));
        $this->assertSame('2025', $svc->sebelumnya('2026'));
        $this->assertSame('2025-12', $svc->sebelumnya('2026-01'));
    }

    public function test_siklus_masuk_bulan_panen_terakhir_bukan_bulan_masuk(): void
    {
        $svc = app(PerformaPeriode::class);

        $this->assertSame(1, $svc->hitung($this->peternak, $this->bulanPanen)['jumlah_siklus']);
        $this->assertSame(0, $svc->hitung($this->peternak, Carbon::parse($this->bulanPanen.'-01')->subMonth()->format('Y-m'))['jumlah_siklus']);
    }

    public function test_performa_tidak_memuat_siklus_peternak_lain(): void
    {
        $lain = User::factory()->create();

        $this->assertSame(0, app(PerformaPeriode::class)->hitung($lain, '2026')['jumlah_siklus']);
    }

    public function test_dashboard_tidak_lagi_memuat_performa_dan_sidebar_punya_menu_performa(): void
    {
        $this->actingAs($this->peternak)->get(route('dashboard'))->assertOk()
            ->assertDontSee('Periode dipanen')->assertDontSee('Per bulan')->assertSee(route('performa.index'), false)->assertDontSee('>Laporan<', false);
    }

    public function test_halaman_performa_memuat_ringkasan_pilihan_periode_tabel_dan_grafik(): void
    {
        $this->actingAs($this->peternak)->get(route('performa.index'))->assertOk()
            ->assertSee('Performa')->assertSee('Ringkasan · Tahun 2026')->assertSee('Periode dipanen')->assertSee('Total panen')->assertSee('2.904 ekor')->assertSee('1,652')
            ->assertSee('Semua waktu')->assertSee('Per bulan')->assertSee('Per tahun')->assertSee('November 2026 (bulan ini)')->assertSee('Tahun 2026 (tahun ini)')
            ->assertSee('Sebelumnya: –')->assertSee('Periode yang dipanen')->assertSee('IP per periode')->assertSee('FCR per periode')->assertSee(route('laporan.siklus', $this->siklus), false)
            ->assertSeeInOrder(['<option value="2026" selected', 'Tahun 2026 (tahun ini)'], false);
    }

    public function test_performa_semua_periode_tanpa_pembanding_dan_periode_kosong_menjelaskan(): void
    {
        $this->actingAs($this->peternak)->get(route('performa.index', ['periode' => 'semua']))->assertOk()->assertSee('Ringkasan · Semua waktu')->assertSee('Tanpa pembanding');
        $this->actingAs($this->peternak)->get(route('performa.index', ['periode' => '2025']))->assertOk()
            ->assertSee('Belum ada periode yang dipanen pada Tahun 2025')->assertSee('Lihat semua waktu')->assertDontSee('Ringkasan · ');
        $this->actingAs($this->peternak)->get(route('performa.index', ['periode' => 'ngawur']))->assertOk()->assertSee('Ringkasan · Tahun 2026');
        $this->actingAs($this->peternak)->get(route('performa.index', ['periode' => $this->bulanPanen]))->assertOk()->assertSee('2.904 ekor');
    }

    public function test_performa_filter_kandang_dan_status_dan_tidak_memuat_peternak_lain(): void
    {
        $lain = Kandang::create(['user_id' => $this->peternak->id, 'nama_farm' => 'Kandang Lain', 'kapasitas' => 5000]);
        $s2 = Siklus::create(['kandang_id' => $lain->id, 'tanggal_masuk' => '2026-09-01', 'populasi' => 100, 'status' => 'dihentikan']);
        $s2->panen()->create(['tanggal' => '2026-10-05', 'umur_hari' => 35, 'jumlah_ekor' => 90, 'tonase_kg' => 150, 'nomor_btpa' => 'Z']);
        $asing = User::factory()->create();
        $k = Kandang::create(['user_id' => $asing->id, 'nama_farm' => 'Milik Orang', 'kapasitas' => 10]);
        $s3 = Siklus::create(['kandang_id' => $k->id, 'tanggal_masuk' => '2026-09-01', 'populasi' => 10, 'status' => 'selesai']);
        $s3->panen()->create(['tanggal' => '2026-10-05', 'umur_hari' => 35, 'jumlah_ekor' => 9, 'tonase_kg' => 15, 'nomor_btpa' => 'Y']);

        $this->actingAs($this->peternak)->get(route('performa.index', ['kandang' => $lain->id]))->assertViewHas('baris', fn ($b) => $b->pluck('siklus.id')->all() === [$s2->id]);
        $this->actingAs($this->peternak)->get(route('performa.index', ['status' => 'selesai']))->assertViewHas('baris', fn ($b) => $b->pluck('siklus.id')->all() === [$this->siklus->id]);
        $this->actingAs($this->peternak)->get(route('performa.index'))->assertViewHas('baris', fn ($b) => $b->count() === 2)->assertDontSee('Milik Orang');
    }

    public function test_performa_hanya_untuk_peternak(): void
    {
        $abk = User::factory()->abk($this->peternak)->create();

        $this->actingAs($abk)->get(route('performa.index'))->assertForbidden();
        auth()->logout();
        $this->get(route('performa.index'))->assertRedirect(route('login'));
    }

    public function test_pilihan_periode_memuat_dua_belas_bulan_dan_tahun_dari_siklus_tertua(): void
    {
        $p = app(PerformaPeriode::class)->pilihan($this->peternak);

        $this->assertCount(12, $p['bulan']);
        $this->assertSame('2026-11', array_key_first($p['bulan']));
        $this->assertEquals(['2026'], array_keys($p['tahun']));

        $k = Kandang::create(['user_id' => $this->peternak->id, 'nama_farm' => 'Tua', 'kapasitas' => 10]);
        Siklus::create(['kandang_id' => $k->id, 'tanggal_masuk' => '2024-02-01', 'populasi' => 10, 'status' => 'selesai']);
        $this->assertEquals(['2026', '2025', '2024'], array_keys(app(PerformaPeriode::class)->pilihan($this->peternak)['tahun']));
    }

    public function test_sidebar_hanya_punya_keluar_tanpa_nama_dan_peran(): void
    {
        $html = $this->actingAs($this->peternak)->get(route('dashboard'))->getContent();
        $sidebar = substr($html, strpos($html, '<aside'), strpos($html, '</aside>') - strpos($html, '<aside'));

        $this->assertStringContainsString('Keluar', $sidebar);
        $this->assertStringNotContainsString($this->peternak->name, $sidebar);
        $this->assertStringNotContainsString('Peternak Plasma', $sidebar);
    }

    // ---- Unduhan ----

    public function test_unduh_daftar_btpa_memuat_semua_tahap_dengan_nomor_btpa(): void
    {
        $r = $this->actingAs($this->peternak)->get(route('panen.csv', $this->siklus))->assertOk()->assertDownload();
        $isi = $r->streamedContent();

        $this->assertStringStartsWith(chr(239).chr(187).chr(191).'Tahap,Tanggal,"Umur (hari)","Jumlah ekor","Tonase (kg)","ABW (kg)","Nomor BTPA"', $isi);
        foreach ($this->siklus->panen as $t) {
            $this->assertStringContainsString((string) $t->nomor_btpa, $isi);
        }
        $this->assertSame(1 + $this->siklus->panen()->count(), count(array_filter(explode("\n", trim($isi)))));
        $this->actingAs($this->peternak)->get(route('panen.index', $this->siklus))->assertSee('Unduh daftar BTPA')->assertSee(route('panen.csv', $this->siklus), false);
    }

    public function test_unduh_rekap_pembanding_rhpp_memuat_baris_dan_kolom_rhpp(): void
    {
        $r = $this->actingAs($this->peternak)->get(route('rhpp.rekap', $this->siklus))->assertOk()->assertDownload();
        $isi = $r->streamedContent();

        $this->assertStringContainsString('"Catatan peternak (NOA)"', $isi);
        $this->assertStringContainsString('"RHPP inti"', $isi);
        foreach (['Populasi DOC', 'Ayam dipanen', 'Tonase panen', 'FCR', '"Biaya pakan"', 'Pendapatan', 'Bonus', 'Hasil bersih'] as $baris) {
            $this->assertStringContainsString(trim($baris, '"'), $isi);
        }
        $this->assertStringContainsString('"3.000 ekor"', $isi);
        $this->actingAs($this->peternak)->get(route('rhpp.show', $this->siklus))->assertSee('Unduh rekap pembanding');
    }

    public function test_rekap_tanpa_rhpp_tetap_bisa_diunduh_dengan_kolom_rhpp_kosong(): void
    {
        $this->siklus->rhpp()->forceDelete();
        $this->siklus->update(['status' => Siklus::STATUS_MENUNGGU_RHPP, 'ditutup_pada' => null]);

        $isi = $this->actingAs($this->peternak)->get(route('rhpp.rekap', $this->siklus))->assertOk()->streamedContent();

        $this->assertStringContainsString('"3.000 ekor",,,', $isi);
    }

    public function test_unduhan_hanya_untuk_peternak_pemilik(): void
    {
        $abk = User::factory()->abk($this->peternak)->create();
        $lain = User::factory()->create();

        foreach ([$abk, $lain] as $user) {
            $this->actingAs($user)->get(route('panen.csv', $this->siklus))->assertForbidden();
            $this->actingAs($user)->get(route('rhpp.rekap', $this->siklus))->assertForbidden();
        }
    }
}
