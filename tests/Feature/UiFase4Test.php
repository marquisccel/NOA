<?php

namespace Tests\Feature;

use App\Models\Kandang;
use App\Models\PemberianOvk;
use App\Models\RecordingHarian;
use App\Models\SamplingBobot;
use App\Models\Siklus;
use App\Models\User;
use App\Services\RingkasanRecording;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * UI Fase 4: Recording (riwayat, entri, sukses), Sampling, OVK.
 */
class UiFase4Test extends TestCase
{
    use RefreshDatabase;

    private User $peternak;

    private Siklus $siklus;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 08:00:00');
        $this->peternak = User::factory()->create();
        $kandang = Kandang::create(['user_id' => $this->peternak->id, 'nama_farm' => 'Kandang 1', 'alamat' => 'Desa Uji', 'kapasitas' => 5000, 'tipe' => 'closed_house']);
        // Hari ini = hari ke-5.
        $this->siklus = Siklus::create(['kandang_id' => $kandang->id, 'tanggal_masuk' => '2026-11-06', 'populasi' => 5000, 'status' => 'berjalan', 'bobot_sak_kg' => 50, 'nama_ppl' => 'Rina']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function rekam(int $hari, array $o = []): RecordingHarian
    {
        return RecordingHarian::create(array_merge([
            'siklus_id' => $this->siklus->id, 'tanggal' => Carbon::parse('2026-11-06')->addDays($hari - 1)->toDateString(), 'hari_ke' => $hari,
            'pakan_masuk_sak' => 0, 'pakan_pakai_sak' => 10, 'mati' => 2, 'afkir' => 1,
        ], $o));
    }

    private function terkunci(string $status = 'selesai'): void
    {
        $this->siklus->update(['status' => $status, 'tanggal_panen' => '2026-11-09']);
    }

    // ---- Recording: riwayat ----

    public function test_standar_pakan_harian_adalah_selisih_kumulatif(): void
    {
        $this->siklus->standarHarian()->createMany([
            ['hari_ke' => 1, 'pakan_std_sak' => 1.0, 'bobot_std_gram' => 40, 'fcr_std' => 0.2, 'feed_intake_std_gram' => 10],
            ['hari_ke' => 2, 'pakan_std_sak' => 2.5, 'bobot_std_gram' => 55, 'fcr_std' => 0.3, 'feed_intake_std_gram' => 25],
            ['hari_ke' => 4, 'pakan_std_sak' => 9.0, 'bobot_std_gram' => 90, 'fcr_std' => 0.5, 'feed_intake_std_gram' => 60],
        ]);

        $hasil = app(RingkasanRecording::class)->pakanStdHarian($this->siklus->standarHarian()->get());

        $this->assertSame(1.0, $hasil[1]);
        $this->assertSame(1.5, $hasil[2]);
        $this->assertSame(9.0, $hasil[4]);
    }

    public function test_riwayat_recording_memakai_kolom_desain_dan_hari_ini_belum_diisi(): void
    {
        $this->siklus->standarHarian()->create(['hari_ke' => 5, 'pakan_std_sak' => 6, 'bobot_std_gram' => 100, 'fcr_std' => 0.5, 'feed_intake_std_gram' => 70, 'deplesi_std_persen' => 0.30]);
        $this->rekam(1, ['pakan_masuk_sak' => 40]);

        $this->actingAs($this->peternak)->get(route('recording.index', $this->siklus))->assertOk()
            ->assertSee('Recording harian')->assertSeeInOrder(['Pakan std', 'Pakan akt.', 'Ayam mati', 'Ayam afkir', 'Depl. std', 'Depl. akt.', 'Stok pakan', 'Paraf PPL', 'Aksi'])
            ->assertSee('Hari ini')->assertSee('Belum diisi')->assertSee('↑40')->assertSee('Isi recording hari ini')
            ->assertSee('Hari ke-2 (7 Nov 2026) belum diisi')->assertSee('Entri mundur diizinkan');
    }

    public function test_angka_di_atas_standar_ditandai_untuk_deplesi(): void
    {
        $this->siklus->standarHarian()->create(['hari_ke' => 1, 'pakan_std_sak' => 6, 'bobot_std_gram' => 42, 'fcr_std' => 0.2, 'feed_intake_std_gram' => 14, 'deplesi_std_persen' => 0.01]);
        $this->rekam(1);

        $this->actingAs($this->peternak)->get(route('recording.index', $this->siklus))->assertSee('(di atas standar)');
    }

    public function test_riwayat_kosong_menampilkan_keadaan_kosong(): void
    {
        $this->siklus->update(['tanggal_masuk' => '2026-11-11']);

        $this->actingAs($this->peternak)->get(route('recording.index', $this->siklus))->assertOk()
            ->assertSee('Belum ada recording')->assertSee('11 Nov 2026');
    }

    public function test_baris_terparaf_terkunci_dengan_nama_ppl_dan_penjelasan_r11(): void
    {
        $this->rekam(1, ['diparaf_ppl' => true, 'tanggal_paraf' => '2026-11-07']);
        $this->rekam(2);

        $html = $this->actingAs($this->peternak)->get(route('recording.index', $this->siklus))->assertOk()
            ->assertSee('Baris terparaf terkunci')->assertSee('diparaf oleh Rina')->assertSee('Terkunci')->assertSee('Tandai paraf')->getContent();

        // Baris terparaf tidak punya tautan Ubah; baris yang belum diparaf punya.
        $this->assertSame(1, substr_count($html, '/recording/'.RecordingHarian::where('hari_ke', 2)->value('id').'/edit'));
        $this->assertSame(0, substr_count($html, '/recording/'.RecordingHarian::where('hari_ke', 1)->value('id').'/edit'));
    }

    public function test_tandai_paraf_lewat_riwayat_mengunci_baris(): void
    {
        $r = $this->rekam(1);

        $this->actingAs($this->peternak)->patch(route('recording.paraf', $r), ['tanggal_paraf' => '2026-11-09'])->assertRedirect(route('recording.index', $this->siklus));

        $this->assertTrue($r->fresh()->diparaf_ppl);
    }

    public function test_riwayat_siklus_ditutup_semua_terkunci_tanpa_tombol_isi(): void
    {
        $this->rekam(1);
        $this->terkunci();

        $this->actingAs($this->peternak)->get(route('recording.index', $this->siklus))->assertOk()
            ->assertSee('Siklus ditutup · semua baris hanya dapat dilihat')->assertDontSee('Isi recording hari ini')->assertSee('Terkunci');
    }

    // ---- Recording: entri dan halaman sukses ----

    public function test_form_entri_menampilkan_standar_hari_itu_dan_penanda_ovk(): void
    {
        $this->siklus->standarHarian()->createMany([
            ['hari_ke' => 4, 'pakan_std_sak' => 5.0, 'bobot_std_gram' => 90, 'fcr_std' => 0.5, 'feed_intake_std_gram' => 60],
            ['hari_ke' => 5, 'pakan_std_sak' => 7.4, 'bobot_std_gram' => 110, 'fcr_std' => 0.6, 'feed_intake_std_gram' => 80],
        ]);

        $this->actingAs($this->peternak)->get(route('recording.create', [$this->siklus, 'tanggal' => '2026-11-10']))->assertOk()
            ->assertSee('Hari ke-5')->assertSee('Hari ini')->assertSee('Standar 2,4 sak')->assertSee('Pakan terpakai *')->assertSee('Ayam mati *')->assertSee('Ayam afkir *')
            ->assertSee('Detail opsional')->assertSee('OVK diberikan (penanda)')->assertSee('name="ovk_pagi"', false)->assertSee('Simpan');
    }

    public function test_entri_mundur_diberi_tanda_dan_abk_melihat_catatan_tanpa_biaya(): void
    {
        $this->actingAs($this->peternak)->get(route('recording.create', [$this->siklus, 'tanggal' => '2026-11-07']))
            ->assertSee('Entri mundur')->assertSee('Mengisi entri yang terlewat')->assertSee('Hari ke-2');

        $abk = User::factory()->abk($this->peternak)->create();
        $abk->forceFill(['siklus_id' => $this->siklus->id])->save();
        $this->actingAs($abk)->get(route('recording.create', [$this->siklus, 'tanggal' => '2026-11-10']))->assertOk()->assertSee('tanpa biaya')->assertDontSee('tab OVK');
    }

    public function test_entri_ganda_diarahkan_ke_entri_yang_ada_dengan_pesan_desain(): void
    {
        $r = $this->rekam(5);

        $this->actingAs($this->peternak)->get(route('recording.create', [$this->siklus, 'tanggal' => '2026-11-10']))->assertRedirect(route('recording.edit', $r))
            ->assertSessionHas('peringatan', fn ($p) => str_contains($p, 'Hari ke-5 sudah punya recording'));
    }

    public function test_penanda_ovk_tersimpan_dan_tercentang_kembali(): void
    {
        $this->actingAs($this->peternak)->post(route('recording.store', $this->siklus), [
            'tanggal' => '2026-11-10', 'pakan_pakai_sak' => 5, 'mati' => 0, 'afkir' => 0, 'ovk_pagi' => 'Diberikan',
        ])->assertSessionDoesntHaveErrors();

        $r = RecordingHarian::firstOrFail();
        $this->assertSame('Diberikan', $r->ovk_pagi);
        $this->assertNull($r->ovk_sore);
        $this->actingAs($this->peternak)->get(route('recording.edit', $r))->assertOk()->assertSee('value="Diberikan" checked', false);
    }

    public function test_halaman_tersimpan_memuat_angka_berjalan_dan_pengingat_hari_terlewat(): void
    {
        $this->rekam(5, ['pakan_pakai_sak' => 12.5, 'mati' => 3, 'afkir' => 1, 'pakan_masuk_sak' => 10]);

        $this->actingAs($this->peternak)->get(route('recording.tersimpan', [$this->siklus, 5]))->assertOk()
            ->assertSee('Recording hari ke-5 tersimpan')->assertSee('pakan 12,50 sak')->assertSee('ayam mati 3 ekor')->assertSee('Angka berjalan terbaru')
            ->assertSee('Deplesi kumulatif')->assertSee('0,08%')->assertSee('Populasi kini')->assertSee('4.996')->assertSee('Stok pakan')->assertSee('FCR berjalan')
            ->assertSee('Recording hari ke-1 belum diisi')->assertSee('Isi hari ke-1')->assertSee('Lihat riwayat')->assertSee('Selesai')
            ->assertSee('Stok pakan -2,50 sak')->assertSee('pengiriman pakan yang belum dicatat');
    }

    public function test_halaman_tersimpan_hari_tak_ada_404_dan_orang_lain_ditolak(): void
    {
        $this->rekam(5);

        $this->actingAs($this->peternak)->get(route('recording.tersimpan', [$this->siklus, 3]))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('recording.tersimpan', [$this->siklus, 5]))->assertForbidden();
    }

