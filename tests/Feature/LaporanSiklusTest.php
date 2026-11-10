<?php

namespace Tests\Feature;

use App\Models\Kandang;
use App\Models\Siklus;
use App\Models\User;
use App\Services\LaporanSiklus;
use Database\Seeders\SiklusContohSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LaporanSiklusTest extends TestCase
{
    use RefreshDatabase;

    private Siklus $siklus;

    private User $peternak;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 08:00:00');
        $this->seed(SiklusContohSeeder::class);

        $this->siklus = Siklus::where('nama_inti', 'PT Inti Contoh')->firstOrFail();
        $this->peternak = $this->siklus->kandang->user;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_f7_1_biaya_produksi_dari_doc_pakan_dan_ovk(): void
    {
        $biaya = app(LaporanSiklus::class)->biaya($this->siklus->fresh(['kontrak']));

        $this->assertEqualsWithDelta(7000 * 3000, $biaya['doc'], 0.5);
        // 195 sak pakan masuk (tagihan sapronak) x Rp430.000.
        $this->assertEqualsWithDelta(195 * 430000, $biaya['pakan'], 0.5);
        $this->assertEqualsWithDelta(150000 + 175000 + 150000 + 90000, $biaya['ovk'], 0.5);
        $this->assertTrue($biaya['lengkap']);

        $total = 21000000 + 83850000 + 565000;
        $this->assertEqualsWithDelta($total, $biaya['total'], 0.5);
        $this->assertEqualsWithDelta($total / 5750, $biaya['per_kg'], 0.01);
        $this->assertEqualsWithDelta($total / 2904, $biaya['per_ekor'], 0.01);

        $pendapatan = 780 * 20500 + 3015.54 * 20500 + 1954.46 * 20800;
        $this->assertEqualsWithDelta($pendapatan - $total, $biaya['margin_kasar'], 1.0);
    }

    public function test_biaya_tidak_lengkap_bila_harga_kontrak_kosong_dan_margin_tidak_dihitung(): void
    {
        $this->siklus->kontrak->update(['harga_doc' => null]);

        $biaya = app(LaporanSiklus::class)->biaya($this->siklus->fresh(['kontrak']));

        $this->assertNull($biaya['doc']);
        $this->assertFalse($biaya['lengkap']);
        $this->assertNull($biaya['margin_kasar']);
        $this->actingAs($this->peternak)->get(route('laporan.siklus', $this->siklus))
            ->assertOk()->assertSee('belum lengkap')->assertSee('Belum lengkap: harga DOC atau pakan di kontrak belum diisi');
    }

    public function test_ovk_selalu_dihitung_walau_harga_kontrak_kosong(): void
    {
        $this->siklus->kontrak->update(['harga_doc' => null, 'harga_pakan_sak' => null]);

        $biaya = app(LaporanSiklus::class)->biaya($this->siklus->fresh(['kontrak']));

        $this->assertEqualsWithDelta(565000, $biaya['ovk'], 0.5);
        $this->assertEqualsWithDelta(565000, $biaya['total'], 0.5);
    }

    public function test_f7_2_data_grafik_bobot_dan_deplesi_per_hari(): void
    {
        $g = app(LaporanSiklus::class)->grafik($this->siklus);

        $this->assertSame(range(1, 35), $g['labels']);
        $this->assertCount(35, $g['bobot']);
        $this->assertCount(35, $g['bobot_standar']);
        $this->assertCount(35, $g['deplesi']);
        $this->assertEqualsWithDelta(1928.0, $g['bobot_standar'][34], 0.01);
        $this->assertEqualsWithDelta(3.20, $g['deplesi'][34], 0.0001);
        // Deplesi kumulatif tidak pernah turun.
        $this->assertSame($g['deplesi'], collect($g['deplesi'])->sort()->values()->all());
    }

    public function test_grafik_kosong_untuk_siklus_tanpa_data(): void
    {
        $kandang = Kandang::create(['user_id' => $this->peternak->id, 'nama_farm' => 'Kosong', 'kapasitas' => 100]);
        $kosong = Siklus::create(['kandang_id' => $kandang->id, 'tanggal_masuk' => '2026-11-01', 'populasi' => 100, 'status' => 'berjalan']);

        $g = app(LaporanSiklus::class)->grafik($kosong);

        $this->assertSame([], $g['labels']);
        $this->actingAs($this->peternak)->get(route('laporan.siklus', $kosong))
            ->assertOk()->assertSee('Grafik muncul setelah ada recording');
    }

    public function test_halaman_laporan_memuat_ringkasan_grafik_dan_tombol_cetak(): void
    {
        $this->actingAs($this->peternak)->get(route('laporan.siklus', $this->siklus))
            ->assertOk()
            ->assertSee('Laporan siklus')
            ->assertSee('Biaya produksi (catatan peternak)')
            ->assertSee('1,652')
            ->assertSee('3,20%')
            ->assertSee('195 sak × Rp 430.000')
            ->assertSee('Unduh CSV')
            ->assertSee('Cetak laporan')
            ->assertSee('id="grafik-bobot"', false)
            ->assertSee('id="grafik-deplesi"', false)
            ->assertSee('id="data-grafik"', false)
            ->assertSee('Hasil bersih dan bonus tidak dihitung NOA');
    }

    public function test_lembar_cetak_a4_memuat_performa_biaya_dan_panen(): void
    {
        $this->actingAs($this->peternak)->get(route('laporan.cetak', $this->siklus))->assertOk()
            ->assertSee('Laporan Siklus 1')->assertSee('NOA · catatan peternak')->assertSee('Performa')->assertSee('Total biaya')->assertSee('No. BTPA')
            ->assertSee('Halaman 1 dari 1')->assertSee('size: A4', false)->assertSee('window.print()', false)->assertDontSee('Dashboard');
    }

    public function test_unduh_csv_laporan_dan_recording(): void
    {
        $laporan = $this->actingAs($this->peternak)->get(route('laporan.csv', $this->siklus));
        $laporan->assertOk()->assertDownload();
        $isi = $laporan->streamedContent();
        $this->assertStringStartsWith(chr(239).chr(187).chr(191), $isi);
        $this->assertStringContainsString('Ukuran,Nilai,Keterangan', $isi);
        $this->assertStringContainsString('FCR,1.652', $isi);
        $this->assertStringContainsString('"Biaya pakan (Rp)",83850000', $isi);

        $rec = $this->actingAs($this->peternak)->get(route('recording.csv', $this->siklus));
        $rec->assertOk();
        $this->assertStringContainsString('Hari,Tanggal,"Pakan std (sak)"', $rec->streamedContent());
    }

    public function test_chart_js_hanya_dimuat_di_halaman_laporan(): void
    {
        $this->actingAs($this->peternak)->get(route('laporan.siklus', $this->siklus))->assertSee('laporan', false);

        foreach ([route('dashboard'), route('kandang.index'), route('recording.index', $this->siklus), route('panen.index', $this->siklus)] as $url) {
            $this->actingAs($this->peternak)->get($url)->assertOk()->assertDontSee('laporan-', false)->assertDontSee('data-grafik', false);
        }
    }

    public function test_siklus_dihentikan_ditandai_tidak_sebanding_di_laporan(): void
    {
        $this->siklus->update(['status' => Siklus::STATUS_DIHENTIKAN]);

        $this->actingAs($this->peternak)->get(route('laporan.siklus', $this->siklus))->assertSee('Tidak sebanding');
    }

    public function test_perbandingan_global_lintas_kandang_dengan_siklus_berjalan_tampil_strip(): void
    {
        $kandang = $this->siklus->kandang;
        $baru = Siklus::create(['kandang_id' => $kandang->id, 'tanggal_masuk' => '2026-11-01', 'populasi' => 3000, 'status' => 'berjalan']);
        $lain = Kandang::create(['user_id' => $this->peternak->id, 'nama_farm' => 'Kandang Lain', 'kapasitas' => 5000]);
        Siklus::create(['kandang_id' => $lain->id, 'tanggal_masuk' => '2026-10-01', 'populasi' => 777, 'status' => 'dihentikan']);

        $response = $this->actingAs($this->peternak)->get(route('laporan.index'));

        $response->assertOk()->assertSee('Perbandingan antar-siklus')->assertSee('Kandang Lain')->assertSee('Siklus 2')->assertSee('1,652')->assertSee('Dihentikan')->assertSee('Berjalan')
            ->assertSee('IP per siklus')->assertSee('FCR per siklus');
        $response->assertSeeInOrder(['1 Nov 2026', '20 Sep 2026']);
        $response->assertViewHas('baris', fn ($b) => $b->pluck('siklus.id')->first() === $baru->id);
    }

    public function test_perbandingan_filter_kandang_status_dan_tahun(): void
    {
        $lain = Kandang::create(['user_id' => $this->peternak->id, 'nama_farm' => 'Kandang Lain', 'kapasitas' => 5000]);
        $s = Siklus::create(['kandang_id' => $lain->id, 'tanggal_masuk' => '2025-03-01', 'populasi' => 777, 'status' => 'dihentikan']);

        $this->actingAs($this->peternak)->get(route('laporan.index', ['kandang' => $lain->id]))->assertViewHas('baris', fn ($b) => $b->count() === 1);
        $this->actingAs($this->peternak)->get(route('laporan.index', ['status' => 'selesai']))->assertViewHas('baris', fn ($b) => $b->pluck('siklus.id')->all() === [$this->siklus->id]);
        $this->actingAs($this->peternak)->get(route('laporan.index', ['tahun' => 2025]))->assertViewHas('baris', fn ($b) => $b->pluck('siklus.id')->all() === [$s->id]);
    }

    public function test_perbandingan_tidak_mencampur_siklus_peternak_lain(): void
    {
        $lain = User::factory()->create();
        $k = Kandang::create(['user_id' => $lain->id, 'nama_farm' => 'Milik Orang', 'kapasitas' => 100]);
        Siklus::create(['kandang_id' => $k->id, 'tanggal_masuk' => '2026-11-01', 'populasi' => 777, 'status' => 'berjalan']);

        $this->actingAs($this->peternak)->get(route('laporan.index'))->assertDontSee('Milik Orang')->assertDontSee('777 ekor');
    }

    public function test_tautan_lama_perbandingan_kandang_mengarah_ke_halaman_global(): void
    {
        $this->actingAs($this->peternak)->get(route('laporan.perbandingan', $this->siklus->kandang))->assertRedirect(route('laporan.index', ['kandang' => $this->siklus->kandang->id]));
    }

    public function test_laporan_dan_perbandingan_hanya_untuk_peternak_pemilik(): void
    {
        $abk = User::factory()->abk($this->peternak)->create();
        $lain = User::factory()->create();

        foreach ([$abk, $lain] as $user) {
            $this->actingAs($user)->get(route('laporan.siklus', $this->siklus))->assertForbidden();
            $this->actingAs($user)->get(route('laporan.perbandingan', $this->siklus->kandang))->assertForbidden();
            $this->actingAs($user)->get(route('laporan.cetak', $this->siklus))->assertForbidden();
            $this->actingAs($user)->get(route('laporan.csv', $this->siklus))->assertForbidden();
        }
    }

    public function test_abk_ditolak_di_laporan_global(): void
    {
        $abk = User::factory()->abk($this->peternak)->create();

        $this->actingAs($abk)->get(route('laporan.index'))->assertForbidden();
    }
}
