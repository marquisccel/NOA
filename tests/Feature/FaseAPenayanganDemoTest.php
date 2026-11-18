<?php

namespace Tests\Feature;

use App\Models\Kandang;
use App\Models\Panen;
use App\Models\RecordingHarian;
use App\Models\Siklus;
use App\Models\User;
use App\Services\RingkasanDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Penayangan (Vercel/Render), mode demo, kartu "Perlu perhatian", "Mulai di sini", toast, dan tur panduan. */
class FaseAPenayanganDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 08:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ---- Berkas penayangan ----

    public function test_berkas_penayangan_vercel_dan_render_ada_dan_sah(): void
    {
        $v = json_decode(file_get_contents(base_path('vercel.json')), true);
        $this->assertSame('vercel-php@0.7.4', $v['functions']['api/index.php']['runtime']);
        $this->assertSame('public', $v['outputDirectory']);
        $this->assertSame('/api/index.php', $v['rewrites'][0]['destination']);
        $this->assertSame('/tmp/storage', $v['env']['LARAVEL_STORAGE_PATH']);
        $this->assertSame('cookie', $v['env']['SESSION_DRIVER']);
        $this->assertSame('true', $v['env']['NOA_DEMO']);
        $this->assertSame('false', $v['env']['NOA_UNGGAH']);

        foreach (['api/index.php', 'Dockerfile', 'docker/entrypoint.sh', 'render.yaml', '.vercelignore', '.dockerignore'] as $berkas) {
            $this->assertFileExists(base_path($berkas));
        }
        $this->assertStringContainsString('public/index.php', file_get_contents(base_path('api/index.php')));
        $this->assertStringContainsString('noa:siapkan-demo', file_get_contents(base_path('docker/entrypoint.sh')));
        $this->assertStringContainsString('healthCheckPath: /up', file_get_contents(base_path('render.yaml')));
    }

    public function test_endpoint_kesehatan_tersedia_untuk_penyedia_hosting(): void
    {
        $this->get('/up')->assertOk();
    }

    // ---- Mode demo ----

    public function test_tanpa_mode_demo_tombol_dan_rute_demo_tidak_tersedia(): void
    {
        config(['noa.demo' => false]);

        $this->get(route('login'))->assertOk()->assertDontSee('Coba demo tanpa daftar');
        $this->post(route('demo.masuk', 'plasma'))->assertNotFound();
    }

    public function test_mode_demo_menampilkan_tombol_dan_masuk_satu_klik_sebagai_plasma_dan_abk(): void
    {
        config(['noa.demo' => true]);

        $this->get(route('login'))->assertOk()->assertSee('Coba demo tanpa daftar')->assertSee('Masuk sebagai Plasma')->assertSee('Masuk sebagai ABK');

        $this->post(route('demo.masuk', 'plasma'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'simulasi@noa.test')->firstOrFail());
        $this->post(route('logout'));

        $this->post(route('demo.masuk', 'abk'))->assertRedirect(route('dashboard'));
        $this->assertTrue(auth()->user()->isAbk());
        $this->post(route('logout'));
        $this->post(route('demo.masuk', 'admin'))->assertNotFound();
    }

    public function test_banner_demo_hanya_untuk_akun_demo_dan_reset_mengembalikan_data(): void
    {
        config(['noa.demo' => true]);
        $this->post(route('demo.masuk', 'plasma'));
        $demo = auth()->user();

        $this->get(route('dashboard'))->assertSee('Mode demo')->assertSee('Kembalikan data demo')->assertSee('Tur panduan');

        // Ubah data lalu reset: data simulasi dibuat ulang dan pengguna tetap masuk.
        Kandang::where('user_id', $demo->id)->first()->update(['nama_farm' => 'Diubah Tamu']);
        $this->post(route('demo.reset'))->assertRedirect(route('dashboard'))->assertSessionHas('status', 'Data demo dikembalikan ke keadaan awal.');

        $this->assertSame('simulasi@noa.test', auth()->user()->email);
        $this->assertSame(0, Kandang::where('nama_farm', 'Diubah Tamu')->count());
        $this->assertSame(4, Kandang::where('user_id', auth()->id())->count());

        $biasa = User::factory()->create();
        $this->actingAs($biasa)->get(route('dashboard'))->assertDontSee('Mode demo');
        $this->actingAs($biasa)->post(route('demo.reset'))->assertNotFound();
    }

    public function test_perintah_siapkan_demo_idempoten(): void
    {
        $this->artisan('noa:siapkan-demo')->assertSuccessful();
        $id = User::where('email', 'simulasi@noa.test')->value('id');
        $this->artisan('noa:siapkan-demo')->expectsOutputToContain('dilewati')->assertSuccessful();
        $this->assertSame($id, User::where('email', 'simulasi@noa.test')->value('id'));
    }

    // ---- Unggah dapat dinonaktifkan ----

    public function test_unggah_dapat_dinonaktifkan_untuk_penyimpanan_serverless(): void
    {
        $p = User::factory()->create();
        $k = Kandang::create(['user_id' => $p->id, 'nama_farm' => 'K', 'kapasitas' => 100]);
        $s = Siklus::create(['kandang_id' => $k->id, 'tanggal_masuk' => '2026-10-01', 'populasi' => 100, 'status' => 'berjalan']);

        config(['noa.unggah' => true]);
        $this->actingAs($p)->get(route('panen.create', $s))->assertSee('Foto BTPA (opsional)');

        config(['noa.unggah' => false]);
        $this->actingAs($p)->get(route('panen.create', $s))->assertDontSee('Foto BTPA (opsional)');
        $s->update(['status' => 'menunggu_rhpp']);
        $this->actingAs($p)->get(route('rhpp.form', $s))->assertOk()->assertDontSee('Lampiran (foto/PDF)');
    }

    // ---- Dashboard: Mulai di sini dan Perlu perhatian ----

    public function test_pengguna_baru_melihat_mulai_di_sini_dengan_kemajuan_nol(): void
    {
        $p = User::factory()->create();
        Kandang::create(['user_id' => $p->id, 'nama_farm' => 'Kandang Baru', 'kapasitas' => 100]);

        $this->actingAs($p)->get(route('dashboard'))->assertOk()
            ->assertSee('Mulai di sini')->assertSee('1 dari 6 langkah selesai')->assertSee('berikutnya:')->assertSee('Buka siklus pertama')
            ->assertSee('role="progressbar"', false)->assertSee('aria-valuenow="1"', false)->assertDontSee('Perlu perhatian');
    }

    public function test_langkah_awal_selesai_menurut_data_dan_blok_hilang_bila_semua_beres(): void
    {
        $p = User::factory()->create();
        $k = Kandang::create(['user_id' => $p->id, 'nama_farm' => 'K', 'kapasitas' => 100]);
        $s = Siklus::create(['kandang_id' => $k->id, 'tanggal_masuk' => '2026-11-01', 'populasi' => 100, 'status' => 'berjalan']);
        RecordingHarian::create(['siklus_id' => $s->id, 'tanggal' => '2026-11-02', 'hari_ke' => 2, 'pakan_pakai_sak' => 1, 'mati' => 0, 'afkir' => 0]);

        $langkah = collect(app(RingkasanDashboard::class)->langkahAwal($p))->pluck('selesai')->all();
        $this->assertSame([true, true, true, false, false, false], $langkah);

        $s->samplings()->create(['tanggal' => '2026-11-08', 'hari_ke' => 8, 'jumlah_sampel' => 5, 'total_bobot_kg' => 1]);
        Panen::create(['siklus_id' => $s->id, 'tanggal' => '2026-11-09', 'umur_hari' => 9, 'jumlah_ekor' => 10, 'tonase_kg' => 20, 'nomor_btpa' => 'X']);
        $s->rhpp()->create(['nomor_rhpp' => 'R', 'tanggal_terbit' => '2026-11-10']);

        $this->assertSame(array_fill(0, 6, true), collect(app(RingkasanDashboard::class)->langkahAwal($p))->pluck('selesai')->all());
        $this->actingAs($p)->get(route('dashboard'))->assertDontSee('Mulai di sini');
    }

    public function test_perlu_perhatian_mengumpulkan_masalah_dari_semua_siklus_dengan_yang_mendesak_dulu(): void
    {
        $p = User::factory()->create();
        $k1 = Kandang::create(['user_id' => $p->id, 'nama_farm' => 'Kandang Satu', 'kapasitas' => 100]);
        $k2 = Kandang::create(['user_id' => $p->id, 'nama_farm' => 'Kandang Dua', 'kapasitas' => 100]);
        $s1 = Siklus::create(['kandang_id' => $k1->id, 'tanggal_masuk' => '2026-11-05', 'populasi' => 100, 'status' => 'berjalan', 'bobot_sak_kg' => 50]);
        Siklus::create(['kandang_id' => $k2->id, 'tanggal_masuk' => '2026-11-08', 'populasi' => 100, 'status' => 'berjalan', 'bobot_sak_kg' => 50]);
        RecordingHarian::create(['siklus_id' => $s1->id, 'tanggal' => '2026-11-05', 'hari_ke' => 1, 'pakan_masuk_sak' => 10, 'pakan_pakai_sak' => 6, 'mati' => 0, 'afkir' => 0]);
        RecordingHarian::create(['siklus_id' => $s1->id, 'tanggal' => '2026-11-06', 'hari_ke' => 2, 'pakan_pakai_sak' => 3.5, 'mati' => 0, 'afkir' => 0]);

        $h = app(RingkasanDashboard::class)->perhatian($p);

        $this->assertSame('penting', $h->first()['tingkat']);
        $this->assertTrue($h->contains(fn ($x) => str_contains($x['siklus'], 'Kandang Dua') && str_contains($x['judul'], 'belum diisi')));
        $this->assertTrue($h->contains(fn ($x) => str_contains($x['siklus'], 'Kandang Satu')));
        $this->actingAs($p)->get(route('dashboard'))->assertSee('Perlu perhatian')->assertSee('Kandang Dua · Siklus 1')->assertSee('Isi recording →', false);
    }

    public function test_perlu_perhatian_tidak_memuat_siklus_peternak_lain_dan_dibatasi_enam(): void
    {
        $lain = User::factory()->create();
        $k = Kandang::create(['user_id' => $lain->id, 'nama_farm' => 'Milik Orang', 'kapasitas' => 100]);
        Siklus::create(['kandang_id' => $k->id, 'tanggal_masuk' => '2026-11-05', 'populasi' => 100, 'status' => 'berjalan']);
        $p = User::factory()->create();

        $this->assertCount(0, app(RingkasanDashboard::class)->perhatian($p));
        $this->assertLessThanOrEqual(6, app(RingkasanDashboard::class)->perhatian($lain)->count());
    }

    // ---- Notifikasi, tur, dan umpan balik ----

    public function test_pesan_berhasil_tampil_sebagai_toast_yang_hilang_sendiri(): void
    {
        $p = User::factory()->create();

        $this->actingAs($p)->withSession(['status' => 'Tersimpan ya'])->get(route('dashboard'))
            ->assertSee('Tersimpan ya')->assertSee('role="status"', false)->assertSee('setTimeout(() => buka = false, 7000)', false)->assertSee('aria-label="Tutup pesan"', false);
    }

    public function test_tur_panduan_dan_bilah_kemajuan_ada_di_layout(): void
    {
        $p = User::factory()->create();
        Kandang::create(['user_id' => $p->id, 'nama_farm' => 'K', 'kapasitas' => 10]);

        $html = $this->actingAs($p)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('data-tur="otomatis"', $html);
        $this->assertStringContainsString('x-data="tur()"', $html);
        $this->assertStringContainsString('id="bilah-kemajuan"', $html);
        $this->assertStringContainsString('data-tur="Menu utama|', $html);
        $this->assertStringContainsString('data-tur="Mulai di sini|', $html);

        // Tur otomatis hanya di Dashboard.
        $this->actingAs($p)->get(route('aktivitas.index'))->assertDontSee('data-tur="otomatis"', false);
        $this->actingAs($p)->get(route('bantuan'))->assertSee('Ulangi tur panduan');
    }
}
