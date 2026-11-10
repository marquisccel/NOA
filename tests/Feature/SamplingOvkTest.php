<?php

namespace Tests\Feature;

use App\Models\Kandang;
use App\Models\PemberianOvk;
use App\Models\RecordingHarian;
use App\Models\SamplingBobot;
use App\Models\Siklus;
use App\Models\User;
use App\Services\RingkasanRecording;
use App\Services\RingkasanSampling;
use Database\Seeders\SiklusContohSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SamplingOvkTest extends TestCase
{
    use RefreshDatabase;

    private User $peternak;

    private Siklus $siklus;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-15 08:00:00');

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

    private function sampling(string $tanggal, int $jumlah, float $totalKg, ?Siklus $siklus = null)
    {
        return $this->actingAs($this->peternak)->post(
            route('sampling.store', $siklus ?? $this->siklus),
            ['tanggal' => $tanggal, 'jumlah_sampel' => $jumlah, 'total_bobot_kg' => $totalKg],
        );
    }

    private function ovk(array $override = [])
    {
        return $this->actingAs($this->peternak)->post(route('ovk.store', $this->siklus), array_merge([
            'tanggal' => '2026-11-04', 'jenis' => 'vaksin', 'nama_produk' => 'ND-IB', 'dosis' => '1 dosis/ekor', 'biaya' => 150000,
        ], $override));
    }

    /** @return list<array<string, mixed>> */
    private function barisSampling(Siklus $siklus): array
    {
        return app(RingkasanSampling::class)->baris($siklus, $siklus->samplings()->get(), $siklus->standarHarian()->get());
    }

    // ---- Sampling bobot (F3.x) ----

    public function test_f3_1_f3_2_sampling_menyimpan_dan_menghitung_abw(): void
    {
        // Hari ke-7: 150 ekor, 27 kg -> 180 g.
        $this->sampling('2026-11-07', 150, 27)->assertRedirect(route('sampling.index', $this->siklus));

        $s = SamplingBobot::firstOrFail();
        $this->assertSame(7, $s->hari_ke);
        $this->assertSame(150, $s->jumlah_sampel);
        $this->assertEqualsWithDelta(180.0, $this->barisSampling($this->siklus)[0]['abw_gram'], 0.0001);
    }

    public function test_abw_dan_adg_ditampilkan_setelah_simpan(): void
    {
        $this->sampling('2026-11-07', 150, 27);

        $this->sampling('2026-11-14', 150, 67.5)
            ->assertSessionHas('status', fn ($p) => str_contains($p, 'ABW 450 g') && str_contains($p, 'pertambahan 38,6 g/hari'));
    }

    public function test_f3_4_adg_terhadap_sampling_sebelumnya(): void
    {
        $this->sampling('2026-11-07', 150, 27);
        $this->sampling('2026-11-14', 150, 67.5);

        $baris = $this->barisSampling($this->siklus);
        $this->assertNull($baris[0]['adg_gram']);
        // (450 - 180) g / 7 hari.
        $this->assertEqualsWithDelta(270 / 7, $baris[1]['adg_gram'], 0.0001);
    }

    public function test_adg_kosong_bila_dua_sampling_di_hari_yang_sama(): void
    {
        $this->sampling('2026-11-07', 150, 27);
        $this->sampling('2026-11-07', 150, 28.5);

        $this->assertNull($this->barisSampling($this->siklus)[1]['adg_gram']);
    }

    public function test_f3_3_sampel_di_bawah_5_persen_diperingatkan_tetapi_tetap_tersimpan(): void
    {
        $this->sampling('2026-11-07', 100, 18)
            ->assertSessionHas('peringatan', fn ($p) => str_contains($p, 'di bawah batas 5 persen'))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(1, SamplingBobot::count());
        $this->assertFalse($this->barisSampling($this->siklus)[0]['representatif']);
    }

    public function test_sampel_tepat_5_persen_dianggap_representatif(): void
    {
        $this->sampling('2026-11-07', 150, 27)->assertSessionMissing('peringatan');

        $this->assertTrue($this->barisSampling($this->siklus)[0]['representatif']);
    }

    public function test_persentase_dihitung_terhadap_ayam_hidup_pada_hari_itu(): void
    {
        // 3.000 - 1.000 deplesi = 2.000 hidup; 100 sampel = 5 persen.
        RecordingHarian::create(['siklus_id' => $this->siklus->id, 'tanggal' => '2026-11-03', 'hari_ke' => 3, 'mati' => 1000]);

        $this->sampling('2026-11-07', 100, 18)->assertSessionMissing('peringatan');

        $b = $this->barisSampling($this->siklus)[0];
        $this->assertSame(2000, $b['ayam_hidup']);
        $this->assertEqualsWithDelta(5.0, $b['persen_populasi'], 0.0001);
    }

    public function test_jumlah_sampel_tidak_boleh_melebihi_ayam_hidup(): void
    {
        RecordingHarian::create(['siklus_id' => $this->siklus->id, 'tanggal' => '2026-11-03', 'hari_ke' => 3, 'mati' => 2950]);

        $this->sampling('2026-11-07', 51, 10)->assertSessionHasErrors('jumlah_sampel');
        $this->assertSame(0, SamplingBobot::count());
    }

    public function test_bobot_per_ekor_tidak_masuk_akal_ditolak(): void
    {
        // 180 gram salah ketik jadi 180 kg total untuk 150 ekor = 1,2 kg masuk akal; 1.800 kg = 12 kg tidak.
        $this->sampling('2026-11-07', 150, 1800)->assertSessionHasErrors('total_bobot_kg');
    }

    public function test_sampling_wajib_diisi_dan_tanggal_dibatasi(): void
    {
        $this->actingAs($this->peternak)->post(route('sampling.store', $this->siklus), [])
            ->assertSessionHasErrors(['tanggal', 'jumlah_sampel', 'total_bobot_kg']);
        $this->sampling('2026-10-31', 150, 27)->assertSessionHasErrors('tanggal');
        $this->sampling('2026-11-16', 150, 27)->assertSessionHasErrors('tanggal');
        $this->sampling('2026-11-07', 0, 27)->assertSessionHasErrors('jumlah_sampel');
        $this->sampling('2026-11-07', 150, 0)->assertSessionHasErrors('total_bobot_kg');
    }

    public function test_f3_5_abw_dibandingkan_dengan_standar_strain_pada_umur_sama(): void
    {
        $this->siklus->standarHarian()->create(['hari_ke' => 7, 'pakan_std_sak' => 5, 'bobot_std_gram' => 200, 'fcr_std' => 0.8, 'feed_intake_std_gram' => 100]);
        $this->sampling('2026-11-07', 150, 27);

        $b = $this->barisSampling($this->siklus)[0];
        $this->assertEqualsWithDelta(200.0, $b['std_gram'], 0.0001);
        $this->assertEqualsWithDelta(-20.0, $b['selisih_std_gram'], 0.0001);
        $this->assertEqualsWithDelta(-10.0, $b['selisih_std_persen'], 0.0001);
    }

    public function test_tanpa_standar_perbandingan_kosong_dan_sistem_tetap_jalan(): void
    {
        $this->sampling('2026-11-07', 150, 27);

        $b = $this->barisSampling($this->siklus)[0];
        $this->assertNull($b['std_gram']);
        $this->assertNull($b['selisih_std_gram']);
        $this->actingAs($this->peternak)->get(route('sampling.index', $this->siklus))
            ->assertOk()->assertSee('Tabel standar belum diunggah');
    }

    public function test_ubah_dan_hapus_sampling(): void
    {
        $this->sampling('2026-11-07', 150, 27);
        $s = SamplingBobot::firstOrFail();

        $this->actingAs($this->peternak)->put(route('sampling.update', $s), ['tanggal' => '2026-11-08', 'jumlah_sampel' => 160, 'total_bobot_kg' => 32])
            ->assertRedirect(route('sampling.index', $this->siklus));

        $s->refresh();
        $this->assertSame([8, 160], [$s->hari_ke, $s->jumlah_sampel]);

        $this->actingAs($this->peternak)->delete(route('sampling.destroy', $s))->assertRedirect();
        $this->assertSoftDeleted('sampling_bobot', ['id' => $s->id]);
    }

    public function test_abw_sampling_dipakai_untuk_fcr_berjalan_bila_recording_tanpa_bobot(): void
    {
        // 14 sak x 50 kg = 700 kg pakan; hidup 3.000; ABW sampel 180 g -> biomassa 540 kg.
        RecordingHarian::create(['siklus_id' => $this->siklus->id, 'tanggal' => '2026-11-07', 'hari_ke' => 7, 'pakan_pakai_sak' => 14]);
        $this->sampling('2026-11-07', 150, 27);

        $baris = app(RingkasanRecording::class)->baris(
            $this->siklus, $this->siklus->recordings()->get(), collect(), collect(), $this->siklus->samplings()->get(),
        );

        $this->assertEqualsWithDelta(180.0, $baris[7]['bobot_gram'], 0.0001);
        $this->assertEqualsWithDelta(700 / 540, $baris[7]['fcr_berjalan'], 0.0001);
    }

    public function test_bobot_aktual_recording_didahulukan_dari_sampling(): void
    {
        RecordingHarian::create(['siklus_id' => $this->siklus->id, 'tanggal' => '2026-11-07', 'hari_ke' => 7, 'pakan_pakai_sak' => 14, 'bobot_aktual_gram' => 200]);
        $this->sampling('2026-11-07', 150, 27);

        $baris = app(RingkasanRecording::class)->baris(
            $this->siklus, $this->siklus->recordings()->get(), collect(), collect(), $this->siklus->samplings()->get(),
        );

        $this->assertEqualsWithDelta(200.0, $baris[7]['bobot_gram'], 0.0001);
    }

    public function test_sampling_pada_hari_lain_tidak_dipinjam_untuk_fcr(): void
    {
        RecordingHarian::create(['siklus_id' => $this->siklus->id, 'tanggal' => '2026-11-07', 'hari_ke' => 7, 'pakan_pakai_sak' => 14]);
        $this->sampling('2026-11-06', 150, 20);

        $baris = app(RingkasanRecording::class)->baris(
            $this->siklus, $this->siklus->recordings()->get(), collect(), collect(), $this->siklus->samplings()->get(),
        );

        $this->assertNull($baris[7]['fcr_berjalan']);
    }

    public function test_siklus_contoh_sampling_sama_dengan_standar(): void
    {
        $this->seed(SiklusContohSeeder::class);
        $siklus = Siklus::where('nama_inti', 'PT Inti Contoh')->firstOrFail();

        $baris = $this->barisSampling($siklus);

        $this->assertCount(4, $baris);
        foreach ($baris as $b) {
            $this->assertTrue($b['representatif'], 'Seeder memakai 150 sampel (5 persen dari populasi).');
            $this->assertEqualsWithDelta(0.0, $b['selisih_std_gram'], 0.01);
        }
        $this->assertNotNull($baris[1]['adg_gram']);
    }

    public function test_sampling_akses_abk_dan_peternak_lain_ditolak(): void
    {
        $this->sampling('2026-11-07', 150, 27);
        $s = SamplingBobot::firstOrFail();
        $abk = User::factory()->abk()->create();
        $lain = User::factory()->create();

        foreach ([$abk, $lain] as $user) {
            $this->actingAs($user)->get(route('sampling.index', $this->siklus))->assertForbidden();
            $this->actingAs($user)->post(route('sampling.store', $this->siklus), ['tanggal' => '2026-11-08', 'jumlah_sampel' => 150, 'total_bobot_kg' => 27])->assertForbidden();
            $this->actingAs($user)->get(route('sampling.edit', $s))->assertForbidden();
            $this->actingAs($user)->delete(route('sampling.destroy', $s))->assertForbidden();
        }
    }

    // ---- OVK (F4.x) ----

    public function test_f4_1_catat_pemberian_obat_vaksin_kimia(): void
    {
        foreach (PemberianOvk::JENIS as $i => $jenis) {
            $this->ovk(['jenis' => $jenis, 'nama_produk' => "Produk {$jenis}", 'tanggal' => '2026-11-0'.($i + 2)])->assertSessionDoesntHaveErrors();
        }

        $this->assertSame(3, PemberianOvk::count());
        $this->assertDatabaseHas('pemberian_ovk', ['jenis' => 'kimia', 'nama_produk' => 'Produk kimia']);
    }

    public function test_jenis_di_luar_daftar_ditolak(): void
    {
        $this->ovk(['jenis' => 'jamu'])->assertSessionHasErrors('jenis');
    }

    public function test_ovk_wajib_punya_tanggal_dan_nama_produk(): void
    {
        $this->actingAs($this->peternak)->post(route('ovk.store', $this->siklus), ['jenis' => 'obat'])
            ->assertSessionHasErrors(['tanggal', 'nama_produk']);
        $this->ovk(['tanggal' => '2026-10-31'])->assertSessionHasErrors('tanggal');
        $this->ovk(['tanggal' => '2026-11-16'])->assertSessionHasErrors('tanggal');
    }

    public function test_biaya_kosong_dianggap_nol_dan_negatif_ditolak(): void
    {
        $this->ovk(['biaya' => ''])->assertSessionDoesntHaveErrors();
        $this->assertSame('0.00', PemberianOvk::firstOrFail()->biaya);

        $this->ovk(['biaya' => -1])->assertSessionHasErrors('biaya');
    }

    public function test_f4_2_biaya_dijumlahkan_per_jenis_dan_total(): void
    {
        $this->ovk(['jenis' => 'vaksin', 'biaya' => 150000]);
        $this->ovk(['jenis' => 'vaksin', 'nama_produk' => 'Gumboro', 'biaya' => 175000]);
        $this->ovk(['jenis' => 'obat', 'nama_produk' => 'Vitamin', 'biaya' => 90000.5]);

        $this->actingAs($this->peternak)->get(route('ovk.index', $this->siklus))
            ->assertOk()
            ->assertViewHas('totalBiaya', 415000.5)
            ->assertViewHas('biayaPerJenis', fn ($b) => $b['vaksin'] === 325000.0 && $b['obat'] === 90000.5 && ! $b->has('kimia'))
            ->assertSee('Rp 415.001');
    }

    public function test_f4_3_riwayat_satu_siklus_satu_halaman_terbaru_di_atas(): void
    {
        $this->ovk(['tanggal' => '2026-11-04', 'nama_produk' => 'Pertama']);
        $this->ovk(['tanggal' => '2026-11-10', 'nama_produk' => 'Kedua']);
        // OVK siklus lain tidak ikut tampil.
        $lain = $this->buatSiklus($this->peternak);
        $lain->pemberianOvk()->create(['tanggal' => '2026-11-05', 'jenis' => 'obat', 'nama_produk' => 'SiklusLain', 'biaya' => 1]);

        $this->actingAs($this->peternak)->get(route('ovk.index', $this->siklus))
            ->assertOk()->assertSeeInOrder(['Kedua', 'Pertama'])->assertDontSee('SiklusLain');
    }

    public function test_ubah_dan_hapus_ovk(): void
    {
        $this->ovk();
        $o = PemberianOvk::firstOrFail();

        $this->actingAs($this->peternak)->put(route('ovk.update', $o), ['tanggal' => '2026-11-05', 'jenis' => 'obat', 'nama_produk' => 'Vitamin', 'biaya' => 5000])
            ->assertRedirect(route('ovk.index', $this->siklus));
        $this->assertSame('Vitamin', $o->fresh()->nama_produk);

        $this->actingAs($this->peternak)->delete(route('ovk.destroy', $o))->assertRedirect();
        $this->assertSoftDeleted('pemberian_ovk', ['id' => $o->id]);
    }

    public function test_ovk_akses_abk_dan_peternak_lain_ditolak(): void
    {
        $this->ovk();
        $o = PemberianOvk::firstOrFail();
        $abk = User::factory()->abk()->create();
        $lain = User::factory()->create();

        foreach ([$abk, $lain] as $user) {
            $this->actingAs($user)->get(route('ovk.index', $this->siklus))->assertForbidden();
            $this->actingAs($user)->post(route('ovk.store', $this->siklus), ['tanggal' => '2026-11-05', 'jenis' => 'obat', 'nama_produk' => 'X', 'biaya' => 1])->assertForbidden();
            $this->actingAs($user)->get(route('ovk.edit', $o))->assertForbidden();
            $this->actingAs($user)->delete(route('ovk.destroy', $o))->assertForbidden();
        }
    }

    public function test_halaman_sampling_dan_ovk_dapat_dirender(): void
    {
        $this->sampling('2026-11-07', 150, 27);
        $this->ovk();

        foreach ([
            route('sampling.index', $this->siklus), route('sampling.edit', SamplingBobot::firstOrFail()),
            route('ovk.index', $this->siklus), route('ovk.edit', PemberianOvk::firstOrFail()),
            route('siklus.show', $this->siklus),
        ] as $url) {
            $this->actingAs($this->peternak)->get($url)->assertOk();
        }
    }
}