    public function test_simpan_mengarah_ke_halaman_tersimpan_hari_itu(): void
    {
        $this->actingAs($this->peternak)->post(route('recording.store', $this->siklus), ['tanggal' => '2026-11-10', 'pakan_pakai_sak' => 5, 'mati' => 1, 'afkir' => 0])
            ->assertRedirect(route('recording.tersimpan', [$this->siklus, 5]));
    }

    // ---- Sampling ----

    private function sampling(string $tanggal, int $jumlah, float $kg)
    {
        return $this->actingAs($this->peternak)->post(route('sampling.store', $this->siklus), ['tanggal' => $tanggal, 'jumlah_sampel' => $jumlah, 'total_bobot_kg' => $kg]);
    }

    public function test_sampling_kosong_memakai_keadaan_kosong_dengan_perkiraan_sampel(): void
    {
        $this->actingAs($this->peternak)->get(route('sampling.index', $this->siklus))->assertOk()
            ->assertSee('Belum ada sampling bobot')->assertSee('± 250 ekor untuk 5.000 ekor')->assertSee('Sampling pertama');
    }

    public function test_halaman_sampling_baru_memuat_panel_hasil_dan_batas_minimal(): void
    {
        $this->actingAs($this->peternak)->get(route('sampling.create', $this->siklus))->assertOk()
            ->assertSee('Timbang minimal 5% populasi hidup agar representatif')->assertSee('Jumlah sampel *')->assertSee('Total bobot *')
            ->assertSee('Hasil (dihitung otomatis)')->assertSee('Selisih vs standar')->assertSee('Proporsi sampel')->assertSee('Min. 250 ekor')->assertSee('Simpan sampling');
    }

