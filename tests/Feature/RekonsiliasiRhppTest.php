<?php

namespace Tests\Feature;

use App\Models\Rhpp;
use App\Models\Siklus;
use App\Models\User;
use App\Services\Rekonsiliasi;
use Database\Seeders\SiklusContohSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Siklus contoh seeder: 3.000 DOC, pakan masuk 195 sak (9.750 kg), retur 5 sak, panen 2.904 ekor / 5.750 kg,
 * kontrak harga pakan Rp430.000/sak dan harga ayam Rp20.500/kg, bonus FCR dan deplesi aktif.
 * RHPP seeder identik dengan catatan peternak.
 */
class RekonsiliasiRhppTest extends TestCase
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

    /** @return array<string, mixed> */
    private function hitung(): array
    {
        return app(Rekonsiliasi::class)->hitung($this->siklus->fresh(['kontrak']), $this->siklus->rhpp()->first());
    }

    /** @return array<string, array<string, mixed>> baris dikunci menurut kunci */
    private function baris(): array
    {
        return collect($this->hitung()['baris'])->keyBy('kunci')->all();
    }

    private function ubahRhpp(array $nilai): void
    {
        $this->siklus->rhpp()->first()->update($nilai);
    }

    public function test_rhpp_identik_dengan_catatan_semua_selisih_nol(): void
    {
        $hasil = $this->hitung();

        $this->assertTrue($hasil['ada_rhpp']);
        $this->assertSame(0, $hasil['jumlah_melebihi']);
        foreach ($hasil['baris'] as $b) {
            $this->assertTrue($b['nol'], "Baris {$b['kunci']} seharusnya selisih nol");
            $this->assertSame('sama', $b['arah']);
        }
        $this->assertNull($hasil['total_dampak_rp']);
    }

    public function test_selisih_nol_ditampilkan_sebagai_nol_bukan_dikosongkan(): void
    {
        $this->actingAs($this->peternak)->get(route('rhpp.show', $this->siklus))
            ->assertOk()
            ->assertSee('Tidak ada baris yang melebihi ambang selisih wajar')
            ->assertSee('Sama')
            ->assertSee('0,000')   // FCR dan ABW: 3 desimal
            ->assertSee('0,00');
    }

    public function test_baris_yang_dibandingkan_sesuai_prd(): void
    {
        $this->assertSame(
            ['pakan_masuk_kg', 'pakan_terpakai_kg', 'pakan_retur_sak', 'total_ekor', 'total_tonase', 'abw', 'umur_panen', 'deplesi_persen', 'fcr', 'ip'],
            array_keys(Rekonsiliasi::BARIS),
        );
    }

    public function test_angka_peternak_dihitung_dari_catatan_dan_rhpp_dari_lembar_inti(): void
    {
        $b = $this->baris();

        $this->assertEqualsWithDelta(9750.0, $b['pakan_masuk_kg']['peternak'], 0.001);
        $this->assertEqualsWithDelta(9500.0, $b['pakan_terpakai_kg']['peternak'], 0.001);
        $this->assertEqualsWithDelta(5.0, $b['pakan_retur_sak']['peternak'], 0.001);
        $this->assertEqualsWithDelta(9750.0, $b['pakan_masuk_kg']['rhpp'], 0.001);
        // Terpakai menurut RHPP = tagihan dikurangi retur.
        $this->assertEqualsWithDelta(9500.0, $b['pakan_terpakai_kg']['rhpp'], 0.001);
        $this->assertSame(2904.0, $b['total_ekor']['peternak']);
    }

    public function test_selisih_adalah_rhpp_dikurangi_peternak_dengan_arah(): void
    {
        $this->ubahRhpp(['total_tonase' => 5700]);

        $b = $this->baris()['total_tonase'];
        $this->assertEqualsWithDelta(-50.0, $b['selisih'], 0.001);
        $this->assertSame('RHPP lebih rendah', $b['arah']);
        $this->assertTrue($b['melebihi']);
    }

    public function test_f6_3_selisih_dalam_ambang_wajar_tidak_ditandai_tetapi_tetap_tampil(): void
    {
        $this->ubahRhpp(['total_tonase' => 5747]);   // -3 kg, ambang bawaan 5 kg

        $b = $this->baris()['total_tonase'];
        $this->assertEqualsWithDelta(-3.0, $b['selisih'], 0.001);
        $this->assertFalse($b['melebihi']);
        $this->assertFalse($b['nol']);
    }

    public function test_selisih_tepat_di_ambang_tidak_ditandai_hanya_yang_melebihi(): void
    {
        $this->ubahRhpp(['total_tonase' => 5745]);   // -5 kg = ambang

        $this->assertFalse($this->baris()['total_tonase']['melebihi']);

        $this->ubahRhpp(['total_tonase' => 5744.99]);
        $this->assertTrue($this->baris()['total_tonase']['melebihi']);
    }

    public function test_f6_5_dampak_rupiah_dihitung_dari_kontrak_siklus(): void
    {
        $this->ubahRhpp(['total_pakan_kg' => 9900, 'pakan_retur_sak' => 3, 'total_tonase' => 5700]);

        $b = $this->baris();
        // Tagihan 150 kg lebih banyak = 3 sak x Rp430.000 dibayar lebih.
        $this->assertEqualsWithDelta(-1290000.0, $b['pakan_masuk_kg']['dampak_rp'], 0.5);
        // Retur diakui 2 sak lebih sedikit: kredit berkurang 2 x Rp430.000.
        $this->assertEqualsWithDelta(-860000.0, $b['pakan_retur_sak']['dampak_rp'], 0.5);
        // Tonase 50 kg lebih rendah x Rp20.500.
        $this->assertEqualsWithDelta(-1025000.0, $b['total_tonase']['dampak_rp'], 0.5);

        $this->assertEqualsWithDelta(-1290000 - 860000 - 1025000, $this->hitung()['total_dampak_rp'], 1.0);
    }

    public function test_dampak_menguntungkan_bernilai_positif(): void
    {
        $this->ubahRhpp(['total_tonase' => 5800]);

        $this->assertEqualsWithDelta(50 * 20500, $this->baris()['total_tonase']['dampak_rp'], 0.5);
    }

    public function test_dampak_rupiah_kosong_bila_harga_kontrak_belum_diisi(): void
    {
        $this->siklus->kontrak->update(['harga_pakan_sak' => null, 'harga_kontrak_ayam' => null]);
        $this->ubahRhpp(['total_pakan_kg' => 9900, 'total_tonase' => 5700]);

        $b = $this->baris();
        $this->assertNull($b['pakan_masuk_kg']['dampak_rp']);
        $this->assertNull($b['total_tonase']['dampak_rp']);
        $this->assertNull($this->hitung()['total_dampak_rp']);
    }

    public function test_bonus_fcr_dan_deplesi_ditandai_bukan_dikonversi_ke_rupiah(): void
    {
        $this->ubahRhpp(['fcr' => 1.700, 'deplesi_persen' => 4.00, 'ip' => 300]);

        $b = $this->baris();
        $this->assertTrue($b['fcr']['bonus_terkait']);
        $this->assertTrue($b['deplesi_persen']['bonus_terkait']);
        $this->assertNull($b['fcr']['dampak_rp']);
        // Kontrak contoh tidak mengaktifkan bonus IP.
        $this->assertFalse($b['ip']['bonus_terkait']);
    }

    public function test_ambang_per_siklus_menimpa_bawaan(): void
    {
        $this->ubahRhpp(['total_tonase' => 5700]);
        $this->assertTrue($this->baris()['total_tonase']['melebihi']);

        $this->actingAs($this->peternak)->patch(route('rhpp.ambang', $this->siklus), ['ambang' => ['total_tonase' => 100]])->assertRedirect();

        $this->assertEquals(['total_tonase' => 100.0], $this->siklus->fresh()->ambang_rekonsiliasi);
        $this->assertFalse($this->baris()['total_tonase']['melebihi']);
        $this->assertSame(0.002, app(Rekonsiliasi::class)->ambang($this->siklus->fresh())['fcr']);
    }

    public function test_ambang_sama_dengan_bawaan_atau_kosong_tidak_disimpan(): void
    {
        $this->actingAs($this->peternak)->patch(route('rhpp.ambang', $this->siklus), ['ambang' => ['total_tonase' => 100]]);
        $this->actingAs($this->peternak)->patch(route('rhpp.ambang', $this->siklus), ['ambang' => ['total_tonase' => '', 'fcr' => 0.002]])->assertSessionDoesntHaveErrors();

        $this->assertNull($this->siklus->fresh()->ambang_rekonsiliasi);
    }

    public function test_ambang_negatif_atau_bukan_angka_ditolak(): void
    {
        $this->actingAs($this->peternak)->patch(route('rhpp.ambang', $this->siklus), ['ambang' => ['fcr' => -1, 'abw' => 'abc']])
            ->assertSessionHasErrors(['ambang.fcr', 'ambang.abw']);
    }

    public function test_kolom_rhpp_kosong_membuat_baris_belum_bisa_dibandingkan(): void
    {
        $this->ubahRhpp(['fcr' => null]);

        $b = $this->baris()['fcr'];
        $this->assertNull($b['rhpp']);
        $this->assertNull($b['selisih']);
        $this->assertFalse($b['melebihi']);
        $this->actingAs($this->peternak)->get(route('rhpp.show', $this->siklus))->assertSee('Belum bisa dibandingkan');
    }

    public function test_rhpp_tidak_pernah_diterbitkan_kolom_dikosongkan_dan_siklus_tetap_bisa_ditutup(): void
    {
        $this->siklus->rhpp()->first()->delete();

        $hasil = $this->hitung();
        $this->assertFalse($hasil['ada_rhpp']);
        foreach ($hasil['baris'] as $b) {
            $this->assertNull($b['rhpp']);
            $this->assertNull($b['selisih']);
        }
        $this->actingAs($this->peternak)->get(route('rhpp.show', $this->siklus))
            ->assertOk()->assertSee('RHPP belum dimasukkan');
        $this->actingAs($this->peternak)->get(route('rhpp.cetak', $this->siklus))->assertOk()->assertSee('RHPP belum dimasukkan');
    }

    public function test_pendapatan_bersifat_informatif_dan_tidak_ditandai(): void
    {
        $this->ubahRhpp(['pendapatan' => 100000000]);

        $hasil = $this->hitung();
        $this->assertNotNull($hasil['pendapatan']['selisih']);
        $this->assertSame(0, $hasil['jumlah_melebihi']);
    }

    public function test_f6_1_simpan_rhpp_membuat_dan_memperbarui_satu_baris_per_siklus(): void
    {
        $this->siklus->rhpp()->first()->forceDelete();
        $data = ['tanggal_terbit' => '2026-10-29', 'total_pakan_kg' => 9750, 'pakan_retur_sak' => 5, 'total_ekor' => 2904, 'total_tonase' => 5750, 'abw' => 1.98, 'umur_panen' => 34.2, 'deplesi_persen' => 3.2, 'fcr' => 1.652, 'ip' => 339.2];

        $this->actingAs($this->peternak)->put(route('rhpp.simpan', $this->siklus), $data)
            ->assertRedirect(route('rhpp.show', $this->siklus))
            ->assertSessionHas('status', fn ($p) => str_contains($p, 'Angka RHPP disimpan'));
        $this->assertSame(1, Rhpp::where('siklus_id', $this->siklus->id)->count());

        $this->actingAs($this->peternak)->put(route('rhpp.simpan', $this->siklus), array_merge($data, ['total_tonase' => 5700]));
        $this->assertSame(1, Rhpp::where('siklus_id', $this->siklus->id)->count());
        $this->assertSame('5700.00', $this->siklus->rhpp()->first()->total_tonase);
    }

    public function test_simpan_rhpp_melaporkan_jumlah_baris_melebihi_ambang(): void
    {
        $this->actingAs($this->peternak)->put(route('rhpp.simpan', $this->siklus), [
            'tanggal_terbit' => '2026-10-29', 'total_pakan_kg' => 9900, 'pakan_retur_sak' => 5, 'total_ekor' => 2904, 'total_tonase' => 5700,
        ])->assertSessionHas('status', fn ($p) => str_contains($p, 'baris melebihi ambang'));
    }

    public function test_hapus_rhpp_soft_delete_dan_simpan_ulang_memulihkan_tanpa_bentrok(): void
    {
        $this->actingAs($this->peternak)->delete(route('rhpp.hapus', $this->siklus))->assertRedirect(route('rhpp.show', $this->siklus));
        $this->assertSame(0, Rhpp::count());
        $this->assertSame(1, Rhpp::withTrashed()->count());

        $this->actingAs($this->peternak)->put(route('rhpp.simpan', $this->siklus), ['tanggal_terbit' => '2026-10-30', 'total_ekor' => 2904])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(1, Rhpp::withTrashed()->count());
        $this->assertSame(1, Rhpp::count());
    }

    public function test_pakan_retur_kosong_disimpan_sebagai_tidak_diketahui_bukan_nol(): void
    {
        $this->actingAs($this->peternak)->put(route('rhpp.simpan', $this->siklus), ['tanggal_terbit' => '2026-10-29', 'total_pakan_kg' => 9750, 'pakan_retur_sak' => '']);

        $this->assertNull($this->siklus->rhpp()->first()->pakan_retur_sak);
        $this->assertNull($this->baris()['pakan_retur_sak']['rhpp']);
        $this->assertNull($this->baris()['pakan_terpakai_kg']['rhpp']);
    }

    public function test_validasi_rhpp(): void
    {
        $this->actingAs($this->peternak)->put(route('rhpp.simpan', $this->siklus), [])->assertSessionHasErrors('tanggal_terbit');
        $this->actingAs($this->peternak)->put(route('rhpp.simpan', $this->siklus), ['tanggal_terbit' => '2026-09-01'])->assertSessionHasErrors('tanggal_terbit');
        $this->actingAs($this->peternak)->put(route('rhpp.simpan', $this->siklus), ['tanggal_terbit' => '2026-11-11'])->assertSessionHasErrors('tanggal_terbit');
        $this->actingAs($this->peternak)->put(route('rhpp.simpan', $this->siklus), ['tanggal_terbit' => '2026-10-29', 'deplesi_persen' => 101, 'fcr' => -1, 'total_ekor' => 1.5])
            ->assertSessionHasErrors(['deplesi_persen', 'fcr', 'total_ekor']);
    }

    public function test_f6_4_lembar_cetak_memuat_tiga_kolom_dan_tanda_tangan_tanpa_navigasi(): void
    {
        $this->ubahRhpp(['total_tonase' => 5700]);

        $this->actingAs($this->peternak)->get(route('rhpp.cetak', $this->siklus))
            ->assertOk()
            ->assertSee('Lembar perbandingan catatan peternak dengan RHPP')
            ->assertSee('Versi peternak')
            ->assertSee('Versi RHPP')
            ->assertSee('Selisih (RHPP − peternak)')
            ->assertSee('Melebihi ambang')
            ->assertSee('Perwakilan inti (PT Inti Contoh)')
            ->assertSee('size: A4', false)
            ->assertSee('window.print()', false)
            ->assertDontSee('Dashboard');
    }

    public function test_halaman_menampilkan_dampak_rupiah_dan_catatan_bonus(): void
    {
        $this->ubahRhpp(['total_tonase' => 5700, 'fcr' => 1.7]);

        $this->actingAs($this->peternak)->get(route('rhpp.show', $this->siklus))
            ->assertOk()
            ->assertSee('merugikan Anda')
            ->assertSee('−Rp 1.025.000')
            ->assertSee('butuh tabel bonus inti');
    }

    public function test_rekonsiliasi_hanya_untuk_peternak_pemilik_abk_dan_orang_lain_ditolak_r13(): void
    {
        $abk = User::factory()->abk($this->peternak)->create();
        $lain = User::factory()->create();
        $data = ['tanggal_terbit' => '2026-10-29'];

        foreach ([$abk, $lain] as $user) {
            $this->actingAs($user)->get(route('rhpp.show', $this->siklus))->assertForbidden();
            $this->actingAs($user)->get(route('rhpp.cetak', $this->siklus))->assertForbidden();
            $this->actingAs($user)->put(route('rhpp.simpan', $this->siklus), $data)->assertForbidden();
            $this->actingAs($user)->delete(route('rhpp.hapus', $this->siklus))->assertForbidden();
            $this->actingAs($user)->patch(route('rhpp.ambang', $this->siklus), ['ambang' => ['fcr' => 1]])->assertForbidden();
        }

        auth()->logout();
        $this->get(route('rhpp.show', $this->siklus))->assertRedirect(route('login'));
    }
}
