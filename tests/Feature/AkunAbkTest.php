<?php

namespace Tests\Feature;

use App\Models\Kandang;
use App\Models\KontrakSiklus;
use App\Models\RecordingHarian;
use App\Models\Siklus;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AkunAbkTest extends TestCase
{
    use RefreshDatabase;

    private User $peternak;

    private User $abk;

    private Siklus $siklus;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-15 08:00:00');

        $this->peternak = User::factory()->create(['role' => User::ROLE_PETERNAK]);
        $this->abk = User::factory()->abk($this->peternak)->create(['email' => 'abk-uji@noa.test']);

        $kandang = Kandang::create(['user_id' => $this->peternak->id, 'nama_farm' => 'Farm Rahasia Harga', 'kapasitas' => 4000]);
        $this->siklus = Siklus::create(['kandang_id' => $kandang->id, 'tanggal_masuk' => '2026-11-01', 'populasi' => 3000, 'bobot_sak_kg' => 50, 'status' => 'berjalan']);
        // ABK hanya mencatat pada siklus yang ditugaskan padanya.
        $this->abk->forceFill(['siklus_id' => $this->siklus->id])->save();
        KontrakSiklus::create([
            'siklus_id' => $this->siklus->id, 'harga_doc' => 7123, 'harga_pakan_sak' => 431777, 'harga_ovk' => 1500, 'harga_kontrak_ayam' => 20987, 'bonus_fcr' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function entri(string $tanggal, array $override = []): array
    {
        return array_merge(['tanggal' => $tanggal, 'pakan_pakai_sak' => 14, 'mati' => 5, 'afkir' => 0], $override);
    }

    // ---- Pengelolaan akun ABK oleh peternak ----

    public function test_peternak_membuat_akun_abk_dengan_peran_dan_pemilik_dari_server(): void
    {
        $this->actingAs($this->peternak)->post(route('abk.store'), [
            'name' => 'Budi ABK', 'email' => 'budi@noa.test', 'password' => 'rahasia-aman-1', 'password_confirmation' => 'rahasia-aman-1',
            // Upaya menyusupkan peran dan pemilik lewat form harus diabaikan.
            'role' => 'peternak', 'peternak_id' => 999,
        ])->assertRedirect(route('abk.index'));

        $baru = User::where('email', 'budi@noa.test')->firstOrFail();
        $this->assertSame(User::ROLE_ABK, $baru->role);
        $this->assertSame($this->peternak->id, $baru->peternak_id);
        $this->assertNotNull($baru->email_verified_at);
        $this->assertTrue(\Hash::check('rahasia-aman-1', $baru->password));
    }

    public function test_akun_abk_baru_bisa_masuk_ke_dashboard(): void
    {
        $this->actingAs($this->peternak)->post(route('abk.store'), [
            'name' => 'Budi', 'email' => 'budi@noa.test', 'password' => 'rahasia-aman-1', 'password_confirmation' => 'rahasia-aman-1',
        ]);
        auth()->logout();

        $this->post('/login', ['email' => 'budi@noa.test', 'password' => 'rahasia-aman-1'])->assertRedirect(route('dashboard', absolute: false));
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_validasi_pembuatan_abk(): void
    {
        $this->actingAs($this->peternak)->post(route('abk.store'), [])->assertSessionHasErrors(['name' => 'Nama ABK wajib diisi.', 'email' => 'Email wajib diisi.', 'password' => 'Kata sandi awal wajib diisi.']);
        $this->actingAs($this->peternak)->post(route('abk.store'), ['name' => 'X', 'email' => 'abk-uji@noa.test', 'password' => 'rahasia-aman-1'])->assertSessionHasErrors(['email' => 'Email ini sudah dipakai akun lain.']);
        $this->actingAs($this->peternak)->post(route('abk.store'), ['name' => 'X', 'email' => 'andi@', 'password' => 'rahasia-aman-1'])->assertSessionHasErrors(['email' => 'Masukkan email yang valid.']);
        $this->actingAs($this->peternak)->post(route('abk.store'), ['name' => 'X', 'email' => 'x@noa.test', 'password' => 'pendek1'])->assertSessionHasErrors('password');
        $this->actingAs($this->peternak)->post(route('abk.store'), ['name' => 'X', 'email' => 'x@noa.test', 'password' => 'hurufsaja'])->assertSessionHasErrors('password');
    }

    public function test_pendaftaran_publik_tidak_bisa_memilih_peran(): void
    {
        auth()->logout();

        $this->post('/register', [
            'name' => 'Penyusup', 'email' => 'penyusup@noa.test', 'password' => 'rahasia-aman-1', 'password_confirmation' => 'rahasia-aman-1',
            'role' => 'abk', 'peternak_id' => $this->peternak->id,
        ]);

        $u = User::where('email', 'penyusup@noa.test')->firstOrFail();
        $this->assertSame(User::ROLE_PETERNAK, $u->role);
        $this->assertNull($u->peternak_id);
    }

    public function test_daftar_abk_hanya_menampilkan_milik_sendiri(): void
    {
        User::factory()->abk(User::factory()->create())->create(['name' => 'ABK Orang Lain']);

        $this->actingAs($this->peternak)->get(route('abk.index'))
            ->assertOk()->assertSee('abk-uji@noa.test')->assertDontSee('ABK Orang Lain');
    }

    public function test_abk_tidak_bisa_mengelola_akun_abk(): void
    {
        $this->actingAs($this->abk)->get(route('abk.index'))->assertForbidden();
        $this->actingAs($this->abk)->post(route('abk.store'), ['name' => 'X', 'email' => 'x@noa.test', 'password' => 'rahasia-aman-1', 'password_confirmation' => 'rahasia-aman-1'])->assertForbidden();
        $this->actingAs($this->abk)->patch(route('abk.status', $this->abk), ['aktif' => 0])->assertForbidden();
        $this->actingAs($this->abk)->put(route('abk.update', $this->abk), ['name' => 'Y', 'email' => 'y@noa.test'])->assertForbidden();
    }

    public function test_abk_wajib_punya_peternak_di_basis_data(): void
    {
        $this->expectException(QueryException::class);
        User::factory()->create(['role' => User::ROLE_ABK, 'peternak_id' => null]);
    }

    public function test_peternak_dicabut_akun_abk_ikut_terhapus(): void
    {
        $this->peternak->delete();

        $this->assertDatabaseMissing('users', ['id' => $this->abk->id]);
    }

    // ---- F8.2: ABK mengisi entri harian ----

    public function test_abk_melihat_dan_mengisi_recording_siklus_peternaknya(): void
    {
        $this->actingAs($this->abk)->get(route('recording.index', $this->siklus))->assertOk();
        $this->actingAs($this->abk)->get(route('recording.create', $this->siklus))->assertOk();

        $this->actingAs($this->abk)->post(route('recording.store', $this->siklus), $this->entri('2026-11-02'))
            ->assertRedirectContains('/tersimpan')->assertSessionDoesntHaveErrors();

        $r = RecordingHarian::firstOrFail();
        $this->assertSame(2, $r->hari_ke);
        // Peternak melihat entri yang diisi ABK.
        $this->actingAs($this->peternak)->get(route('recording.index', $this->siklus))->assertOk()->assertSee('2 Nov');
    }

    public function test_abk_memperbaiki_entri_yang_belum_diparaf(): void
    {
        $this->actingAs($this->abk)->post(route('recording.store', $this->siklus), $this->entri('2026-11-02'));
        $r = RecordingHarian::firstOrFail();

        $this->actingAs($this->abk)->get(route('recording.edit', $r))->assertOk();
        $this->actingAs($this->abk)->put(route('recording.update', $r), ['pakan_pakai_sak' => 20, 'mati' => 1, 'afkir' => 0])->assertSessionDoesntHaveErrors();

        $this->assertSame('20.00', $r->fresh()->pakan_pakai_sak);
    }

    public function test_abk_tidak_bisa_menghapus_entri_atau_menandai_paraf(): void
    {
        $this->actingAs($this->abk)->post(route('recording.store', $this->siklus), $this->entri('2026-11-02'));
        $r = RecordingHarian::firstOrFail();

        $this->actingAs($this->abk)->delete(route('recording.destroy', $r))->assertForbidden();
        $this->actingAs($this->abk)->patch(route('recording.paraf', $r), ['tanggal_paraf' => '2026-11-03'])->assertForbidden();

        $this->assertNotSoftDeleted('recording_harian', ['id' => $r->id]);
        $this->assertFalse($r->fresh()->diparaf_ppl);
    }

    public function test_aturan_paraf_dan_siklus_selesai_berlaku_juga_untuk_abk(): void
    {
        $this->actingAs($this->abk)->post(route('recording.store', $this->siklus), $this->entri('2026-11-02'));
        $r = RecordingHarian::firstOrFail();
        $r->update(['diparaf_ppl' => true, 'tanggal_paraf' => '2026-11-03']);

        $this->actingAs($this->abk)->put(route('recording.update', $r), ['pakan_pakai_sak' => 99, 'mati' => 0, 'afkir' => 0])->assertForbidden();

        $this->siklus->update(['status' => Siklus::STATUS_SELESAI, 'tanggal_panen' => '2026-11-10']);
        $this->actingAs($this->abk)->post(route('recording.store', $this->siklus), $this->entri('2026-11-03'))->assertForbidden();
    }

    public function test_abk_peternak_lain_tidak_bisa_mengakses_recording(): void
    {
        $abkLain = User::factory()->abk()->create();
        $r = RecordingHarian::create(['siklus_id' => $this->siklus->id, 'tanggal' => '2026-11-02', 'hari_ke' => 2]);

        $this->actingAs($abkLain)->get(route('recording.index', $this->siklus))->assertForbidden();
        $this->actingAs($abkLain)->post(route('recording.store', $this->siklus), $this->entri('2026-11-03'))->assertForbidden();
        $this->actingAs($abkLain)->get(route('recording.edit', $r))->assertForbidden();
        $this->actingAs($abkLain)->put(route('recording.update', $r), ['pakan_pakai_sak' => 1, 'mati' => 0, 'afkir' => 0])->assertForbidden();
    }

    public function test_akun_pengawas_tidak_punya_akses_recording(): void
    {
        $pengawas = User::factory()->create(['role' => User::ROLE_PENGAWAS]);

        $this->actingAs($pengawas)->get(route('recording.index', $this->siklus))->assertForbidden();
    }

    // ---- R13: ABK tidak melihat kontrak, biaya, rekonsiliasi ----

    public function test_r13_abk_ditolak_di_semua_halaman_komersial(): void
    {
        $this->actingAs($this->abk);

        foreach ([
            route('siklus.show', $this->siklus), route('siklus.edit', $this->siklus), route('siklus.index'),
            route('kandang.index'), route('kandang.show', $this->siklus->kandang),
            route('ovk.index', $this->siklus), route('panen.index', $this->siklus), route('sampling.index', $this->siklus),
            route('rhpp.show', $this->siklus), route('rhpp.cetak', $this->siklus),
            route('laporan.siklus', $this->siklus), route('laporan.perbandingan', $this->siklus->kandang),
            route('siklus.standar', $this->siklus), route('siklus.acuan', $this->siklus), route('abk.index'),
        ] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_r13_halaman_yang_dibuka_abk_tidak_membocorkan_harga_atau_kontrak(): void
    {
        $this->actingAs($this->peternak)->post(route('recording.store', $this->siklus), $this->entri('2026-11-02', ['pakan_masuk_sak' => 40]));
        $r = RecordingHarian::firstOrFail();

        foreach ([
            route('dashboard'), route('recording.index', $this->siklus), route('recording.create', $this->siklus), route('recording.edit', $r),
        ] as $url) {
            $html = $this->actingAs($this->abk)->get($url)->assertOk()->getContent();

            foreach (['7123', '7.123', '431777', '431.777', '20987', '20.987', 'Rp ', 'Kontrak', 'Rekonsiliasi', 'RHPP', 'Detail siklus', 'Biaya'] as $terlarang) {
                $this->assertStringNotContainsString($terlarang, $html, "{$url} membocorkan: {$terlarang}");
            }
        }
    }

    public function test_navigasi_abk_tidak_memuat_menu_komersial(): void
    {
        $html = $this->actingAs($this->abk)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('href="'.route('kandang.index').'"', $html);
        $this->assertStringNotContainsString('href="'.route('siklus.index').'"', $html);
        $this->assertStringNotContainsString('href="'.route('abk.index').'"', $html);
    }

    public function test_dashboard_abk_hanya_menampilkan_siklus_berjalan_peternaknya_dengan_tombol_entri(): void
    {
        $lain = Kandang::create(['user_id' => User::factory()->create()->id, 'nama_farm' => 'Kandang Milik Orang Lain', 'kapasitas' => 100]);
        Siklus::create(['kandang_id' => $lain->id, 'tanggal_masuk' => '2026-11-01', 'populasi' => 100, 'status' => 'berjalan']);

        $this->actingAs($this->abk)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Farm Rahasia Harga')
            ->assertSee('Buka Recording Harian')
            ->assertDontSee('Kandang Milik Orang Lain');
    }

    public function test_dashboard_peternak_tetap_normal(): void
    {
        $this->actingAs($this->peternak)->get(route('dashboard'))->assertOk()->assertSee('Farm Rahasia Harga')->assertSee('Recording');
    }

    public function test_navigasi_peternak_memuat_menu_abk(): void
    {
        $this->actingAs($this->peternak)->get(route('dashboard'))->assertSee(route('abk.index'), false);
    }

    public function test_tamu_diarahkan_ke_login_untuk_recording(): void
    {
        auth()->logout();

        $this->get(route('recording.index', $this->siklus))->assertRedirect(route('login'));
    }
}
