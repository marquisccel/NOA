<?php

namespace Tests\Feature;

use App\Models\Kandang;
use App\Models\Panen;
use App\Models\RecordingHarian;
use App\Models\Siklus;
use App\Models\User;
use App\Services\PerformanceCalculator;
use App\Services\RingkasanPerforma;
use Database\Seeders\SiklusContohSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PanenPerformaTest extends TestCase
{
    use RefreshDatabase;

    private User $peternak;

    private Siklus $siklus;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-12-10 08:00:00');
        Storage::fake('local');

        $this->peternak = User::factory()->create(['role' => User::ROLE_PETERNAK]);
        $this->siklus = $this->buatSiklus($this->peternak);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function buatSiklus(User $user): Siklus
    {
        $kandang = Kandang::create(['user_id' => $user->id, 'nama_farm' => 'Uji', 'kapasitas' => 4000]);

        return Siklus::create([
            'kandang_id' => $kandang->id,
            'tanggal_masuk' => '2026-11-01',
            'populasi' => 3000,
            'bobot_sak_kg' => 50,
            'status' => Siklus::STATUS_BERJALAN,
        ]);
    }

    /** @return array<string, mixed> */
    private function dataPanen(string $tanggal, int $ekor, float $tonase, array $override = []): array
    {
        return array_merge(['tanggal' => $tanggal, 'jumlah_ekor' => $ekor, 'tonase_kg' => $tonase, 'harga_per_kg' => 20500, 'nomor_btpa' => 'BTPA-'.$ekor], $override);
    }

    private function panen(string $tanggal, int $ekor, float $tonase, array $override = [], ?Siklus $siklus = null)
    {
        return $this->actingAs($this->peternak)->post(route('panen.store', $siklus ?? $this->siklus), $this->dataPanen($tanggal, $ekor, $tonase, $override));
    }

    /** Siklus contoh PRD: 3 tahap 400/1523/981 ekor pada umur 33/34/35, 5.750 kg total. */
    private function panenContohPrd(): void
    {
        $this->panen('2026-12-03', 400, 780);
        $this->panen('2026-12-04', 1523, 3015.54);
        $this->panen('2026-12-05', 981, 1954.46);
    }

    private function isiRecording(int $mati = 78, int $afkir = 18, float $pakaiSak = 190): void
    {
        RecordingHarian::create([
            'siklus_id' => $this->siklus->id, 'tanggal' => '2026-11-01', 'hari_ke' => 1,
            'pakan_pakai_sak' => $pakaiSak, 'mati' => $mati, 'afkir' => $afkir, 'sebab_afkir' => $afkir ? 'sakit' : null,
        ]);
    }

    /** @return array<string, mixed> */
    private function performa(?Siklus $siklus = null): array
    {
        return app(RingkasanPerforma::class)->hitung(($siklus ?? $this->siklus)->fresh());
    }

    public function test_f5_1_f5_2_catat_tahap_panen_dengan_umur_dan_nomor_btpa(): void
    {
        $this->panen('2026-12-03', 400, 780)->assertRedirect(route('panen.index', $this->siklus))->assertSessionDoesntHaveErrors();

        $p = Panen::firstOrFail();
        $this->assertSame(33, $p->umur_hari);
        $this->assertSame(400, $p->jumlah_ekor);
        $this->assertSame('780.00', $p->tonase_kg);
        $this->assertSame('BTPA-400', $p->nomor_btpa);
        $this->assertSame('20500.00', $p->harga_per_kg);
    }

    public function test_pesan_setelah_simpan_menampilkan_total(): void
    {
        $this->isiRecording();

        $this->panen('2026-12-03', 2904, 5750)
            ->assertSessionHas('status', fn ($p) => str_contains($p, 'Total panen 2.904 ekor, 5.750,00 kg') && str_contains($p, 'FCR 1,652'));
    }

    public function test_f5_4_total_ekor_dan_tonase_dijumlahkan(): void
    {
        $this->panenContohPrd();

        $p = $this->performa();
        $this->assertSame(2904, $p['total_ekor']);
        $this->assertEqualsWithDelta(5750.0, $p['total_tonase'], 0.001);
        $this->assertSame(3, $p['jumlah_tahap']);
        $this->assertEqualsWithDelta(5750 / 2904, $p['abw'], 0.0001);
    }

    public function test_f5_5_umur_panen_rata_rata_berbobot(): void
    {
        $this->panenContohPrd();

        $this->assertEqualsWithDelta(34.2, $this->performa()['umur_panen'], 0.0001);
    }

    public function test_umur_rata_rata_berbobot_bukan_rata_rata_biasa(): void
    {
        $this->panen('2026-12-03', 100, 200);   // umur 33
        $this->panen('2026-12-05', 900, 1800);  // umur 35

        // Rata-rata biasa 34; berbobot (100x33 + 900x35) / 1000 = 34,8.
        $this->assertEqualsWithDelta(34.8, $this->performa()['umur_panen'], 0.0001);
    }

    public function test_r10_fcr_dan_ip_kosong_sebelum_ada_panen(): void
    {
        $this->isiRecording();

        $p = $this->performa();
        $this->assertNull($p['fcr']);
        $this->assertNull($p['ip']);
        $this->assertNull($p['abw']);
        $this->assertNull($p['umur_panen']);
        $this->actingAs($this->peternak)->get(route('panen.index', $this->siklus))->assertOk()->assertSee('Belum ada catatan panen');
    }

    public function test_fcr_dan_ip_sesuai_contoh_prd(): void
    {
        // 9.500 kg pakan (190 sak): lihat catatan di PerformanceCalculatorTest tentang 420 sak vs FCR 1,652 di PRD.
        $this->isiRecording();
        $this->panenContohPrd();

        $p = $this->performa();
        $this->assertEqualsWithDelta(3.20, $p['deplesi_persen'], 0.0001);
        $this->assertEqualsWithDelta(96.80, $p['liveability'], 0.0001);
        $this->assertEqualsWithDelta(1.652, $p['fcr'], 0.001);
        $this->assertEqualsWithDelta(339.2, $p['ip'], 0.05);
    }

    public function test_pakan_sisa_di_akhir_siklus_mengurangi_pakan_terpakai(): void
    {
        $this->isiRecording();
        $this->panenContohPrd();

        $this->actingAs($this->peternak)->patch(route('panen.retur', $this->siklus), ['pakan_retur_sak' => 10])
            ->assertRedirect(route('panen.index', $this->siklus));

        $p = $this->performa();
        $this->assertEqualsWithDelta(180.0, $p['pakan_bersih_sak'], 0.0001);
        $this->assertEqualsWithDelta(9000 / 5750, $p['fcr'], 0.0001);
    }

    public function test_pakan_sisa_tidak_boleh_negatif(): void
    {
        $this->actingAs($this->peternak)->patch(route('panen.retur', $this->siklus), ['pakan_retur_sak' => -1])
            ->assertSessionHasErrors('pakan_retur_sak');
    }

    public function test_f5_6_r7_selisih_populasi_ditandai_bukan_dipaksa_seimbang(): void
    {
        $this->isiRecording();

        $this->panen('2026-12-03', 2904, 5750);
        $this->assertSame(0, $this->performa()['selisih_populasi']);

        Panen::query()->delete();
        $this->panen('2026-12-03', 2900, 5750);
        $this->assertSame(4, $this->performa()['selisih_populasi']);
        $this->actingAs($this->peternak)->get(route('panen.index', $this->siklus))->assertOk()->assertSee('4 ekor belum terhitung');
    }

    public function test_selisih_negatif_memunculkan_peringatan_saat_simpan(): void
    {
        $this->isiRecording();

        $this->panen('2026-12-03', 2910, 5750)
            ->assertSessionHas('peringatan', fn ($p) => str_contains($p, 'melebihi populasi awal sebanyak 6 ekor'));

        $this->assertSame(-6, $this->performa()['selisih_populasi']);
        // Tetap tersimpan, tidak diblokir.
        $this->assertSame(1, Panen::count());
    }

    public function test_panen_bertahap_dengan_harga_berbeda_pendapatan_dijumlahkan_per_tahap(): void
    {
        $this->panen('2026-12-03', 400, 780, ['harga_per_kg' => 20500]);
        $this->panen('2026-12-04', 600, 1200, ['harga_per_kg' => 21000]);

        $this->assertEqualsWithDelta(780 * 20500 + 1200 * 21000, $this->performa()['pendapatan'], 0.01);
    }

    public function test_tahap_tanpa_harga_atau_btpa_ditandai(): void
    {
        $this->panen('2026-12-03', 400, 780, ['harga_per_kg' => '', 'nomor_btpa' => '']);

        $p = $this->performa();
        $this->assertSame(1, $p['tahap_tanpa_harga']);
        $this->assertSame(1, $p['tahap_tanpa_btpa']);
        $this->actingAs($this->peternak)->get(route('panen.index', $this->siklus))
            ->assertSee('belum diisi')->assertSee('belum ada nomor');
    }

    public function test_siklus_dihentikan_dihitung_tetapi_ditandai_tidak_sebanding(): void
    {
        $this->isiRecording();
        $this->panen('2026-12-03', 2000, 3000);
        $this->siklus->update(['status' => Siklus::STATUS_DIHENTIKAN, 'tanggal_panen' => '2026-12-03']);

        $p = $this->performa();
        $this->assertTrue($p['tidak_sebanding']);
        $this->assertNotNull($p['fcr']);
        $this->actingAs($this->peternak)->get(route('panen.index', $this->siklus))->assertSee('Tidak sebanding');
    }

    public function test_validasi_panen(): void
    {
        $this->actingAs($this->peternak)->post(route('panen.store', $this->siklus), [])
            ->assertSessionHasErrors(['tanggal', 'jumlah_ekor', 'tonase_kg']);
        $this->panen('2026-10-31', 100, 200)->assertSessionHasErrors('tanggal');
        $this->panen('2026-12-11', 100, 200)->assertSessionHasErrors('tanggal');
        $this->panen('2026-12-03', 0, 200)->assertSessionHasErrors('jumlah_ekor');
        $this->panen('2026-12-03', 100, 0)->assertSessionHasErrors('tonase_kg');
        // 100 ekor / 2.000 kg = 20 kg per ekor.
        $this->panen('2026-12-03', 100, 2000)->assertSessionHasErrors('tonase_kg');
        $this->assertSame(0, Panen::count());
    }

    public function test_ubah_dan_hapus_panen(): void
    {
        $this->panen('2026-12-03', 400, 780);
        $p = Panen::firstOrFail();

        $this->actingAs($this->peternak)->put(route('panen.update', $p), $this->dataPanen('2026-12-04', 450, 900))
            ->assertRedirect(route('panen.index', $this->siklus));

        $p->refresh();
        $this->assertSame([34, 450], [$p->umur_hari, $p->jumlah_ekor]);

        $this->actingAs($this->peternak)->delete(route('panen.destroy', $p))->assertRedirect();
        $this->assertSoftDeleted('panen', ['id' => $p->id]);
    }

    // ---- Foto BTPA (F5.3) ----

    public function test_foto_btpa_disimpan_privat_dengan_nama_acak(): void
    {
        $this->panen('2026-12-03', 400, 780, ['foto_btpa' => UploadedFile::fake()->image('btpa-asli.jpg')])->assertSessionDoesntHaveErrors();

        $path = Panen::firstOrFail()->foto_btpa;
        $this->assertStringStartsWith('btpa/', $path);
        $this->assertStringNotContainsString('btpa-asli', $path);
        Storage::disk('local')->assertExists($path);
        // Bukan di disk publik.
        Storage::disk('public')->assertMissing($path);
    }

    public function test_foto_btpa_hanya_lewat_rute_terautentikasi_dan_terotorisasi(): void
    {
        $this->panen('2026-12-03', 400, 780, ['foto_btpa' => UploadedFile::fake()->image('btpa.jpg')]);
        $p = Panen::firstOrFail();

        $this->actingAs($this->peternak)->get(route('panen.foto', $p))->assertOk();

        auth()->logout();
        $this->get(route('panen.foto', $p))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ABK]))->get(route('panen.foto', $p))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('panen.foto', $p))->assertForbidden();
    }

    public function test_foto_btpa_404_bila_tidak_ada(): void
    {
        $this->panen('2026-12-03', 400, 780);

        $this->actingAs($this->peternak)->get(route('panen.foto', Panen::firstOrFail()))->assertNotFound();
    }

    public function test_foto_btpa_menolak_berkas_bukan_gambar_atau_terlalu_besar(): void
    {
        $this->panen('2026-12-03', 400, 780, ['foto_btpa' => UploadedFile::fake()->create('rahasia.php', 5, 'text/x-php')])->assertSessionHasErrors('foto_btpa');
        $this->panen('2026-12-03', 400, 780, ['foto_btpa' => UploadedFile::fake()->create('dokumen.pdf', 50, 'application/pdf')])->assertSessionHasErrors('foto_btpa');
        $this->panen('2026-12-03', 400, 780, ['foto_btpa' => UploadedFile::fake()->image('besar.jpg')->size(5000)])->assertSessionHasErrors('foto_btpa');

        $this->assertSame(0, Panen::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_ganti_foto_menghapus_berkas_lama_dan_hapus_foto_mengosongkan(): void
    {
        $this->panen('2026-12-03', 400, 780, ['foto_btpa' => UploadedFile::fake()->image('a.jpg')]);
        $p = Panen::firstOrFail();
        $lama = $p->foto_btpa;

        $this->actingAs($this->peternak)->put(route('panen.update', $p), $this->dataPanen('2026-12-03', 400, 780, ['foto_btpa' => UploadedFile::fake()->image('b.png')]));
        $baru = $p->fresh()->foto_btpa;
        $this->assertNotSame($lama, $baru);
        Storage::disk('local')->assertMissing($lama);
        Storage::disk('local')->assertExists($baru);

        $this->actingAs($this->peternak)->put(route('panen.update', $p), $this->dataPanen('2026-12-03', 400, 780, ['hapus_foto' => 1]));
        $this->assertNull($p->fresh()->foto_btpa);
        Storage::disk('local')->assertMissing($baru);
    }

    public function test_ubah_tanpa_foto_baru_mempertahankan_foto(): void
    {
        $this->panen('2026-12-03', 400, 780, ['foto_btpa' => UploadedFile::fake()->image('a.jpg')]);
        $p = Panen::firstOrFail();
        $foto = $p->foto_btpa;

        $this->actingAs($this->peternak)->put(route('panen.update', $p), $this->dataPanen('2026-12-03', 410, 800));

        $this->assertSame($foto, $p->fresh()->foto_btpa);
        Storage::disk('local')->assertExists($foto);
    }

    public function test_hapus_panen_membiarkan_berkas_foto_untuk_pelacakan(): void
    {
        $this->panen('2026-12-03', 400, 780, ['foto_btpa' => UploadedFile::fake()->image('a.jpg')]);
        $p = Panen::firstOrFail();
        $foto = $p->foto_btpa;

        $this->actingAs($this->peternak)->delete(route('panen.destroy', $p));

        Storage::disk('local')->assertExists($foto);
    }

    // ---- Penutupan siklus, rumus IP, otorisasi ----

    public function test_menutup_siklus_tanpa_panen_diperingatkan(): void
    {
        $this->actingAs($this->peternak)->patch(route('siklus.selesai', $this->siklus), ['status' => 'selesai', 'tanggal_panen' => '2026-12-05'])
            ->assertSessionHas('peringatan', fn ($p) => str_contains($p, 'Belum ada catatan panen'));
    }

    public function test_menutup_siklus_dengan_selisih_populasi_diperingatkan(): void
    {
        $this->isiRecording();
        $this->panen('2026-12-03', 2800, 5500);

        $this->actingAs($this->peternak)->patch(route('siklus.selesai', $this->siklus), ['status' => 'selesai', 'tanggal_panen' => '2026-12-05'])
            ->assertSessionHas('peringatan', fn ($p) => str_contains($p, 'berselisih 104 ekor'));
    }

    public function test_menutup_siklus_seimbang_tanpa_peringatan(): void
    {
        $this->isiRecording();
        $this->panen('2026-12-03', 2904, 5750);

        $this->actingAs($this->peternak)->patch(route('siklus.selesai', $this->siklus), ['status' => 'selesai', 'tanggal_panen' => '2026-12-05'])
            ->assertSessionMissing('peringatan');
    }

    public function test_rumus_ip_disimpan_per_siklus_dan_nama_tidak_dikenal_ditolak(): void
    {
        $payload = ['tanggal_masuk' => '2026-11-01', 'populasi' => 3000, 'bobot_sak_kg' => 50, 'kontrak' => []];

        $this->actingAs($this->peternak)->put(route('siklus.update', $this->siklus), $payload + ['rumus_ip' => 'standar'])->assertSessionDoesntHaveErrors();
        $this->assertSame('standar', $this->siklus->fresh()->rumus_ip);

        $this->actingAs($this->peternak)->put(route('siklus.update', $this->siklus), $payload + ['rumus_ip' => 'karangan'])->assertSessionHasErrors('rumus_ip');
        $this->assertSame('standar', $this->siklus->fresh()->rumus_ip);
    }

    public function test_siklus_tanpa_rumus_ip_di_form_tetap_memakai_bawaan(): void
    {
        $payload = ['tanggal_masuk' => '2026-11-01', 'populasi' => 3000, 'bobot_sak_kg' => 50, 'kontrak' => []];

        $this->actingAs($this->peternak)->put(route('siklus.update', $this->siklus), $payload)->assertSessionDoesntHaveErrors();

        $this->assertSame('standar', $this->siklus->fresh()->rumus_ip);
    }

    public function test_rumus_ip_tidak_dikenal_di_kalkulator_gagal_keras(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new PerformanceCalculator)->indeksPerforma(96.8, 1.98, 1.652, 34.2, 'karangan');
    }

    public function test_panen_akses_abk_dan_peternak_lain_ditolak(): void
    {
        $this->panen('2026-12-03', 400, 780);
        $p = Panen::firstOrFail();

        foreach ([User::factory()->create(['role' => User::ROLE_ABK]), User::factory()->create()] as $user) {
            $this->actingAs($user)->get(route('panen.index', $this->siklus))->assertForbidden();
            $this->actingAs($user)->post(route('panen.store', $this->siklus), $this->dataPanen('2026-12-04', 100, 200))->assertForbidden();
            $this->actingAs($user)->patch(route('panen.retur', $this->siklus), ['pakan_retur_sak' => 1])->assertForbidden();
            $this->actingAs($user)->get(route('panen.edit', $p))->assertForbidden();
            $this->actingAs($user)->put(route('panen.update', $p), $this->dataPanen('2026-12-04', 100, 200))->assertForbidden();
            $this->actingAs($user)->delete(route('panen.destroy', $p))->assertForbidden();
        }
    }

    // ---- Siklus contoh seeder ----

    public function test_siklus_contoh_menghasilkan_angka_prd(): void
    {
        $this->seed(SiklusContohSeeder::class);
        $siklus = Siklus::where('nama_inti', 'PT Inti Contoh')->firstOrFail();

        $p = $this->performa($siklus);

        $this->assertEqualsWithDelta(1.652, $p['fcr'], 0.001);
        $this->assertEqualsWithDelta(339.2, $p['ip'], 0.05);
        $this->assertEqualsWithDelta(1.98, $p['abw'], 0.001);
        $this->assertEqualsWithDelta(34.2, $p['umur_panen'], 0.001);
        $this->assertSame(0, $p['selisih_populasi']);
        $this->assertSame(96, $p['deplesi']);
        $this->assertEqualsWithDelta(780 * 20500 + 3015.54 * 20500 + 1954.46 * 20800, $p['pendapatan'], 0.5);
    }

    public function test_halaman_panen_dapat_dirender(): void
    {
        $this->panen('2026-12-03', 400, 780, ['foto_btpa' => UploadedFile::fake()->image('a.jpg')]);

        foreach ([route('panen.index', $this->siklus), route('panen.edit', Panen::firstOrFail()), route('siklus.show', $this->siklus), route('siklus.edit', $this->siklus)] as $url) {
            $this->actingAs($this->peternak)->get($url)->assertOk();
        }
    }
}
