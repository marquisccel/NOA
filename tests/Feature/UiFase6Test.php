<?php

namespace Tests\Feature;

use App\Models\AksesPengawas;
use App\Models\Kandang;
use App\Models\PemberianOvk;
use App\Models\RecordingHarian;
use App\Models\SamplingBobot;
use App\Models\Siklus;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * UI Fase 6: peran Pengawas Inti (baca-saja) dan akses per siklus yang diberikan dan dicabut peternak.
 */
class UiFase6Test extends TestCase
{
    use RefreshDatabase;

    private User $peternak;

    private Siklus $siklus;

    private Siklus $lain;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 08:00:00');
        $this->peternak = User::factory()->create(['name' => 'Pak Budi']);
        $k1 = Kandang::create(['user_id' => $this->peternak->id, 'nama_farm' => 'Kandang 1', 'alamat' => 'Desa Uji', 'kapasitas' => 5000, 'tipe' => 'closed_house']);
        $k2 = Kandang::create(['user_id' => $this->peternak->id, 'nama_farm' => 'Kandang 2', 'alamat' => 'Desa Uji', 'kapasitas' => 5000, 'tipe' => 'open_house']);
        $this->siklus = Siklus::create(['kandang_id' => $k1->id, 'tanggal_masuk' => '2026-10-15', 'populasi' => 5000, 'status' => 'berjalan', 'bobot_sak_kg' => 50, 'nama_inti' => 'PT Sumber Unggas', 'nama_ppl' => 'Rina']);
        $this->lain = Siklus::create(['kandang_id' => $k2->id, 'tanggal_masuk' => '2026-10-22', 'populasi' => 3000, 'status' => 'berjalan', 'bobot_sak_kg' => 50]);
        RecordingHarian::create(['siklus_id' => $this->siklus->id, 'tanggal' => '2026-10-16', 'hari_ke' => 2, 'pakan_pakai_sak' => 5, 'mati' => 2, 'afkir' => 1]);
        SamplingBobot::create(['siklus_id' => $this->siklus->id, 'tanggal' => '2026-10-22', 'hari_ke' => 8, 'jumlah_sampel' => 250, 'total_bobot_kg' => 50]);
        PemberianOvk::create(['siklus_id' => $this->siklus->id, 'tanggal' => '2026-10-19', 'jenis' => 'vaksin', 'nama_produk' => 'Gumboro', 'dosis' => '1 dosis', 'biaya' => 600000]);
        $this->siklus->panen()->create(['tanggal' => '2026-11-09', 'umur_hari' => 26, 'jumlah_ekor' => 100, 'tonase_kg' => 200, 'nomor_btpa' => 'SU-9', 'foto_btpa' => 'btpa/x.jpg']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function pengawas(?Siklus $siklus = null): User
    {
        $p = User::factory()->pengawas()->create(['name' => 'Rina', 'email' => 'rina@inti.co.id']);
        AksesPengawas::create(['pengawas_id' => $p->id, 'siklus_id' => ($siklus ?? $this->siklus)->id, 'peternak_id' => $this->peternak->id]);

        return $p;
    }

    // ---- Peternak memberi dan mencabut akses ----

    public function test_halaman_akses_pengawas_kosong_dan_tab_pengguna(): void
    {
        $this->actingAs($this->peternak)->get(route('akses.index'))->assertOk()
            ->assertSee('Pengguna & Akses')->assertSee('Akun ABK')->assertSee('Akses Pengawas')->assertSee('Belum ada akses Pengawas')
            ->assertSee('Beri akses ke satu siklus')->assertSee('Email Pengawas *')->assertSee('Kandang 2 · Siklus 1 (Berjalan)')->assertSee('Satu akses = satu siklus')->assertSee('Pengawas tidak memberi paraf');

        $this->actingAs($this->peternak)->get(route('abk.index'))->assertOk()->assertSee(route('akses.index'), false)->assertSee('Akses Pengawas');
    }

    public function test_beri_akses_ke_email_baru_membuat_akun_pengawas_dengan_sandi_sekali(): void
    {
        $r = $this->actingAs($this->peternak)->post(route('akses.store'), ['email' => 'dodi.pratama@inti.co.id', 'siklus_id' => $this->siklus->id]);

        $r->assertRedirect(route('akses.index'));
        $p = User::where('email', 'dodi.pratama@inti.co.id')->firstOrFail();
        $this->assertSame(User::ROLE_PENGAWAS, $p->role);
        $this->assertSame('Dodi Pratama', $p->name);
        $this->assertTrue($p->aktif);
        $sandi = session('sandi_pengawas.sandi');
        $this->assertTrue(Hash::check($sandi, $p->password));
        $this->assertSame(1, AksesPengawas::aktif()->where('pengawas_id', $p->id)->where('siklus_id', $this->siklus->id)->count());

        $this->actingAs($this->peternak)->get(route('akses.index'))->assertSee('Dodi Pratama')->assertSee('Kandang 1 · Siklus 1')->assertSee('Aktif')->assertSee('Cabut akses')->assertSee($sandi);
        $this->actingAs($this->peternak)->get(route('akses.index'))->assertDontSee($sandi);
    }

    public function test_beri_akses_ke_pengawas_yang_sudah_ada_tidak_membuat_akun_baru(): void
    {
        $p = User::factory()->pengawas()->create(['email' => 'rina@inti.co.id']);

        $this->actingAs($this->peternak)->post(route('akses.store'), ['email' => 'RINA@inti.co.id', 'siklus_id' => $this->siklus->id])->assertSessionHas('status')->assertSessionMissing('sandi_pengawas');

        $this->assertSame(1, User::where('role', 'pengawas')->count());
        $this->assertSame(1, $p->aksesPengawas()->count());
    }

    public function test_akses_ditolak_untuk_email_akun_lain_siklus_orang_lain_dan_duplikat(): void
    {
        $abk = User::factory()->abk($this->peternak)->create(['email' => 'abk@x.test']);
        $this->actingAs($this->peternak)->post(route('akses.store'), ['email' => $abk->email, 'siklus_id' => $this->siklus->id])->assertSessionHasErrors('email');

        $asing = Siklus::create(['kandang_id' => Kandang::create(['user_id' => User::factory()->create()->id, 'nama_farm' => 'Orang', 'kapasitas' => 10])->id, 'tanggal_masuk' => '2026-10-01', 'populasi' => 10, 'status' => 'berjalan']);
        $this->actingAs($this->peternak)->post(route('akses.store'), ['email' => 'baru@inti.co.id', 'siklus_id' => $asing->id])->assertSessionHasErrors('siklus_id');
        $this->assertNull(User::where('email', 'baru@inti.co.id')->first());

        $this->pengawas();
        $this->actingAs($this->peternak)->post(route('akses.store'), ['email' => 'rina@inti.co.id', 'siklus_id' => $this->siklus->id])->assertSessionHasErrors('email');
        $this->actingAs($this->peternak)->post(route('akses.store'), ['email' => 'bukan-email', 'siklus_id' => $this->siklus->id])->assertSessionHasErrors('email');
        $this->assertSame(1, AksesPengawas::count());
    }

    public function test_satu_pengawas_boleh_dua_siklus_tetapi_satu_akses_aktif_per_siklus_dijaga_basis_data(): void
    {
        $p = $this->pengawas();
        AksesPengawas::create(['pengawas_id' => $p->id, 'siklus_id' => $this->lain->id, 'peternak_id' => $this->peternak->id]);
        $this->assertSame(2, $p->aksesPengawas()->aktif()->count());

        $this->expectException(QueryException::class);
        AksesPengawas::create(['pengawas_id' => $p->id, 'siklus_id' => $this->siklus->id, 'peternak_id' => $this->peternak->id]);
    }

    public function test_cabut_akses_mencatat_tanggal_menyimpan_riwayat_dan_bisa_diberikan_lagi(): void
    {
        $p = $this->pengawas();
        $akses = $p->aksesPengawas()->firstOrFail();

        $this->actingAs($this->peternak)->get(route('akses.index'))->assertSee('Cabut akses Rina?')->assertSee('Data siklus tidak berubah');
        $this->actingAs($this->peternak)->delete(route('akses.cabut', $akses))->assertRedirect(route('akses.index'));

        $this->assertNotNull($akses->fresh()->dicabut_pada);
        $this->actingAs($this->peternak)->get(route('akses.index'))->assertSee('Dicabut 10 Nov 2026')->assertDontSee('Cabut akses Rina?');

        $this->actingAs($this->peternak)->post(route('akses.store'), ['email' => 'rina@inti.co.id', 'siklus_id' => $this->siklus->id])->assertSessionDoesntHaveErrors();
        $this->assertSame(2, AksesPengawas::count());
        $this->assertSame(1, AksesPengawas::aktif()->count());
    }

    public function test_hanya_pemberi_yang_boleh_mencabut_dan_pengawas_abk_ditolak_di_halaman_akses(): void
    {
        $p = $this->pengawas();
        $akses = $p->aksesPengawas()->firstOrFail();
        $lain = User::factory()->create();
        $abk = User::factory()->abk($this->peternak)->create();

        $this->actingAs($lain)->delete(route('akses.cabut', $akses))->assertForbidden();
        foreach ([$p, $abk] as $user) {
            $this->actingAs($user)->get(route('akses.index'))->assertForbidden();
            $this->actingAs($user)->post(route('akses.store'), ['email' => 'x@y.co', 'siklus_id' => $this->siklus->id])->assertForbidden();
            $this->actingAs($user)->delete(route('akses.cabut', $akses))->assertForbidden();
        }
        $this->assertNull($akses->fresh()->dicabut_pada);
    }

    // ---- Pengawas: baca-saja ----

    public function test_dashboard_pengawas_menampilkan_siklus_dibagikan_tanpa_data_komersial(): void
    {
        $p = $this->pengawas();

        $this->actingAs($p)->get(route('dashboard'))->assertOk()
            ->assertSee('Siklus Dibagikan')->assertSee('Siklus 1')->assertSee('Kandang 1')->assertSee('Dibagikan oleh')->assertSee('Pak Budi')->assertSee('Hari ke-')
            ->assertSee('Buka siklus')->assertDontSee('Kandang 2')->assertDontSee('Kandang &amp; Siklus', false)->assertDontSee('Laporan');
    }

    public function test_dashboard_pengawas_tanpa_akses_menampilkan_keadaan_kosong(): void
    {
        $p = User::factory()->pengawas()->create();

        $this->actingAs($p)->get(route('dashboard'))->assertOk()->assertSee('Belum ada siklus yang dibagikan');
    }

    public function test_pengawas_melihat_empat_tab_baca_saja_dengan_banner_mode(): void
    {
        $p = $this->pengawas();

        foreach ([route('recording.index', $this->siklus), route('sampling.index', $this->siklus), route('ovk.index', $this->siklus), route('panen.index', $this->siklus)] as $url) {
            $this->actingAs($p)->get($url)->assertOk()
                ->assertSee('Mode baca saja')->assertSee('dibagikan oleh Pak Budi')->assertSee('Tab siklus')
                ->assertSee(route('recording.index', $this->siklus), false)->assertSee(route('sampling.index', $this->siklus), false)->assertSee(route('ovk.index', $this->siklus), false)->assertSee(route('panen.index', $this->siklus), false)
                ->assertDontSee('Pengaturan Siklus')->assertDontSee(route('rhpp.show', $this->siklus), false)->assertDontSee(route('laporan.siklus', $this->siklus), false);
        }
    }

    public function test_recording_baca_saja_tanpa_isi_ubah_paraf_dan_unduh(): void
    {
        $p = $this->pengawas();

        $this->actingAs($p)->get(route('recording.index', $this->siklus))->assertOk()
            ->assertSee('Recording harian')->assertSee('Belum diparaf')
            ->assertDontSee('Isi recording hari ini')->assertDontSee('Tandai paraf')->assertDontSee('Ubah')->assertDontSee('Unduh CSV')->assertDontSee('>Aksi<', false)->assertDontSee('Terkunci');
    }

    public function test_sampling_baca_saja_tanpa_tombol_tambah_atau_ubah(): void
    {
        $this->actingAs($this->pengawas())->get(route('sampling.index', $this->siklus))->assertOk()
            ->assertSee('Riwayat sampling')->assertSee('200 g')->assertDontSee('Sampling baru')->assertDontSee('Ubah');
    }

    public function test_ovk_baca_saja_tanpa_biaya_total_dan_tombol(): void
    {
        $this->actingAs($this->pengawas())->get(route('ovk.index', $this->siklus))->assertOk()
            ->assertSee('Gumboro')->assertSee('1 pemberian')->assertDontSee('Rp 600.000')->assertDontSee('Biaya')->assertDontSee('total Rp')->assertDontSee('Pemberian OVK baru')->assertDontSee('Ubah');
    }

    public function test_panen_baca_saja_tanpa_foto_retur_ringkasan_dan_aksi(): void
    {
        $this->actingAs($this->pengawas())->get(route('panen.index', $this->siklus))->assertOk()
            ->assertSee('SU-9')->assertSee('100 ekor')
            ->assertDontSee('Tambah tahap panen')->assertDontSee('Tandai panen selesai')->assertDontSee('Ringkasan panen')->assertDontSee('Pakan sisa di akhir siklus')->assertDontSee('Lihat foto')->assertDontSee('Ubah');
    }

    public function test_pengawas_tidak_bisa_membuka_siklus_yang_tidak_dibagikan(): void
    {
        $p = $this->pengawas();

        foreach ([route('recording.index', $this->lain), route('sampling.index', $this->lain), route('ovk.index', $this->lain), route('panen.index', $this->lain)] as $url) {
            $this->actingAs($p)->get($url)->assertForbidden();
        }
    }

    public function test_pengawas_tidak_bisa_menulis_atau_membuka_halaman_komersial(): void
    {
        $p = $this->pengawas();
        $s = $this->siklus;
        $rec = $s->recordings()->firstOrFail();
        $tahap = $s->panen()->firstOrFail();

        foreach ([
            route('recording.create', $s), route('recording.edit', $rec), route('recording.csv', $s), route('sampling.create', $s), route('ovk.create', $s), route('panen.create', $s), route('panen.edit', $tahap),
            route('panen.foto', $tahap), route('panen.ringkasan', $s), route('panen.selisih', $s), route('rhpp.show', $s), route('rhpp.cetak', $s), route('laporan.siklus', $s), route('laporan.cetak', $s),
            route('laporan.csv', $s), route('laporan.index'), route('siklus.show', $s), route('siklus.pengaturan', $s), route('siklus.tutup', $s), route('siklus.hentikan', $s),
            route('kandang.index'), route('abk.index'), route('akses.index'),
        ] as $url) {
            $this->actingAs($p)->get($url)->assertForbidden();
        }

        $this->actingAs($p)->post(route('recording.store', $s), ['tanggal' => '2026-10-20', 'pakan_pakai_sak' => 1, 'mati' => 0, 'afkir' => 0])->assertForbidden();
        $this->actingAs($p)->patch(route('recording.paraf', $rec), ['tanggal_paraf' => '2026-11-01'])->assertForbidden();
        $this->actingAs($p)->post(route('sampling.store', $s), ['tanggal' => '2026-10-20', 'jumlah_sampel' => 10, 'total_bobot_kg' => 5])->assertForbidden();
        $this->actingAs($p)->post(route('ovk.store', $s), ['tanggal' => '2026-10-20', 'jenis' => 'obat', 'nama_produk' => 'X', 'dosis' => '1', 'biaya' => 1])->assertForbidden();
        $this->actingAs($p)->post(route('panen.store', $s), ['tanggal' => '2026-10-30', 'jumlah_ekor' => 1, 'tonase_kg' => 1, 'nomor_btpa' => 'X'])->assertForbidden();
        $this->actingAs($p)->delete(route('panen.destroy', $tahap))->assertForbidden();
        $this->actingAs($p)->put(route('rhpp.simpan', $s), [])->assertForbidden();

        $this->assertSame(1, RecordingHarian::count());
        $this->assertFalse($rec->fresh()->diparaf_ppl);
    }

    public function test_akses_dicabut_langsung_menutup_semua_halaman(): void
    {
        $p = $this->pengawas();
        $this->actingAs($p)->get(route('recording.index', $this->siklus))->assertOk();

        $p->aksesPengawas()->firstOrFail()->update(['dicabut_pada' => now()]);

        foreach ([route('recording.index', $this->siklus), route('sampling.index', $this->siklus), route('ovk.index', $this->siklus), route('panen.index', $this->siklus)] as $url) {
            $this->actingAs($p)->get($url)->assertForbidden();
        }
        $this->actingAs($p)->get(route('dashboard'))->assertOk()->assertSee('Belum ada siklus yang dibagikan');
    }

    public function test_pengawas_nonaktif_keluar_otomatis(): void
    {
        $p = $this->pengawas();
        $p->forceFill(['aktif' => false])->save();

        $this->actingAs($p)->get(route('recording.index', $this->siklus))->assertRedirect(route('login'));
    }

    public function test_siklus_tertutup_tetap_terlihat_baca_saja_oleh_pengawas(): void
    {
        $p = $this->pengawas();
        $this->siklus->update(['status' => 'selesai', 'tanggal_panen' => '2026-11-09']);

        $this->actingAs($p)->get(route('recording.index', $this->siklus))->assertOk()->assertSee('sudah ditutup')->assertDontSee('Isi recording hari ini');
    }

    public function test_menu_pengawas_hanya_siklus_dibagikan_dan_halaman_403_khusus(): void
    {
        $p = $this->pengawas();

        $this->actingAs($p)->get(route('dashboard'))->assertSee('Pengawasan')->assertSee('Pengawas Inti');
        $this->actingAs($p)->get(route('kandang.index'))->assertForbidden()->assertSee('Halaman ini tidak tersedia untuk Pengawas Inti')->assertSee('hanya dapat melihat recording, sampling, OVK, dan panen');
    }

    public function test_abk_tetap_tidak_bisa_membuka_sampling_ovk_dan_panen(): void
    {
        $abk = User::factory()->abk($this->peternak)->create();
        $abk->forceFill(['siklus_id' => $this->siklus->id])->save();

        foreach ([route('sampling.index', $this->siklus), route('ovk.index', $this->siklus), route('panen.index', $this->siklus)] as $url) {
            $this->actingAs($abk)->get($url)->assertForbidden();
        }
    }

    public function test_peternak_lain_tidak_bisa_memakai_akses_pengawas_siklus_orang(): void
    {
        $lain = User::factory()->create();

        foreach ([route('sampling.index', $this->siklus), route('ovk.index', $this->siklus), route('panen.index', $this->siklus), route('recording.index', $this->siklus)] as $url) {
            $this->actingAs($lain)->get($url)->assertForbidden();
        }
    }
}
