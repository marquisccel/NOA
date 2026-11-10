<?php

namespace Tests\Feature;

use App\Models\Kandang;
use App\Models\RecordingHarian;
use App\Models\Siklus;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * UI Fase 2: dashboard, kandang, pengguna dan akses (ABK per siklus), pengaturan.
 */
class UiFase2Test extends TestCase
{
    use RefreshDatabase;

    private User $peternak;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 08:00:00');
        $this->peternak = User::factory()->create(['name' => 'Pak Budi']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function kandang(string $nama = 'Kandang 1', string $tipe = 'closed_house', ?User $pemilik = null): Kandang
    {
        return Kandang::create([
            'user_id' => ($pemilik ?? $this->peternak)->id,
            'nama_farm' => $nama,
            'alamat' => "Desa {$nama}, Kec. Uji",
            'kapasitas' => 5000,
            'tipe' => $tipe,
        ]);
    }

    private function siklus(Kandang $k, string $masuk = '2026-10-15', string $status = 'berjalan', int $populasi = 5000): Siklus
    {
        return Siklus::create(['kandang_id' => $k->id, 'tanggal_masuk' => $masuk, 'populasi' => $populasi, 'status' => $status, 'bobot_sak_kg' => 50]);
    }

    private function abk(?Siklus $siklus = null, array $attrs = []): User
    {
        $abk = User::factory()->abk($this->peternak)->create($attrs);
        if ($siklus) {
            $abk->forceFill(['siklus_id' => $siklus->id])->save();
        }

        return $abk->fresh();
    }

    // ---- Penomoran siklus ----

    public function test_nomor_siklus_per_kandang_menurut_tanggal_masuk(): void
    {
        $a = $this->kandang('A');
        $b = $this->kandang('B');
        $a2 = $this->siklus($a, '2026-06-01', 'selesai');
        $a1 = $this->siklus($a, '2026-01-01', 'selesai');
        $a3 = $this->siklus($a, '2026-10-01');
        $b1 = $this->siklus($b, '2026-09-01');

        $peta = Siklus::nomorMap([$a->id, $b->id]);

        $this->assertSame([$a1->id => 1, $a2->id => 2, $a3->id => 3, $b1->id => 1], $peta);
    }

    // ---- Dashboard ----

    public function test_dashboard_menampilkan_angka_kartu_dan_siklus_aktif(): void
    {
        $k1 = $this->kandang('Kandang 1');
        $this->kandang('Kandang 2');
        $s = $this->siklus($k1, '2026-10-17', 'berjalan', 5000);
        RecordingHarian::create(['siklus_id' => $s->id, 'tanggal' => '2026-10-17', 'hari_ke' => 1, 'mati' => 90, 'afkir' => 4]);

        $r = $this->actingAs($this->peternak)->get(route('dashboard'))->assertOk();

        $r->assertSee('Ringkasan peternakan per 10 Nov 2026.')
            ->assertSee('Total Kandang')->assertSee('Terdaftar atas nama Anda')
            ->assertSee('Siklus 1')->assertSee('Hari ke-25')
            ->assertSee('4.906 ekor')   // 5.000 - (90 + 4)
            ->assertSee('Buka detail siklus')
            ->assertSee('Recording harian')->assertSee('Aktivitas terbaru');
        $ringkas = app(\App\Services\RingkasanDashboard::class)->peternak($this->peternak);
        $this->assertSame([2, 1, 1, 4906], [$ringkas['total_kandang'], $ringkas['kandang_aktif'], $ringkas['siklus_berjalan'], $ringkas['populasi_aktif']]);
    }

    public function test_dashboard_tanpa_kandang_menampilkan_sambutan(): void
    {
        $this->actingAs($this->peternak)->get(route('dashboard'))
            ->assertSee('Selamat datang di NOA')->assertSee('Belum ada kandang terdaftar')->assertSee('Tambah Kandang');
    }

    public function test_dashboard_tanpa_siklus_berjalan(): void
    {
        $this->siklus($this->kandang(), '2026-05-01', 'selesai');

        $this->actingAs($this->peternak)->get(route('dashboard'))
            ->assertSee('Belum ada siklus berjalan')->assertSee('Belum ada aktivitas', false)->assertSee('Tidak ada siklus terbuka');
    }

    public function test_sapaan_menurut_jam(): void
    {
        $this->kandang();
        foreach (['08:00' => 'Selamat pagi', '13:00' => 'Selamat siang', '16:30' => 'Selamat sore', '20:00' => 'Selamat malam', '02:00' => 'Selamat malam'] as $jam => $sapa) {
            Carbon::setTestNow("2026-11-10 {$jam}:00");
            $this->actingAs($this->peternak)->get(route('dashboard'))->assertSee("{$sapa}, Pak Budi");
        }
    }

    public function test_aktivitas_terbaru_urut_dari_yang_terbaru_dan_maksimal_lima(): void
    {
        $s = $this->siklus($this->kandang());
        foreach (range(1, 7) as $hari) {
            RecordingHarian::create(['siklus_id' => $s->id, 'tanggal' => Carbon::parse('2026-10-15')->addDays($hari - 1), 'hari_ke' => $hari]);
        }

        $a = app(\App\Services\RingkasanDashboard::class)->aktivitas($this->peternak);

        $this->assertCount(5, $a);
        $this->assertStringContainsString('Hari ke-7', $a->first()['keterangan']);
        $this->assertStringContainsString('Siklus 1', $a->first()['keterangan']);
    }

    public function test_dashboard_abk_hanya_siklus_ditugaskan(): void
    {
        $k = $this->kandang('Kandang A');
        $lain = $this->siklus($this->kandang('Kandang Lain'));
        $abk = $this->abk($this->siklus($k), ['name' => 'Andi']);

        $this->actingAs($abk)->get(route('dashboard'))
            ->assertSee('Siklus Ditugaskan')->assertSee('Kandang A')->assertSee('Buka Recording Harian')
            ->assertDontSee('Kandang Lain')->assertDontSee('Tambah Kandang');
    }

    public function test_dashboard_abk_belum_ditugaskan_atau_nonaktif(): void
    {
        $abk = $this->abk();
        $this->actingAs($abk)->get(route('dashboard'))->assertSee('Belum ada siklus yang ditugaskan');
    }

    // ---- Kandang ----

    public function test_daftar_kandang_cari_dan_filter(): void
    {
        $open = $this->kandang('Sukamaju', 'open_house');
        $closed = $this->kandang('Pujon', 'closed_house');
        $this->siklus($closed);

        $this->actingAs($this->peternak)->get(route('kandang.index'))
            ->assertSee('2 kandang · 1 punya siklus terbuka');
        $this->actingAs($this->peternak)->get(route('kandang.index', ['q' => 'suka']))->assertSee('Sukamaju')->assertDontSee('Pujon')->assertSee('1 hasil');
        $this->actingAs($this->peternak)->get(route('kandang.index', ['tipe' => 'closed_house']))->assertSee('Pujon')->assertDontSee('Sukamaju');
        $this->actingAs($this->peternak)->get(route('kandang.index', ['status' => 'berjalan']))->assertSee('Pujon')->assertDontSee('Sukamaju');
        $this->actingAs($this->peternak)->get(route('kandang.index', ['status' => 'tanpa']))->assertSee('Sukamaju')->assertDontSee('Pujon');
    }

    public function test_pencarian_juga_mencari_alamat_dan_tanpa_hasil(): void
    {
        $this->kandang('Sukamaju');

        $this->actingAs($this->peternak)->get(route('kandang.index', ['q' => 'Desa Sukamaju']))->assertSee('Sukamaju');
        $this->actingAs($this->peternak)->get(route('kandang.index', ['q' => 'Wonodadi']))
            ->assertSee('Tidak ada kandang dengan nama “Wonodadi”')->assertSee('Hapus pencarian');
    }

    public function test_daftar_kandang_kosong(): void
    {
        $this->actingAs($this->peternak)->get(route('kandang.index'))->assertSee('Belum ada kandang')->assertSee('Tambah Kandang');
    }

    public function test_kandang_orang_lain_tidak_muncul_di_daftar(): void
    {
        $this->kandang('Milik Orang Lain', 'open_house', User::factory()->create());

        $this->actingAs($this->peternak)->get(route('kandang.index'))->assertDontSee('Milik Orang Lain');
    }

    public function test_form_kandang_alamat_dan_tipe_wajib_dengan_pesan_desain(): void
    {
        $this->actingAs($this->peternak)->post(route('kandang.store'), ['nama_farm' => '', 'alamat' => '', 'kapasitas' => -500])
            ->assertSessionHasErrors([
                'nama_farm' => 'Nama kandang wajib diisi.',
                'alamat' => 'Alamat kandang wajib diisi.',
                'kapasitas' => 'Kapasitas harus berupa angka lebih dari 0.',
                'tipe' => 'Pilih salah satu tipe kandang.',
            ]);
        $this->actingAs($this->peternak)->post(route('kandang.store'), ['nama_farm' => 'X', 'alamat' => 'Y', 'kapasitas' => 10, 'tipe' => 'panggung'])
            ->assertSessionHasErrors('tipe');
    }

    public function test_tambah_dan_ubah_kandang_dengan_pesan_sukses(): void
    {
        $this->actingAs($this->peternak)->post(route('kandang.store'), ['nama_farm' => 'Kandang 4', 'alamat' => 'Desa X', 'kapasitas' => 4000, 'tipe' => 'open_house'])
            ->assertRedirect(route('kandang.index'))
            ->assertSessionHas('status', 'Kandang “Kandang 4” berhasil ditambahkan. Buka siklus baru dari halaman detail kandang.');

        $k = Kandang::firstOrFail();
        $this->assertSame('Open House', $k->labelTipe());

        $this->actingAs($this->peternak)->put(route('kandang.update', $k), ['nama_farm' => 'Kandang 4B', 'alamat' => 'Desa Y', 'kapasitas' => 4500, 'tipe' => 'closed_house'])
            ->assertRedirect(route('kandang.show', $k))->assertSessionHas('status', 'Perubahan data kandang berhasil disimpan.');
        $this->assertSame('Closed House', $k->fresh()->labelTipe());
    }

    public function test_form_menampilkan_pilihan_tipe_dan_panel_info(): void
    {
        $this->actingAs($this->peternak)->get(route('kandang.create'))
            ->assertSee('Open House')->assertSee('Closed House')->assertSee('Ventilasi alami')->assertSee('Setelah kandang tersimpan')->assertSee('Alamat kandang *');

        $k = $this->kandang();
        $this->actingAs($this->peternak)->get(route('kandang.edit', $k))->assertSee('Tentang perubahan')->assertSee('Hapus kandang');
    }

    public function test_tipe_kandang_di_luar_daftar_ditolak_basis_data(): void
    {
        $this->expectException(QueryException::class);
        Kandang::create(['user_id' => $this->peternak->id, 'nama_farm' => 'X', 'kapasitas' => 10, 'tipe' => 'panggung']);
    }

    public function test_detail_kandang_dengan_siklus_berjalan(): void
    {
        $k = $this->kandang('Kandang 1');
        $this->siklus($k, '2026-01-10', 'selesai');
        $this->siklus($k, '2026-10-15');

        $this->actingAs($this->peternak)->get(route('kandang.show', $k))
            ->assertSee('Siklus saat ini')->assertSee('Siklus 2')->assertSee('Buka Detail Siklus')
            ->assertSee('Siklus baru dapat dibuka setelah Siklus 2 ditutup.')
            ->assertSee('1 siklus tercatat untuk kandang ini')
            ->assertDontSee('Lanjut ke inisialisasi siklus');
    }

    public function test_detail_kandang_tanpa_siklus_terbuka_memuat_dialog_buka_siklus(): void
    {
        $k = $this->kandang('Kandang 4');
        $this->siklus($k, '2026-02-01', 'selesai');

        $this->actingAs($this->peternak)->get(route('kandang.show', $k))
            ->assertSee('Tidak ada siklus terbuka')->assertSee('Siklus 1 sudah ditutup')
            ->assertSee('Siklus berikutnya: Siklus 2')->assertSee('Lanjut ke inisialisasi siklus')
            ->assertSee(route('siklus.create', ['kandang_id' => $k->id]), false);
    }

    public function test_detail_kandang_belum_pernah_dipakai(): void
    {
        $this->actingAs($this->peternak)->get(route('kandang.show', $this->kandang()))
            ->assertSee('belum pernah dipakai')->assertSee('Siklus berikutnya: Siklus 1');
    }

    public function test_riwayat_siklus_dan_filter_status(): void
    {
        $k = $this->kandang('Kandang 1');
        $this->siklus($k, '2026-01-01', 'selesai');
        $this->siklus($k, '2026-05-01', 'dihentikan');
        $this->siklus($k, '2026-10-01', 'berjalan');

        $this->actingAs($this->peternak)->get(route('kandang.riwayat', $k))
            ->assertOk()->assertSee('3 siklus tercatat')->assertSee('Siklus 3')->assertSee('Dihentikan')->assertSee('Ditutup');
        $this->actingAs($this->peternak)->get(route('kandang.riwayat', [$k, 'status' => 'dihentikan']))
            ->assertSee('1 sesuai filter')->assertSee('Siklus 2')->assertDontSee('Siklus 3');
    }

    public function test_riwayat_menampilkan_panen_terakhir_dan_rechick_in(): void
    {
        $k = $this->kandang();
        $s = $this->siklus($k, '2026-09-01', 'selesai');
        $s->update(['tanggal_rechick' => '2026-11-20']);
        $s->panen()->create(['tanggal' => '2026-10-05', 'umur_hari' => 35, 'jumlah_ekor' => 100, 'tonase_kg' => 200]);

        $this->actingAs($this->peternak)->get(route('kandang.riwayat', $k))->assertSee('5 Okt 2026')->assertSee('20 Nov 2026');
    }

    public function test_kandang_orang_lain_ditolak_untuk_riwayat(): void
    {
        $k = $this->kandang('X', 'open_house', User::factory()->create());

        $this->actingAs($this->peternak)->get(route('kandang.riwayat', $k))->assertForbidden();
    }

    // ---- Pengguna & akses (ABK) ----

    public function test_buat_abk_dan_tugaskan_ke_siklus_berjalan(): void
    {
        $k = $this->kandang('Kandang 1');
        $s = $this->siklus($k);

        $this->actingAs($this->peternak)->post(route('abk.store'), ['name' => 'Andi', 'email' => 'andi@noa.id', 'password' => 'Rahasia2026', 'siklus_id' => $s->id])
            ->assertRedirect(route('abk.index'))
            ->assertSessionHas('status', 'Akun ABK “Andi” berhasil dibuat dan ditugaskan ke Kandang 1 · Siklus 1.');

        $abk = User::where('email', 'andi@noa.id')->firstOrFail();
        $this->assertSame([User::ROLE_ABK, $this->peternak->id, $s->id, true], [$abk->role, $abk->peternak_id, $abk->siklus_id, $abk->aktif]);
    }

    public function test_penugasan_hanya_ke_siklus_berjalan_milik_sendiri(): void
    {
        $milikLain = $this->siklus($this->kandang('Lain', 'open_house', User::factory()->create()));
        $ditutup = $this->siklus($this->kandang('Tutup'), '2026-01-01', 'selesai');
        $data = ['name' => 'Andi', 'email' => 'andi@noa.id', 'password' => 'Rahasia2026'];

        $this->actingAs($this->peternak)->post(route('abk.store'), $data + ['siklus_id' => $milikLain->id])->assertSessionHasErrors(['siklus_id' => 'Pilih siklus berjalan milik kandang Anda.']);
        $this->actingAs($this->peternak)->post(route('abk.store'), $data + ['siklus_id' => $ditutup->id])->assertSessionHasErrors('siklus_id');
        $this->assertSame(0, User::where('email', 'andi@noa.id')->count());
    }

    public function test_abk_boleh_dibuat_tanpa_penugasan(): void
    {
        $this->actingAs($this->peternak)->post(route('abk.store'), ['name' => 'Andi', 'email' => 'andi@noa.id', 'password' => 'Rahasia2026'])->assertSessionDoesntHaveErrors();

        $this->assertNull(User::where('email', 'andi@noa.id')->firstOrFail()->siklus_id);
    }

    public function test_daftar_abk_menampilkan_penugasan_status_dan_pencarian(): void
    {
        $s = $this->siklus($this->kandang('Kandang 1'));
        $this->abk($s, ['name' => 'Andi', 'email' => 'andi@noa.id']);
        $nonaktif = $this->abk(null, ['name' => 'Agus', 'email' => 'agus@noa.id']);
        $nonaktif->forceFill(['aktif' => false])->save();

        $this->actingAs($this->peternak)->get(route('abk.index'))
            ->assertSee('2 akun ABK · 1 aktif')->assertSee('Kandang 1 · Siklus 1')->assertSee('— (belum ditugaskan)')->assertSee('Nonaktif')->assertSee('Aktifkan');
        $this->actingAs($this->peternak)->get(route('abk.index', ['q' => 'agus']))->assertSee('agus@noa.id')->assertDontSee('andi@noa.id');
    }

    public function test_ubah_abk_dan_cabut_penugasan(): void
    {
        $s = $this->siklus($this->kandang());
        $abk = $this->abk($s, ['email' => 'andi@noa.id']);

        $this->actingAs($this->peternak)->put(route('abk.update', $abk), ['name' => 'Andi Baru', 'email' => 'andi@noa.id', 'siklus_id' => $s->id])->assertSessionDoesntHaveErrors();
        $this->assertSame([$s->id, 'Andi Baru'], [$abk->fresh()->siklus_id, $abk->fresh()->name]);

        // Select siklus dinonaktifkan (pilih "belum ditugaskan") tidak terkirim: penugasan dicabut.
        $this->actingAs($this->peternak)->put(route('abk.update', $abk), ['name' => 'Andi Baru', 'email' => 'andi@noa.id'])->assertSessionDoesntHaveErrors();
        $this->assertNull($abk->fresh()->siklus_id);
    }

    public function test_nonaktifkan_abk_memblokir_masuk_dan_mencabut_penugasan(): void
    {
        $s = $this->siklus($this->kandang());
        $abk = $this->abk($s, ['email' => 'andi@noa.id']);

        $this->actingAs($this->peternak)->patch(route('abk.status', $abk), ['aktif' => 0])
            ->assertRedirect(route('abk.index'))->assertSessionHas('status', fn ($p) => str_contains($p, 'dinonaktifkan') && str_contains($p, 'tetap tersimpan'));

        $abk->refresh();
        $this->assertFalse($abk->aktif);
        $this->assertNull($abk->siklus_id);

        auth()->logout();
        $this->post('/login', ['email' => 'andi@noa.id', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_sesi_abk_yang_dinonaktifkan_langsung_berakhir(): void
    {
        $abk = $this->abk($this->siklus($this->kandang()));
        $this->actingAs($abk)->get(route('dashboard'))->assertOk();

        $abk->forceFill(['aktif' => false])->save();

        $this->actingAs($abk->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_aktifkan_kembali_abk(): void
    {
        $abk = $this->abk();
        $abk->forceFill(['aktif' => false])->save();

        $this->actingAs($this->peternak)->patch(route('abk.status', $abk), ['aktif' => 1])->assertSessionHas('status', fn ($p) => str_contains($p, 'diaktifkan'));

        $this->assertTrue($abk->fresh()->aktif);
    }

    public function test_atur_ulang_kata_sandi_abk_menampilkan_sandi_baru_sekali(): void
    {
        $abk = $this->abk(null, ['email' => 'andi@noa.id']);

        $r = $this->actingAs($this->peternak)->post(route('abk.reset', $abk))->assertRedirect(route('abk.edit', $abk));
        $baru = session('sandi_baru');

        $this->assertMatchesRegularExpression('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $baru, 'Sandi baru harus memenuhi syarat huruf dan angka');
        $this->assertTrue(Hash::check($baru, $abk->fresh()->password));
        $this->actingAs($this->peternak)->get(route('abk.edit', $abk))->assertSee($baru);
        $this->actingAs($this->peternak)->get(route('abk.edit', $abk))->assertDontSee($baru);
    }

    public function test_halaman_tambah_dan_ubah_abk_memuat_hak_akses(): void
    {
        $this->siklus($this->kandang());
        $abk = $this->abk();

        foreach ([route('abk.create'), route('abk.edit', $abk)] as $url) {
            $this->actingAs($this->peternak)->get($url)->assertOk()
                ->assertSee('Hak akses ABK')->assertSee('Membuka Recording Harian')->assertSee('RHPP &amp; rekonsiliasi', false);
        }
        $this->actingAs($this->peternak)->get(route('abk.edit', $abk))->assertSee('Atur ulang kata sandi')->assertSee('Nonaktifkan');
        $this->actingAs($this->peternak)->get(route('abk.create'))->assertSee('Kata sandi awal');
    }

    public function test_peternak_tidak_bisa_mengelola_abk_orang_lain(): void
    {
        $abkLain = User::factory()->abk(User::factory()->create())->create();

        $this->actingAs($this->peternak)->get(route('abk.edit', $abkLain))->assertForbidden();
        $this->actingAs($this->peternak)->patch(route('abk.status', $abkLain), ['aktif' => 0])->assertForbidden();
        $this->actingAs($this->peternak)->post(route('abk.reset', $abkLain))->assertForbidden();
        $this->assertTrue($abkLain->fresh()->aktif);
    }

    public function test_abk_hanya_bisa_mencatat_pada_siklus_yang_ditugaskan(): void
    {
        $k = $this->kandang();
        $ditugaskan = $this->siklus($k);
        $kandang2 = $this->kandang('Kandang 2');
        $lain = $this->siklus($kandang2);
        $abk = $this->abk($ditugaskan);

        $this->actingAs($abk)->get(route('recording.index', $ditugaskan))->assertOk();
        // Siklus lain milik peternak yang sama tetap tertutup bagi ABK ini.
        $this->actingAs($abk)->get(route('recording.index', $lain))->assertForbidden();
        $this->actingAs($abk)->post(route('recording.store', $lain), ['tanggal' => '2026-10-16', 'pakan_pakai_sak' => 1, 'mati' => 0, 'afkir' => 0])->assertForbidden();
    }

    public function test_abk_tanpa_penugasan_tidak_bisa_mencatat(): void
    {
        $s = $this->siklus($this->kandang());
        $abk = $this->abk();

        $this->actingAs($abk)->get(route('recording.index', $s))->assertForbidden();
    }

    public function test_halaman_403_untuk_abk_memakai_pesan_desain(): void
    {
        $s = $this->siklus($this->kandang());
        $abk = $this->abk($s);

        $this->actingAs($abk)->get(route('kandang.index'))->assertForbidden()
            ->assertSee('Kode 403')->assertSee('Halaman ini tidak tersedia untuk akun ABK')
            ->assertSee('Ke Recording Harian')->assertSee(route('recording.index', $s), false);
    }

    public function test_halaman_403_untuk_tamu_dan_peran_lain(): void
    {
        $abk = $this->abk();
        $this->actingAs($abk)->get(route('kandang.index'))->assertForbidden()->assertSee('Ke Dashboard');
    }

    // ---- Pengaturan ----

    public function test_halaman_pengaturan_sesuai_desain(): void
    {
        $this->actingAs($this->peternak)->get(route('profile.edit'))->assertOk()
            ->assertSee('Profil dan keamanan akun')->assertSee('Nama lengkap *')->assertSee('Peternak Plasma')->assertSee('Simpan profil')
            ->assertSee('Ubah kata sandi')->assertSee('Kata sandi saat ini *')->assertSee('Minimal 8 karakter')->assertSee('Mengandung huruf dan angka')
            ->assertDontSee('Hapus akun');
    }

    public function test_ubah_profil_menampilkan_pesan_indonesia(): void
    {
        $this->actingAs($this->peternak)->patch(route('profile.update'), ['name' => 'Pak Budi Baru', 'email' => 'baru@noa.test'])
            ->assertRedirect(route('profile.edit'))->assertSessionHas('status', 'Profil berhasil disimpan.');
    }

    public function test_ubah_kata_sandi_pesan_sukses_dan_galat_di_bag_updatepassword(): void
    {
        $this->actingAs($this->peternak)->from(route('profile.edit'))->put(route('password.update'), ['current_password' => 'password', 'password' => 'KataBaru2026', 'password_confirmation' => 'KataBaru2026'])
            ->assertSessionHas('status', 'Kata sandi berhasil diubah.');

        $html = $this->actingAs($this->peternak)->from(route('profile.edit'))->followingRedirects()
            ->put(route('password.update'), ['current_password' => 'salah', 'password' => 'abc', 'password_confirmation' => 'abc'])->getContent();
        $this->assertStringContainsString('Kata sandi saat ini salah.', $html);
    }

    public function test_abk_juga_bisa_membuka_pengaturan_miliknya(): void
    {
        $abk = $this->abk();

        $this->actingAs($abk)->get(route('profile.edit'))->assertOk()->assertSee('ABK');
    }
}