    public function test_riwayat_sampling_menampilkan_standar_selisih_dan_interval(): void
    {
        $this->siklus->standarHarian()->createMany([
            ['hari_ke' => 3, 'pakan_std_sak' => 3, 'bobot_std_gram' => 100, 'fcr_std' => 0.5, 'feed_intake_std_gram' => 30],
            ['hari_ke' => 5, 'pakan_std_sak' => 6, 'bobot_std_gram' => 200, 'fcr_std' => 0.6, 'feed_intake_std_gram' => 60],
        ]);
        $this->sampling('2026-11-08', 250, 27.5);
        $this->sampling('2026-11-10', 250, 55);

        $this->actingAs($this->peternak)->get(route('sampling.index', $this->siklus))->assertOk()
            ->assertSee('Riwayat sampling')->assertSee('2 sampling · interval 2 hari')->assertSee('110 g')->assertSee('220 g')->assertSee('+10,0%')->assertSee('55 g/hari')
            ->assertSee('Sampling baru')->assertSee('Sampling hari ke-5 tersimpan');
    }

    public function test_sampel_kurang_dari_lima_persen_ditandai_di_riwayat(): void
    {
        $this->sampling('2026-11-10', 100, 10)->assertSessionHas('peringatan');

        $this->actingAs($this->peternak)->get(route('sampling.index', $this->siklus))->assertSee('Kurang representatif');
    }

