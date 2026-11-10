<?php

namespace Tests\Feature;

use App\Http\Requests\RhppRequest;
use App\Models\Siklus;
use App\Models\User;
use App\Services\Rekonsiliasi;
use Database\Seeders\SiklusContohSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Siklus contoh seeder: 3.000 DOC, pakan masuk 195 sak (9.750 kg), retur 5 sak, panen 2.904 ekor / 5.750 kg,
 * kontrak harga DOC Rp7.000, pakan Rp430.000/sak, ayam Rp20.500/kg. RHPP seeder identik dengan catatan peternak.
 * Penanda "Berbeda" hanya berarti angka tidak sama: tanpa ambang dan tanpa dampak rupiah.
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

    /** @return array<string, array<string, mixed>> */
    private function baris(): array
    {
        return collect($this->hitung()['baris'])->keyBy('kunci')->all();
    }

    private function ubahRhpp(array $nilai): void
    {
        $this->siklus->rhpp()->first()->update($nilai);
    }

    /** Siklus dalam tahap yang membolehkan input RHPP. */
    private function menungguRhpp(): void
    {
        $this->siklus->rhpp()->forceDelete();
        $this->siklus->update(['status' => Siklus::STATUS_MENUNGGU_RHPP, 'ditutup_pada' => null]);
    }

    /** @return array<string, mixed> */
    private function isianRhpp(array $override = []): array
    {
        return array_merge([
            'nomor_rhpp' => 'RHPP/SU/2026/11/0457', 'tanggal_terbit' => '2026-11-08', 'populasi_doc' => 3000, 'total_ekor' => 2904, 'deplesi_ekor' => 96,
            'total_tonase' => 5750, 'pakan_terpakai_kg' => 9500, 'pakan_retur_sak' => 5, 'fcr' => 1.652, 'ip' => 339.2, 'abw' => 1.98, 'umur_panen' => 33,
            'biaya_doc' => 21000000, 'biaya_pakan' => 83850000, 'biaya_ovk' => 565000, 'pendapatan' => 117000000, 'bonus' => 1000000, 'hasil_bersih' => 12000000,
        ], $override);
    }

    public function test_rhpp_identik_dengan_catatan_lima_belas_baris_sama_dan_dua_tidak_dibandingkan(): void
    {
        $hasil = $this->hitung();

        $this->assertTrue($hasil['ada_rhpp']);
        $this->assertSame(15, $hasil['jumlah_dibandingkan']);
        $this->assertSame(15, $hasil['jumlah_sama']);
        $this->assertSame(0, $hasil['jumlah_berbeda']);
        $baris = collect($hasil['baris']);
        $this->assertCount(17, $baris);
        $this->assertSame(['bonus', 'hasil_bersih'], $baris->where('status', Rekonsiliasi::TIDAK)->pluck('kunci')->all());
        foreach ($baris->where('status', Rekonsiliasi::SAMA) as $b) {
            $this->assertSame('0', $b['selisih'], "Baris {$b['kunci']} seharusnya selisih nol");
        }
    }

    public function test_urutan_baris_sesuai_desain(): void
    {
        $this->assertSame(
            ['populasi_doc', 'ekor_panen', 'deplesi', 'tonase', 'abw', 'umur_panen', 'pakan_masuk', 'pakan_terpakai', 'pakan_retur', 'fcr', 'ip', 'biaya_doc', 'biaya_pakan', 'biaya_ovk', 'pendapatan', 'bonus', 'hasil_bersih'],
            array_keys(Rekonsiliasi::BARIS),
        );
    }

    public function test_format_angka_baris_mengikuti_desain(): void
    {
        $b = $this->baris();

        $this->assertSame('3.000 ekor', $b['populasi_doc']['peternak']);
        $this->assertSame('96 ekor · 3,20%', $b['deplesi']['peternak']);
        $this->assertSame('5.750 kg', $b['tonase']['peternak']);
        $this->assertSame('195 sak · 9.750 kg', $b['pakan_masuk']['peternak']);
        $this->assertSame('5 sak · 250 kg', $b['pakan_retur']['peternak']);
        $this->assertSame('Rp 21.000.000', $b['biaya_doc']['peternak']);
        $this->assertSame('Rp 83.850.000', $b['biaya_pakan']['peternak']);
        $this->assertSame('– (tidak dihitung NOA)', $b['bonus']['peternak']);
    }

    public function test_selisih_adalah_rhpp_dikurangi_peternak_dengan_tanda(): void
    {
        $this->ubahRhpp(['total_tonase' => 5716, 'deplesi_ekor' => 110, 'biaya_ovk' => 700000, 'pakan_retur_sak' => 0, 'pakan_terpakai_kg' => 9750]);

        $b = $this->baris();
        $this->assertSame('−34 kg', $b['tonase']['selisih']);
        $this->assertSame(Rekonsiliasi::BERBEDA, $b['tonase']['status']);
        $this->assertSame('+14 ekor · +0,47 poin', $b['deplesi']['selisih']);
        $this->assertSame('+Rp 135.000', $b['biaya_ovk']['selisih']);
        $this->assertSame('−5 sak', $b['pakan_retur']['selisih']);
        $this->assertSame('+250 kg', $b['pakan_terpakai']['selisih']);
    }

    public function test_tidak_ada_ambang_selisih_sekecil_apa_pun_berbeda(): void
    {
        $this->ubahRhpp(['total_tonase' => 5749.5]);

        $this->assertSame(Rekonsiliasi::BERBEDA, $this->baris()['tonase']['status']);
        $this->assertSame('−0,5 kg', $this->baris()['tonase']['selisih']);
    }

    public function test_tanpa_dampak_rupiah_dan_tanpa_konfigurasi_ambang(): void
    {
        $this->ubahRhpp(['total_tonase' => 5000]);

        $hasil = $this->hitung();
        foreach (['jumlah_melebihi', 'total_dampak_rp'] as $kunci) {
            $this->assertArrayNotHasKey($kunci, $hasil);
        }
        $this->assertNull(config('noa.ambang_rekonsiliasi'));
        $this->actingAs($this->peternak)->get(route('rhpp.show', $this->siklus))->assertDontSee('ambang selisih wajar')->assertDontSee('merugikan Anda');
    }

    public function test_tanpa_rhpp_kolom_kosong_dan_tidak_ada_penanda(): void
    {
        $this->siklus->rhpp()->forceDelete();

        $hasil = $this->hitung();
        $this->assertFalse($hasil['ada_rhpp']);
        $this->assertSame(0, $hasil['jumlah_dibandingkan']);
        foreach ($hasil['baris'] as $b) {
            $this->assertNull($b['rhpp']);
            $this->assertNull($b['selisih']);
            $this->assertContains($b['status'], [null, Rekonsiliasi::TIDAK]);
        }
    }

    public function test_halaman_rekonsiliasi_menampilkan_tabel_dan_judul_desain(): void
    {
        $this->siklus->update(['status' => Siklus::STATUS_REKONSILIASI, 'ditutup_pada' => null]);
        $this->ubahRhpp(['total_tonase' => 5716]);
        $nomor = $this->siklus->rhpp()->first()->nomor_rhpp;

        $this->actingAs($this->peternak)->get(route('rhpp.show', $this->siklus))->assertOk()
            ->assertSee('Rekonsiliasi RHPP')->assertSee($nomor)->assertSee('1 dari 15 baris berbeda')
            ->assertSeeInOrder(['Baris', 'Catatan peternak', 'RHPP inti', 'Selisih (RHPP − peternak)', 'Penanda', 'Keterangan'])
            ->assertSee('Berbeda')->assertSee('Sama')->assertSee('Tidak dibandingkan')->assertSee('Penanda “Berbeda” hanya menunjukkan angka tidak sama')
            ->assertSee('Cetak lembar')->assertSee('+ Tambah catatan');
    }

    public function test_siklus_tertutup_tanpa_tombol_ubah_atau_catatan(): void
    {
        $this->ubahRhpp(['total_tonase' => 5716]);

        $this->actingAs($this->peternak)->get(route('rhpp.show', $this->siklus))->assertOk()
            ->assertDontSee('Tambah catatan')->assertDontSee('Ubah RHPP')->assertDontSee('Lanjut tutup siklus')->assertSee('Cetak lembar');
        $this->actingAs($this->peternak)->patch(route('rhpp.catatan', $this->siklus), ['catatan' => ['tonase' => 'x']])->assertForbidden();
    }

    public function test_menunggu_rhpp_menampilkan_pengingat_dan_tutup_tanpa_rhpp(): void
    {
        $this->menungguRhpp();
        $this->siklus->update(['tanggal_panen' => '2026-11-01']);

        $this->actingAs($this->peternak)->get(route('rhpp.show', $this->siklus))->assertOk()
            ->assertSee('Menunggu RHPP dari PT Inti Contoh')->assertSee('3–7 hari')->assertSee('sudah 9 hari')->assertSee('Input RHPP')->assertSee('Tutup tanpa RHPP');
    }

    public function test_simpan_rhpp_mengubah_status_ke_rekonsiliasi_dan_menyimpan_semua_isian(): void
    {
        $this->menungguRhpp();

        $this->actingAs($this->peternak)->put(route('rhpp.simpan', $this->siklus), $this->isianRhpp())->assertRedirect(route('rhpp.show', $this->siklus));

        $this->assertSame(Siklus::STATUS_REKONSILIASI, $this->siklus->fresh()->status);
        $rhpp = $this->siklus->rhpp()->firstOrFail();
        $this->assertSame('RHPP/SU/2026/11/0457', $rhpp->nomor_rhpp);
        $this->assertSame('3.20', $rhpp->deplesi_persen);
        $this->assertSame('12000000.00', $rhpp->hasil_bersih);
        $this->assertSame('1000000.00', $rhpp->bonus);
    }

    public function test_ubah_rhpp_saat_rekonsiliasi_memperbarui_satu_baris(): void
    {
        $this->menungguRhpp();
        $this->actingAs($this->peternak)->put(route('rhpp.simpan', $this->siklus), $this->isianRhpp());
        $this->actingAs($this->peternak)->put(route('rhpp.simpan', $this->siklus), $this->isianRhpp(['total_tonase' => 5700]))->assertSessionDoesntHaveErrors();

        $this->assertSame(1, $this->siklus->rhpp()->count());
        $this->assertSame('5700.00', $this->siklus->rhpp()->first()->total_tonase);
        $this->assertSame(Siklus::STATUS_REKONSILIASI, $this->siklus->fresh()->status);
    }

    public function test_isian_wajib_dilaporkan_per_kolom_dengan_pesan_desain(): void
    {
        $this->menungguRhpp();

        $r = $this->actingAs($this->peternak)->put(route('rhpp.simpan', $this->siklus), $this->isianRhpp(['ip' => '']))
            ->assertSessionHasErrors(['ip' => 'Wajib diisi.']);
        $this->assertSame(0, $this->siklus->rhpp()->count());
        $this->assertSame(Siklus::STATUS_MENUNGGU_RHPP, $this->siklus->fresh()->status);

        $this->actingAs($this->peternak)->get(route('rhpp.form', $this->siklus))->assertOk()->assertSee('Nomor RHPP *')->assertSee('Biaya &amp; hasil (sesuai RHPP)', false)->assertSee('Simpan RHPP');
        $this->assertSame(16, count(RhppRequest::WAJIB));
    }

    public function test_bonus_pakan_retur_dan_lampiran_opsional(): void
    {
        Storage::fake('local');
        $this->menungguRhpp();
        $data = $this->isianRhpp(['bonus' => '', 'pakan_retur_sak' => '', 'lampiran' => UploadedFile::fake()->create('rhpp.pdf', 100, 'application/pdf')]);

        $this->actingAs($this->peternak)->put(route('rhpp.simpan', $this->siklus), $data)->assertSessionDoesntHaveErrors();

        $rhpp = $this->siklus->rhpp()->firstOrFail();
        $this->assertNull($rhpp->bonus);
        $this->assertNull($rhpp->pakan_retur_sak);
        Storage::disk('local')->assertExists($rhpp->lampiran);
        $this->actingAs($this->peternak)->get(route('rhpp.lampiran', $this->siklus))->assertOk();
    }

    public function test_lampiran_menolak_berkas_berbahaya(): void
    {
        Storage::fake('local');
        $this->menungguRhpp();

        $this->actingAs($this->peternak)->put(route('rhpp.simpan', $this->siklus), $this->isianRhpp(['lampiran' => UploadedFile::fake()->create('x.php', 5, 'text/x-php')]))->assertSessionHasErrors('lampiran');
        $this->assertSame(0, $this->siklus->rhpp()->count());
    }

    public function test_rhpp_hanya_bisa_diinput_setelah_panen_selesai_dan_sebelum_ditutup(): void
    {
        $this->siklus->rhpp()->forceDelete();
        foreach ([Siklus::STATUS_BERJALAN, Siklus::STATUS_SELESAI, Siklus::STATUS_DIHENTIKAN] as $status) {
            $this->siklus->update(['status' => $status]);

            $this->actingAs($this->peternak)->put(route('rhpp.simpan', $this->siklus), $this->isianRhpp())->assertRedirect(route('rhpp.show', $this->siklus));
            $this->assertSame(0, $this->siklus->rhpp()->count());
        }
    }

    public function test_catatan_per_baris_tersimpan_dan_tampil_di_lembar_cetak(): void
    {
        $this->siklus->update(['status' => Siklus::STATUS_REKONSILIASI, 'ditutup_pada' => null]);
        $this->ubahRhpp(['total_tonase' => 5716]);

        $this->actingAs($this->peternak)->patch(route('rhpp.catatan', $this->siklus), ['catatan' => ['tonase' => 'Selisih timbang tahap 2', 'bonus' => 'diabaikan?', 'xx' => 'bukan baris', 'fcr' => '']])
            ->assertRedirect(route('rhpp.show', $this->siklus));

        $this->assertEquals(['tonase' => 'Selisih timbang tahap 2', 'bonus' => 'diabaikan?'], $this->siklus->rhpp()->first()->catatan);
        $this->actingAs($this->peternak)->get(route('rhpp.show', $this->siklus))->assertSee('Selisih timbang tahap 2')->assertSee('1 dari 1');
        $this->actingAs($this->peternak)->get(route('rhpp.cetak', $this->siklus))->assertSee('Selisih timbang tahap 2');
    }

    public function test_siap_ditutup_menghitung_catatan_hanya_pada_baris_berbeda(): void
    {
        $this->siklus->update(['status' => Siklus::STATUS_REKONSILIASI, 'ditutup_pada' => null]);
        $this->ubahRhpp(['total_tonase' => 5716, 'fcr' => 1.7, 'catatan' => ['tonase' => 'a', 'populasi_doc' => 'b']]);

        $hasil = $this->hitung();
        $this->assertSame(2, $hasil['jumlah_berbeda']);
        $this->assertSame(1, $hasil['berbeda_bercatatan']);
        $this->actingAs($this->peternak)->get(route('rhpp.show', $this->siklus))->assertSee('Rekonsiliasi siap ditutup')->assertSee('1 baris berbeda belum diberi catatan');
    }

    public function test_lembar_cetak_memuat_identitas_tanda_tangan_dan_a4(): void
    {
        $this->ubahRhpp(['total_tonase' => 5700]);

        $this->actingAs($this->peternak)->get(route('rhpp.cetak', $this->siklus))->assertOk()
            ->assertSee('Lembar Perbandingan Catatan Peternak vs RHPP')->assertSee('No. RHPP:')->assertSee('Selisih = RHPP − catatan peternak')
            ->assertSee('Perwakilan inti (PT Inti Contoh)')->assertSee('Halaman 1 dari 1')->assertSee('size: A4', false)->assertSee('window.print()', false)
            ->assertDontSee('Dashboard');
    }

    public function test_rekonsiliasi_hanya_untuk_peternak_pemilik_abk_dan_orang_lain_ditolak_r13(): void
    {
        $abk = User::factory()->abk($this->peternak)->create();
        $lain = User::factory()->create();

        foreach ([$abk, $lain] as $user) {
            $this->actingAs($user)->get(route('rhpp.show', $this->siklus))->assertForbidden();
            $this->actingAs($user)->get(route('rhpp.cetak', $this->siklus))->assertForbidden();
            $this->actingAs($user)->get(route('rhpp.form', $this->siklus))->assertForbidden();
            $this->actingAs($user)->get(route('rhpp.lampiran', $this->siklus))->assertForbidden();
            $this->actingAs($user)->put(route('rhpp.simpan', $this->siklus), $this->isianRhpp())->assertForbidden();
            $this->actingAs($user)->patch(route('rhpp.catatan', $this->siklus), ['catatan' => ['fcr' => 'x']])->assertForbidden();
        }

        auth()->logout();
        $this->get(route('rhpp.show', $this->siklus))->assertRedirect(route('login'));
    }
}
