<?php

namespace Tests\Feature;

use App\Exceptions\RecordingTerkunci;
use App\Models\Kandang;
use App\Models\RecordingHarian;
use App\Models\Siklus;
use App\Models\User;
use App\Services\RingkasanRecording;
use Database\Seeders\SiklusContohSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecordingHarianTest extends TestCase
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

    private function buatSiklus(User $user, string $status = Siklus::STATUS_BERJALAN): Siklus
    {
        $kandang = Kandang::create(['user_id' => $user->id, 'nama_farm' => 'Uji', 'kapasitas' => 4000]);

        return Siklus::create([
            'kandang_id' => $kandang->id,
            'tanggal_masuk' => '2026-11-01',
            'populasi' => 3000,
            'bobot_sak_kg' => 50,
            'status' => $status,
        ]);
    }

    /** @return array<string, mixed> */
    private function entri(string $tanggal, array $override = []): array
    {
        return array_merge(['tanggal' => $tanggal, 'pakan_pakai_sak' => 14, 'mati' => 7, 'afkir' => 2, 'sebab_afkir' => 'sakit'], $override);
    }

    private function simpan(string $tanggal, array $override = [], ?Siklus $siklus = null)
    {
        return $this->actingAs($this->peternak)
            ->post(route('recording.store', $siklus ?? $this->siklus), $this->entri($tanggal, $override));
    }

    public function test_entri_hari_pertama_menghitung_hari_ke_dan_deplesi(): void
    {
        $this->simpan('2026-11-01', ['pakan_masuk_sak' => 20])
            ->assertRedirect(route('recording.index', $this->siklus));

        $r = RecordingHarian::firstOrFail();
        $this->assertSame(1, $r->hari_ke);
        $this->assertSame(9, (int) $r->fresh()->deplesi_harian);
        $this->assertSame('14.00', $r->pakan_pakai_sak);
    }

    public function test_angka_berjalan_muncul_setelah_simpan(): void
    {
        // 9 ekor / 3.000 = 0,30 persen; tanpa bobot aktual FCR belum bisa dihitung.
        $this->simpan('2026-11-01')
            ->assertSessionHas('status', fn ($pesan) => str_contains($pesan, 'Deplesi kumulatif 0,30 persen')
                && str_contains($pesan, 'FCR berjalan belum bisa dihitung'));

        $this->simpan('2026-11-02', ['bobot_aktual_gram' => 60])
            ->assertSessionHas('status', fn ($pesan) => str_contains($pesan, 'FCR berjalan'));
    }

    public function test_hanya_empat_bidang_wajib_mati_dan_afkir_kosong_berarti_nol(): void
    {
        $this->actingAs($this->peternak)
            ->post(route('recording.store', $this->siklus), ['tanggal' => '2026-11-01', 'pakan_pakai_sak' => 10, 'mati' => '', 'afkir' => ''])
            ->assertSessionHasNoErrors();

        $r = RecordingHarian::firstOrFail();
        $this->assertSame([0, 0], [$r->mati, $r->afkir]);
        $this->assertSame('0.00', $r->pakan_masuk_sak);
    }

    public function test_pakan_terpakai_dan_tanggal_wajib(): void
    {
        $this->actingAs($this->peternak)
            ->post(route('recording.store', $this->siklus), ['mati' => 1, 'afkir' => 0])
            ->assertSessionHasErrors(['tanggal', 'pakan_pakai_sak']);
    }

    public function test_afkir_wajib_punya_sebab(): void
    {
        $this->simpan('2026-11-01', ['sebab_afkir' => ''])->assertSessionHasErrors('sebab_afkir');
        $this->simpan('2026-11-01', ['sebab_afkir' => 'iseng'])->assertSessionHasErrors('sebab_afkir');
    }

    public function test_sebab_dibuang_bila_tidak_ada_afkir(): void
    {
        $this->simpan('2026-11-01', ['afkir' => 0, 'sebab_afkir' => 'sakit']);

        $this->assertNull(RecordingHarian::firstOrFail()->sebab_afkir);
    }

    public function test_f2_13_entri_ganda_pada_hari_umur_sama_ditolak(): void
    {
        $this->simpan('2026-11-03')->assertSessionDoesntHaveErrors();
        $this->simpan('2026-11-03')->assertSessionHasErrors('tanggal');

        $this->assertSame(1, RecordingHarian::count());
    }

    public function test_tanggal_sebelum_masuk_atau_di_masa_depan_ditolak(): void
    {
        $this->simpan('2026-10-31')->assertSessionHasErrors('tanggal');
        $this->simpan('2026-11-16')->assertSessionHasErrors('tanggal');
    }

    public function test_deplesi_tidak_boleh_melebihi_populasi(): void
    {
        $this->simpan('2026-11-01', ['mati' => 2990, 'afkir' => 0, 'sebab_afkir' => null])->assertSessionDoesntHaveErrors();
        $this->simpan('2026-11-02', ['mati' => 11, 'afkir' => 0, 'sebab_afkir' => null])->assertSessionHasErrors('mati');
    }

    public function test_f2_14_stok_negatif_hanya_peringatan_bukan_penghalang(): void
    {
        $this->simpan('2026-11-01', ['pakan_masuk_sak' => 10, 'pakan_pakai_sak' => 12])
            ->assertSessionHas('peringatan')
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(1, RecordingHarian::count());
    }

    public function test_stok_tidak_disimpan_tetapi_diturunkan_dari_akumulasi(): void
    {
        $this->simpan('2026-11-01', ['pakan_masuk_sak' => 50, 'pakan_pakai_sak' => 10]);
        $this->simpan('2026-11-02', ['pakan_pakai_sak' => 12]);

        $this->assertFalse(\Schema::hasColumn('recording_harian', 'stok_sak'));

        $baris = $this->hitung($this->siklus);
        $this->assertEqualsWithDelta(40.0, $baris[1]['stok_sak'], 0.001);
        $this->assertEqualsWithDelta(28.0, $baris[2]['stok_sak'], 0.001);
    }

    public function test_f2_15_entri_mundur_dan_hari_terlewat_ditampilkan(): void
    {
        $this->simpan('2026-11-05');
        $this->simpan('2026-11-03');

        $this->assertSame([3, 5], RecordingHarian::orderBy('hari_ke')->pluck('hari_ke')->all());

        $this->actingAs($this->peternak)->get(route('recording.index', $this->siklus))
            ->assertOk()->assertSee('Belum diisi');
    }

    public function test_form_entri_menawarkan_hari_pertama_yang_terlewat(): void
    {
        $this->simpan('2026-11-01');

        $this->actingAs($this->peternak)->get(route('recording.create', $this->siklus))
            ->assertOk()->assertSee('Hari ke-2');
    }

    public function test_form_entri_untuk_hari_yang_sudah_ada_mengarah_ke_ubah(): void
    {
        $this->simpan('2026-11-01');

        $this->actingAs($this->peternak)->get(route('recording.create', [$this->siklus, 'tanggal' => '2026-11-01']))
            ->assertRedirect(route('recording.edit', RecordingHarian::firstOrFail()));
    }

    public function test_ubah_entri(): void
    {
        $this->simpan('2026-11-01');
        $r = RecordingHarian::firstOrFail();

        $this->actingAs($this->peternak)->put(route('recording.update', $r), ['pakan_pakai_sak' => 15, 'mati' => 3, 'afkir' => 0])
            ->assertRedirect(route('recording.index', $this->siklus));

        $r->refresh();
        $this->assertSame('15.00', $r->pakan_pakai_sak);
        $this->assertSame(3, (int) $r->deplesi_harian);
        $this->assertSame(1, $r->hari_ke);
    }

    public function test_hapus_entri_memakai_soft_delete(): void
    {
        $this->simpan('2026-11-01');
        $r = RecordingHarian::firstOrFail();

        $this->actingAs($this->peternak)->delete(route('recording.destroy', $r))->assertRedirect();

        $this->assertSoftDeleted('recording_harian', ['id' => $r->id]);
    }

    public function test_f2_11_r11_baris_diparaf_terkunci_di_antarmuka_dan_model(): void
    {
        $this->simpan('2026-11-01');
        $r = RecordingHarian::firstOrFail();

        $this->actingAs($this->peternak)
            ->patch(route('recording.paraf', $r), ['tanggal_paraf' => '2026-11-02'])
            ->assertRedirect(route('recording.index', $this->siklus));

        $r->refresh();
        $this->assertTrue($r->diparaf_ppl);
        $this->assertSame('2026-11-02', $r->tanggal_paraf->toDateString());

        $this->actingAs($this->peternak)->put(route('recording.update', $r), ['pakan_pakai_sak' => 99, 'mati' => 0, 'afkir' => 0])->assertForbidden();
        $this->actingAs($this->peternak)->delete(route('recording.destroy', $r))->assertForbidden();
        $this->actingAs($this->peternak)->get(route('recording.edit', $r))->assertRedirect(route('recording.index', $this->siklus));

        // Penjaga di tingkat model, bukan hanya kebijakan.
        $this->expectException(RecordingTerkunci::class);
        $r->update(['pakan_pakai_sak' => 99]);
    }

    public function test_r11_hapus_baris_diparaf_ditolak_model(): void
    {
        $this->simpan('2026-11-01', ['diparaf_ppl' => true]);
        $r = RecordingHarian::firstOrFail();
        $r->update(['diparaf_ppl' => true, 'tanggal_paraf' => '2026-11-02']);

        $this->expectException(RecordingTerkunci::class);
        $r->fresh()->delete();
    }

    public function test_tanggal_paraf_tidak_boleh_sebelum_tanggal_recording(): void
    {
        $this->simpan('2026-11-05');

        $this->actingAs($this->peternak)
            ->patch(route('recording.paraf', RecordingHarian::firstOrFail()), ['tanggal_paraf' => '2026-11-02'])
            ->assertSessionHasErrors('tanggal_paraf');
    }

    public function test_r12_siklus_selesai_menolak_entri_baru_dan_sunting(): void
    {
        $this->simpan('2026-11-01');
        $r = RecordingHarian::firstOrFail();
        $this->siklus->update(['status' => Siklus::STATUS_SELESAI, 'tanggal_panen' => '2026-11-10']);

        $this->simpan('2026-11-02')->assertForbidden();
        $this->actingAs($this->peternak)->put(route('recording.update', $r), ['pakan_pakai_sak' => 1, 'mati' => 0, 'afkir' => 0])->assertForbidden();
        $this->actingAs($this->peternak)->delete(route('recording.destroy', $r))->assertForbidden();

        $this->assertSame(1, RecordingHarian::count());
    }

    public function test_r12_penjaga_model_menolak_penyuntingan_siklus_selesai(): void
    {
        $this->simpan('2026-11-01');
        $r = RecordingHarian::firstOrFail();
        $this->siklus->update(['status' => Siklus::STATUS_SELESAI, 'tanggal_panen' => '2026-11-10']);

        $this->expectException(RecordingTerkunci::class);
        $r->fresh()->update(['mati' => 500]);
    }

    public function test_r12_entri_baru_lewat_model_ditolak_pada_siklus_selesai(): void
    {
        $this->siklus->update(['status' => Siklus::STATUS_SELESAI, 'tanggal_panen' => '2026-11-10']);

        $this->expectException(RecordingTerkunci::class);
        RecordingHarian::create(['siklus_id' => $this->siklus->id, 'tanggal' => '2026-11-01', 'hari_ke' => 1]);
    }

    public function test_r12_paraf_tetap_boleh_setelah_siklus_selesai(): void
    {
        $this->simpan('2026-11-01');
        $r = RecordingHarian::firstOrFail();
        $this->siklus->update(['status' => Siklus::STATUS_SELESAI, 'tanggal_panen' => '2026-11-10']);

        $this->actingAs($this->peternak)->get(route('recording.edit', $r))->assertOk()->assertSee('Siklus sudah selesai');
        $this->actingAs($this->peternak)->patch(route('recording.paraf', $r), ['tanggal_paraf' => '2026-11-12'])->assertSessionHasNoErrors();

        $this->assertTrue($r->fresh()->diparaf_ppl);
    }

    public function test_siklus_dihentikan_tidak_ikut_terkunci(): void
    {
        $this->siklus->update(['status' => Siklus::STATUS_DIHENTIKAN, 'tanggal_panen' => '2026-11-10']);

        $this->simpan('2026-11-02')->assertSessionDoesntHaveErrors();
        $this->assertSame(1, RecordingHarian::count());
    }

    public function test_abk_dan_peternak_lain_tidak_bisa_mengakses(): void
    {
        $this->simpan('2026-11-01');
        $r = RecordingHarian::firstOrFail();

        $abk = User::factory()->abk()->create();
        $lain = User::factory()->create();

        $this->actingAs($abk)->get(route('recording.index', $this->siklus))->assertForbidden();
        $this->actingAs($lain)->get(route('recording.index', $this->siklus))->assertForbidden();
        $this->actingAs($lain)->get(route('recording.edit', $r))->assertForbidden();
        $this->actingAs($lain)->post(route('recording.store', $this->siklus), $this->entri('2026-11-02'))->assertForbidden();
    }

    public function test_status_suhu_dibandingkan_dengan_rentang_umur(): void
    {
        $this->siklus->parameterUmur()->create(['umur_awal' => 1, 'umur_akhir' => 7, 'suhu_min' => 30, 'suhu_maks' => 33]);
        $this->simpan('2026-11-01', ['suhu_pagi' => 28, 'suhu_sore' => 31.5]);

        $baris = $this->hitung($this->siklus);
        $this->assertSame('di bawah target', $baris[1]['suhu_pagi']['status']);
        $this->assertSame('sesuai target', $baris[1]['suhu_sore']['status']);
        $this->assertSame('30–33', $baris[1]['suhu_pagi']['target']);
    }

    public function test_suhu_tanpa_target_umur_tidak_dinilai(): void
    {
        $this->simpan('2026-11-01', ['suhu_pagi' => 28]);

        $this->assertNull($this->hitung($this->siklus)[1]['suhu_pagi']);
    }

    public function test_fcr_berjalan_memakai_bobot_aktual(): void
    {
        // 14 sak x 50 kg = 700 kg pakan; ayam hidup 2.991 x 0,5 kg = 1.495,5 kg.
        $this->simpan('2026-11-01', ['bobot_aktual_gram' => 500]);

        $this->assertEqualsWithDelta(700 / 1495.5, $this->hitung($this->siklus)[1]['fcr_berjalan'], 0.0001);
    }

    public function test_perbandingan_dengan_standar_dan_feed_intake(): void
    {
        $this->siklus->standarHarian()->create(['hari_ke' => 1, 'pakan_std_sak' => 0.96, 'bobot_std_gram' => 42, 'fcr_std' => 0.23, 'feed_intake_std_gram' => 14.4]);
        $this->simpan('2026-11-01');

        $baris = $this->hitung($this->siklus)[1];
        $this->assertSame('0.96', $baris['standar']->pakan_std_sak);
        // 700 kg / 2.991 ekor = 234,03 g/ekor.
        $this->assertEqualsWithDelta(700000 / 2991, $baris['feed_intake_gram'], 0.001);
    }

    public function test_tanpa_tabel_standar_kolom_standar_kosong_dan_sistem_tetap_berjalan(): void
    {
        $this->simpan('2026-11-01');

        $this->assertNull($this->hitung($this->siklus)[1]['standar']);
        $this->actingAs($this->peternak)->get(route('recording.index', $this->siklus))
            ->assertOk()->assertSee('Tabel standar belum diunggah');
    }

    public function test_siklus_contoh_angka_berjalan_dan_subtotal_mingguan(): void
    {
        $this->seed(SiklusContohSeeder::class);
        $siklus = Siklus::where('nama_inti', 'PT Inti Contoh')->firstOrFail();

        $ringkasan = app(RingkasanRecording::class);
        $baris = $this->hitung($siklus);
        $akhir = $baris[35];

        $this->assertSame(96, $akhir['deplesi_kum']);
        $this->assertEqualsWithDelta(3.20, $akhir['deplesi_kum_persen'], 0.0001);
        $this->assertEqualsWithDelta(190.0, $akhir['pakan_kum_sak'], 0.001);
        $this->assertEqualsWithDelta(195 - 190, $akhir['stok_sak'], 0.001);
        $this->assertEqualsWithDelta(9500 * 1000 / 2904, $akhir['feed_intake_gram'], 0.01);

        $mingguan = $ringkasan->mingguan($baris);
        $this->assertCount(5, $mingguan);
        $this->assertEqualsWithDelta(190.0, array_sum(array_column($mingguan, 'pakan_pakai_sak')), 0.001);
        $this->assertSame(96, array_sum(array_column($mingguan, 'deplesi')));
    }

    public function test_halaman_recording_dapat_dirender(): void
    {
        $this->seed(SiklusContohSeeder::class);
        $siklus = Siklus::where('nama_inti', 'PT Inti Contoh')->firstOrFail();
        $peternak = $siklus->kandang->user;
        $r = $siklus->recordings()->where('diparaf_ppl', false)->first();

        $this->actingAs($peternak)->get(route('recording.index', $siklus))->assertOk()->assertSee('Subtotal minggu 1');
        // Siklus contoh sudah selesai: form baru ditolak, baris belum diparaf tampil mode baca-saja.
        $this->actingAs($peternak)->get(route('recording.create', $siklus))->assertForbidden();
        $this->actingAs($peternak)->get(route('recording.edit', $r))->assertOk();
    }

    public function test_halaman_form_dan_index_untuk_siklus_berjalan(): void
    {
        $this->simpan('2026-11-01', ['pakan_masuk_sak' => 30]);
        $r = RecordingHarian::firstOrFail();

        foreach ([route('recording.index', $this->siklus), route('recording.create', $this->siklus), route('recording.edit', $r)] as $url) {
            $this->actingAs($this->peternak)->get($url)->assertOk();
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function hitung(Siklus $siklus): array
    {
        return app(RingkasanRecording::class)->baris(
            $siklus,
            $siklus->recordings()->orderBy('hari_ke')->get(),
            $siklus->standarHarian()->get(),
            $siklus->parameterUmur()->get(),
        );
    }
}