    public function test_sampling_siklus_tertutup_terkunci(): void
    {
        $s = SamplingBobot::create(['siklus_id' => $this->siklus->id, 'tanggal' => '2026-11-09', 'hari_ke' => 4, 'jumlah_sampel' => 250, 'total_bobot_kg' => 25]);
        $this->terkunci();

        $this->actingAs($this->peternak)->get(route('sampling.index', $this->siklus))->assertOk()->assertDontSee('Sampling baru')->assertDontSee('Ubah');
        $this->actingAs($this->peternak)->get(route('sampling.create', $this->siklus))->assertForbidden();
        $this->actingAs($this->peternak)->get(route('sampling.edit', $s))->assertForbidden();
        $this->sampling('2026-11-09', 250, 30)->assertForbidden();
        $this->assertSame(1, SamplingBobot::count());
    }

    public function test_sampling_hanya_untuk_pemilik(): void
    {
        $abk = User::factory()->abk($this->peternak)->create();

        foreach ([User::factory()->create(), $abk] as $user) {
            $this->actingAs($user)->get(route('sampling.create', $this->siklus))->assertForbidden();
            $this->actingAs($user)->get(route('sampling.index', $this->siklus))->assertForbidden();
        }
    }

    // ---- OVK ----

    private function ovk(array $o = [])
    {
        return $this->actingAs($this->peternak)->post(route('ovk.store', $this->siklus), array_merge([
            'tanggal' => '2026-11-09', 'jenis' => 'vaksin', 'nama_produk' => 'ND La Sota', 'dosis' => '1 dosis/ekor', 'biaya' => 450000,
        ], $o));
    }

