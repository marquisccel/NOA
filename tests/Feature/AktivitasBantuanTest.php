<?php

namespace Tests\Feature;

use App\Models\Kandang;
use App\Models\PemberianOvk;
use App\Models\RecordingHarian;
use App\Models\Siklus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Menu Aktivitas, Bantuan (istilah dan alur), tanda tanya penjelasan, dan kartu "Langkah berikutnya". */
class AktivitasBantuanTest extends TestCase
{
    use RefreshDatabase;

    private User $peternak;

    private Siklus $siklus;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 08:00:00');
        $this->peternak = User::factory()->create(['name' => 'Pak Budi Santoso']);
        $k = Kandang::create(['user_id' => $this->peternak->id, 'nama_farm' => 'Kandang 1', 'alamat' => 'Desa Uji', 'kapasitas' => 5000, 'tipe' => 'closed_house']);
        $this->siklus = Siklus::create(['kandang_id' => $k->id, 'tanggal_masuk' => '2026-11-01', 'populasi' => 5000, 'status' => 'berjalan', 'bobot_sak_kg' => 50]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function rekam(int $hari, array $o = []): void
    {
        RecordingHarian::create(array_merge(['siklus_id' => $this->siklus->id, 'tanggal' => Carbon::parse('2026-11-01')->addDays($hari - 1)->toDateString(), 'hari_ke' => $hari, 'pakan_pakai_sak' => 5, 'mati' => 1, 'afkir' => 0], $o));
    }

    // ---- Aktivitas ----

    public function test_aktivitas_adalah_menu_sendiri_dan_tidak_lagi_di_dashboard(): void
    {
        $this->rekam(9);

        $this->actingAs($this->peternak)->get(route('dashboard'))->assertSee(route('aktivitas.index'), false)->assertDontSee('Aktivitas terbaru')->assertDontSee('Belum ada aktivitas');
        $this->actingAs($this->peternak)->get(route('aktivitas.index'))->assertOk()->assertSee('Aktivitas')->assertSee('Catatan terbaru dari semua kandang Anda');
    }

    public function test_aktivitas_dikelompokkan_per_tanggal_dengan_ringkasan_dan_tautan(): void
    {
        $this->rekam(10, ['pakan_pakai_sak' => 12.5, 'mati' => 3, 'afkir' => 1]);
        $this->rekam(9);
        PemberianOvk::create(['siklus_id' => $this->siklus->id, 'tanggal' => '2026-11-09', 'jenis' => 'vaksin', 'nama_produk' => 'Gumboro', 'dosis' => '1', 'biaya' => 100]);

        $this->actingAs($this->peternak)->get(route('aktivitas.index'))->assertOk()
            ->assertSeeInOrder(['Hari ini', 'Recording harian', 'Pakan 12,50 sak · mati 3 · afkir 1', 'Kandang 1 · Siklus 1 · Hari ke-10', 'Kemarin'])
            ->assertSee('Vaksin · Gumboro')->assertSee(route('recording.index', $this->siklus), false)->assertSee(route('ovk.index', $this->siklus), false);
    }

    public function test_aktivitas_filter_jenis_dan_kandang(): void
    {
        $this->rekam(10);
        PemberianOvk::create(['siklus_id' => $this->siklus->id, 'tanggal' => '2026-11-09', 'jenis' => 'obat', 'nama_produk' => 'Enro', 'dosis' => '1', 'biaya' => 100]);
        $lain = Kandang::create(['user_id' => $this->peternak->id, 'nama_farm' => 'Kandang Lain', 'kapasitas' => 100]);
        $s2 = Siklus::create(['kandang_id' => $lain->id, 'tanggal_masuk' => '2026-11-02', 'populasi' => 100, 'status' => 'berjalan']);
        RecordingHarian::create(['siklus_id' => $s2->id, 'tanggal' => '2026-11-03', 'hari_ke' => 2, 'pakan_pakai_sak' => 1, 'mati' => 0, 'afkir' => 0]);

        $this->actingAs($this->peternak)->get(route('aktivitas.index', ['jenis' => 'Pemberian OVK']))->assertSee('Enro')->assertDontSee('mati 1 · afkir 0');
        $this->actingAs($this->peternak)->get(route('aktivitas.index', ['kandang' => $lain->id]))->assertSee('Kandang Lain')->assertDontSee('Enro');
        $this->actingAs($this->peternak)->get(route('aktivitas.index', ['jenis' => 'ngawur']))->assertOk()->assertSee('Enro');
    }

    public function test_aktivitas_kosong_dan_bertingkat_halaman(): void
    {
        $this->actingAs($this->peternak)->get(route('aktivitas.index'))->assertSee('Belum ada aktivitas');

        foreach (range(1, 30) as $hari) {
            $this->rekam($hari);
        }
        $this->actingAs($this->peternak)->get(route('aktivitas.index'))->assertSee('Halaman 1 dari 2')->assertSee('Lebih lama');
        $this->actingAs($this->peternak)->get(route('aktivitas.index', ['halaman' => 2]))->assertSee('Halaman 2 dari 2')->assertSee('Lebih baru');
    }

    public function test_aktivitas_tidak_memuat_data_peternak_lain_dan_abk_ditolak(): void
    {
        $lain = User::factory()->create();
        $k = Kandang::create(['user_id' => $lain->id, 'nama_farm' => 'Milik Orang', 'kapasitas' => 100]);
        $s = Siklus::create(['kandang_id' => $k->id, 'tanggal_masuk' => '2026-11-01', 'populasi' => 100, 'status' => 'berjalan']);
        RecordingHarian::create(['siklus_id' => $s->id, 'tanggal' => '2026-11-03', 'hari_ke' => 3, 'pakan_pakai_sak' => 1, 'mati' => 0, 'afkir' => 0]);

        $this->actingAs($this->peternak)->get(route('aktivitas.index'))->assertDontSee('Milik Orang');
        $abk = User::factory()->abk($this->peternak)->create();
        $this->actingAs($abk)->get(route('aktivitas.index'))->assertForbidden();
    }

    // ---- Topbar ----

    public function test_topbar_hanya_judul_menu_tanpa_remah_dan_tanpa_gambar_profil(): void
    {
        $html = $this->actingAs($this->peternak)->get(route('aktivitas.index'))->getContent();

        $this->assertStringNotContainsString('Jejak halaman', $html);
        $this->assertStringNotContainsString('>PB<', $html);
        $this->assertStringNotContainsString('bg-brand text-[13px] font-bold text-white', $html);
        $this->assertStringContainsString('Pak Budi Santoso', $html);
        $this->assertStringContainsString('Peternak Plasma', $html);
    }

    // ---- Bantuan dan istilah ----

    public function test_halaman_bantuan_memuat_alur_dan_semua_istilah(): void
    {
        $this->actingAs($this->peternak)->get(route('dashboard'))->assertSee(route('bantuan'), false);

        $r = $this->actingAs($this->peternak)->get(route('bantuan'))->assertOk()
            ->assertSee('Alur satu siklus, langkah demi langkah')->assertSee('Daftarkan kandang')->assertSee('Tutup siklus')->assertSee('Langkah berikutnya')->assertSee('Arti istilah');
        foreach (config('istilah') as $kunci => [$nama]) {
            $r->assertSee('id="'.$kunci.'"', false)->assertSee($nama);
        }
        $this->assertGreaterThanOrEqual(15, count(config('istilah')));
    }

    public function test_istilah_teknis_punya_tanda_tanya_penjelasan_di_halaman_siklus(): void
    {
        $this->rekam(2);

        $this->actingAs($this->peternak)->get(route('siklus.show', $this->siklus))->assertOk()
            ->assertSee('Apa arti Deplesi?')->assertSee('Apa arti FCR?')->assertSee('Konversi pakan')->assertSee('Makin kecil makin baik')->assertSee(route('bantuan').'#fcr', false);
    }

    public function test_bantuan_juga_terbuka_untuk_abk(): void
    {
        $abk = User::factory()->abk($this->peternak)->create();

        $this->actingAs($abk)->get(route('bantuan'))->assertOk()->assertSee('Arti istilah');
    }

    // ---- Langkah berikutnya ----

    public function test_langkah_berikutnya_menyesuaikan_status_dan_data(): void
    {
        $url = route('siklus.show', $this->siklus);

        $this->actingAs($this->peternak)->get($url)->assertSee('Langkah berikutnya')->assertSee('Catatan hari ini belum diisi')->assertSee('Isi catatan hari ini');

        $this->rekam(10);
        $this->actingAs($this->peternak)->get($url)->assertSee('Catatan hari ini sudah terisi')->assertSee('Timbang sampel')->assertSee('Catat obat/vaksin');

        $this->siklus->panen()->create(['tanggal' => '2026-11-09', 'umur_hari' => 9, 'jumlah_ekor' => 10, 'tonase_kg' => 20, 'nomor_btpa' => 'X']);
        $this->actingAs($this->peternak)->get($url)->assertSee('Ayam sudah mulai dipanen')->assertSee('Ke halaman Panen');

        $this->siklus->update(['status' => 'menunggu_rhpp']);
        $this->actingAs($this->peternak)->get($url)->assertSee('Menunggu RHPP dari inti')->assertSee('Input RHPP')->assertSee('Tutup tanpa RHPP');

        $this->siklus->update(['status' => 'rekonsiliasi']);
        $this->actingAs($this->peternak)->get($url)->assertSee('Cocokkan dengan RHPP')->assertSee('Lihat perbandingan');

        $this->siklus->update(['status' => 'selesai']);
        $this->actingAs($this->peternak)->get($url)->assertSee('Siklus ini sudah selesai')->assertSee('Buka siklus baru');
    }

    public function test_istilah_teknis_diganti_bahasa_sehari_hari_dan_kode_internal_dibuang(): void
    {
        $this->rekam(2);
        $this->actingAs($this->peternak)->get(route('siklus.show', $this->siklus))->assertSee('Ayam hidup kini')->assertDontSee('Populasi kini');

        $this->siklus->update(['status' => 'rekonsiliasi']);
        $this->actingAs($this->peternak)->get(route('panen.ringkasan', $this->siklus))->assertDontSee('(R10)');
        $this->actingAs($this->peternak)->get(route('recording.index', $this->siklus))->assertDontSee('(R11)');
    }

    public function test_wizard_target_harian_boleh_dilewati_dengan_bahasa_sederhana(): void
    {
        $this->siklus->update(['status' => 'selesai']);
        foreach ([1 => ['tanggal_masuk' => '2026-11-09', 'populasi' => 4000, 'strain_doc' => 'CP', 'jenis_pakan' => 'BR', 'nama_ppl' => 'R', 'nama_inti' => 'PT', 'bobot_sak_kg' => 50], 2 => ['harga_doc' => 6500, 'harga_pakan_sak' => 400000, 'harga_kontrak_ayam' => 19500], 3 => []] as $no => $data) {
            $this->actingAs($this->peternak)->post(route('siklus.baru.simpan', [$this->siklus->kandang, $no]), $data);
        }

        $this->actingAs($this->peternak)->get(route('siklus.baru.langkah', [$this->siklus->kandang, 4]))->assertOk()
            ->assertSee('Target harian dari inti')->assertSee('boleh dilewati')->assertSee('Belum punya? Pilih')->assertSee('Lewati, isi nanti');
    }

    // ---- Topbar: jam dan tanggal ----

    public function test_topbar_memuat_jam_dan_tanggal_tanpa_kartu_pengguna_dan_keluar_ada_di_sidebar(): void
    {
        $html = $this->actingAs($this->peternak)->get(route('dashboard'))->assertOk()
            ->assertSee('role="timer"', false)->assertSee('08.00.00')->assertSee('Sel, 10 Nov')->assertSee('jam(', false)->getContent();

        // Nama dan peran tidak lagi di topbar (tanpa tombol menu pengguna); Keluar ada di sidebar dan drawer ponsel.
        $this->assertStringNotContainsString('aria-haspopup="menu"', $html);
        $this->assertStringNotContainsString('Pengaturan akun', $html);
        $this->assertSame(2, substr_count($html, 'action="'.route('logout').'"'));
        $this->assertStringContainsString('Pak Budi Santoso', $html);
    }

    public function test_semua_dropdown_menyisakan_ruang_untuk_panah(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression('/select:not\(\[multiple\]\)\s*\{\s*padding-right:\s*2\.5rem\s*!important/', $css);
    }
}