    public function test_jenis_vitamin_sah_dan_dilindungi_basis_data(): void
    {
        $this->ovk(['jenis' => 'vitamin', 'nama_produk' => 'Vita Stress'])->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('pemberian_ovk', ['jenis' => 'vitamin']);

        $this->expectException(QueryException::class);
        PemberianOvk::create(['siklus_id' => $this->siklus->id, 'tanggal' => '2026-11-09', 'jenis' => 'jamu', 'nama_produk' => 'X', 'biaya' => 1]);
    }

    public function test_dosis_wajib_diisi(): void
    {
        $this->ovk(['dosis' => ''])->assertSessionHasErrors('dosis');
        $this->assertSame(0, PemberianOvk::count());
    }

    public function test_riwayat_ovk_menampilkan_hari_total_dan_penanda_recording(): void
    {
        $this->rekam(4, ['ovk_pagi' => 'Diberikan']);
        $this->ovk();
        $this->ovk(['tanggal' => '2026-11-07', 'jenis' => 'vitamin', 'nama_produk' => 'Elektrolit', 'biaya' => 60000]);

        $this->actingAs($this->peternak)->get(route('ovk.index', $this->siklus))->assertOk()
            ->assertSee('Riwayat OVK')->assertSee('2 pemberian · total Rp 510.000')->assertSee('Pemberian OVK baru')->assertSee('Lihat di Recording')
            ->assertSeeInOrder(['Hari', 'Tanggal', 'Jenis', 'Nama produk', 'Dosis', 'Biaya', 'Penanda recording'])
            ->assertSee('Pagi (hr 4)')->assertSee('Total');
    }

    public function test_ovk_kosong_dan_form_baru(): void
    {
        $this->actingAs($this->peternak)->get(route('ovk.index', $this->siklus))->assertSee('Belum ada pemberian OVK')->assertSee('Pemberian OVK pertama');

        $this->ovk(['biaya' => 100000]);
        $this->actingAs($this->peternak)->get(route('ovk.create', $this->siklus))->assertOk()
            ->assertSee('Jenis *')->assertSee('Obat')->assertSee('Vitamin')->assertSee('Vaksin')->assertSee('Nama produk *')->assertSee('Dosis *')->assertSee('Simpan OVK')
            ->assertSee('Siklus sejauh ini')->assertSee('Rp 100.000')->assertSee('Rp 20 / ekor')->assertSee('terakhir hari ke-4');
    }

    public function test_kimia_lama_tetap_terbaca_saat_ubah(): void
    {
        $o = PemberianOvk::create(['siklus_id' => $this->siklus->id, 'tanggal' => '2026-11-08', 'jenis' => 'kimia', 'nama_produk' => 'Desinfektan', 'dosis' => '1 ml', 'biaya' => 1000]);

        $this->actingAs($this->peternak)->get(route('ovk.edit', $o))->assertOk()->assertSee('Kimia');
    }

    public function test_ovk_siklus_tertutup_terkunci(): void
    {
        $o = PemberianOvk::create(['siklus_id' => $this->siklus->id, 'tanggal' => '2026-11-08', 'jenis' => 'obat', 'nama_produk' => 'X', 'dosis' => '1', 'biaya' => 1]);
        $this->terkunci('ditutup_tanpa_rhpp');

        $this->actingAs($this->peternak)->get(route('ovk.index', $this->siklus))->assertOk()->assertDontSee('Pemberian OVK baru')->assertDontSee('Ubah');
        $this->actingAs($this->peternak)->get(route('ovk.create', $this->siklus))->assertForbidden();
        $this->actingAs($this->peternak)->get(route('ovk.edit', $o))->assertForbidden();
        $this->actingAs($this->peternak)->delete(route('ovk.destroy', $o))->assertForbidden();
        $this->ovk()->assertForbidden();
        $this->assertSame(1, PemberianOvk::count());
    }
}
