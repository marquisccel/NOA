<?php

namespace Tests\Feature;

use App\Models\Kandang;
use App\Models\Panen;
use App\Models\RecordingHarian;
use App\Models\Siklus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * UI Fase 5: Panen & BTPA, alur status (panen selesai, RHPP, tutup, tutup tanpa RHPP, hentikan), laporan global, halaman galat.
 */
class UiFase5Test extends TestCase
{
    use RefreshDatabase;

    private User $peternak;

    private Siklus $siklus;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 08:00:00');
        $this->peternak = User::factory()->create();
        $kandang = Kandang::create(['user_id' => $this->peternak->id, 'nama_farm' => 'Kandang 3', 'alamat' => 'Desa Uji', 'kapasitas' => 5000, 'tipe' => 'open_house']);
        $this->siklus = Siklus::create(['kandang_id' => $kandang->id, 'tanggal_masuk' => '2026-09-26', 'populasi' => 5000, 'status' => 'berjalan', 'bobot_sak_kg' => 50, 'nama_inti' => 'PT Sumber Unggas', 'nama_ppl' => 'Rina']);
        $this->siklus->kontrak()->create(['harga_doc' => 6500, 'harga_pakan_sak' => 412500, 'harga_kontrak_ayam' => 19500]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** Recording 10 hari: 100 mati+afkir (4 ekor/hari x 25 hari tidak perlu; satu baris cukup) dan pakan. */
    private function rekam(int $deplesi = 104): void
    {
        RecordingHarian::create(['siklus_id' => $this->siklus->id, 'tanggal' => '2026-09-27', 'hari_ke' => 2, 'pakan_masuk_sak' => 325, 'pakan_pakai_sak' => 320, 'mati' => $deplesi - 18, 'afkir' => 18]);
    }

    private function tahap(string $tanggal, int $ekor, float $kg, string $btpa = 'SU-1'): Panen
    {
        return $this->siklus->panen()->create([
            'tanggal' => $tanggal, 'umur_hari' => (int) Carbon::parse('2026-09-26')->diffInDays($tanggal) + 1, 'jumlah_ekor' => $ekor, 'tonase_kg' => $kg, 'nomor_btpa' => $btpa,
        ]);
    }

    private function panenTiga(): void
    {
        $this->tahap('2026-10-24', 1400, 2632, 'SU-1024-118');
        $this->tahap('2026-10-27', 1600, 3248, 'SU-1027-204');
        $this->tahap('2026-10-30', 1880, 4136, 'SU-1030-091');
    }

    private function isianRhpp(array $o = []): array
    {
        return array_merge([
            'nomor_rhpp' => 'RHPP/SU/2026/11/0457', 'tanggal_terbit' => '2026-11-08', 'populasi_doc' => 5000, 'total_ekor' => 4880, 'deplesi_ekor' => 120, 'total_tonase' => 9982,
            'pakan_terpakai_kg' => 16250, 'fcr' => 1.628, 'ip' => 380, 'abw' => 2.046, 'umur_panen' => 32.3, 'biaya_doc' => 32500000, 'biaya_pakan' => 134062500,
            'biaya_ovk' => 2350000, 'pendapatan' => 194649000, 'hasil_bersih' => 27732900,
        ], $o);
    }

    // ---- Panen & BTPA ----

    public function test_panen_kosong_menjelaskan_fcr_final_muncul_setelah_panen_pertama(): void
    {
        $this->actingAs($this->peternak)->get(route('panen.index', $this->siklus))->assertOk()
            ->assertSee('Panen &amp; BTPA', false)->assertSee('Belum ada panen')->assertSee('Siklus masih berjalan (hari ke-46)')
            ->assertSee('FCR final &amp; IP muncul setelah panen pertama', false)->assertSee('Tambah tahap panen')->assertDontSee('Tandai panen selesai');
    }

    public function test_daftar_tahap_memuat_kpi_tabel_total_dan_nomor_btpa(): void
    {
        $this->rekam();
        $this->panenTiga();

        $this->actingAs($this->peternak)->get(route('panen.index', $this->siklus))->assertOk()
            ->assertSee('Total ekor panen')->assertSee('4.880 ekor')->assertSee('3 tahap')->assertSee('10.016 kg')->assertSee('32,30 hari')->assertSee('2,052 kg')
            ->assertSeeInOrder(['Tahap', 'Tanggal', 'Umur', 'Jumlah ekor', 'Tonase', 'ABW', 'No. BTPA', 'Foto BTPA', 'Aksi'])
            ->assertSee('SU-1030-091')->assertSee('1,880 kg')->assertSee('2,200 kg')->assertSee('29 hari')->assertSee('Belum diunggah')
            ->assertSee('Tandai panen selesai')->assertSee('Ringkasan panen');
    }

    public function test_selisih_populasi_ditandai_dengan_rincian_dan_tidak_diseimbangkan(): void
    {
        $this->rekam();
        $this->panenTiga();

        $this->actingAs($this->peternak)->get(route('panen.index', $this->siklus))->assertSee('Selisih populasi: 16 ekor belum tercatat')->assertSee('NOA tidak menyeimbangkan angka otomatis');
        $this->actingAs($this->peternak)->get(route('panen.selisih', $this->siklus))->assertOk()
            ->assertSee('Populasi awal (DOC)')->assertSee('Ayam mati (recording)')->assertSee('86 ekor')->assertSee('Ayam afkir (recording)')->assertSee('4.984 ekor')->assertSee('−16 ekor')
            ->assertSee('Periksa recording')->assertSee('Periksa tahap panen');
    }

    public function test_ringkasan_panen_memuat_cara_hitung(): void
    {
        $this->rekam();
        $this->panenTiga();

        $this->actingAs($this->peternak)->get(route('panen.ringkasan', $this->siklus))->assertOk()
            ->assertSee('Ringkasan panen')->assertSee('2,08%')->assertSee('97,92%')->assertSee('Cara hitung')->assertSee('1.400×29 + 1.600×32 + 1.880×35')->assertSee('10.016 ÷ 4.880')->assertSee('Selisih populasi');
    }

    public function test_form_tahap_panen_memuat_umur_sisa_tercatat_dan_pratinjau(): void
    {
        $this->rekam();
        $this->tahap('2026-10-24', 1400, 2632);
        $this->tahap('2026-10-27', 1600, 3248);

        $this->actingAs($this->peternak)->get(route('panen.create', $this->siklus))->assertOk()
            ->assertSee('Tambah tahap panen')->assertSee('Tahap 3')->assertSee('Tanggal panen *')->assertSee('Umur panen')->assertSee('Jumlah ekor *')->assertSee('Sisa ayam hidup tercatat: 1.896 ekor')
            ->assertSee('Tonase *')->assertSee('Nomor BTPA *')->assertSee('Foto BTPA (opsional)')->assertSee('Pratinjau')->assertSee('ABW tahap ini')->assertSee('Total setelah disimpan');
    }

    public function test_simpan_tahap_dengan_foto_dan_nomor_btpa_wajib(): void
    {
        Storage::fake('local');
        $data = ['tanggal' => '2026-10-30', 'jumlah_ekor' => 1880, 'tonase_kg' => 4136, 'nomor_btpa' => 'SU-1030-091'];

        $this->actingAs($this->peternak)->post(route('panen.store', $this->siklus), array_merge($data, ['nomor_btpa' => '']))->assertSessionHasErrors('nomor_btpa');
        $this->actingAs($this->peternak)->post(route('panen.store', $this->siklus), $data + ['foto_btpa' => UploadedFile::fake()->image('btpa.jpg')])
            ->assertRedirect(route('panen.index', $this->siklus))->assertSessionHas('status', fn ($p) => str_contains($p, 'tersimpan'));

        $t = Panen::firstOrFail();
        $this->assertSame(35, $t->umur_hari);
        Storage::disk('local')->assertExists($t->foto_btpa);
        $this->actingAs($this->peternak)->get(route('panen.index', $this->siklus))->assertSee('Lihat foto');
    }

    public function test_panen_terkunci_untuk_siklus_ditutup(): void
    {
        $t = $this->tahap('2026-10-24', 1400, 2632);
        $this->siklus->update(['status' => 'selesai', 'tanggal_panen' => '2026-10-24']);

        $this->actingAs($this->peternak)->get(route('panen.index', $this->siklus))->assertOk()->assertDontSee('Tambah tahap panen')->assertDontSee('Ubah');
        $this->actingAs($this->peternak)->get(route('panen.create', $this->siklus))->assertForbidden();
        $this->actingAs($this->peternak)->get(route('panen.edit', $t))->assertForbidden();
        $this->actingAs($this->peternak)->post(route('panen.store', $this->siklus), ['tanggal' => '2026-10-25', 'jumlah_ekor' => 10, 'tonase_kg' => 20, 'nomor_btpa' => 'X'])->assertForbidden();
        $this->actingAs($this->peternak)->delete(route('panen.destroy', $t))->assertForbidden();
        $this->actingAs($this->peternak)->patch(route('panen.retur', $this->siklus), ['pakan_retur_sak' => 1])->assertForbidden();
    }

    public function test_panen_tetap_bisa_diubah_saat_menunggu_rhpp(): void
    {
        $this->tahap('2026-10-24', 1400, 2632);
        $this->siklus->update(['status' => 'menunggu_rhpp', 'tanggal_panen' => '2026-10-24']);

        $this->actingAs($this->peternak)->get(route('panen.create', $this->siklus))->assertOk();
    }

    // ---- Tandai panen selesai ----

    public function test_tandai_panen_selesai_mengubah_status_ke_menunggu_rhpp(): void
    {
        $this->panenTiga();

        $this->actingAs($this->peternak)->post(route('siklus.panen-selesai', $this->siklus), ['tanggal_panen' => '2026-10-30'])->assertRedirect(route('rhpp.show', $this->siklus));

        $s = $this->siklus->fresh();
        $this->assertSame(Siklus::STATUS_MENUNGGU_RHPP, $s->status);
        $this->assertSame('2026-10-30', $s->tanggal_panen->toDateString());
        $this->assertFalse($s->isTerkunci());
    }

    public function test_tandai_panen_selesai_butuh_tahap_dan_status_berjalan(): void
    {
        $this->actingAs($this->peternak)->post(route('siklus.panen-selesai', $this->siklus), ['tanggal_panen' => '2026-10-30'])->assertSessionHasErrors('siklus');
        $this->assertSame('berjalan', $this->siklus->fresh()->status);

        $this->panenTiga();
        $this->actingAs($this->peternak)->post(route('siklus.panen-selesai', $this->siklus), [])->assertSessionHasErrors('tanggal_panen');

        $this->siklus->update(['status' => 'menunggu_rhpp']);
        $this->actingAs($this->peternak)->post(route('siklus.panen-selesai', $this->siklus), ['tanggal_panen' => '2026-10-30'])->assertSessionHasErrors('siklus');
    }

    public function test_dialog_panen_selesai_menyebut_selisih_populasi(): void
    {
        $this->rekam();
        $this->panenTiga();

        $this->actingAs($this->peternak)->get(route('panen.index', $this->siklus))
            ->assertSee('Tandai panen Siklus 1 selesai?')->assertSee('Menunggu RHPP')->assertSee('16 ekor belum tercatat')->assertSee('Tanggal panen terakhir *')->assertSee('Tandai selesai');
    }

    // ---- RHPP ke penutupan ----

    public function test_alur_lengkap_panen_rhpp_rekonsiliasi_tutup(): void
    {
        $this->rekam();
        $this->panenTiga();

        $this->actingAs($this->peternak)->post(route('siklus.panen-selesai', $this->siklus), ['tanggal_panen' => '2026-10-30']);
        $this->assertSame('menunggu_rhpp', $this->siklus->fresh()->status);

        $this->actingAs($this->peternak)->put(route('rhpp.simpan', $this->siklus), $this->isianRhpp())->assertRedirect(route('rhpp.show', $this->siklus));
        $this->assertSame('rekonsiliasi', $this->siklus->fresh()->status);

        $this->actingAs($this->peternak)->get(route('siklus.tutup', $this->siklus))->assertOk()
            ->assertSee('Tutup siklus')->assertSee('Langkah terakhir setelah rekonsiliasi')->assertSee('Tanggal panen terakhir *')->assertSee('Rencana re-chick-in')
            ->assertSee('Ringkasan akhir')->assertSee('Diterima 8 Nov 2026')->assertSee('Ya, tutup &amp; kunci', false)->assertSee('Tutup Siklus 1?')
            ->assertSee('Selisih populasi 16 ekor tetap tercatat');

        $this->actingAs($this->peternak)->post(route('siklus.tutup.simpan', $this->siklus), ['tanggal_panen' => '2026-10-30', 'tanggal_rechick' => '2026-11-20'])->assertRedirect(route('siklus.show', $this->siklus));

        $s = $this->siklus->fresh();
        $this->assertSame(Siklus::STATUS_SELESAI, $s->status);
        $this->assertSame('2026-11-10', $s->ditutup_pada->toDateString());
        $this->assertSame('2026-11-20', $s->tanggal_rechick->toDateString());
        $this->assertTrue($s->isTerkunci());
        $this->actingAs($this->peternak)->get(route('siklus.show', $this->siklus))->assertSee('Siklus 1 sudah ditutup. Semua data hanya dapat dilihat.');
    }

    public function test_tutup_butuh_rhpp_dan_rechick_tidak_boleh_sebelum_panen(): void
    {
        $this->panenTiga();
        $this->siklus->update(['status' => 'menunggu_rhpp']);

        $this->actingAs($this->peternak)->post(route('siklus.tutup.simpan', $this->siklus), ['tanggal_panen' => '2026-10-30'])->assertSessionHasErrors('siklus');
        $this->assertSame('menunggu_rhpp', $this->siklus->fresh()->status);

        $this->siklus->update(['status' => 'rekonsiliasi']);
        $this->actingAs($this->peternak)->post(route('siklus.tutup.simpan', $this->siklus), ['tanggal_panen' => '2026-10-30', 'tanggal_rechick' => '2026-10-01'])->assertSessionHasErrors('tanggal_rechick');
        $this->assertSame('rekonsiliasi', $this->siklus->fresh()->status);
    }

    public function test_menunggu_rhpp_tidak_bisa_langsung_ditutup_biasa_tetapi_ada_halaman_tutup(): void
    {
        $this->panenTiga();
        $this->siklus->update(['status' => 'menunggu_rhpp']);

        $this->actingAs($this->peternak)->get(route('siklus.tutup', $this->siklus))->assertOk()->assertSee('RHPP belum diinput')->assertSee('Tutup tanpa RHPP');
    }

    public function test_tutup_tanpa_rhpp_wajib_alasan_dan_mengunci_data(): void
    {
        $this->panenTiga();
        $this->siklus->update(['status' => 'menunggu_rhpp', 'tanggal_panen' => '2026-10-30']);

        $this->actingAs($this->peternak)->get(route('siklus.tutup-tanpa-rhpp', $this->siklus))->assertOk()
            ->assertSee('Tutup tanpa RHPP')->assertSee('RHPP tidak diterbitkan inti')->assertSee('Rekonsiliasi tercatat kosong')->assertSee('Tutup Siklus 1 tanpa RHPP?');

        $this->actingAs($this->peternak)->post(route('siklus.tutup-tanpa-rhpp.simpan', $this->siklus), ['tanggal_panen' => '2026-10-30', 'alasan_penutupan' => 'Karena iseng'])->assertSessionHasErrors('alasan_penutupan');
        $this->actingAs($this->peternak)->post(route('siklus.tutup-tanpa-rhpp.simpan', $this->siklus), [
            'tanggal_panen' => '2026-10-30', 'alasan_penutupan' => 'RHPP tidak diterbitkan inti', 'keterangan_penutupan' => 'Sudah ditanyakan ke PPL Rina.',
        ])->assertRedirect(route('siklus.show', $this->siklus));

        $s = $this->siklus->fresh();
        $this->assertSame(Siklus::STATUS_DITUTUP_TANPA_RHPP, $s->status);
        $this->assertSame('RHPP tidak diterbitkan inti', $s->alasan_penutupan);
        $this->assertSame('Sudah ditanyakan ke PPL Rina.', $s->keterangan_penutupan);
        $this->assertTrue($s->isTerkunci());
        $this->actingAs($this->peternak)->get(route('rhpp.show', $this->siklus))->assertSee('Siklus ditutup tanpa RHPP');
    }

    public function test_tutup_tanpa_rhpp_tidak_untuk_siklus_berjalan(): void
    {
        $this->actingAs($this->peternak)->get(route('siklus.tutup-tanpa-rhpp', $this->siklus))->assertRedirect(route('siklus.show', $this->siklus));
        $this->actingAs($this->peternak)->post(route('siklus.tutup-tanpa-rhpp.simpan', $this->siklus), ['tanggal_panen' => '2026-10-30', 'alasan_penutupan' => 'RHPP tidak diterbitkan inti'])->assertSessionHasErrors('siklus');
        $this->assertSame('berjalan', $this->siklus->fresh()->status);
    }

    public function test_kandang_bisa_membuka_siklus_baru_setelah_ditutup(): void
    {
        $this->siklus->update(['status' => 'ditutup_tanpa_rhpp']);

        $this->actingAs($this->peternak)->get(route('siklus.baru.langkah', [$this->siklus->kandang, 1]))->assertOk()->assertSee('Identitas');
    }

    // ---- Hentikan dini ----

    public function test_hentikan_dini_menampilkan_data_saat_ini_dan_menandai_tidak_sebanding(): void
    {
        $this->rekam(32);

        $this->actingAs($this->peternak)->get(route('siklus.hentikan', $this->siklus))->assertOk()
            ->assertSee('Hentikan siklus dini')->assertSee('Wabah penyakit')->assertSee('Keterangan *')->assertSee('Tanggal dihentikan *')->assertSee('4.968')->assertSee('tidak sebanding')
            ->assertSee('Data saat ini')->assertSee('Dihentikan · terkunci');

        $this->actingAs($this->peternak)->post(route('siklus.hentikan.simpan', $this->siklus), ['alasan_penutupan' => 'Wabah penyakit', 'tanggal_dihentikan' => '2026-11-08'])->assertSessionHasErrors('keterangan_penutupan');
        $this->actingAs($this->peternak)->post(route('siklus.hentikan.simpan', $this->siklus), [
            'alasan_penutupan' => 'Wabah penyakit', 'keterangan_penutupan' => 'Kematian mendadak sejak hari ke-15.', 'tanggal_dihentikan' => '2026-11-08',
        ])->assertRedirect(route('siklus.show', $this->siklus));

        $s = $this->siklus->fresh();
        $this->assertSame(Siklus::STATUS_DIHENTIKAN, $s->status);
        $this->assertSame('2026-11-08', $s->ditutup_pada->toDateString());
        $this->assertTrue($s->isTerkunci());
        $this->actingAs($this->peternak)->get(route('laporan.siklus', $this->siklus))->assertSee('Tidak sebanding');
    }

    public function test_hentikan_hanya_untuk_siklus_berjalan(): void
    {
        $this->siklus->update(['status' => 'menunggu_rhpp']);

        $this->actingAs($this->peternak)->get(route('siklus.hentikan', $this->siklus))->assertRedirect(route('siklus.show', $this->siklus));
        $this->actingAs($this->peternak)->post(route('siklus.hentikan.simpan', $this->siklus), ['alasan_penutupan' => 'Wabah penyakit', 'keterangan_penutupan' => 'x', 'tanggal_dihentikan' => '2026-11-08'])->assertSessionHasErrors('siklus');
    }

    public function test_pengaturan_siklus_menunjuk_ke_alur_penutupan_sesuai_status(): void
    {
        $this->actingAs($this->peternak)->get(route('siklus.pengaturan', $this->siklus))->assertSee('Akhiri siklus')->assertSee(route('siklus.hentikan', $this->siklus), false)->assertSee(route('panen.index', $this->siklus), false);

        $this->siklus->update(['status' => 'rekonsiliasi']);
        $this->actingAs($this->peternak)->get(route('siklus.pengaturan', $this->siklus))->assertSee(route('siklus.tutup', $this->siklus), false)->assertDontSee(route('siklus.hentikan', $this->siklus), false);
    }

    public function test_alur_penutupan_hanya_untuk_peternak_pemilik(): void
    {
        $abk = User::factory()->abk($this->peternak)->create();
        $lain = User::factory()->create();

        foreach ([$abk, $lain] as $user) {
            $this->actingAs($user)->get(route('siklus.tutup', $this->siklus))->assertForbidden();
            $this->actingAs($user)->get(route('siklus.tutup-tanpa-rhpp', $this->siklus))->assertForbidden();
            $this->actingAs($user)->get(route('siklus.hentikan', $this->siklus))->assertForbidden();
            $this->actingAs($user)->post(route('siklus.panen-selesai', $this->siklus), ['tanggal_panen' => '2026-10-30'])->assertForbidden();
            $this->actingAs($user)->post(route('siklus.tutup.simpan', $this->siklus), ['tanggal_panen' => '2026-10-30'])->assertForbidden();
            $this->actingAs($user)->post(route('siklus.hentikan.simpan', $this->siklus), ['alasan_penutupan' => 'Wabah penyakit', 'keterangan_penutupan' => 'x', 'tanggal_dihentikan' => '2026-11-08'])->assertForbidden();
            $this->actingAs($user)->get(route('panen.selisih', $this->siklus))->assertForbidden();
            $this->actingAs($user)->get(route('panen.ringkasan', $this->siklus))->assertForbidden();
        }
        $this->assertSame('berjalan', $this->siklus->fresh()->status);
    }

    // ---- Laporan dan navigasi ----

    public function test_sidebar_peternak_punya_item_laporan_tetapi_abk_tidak(): void
    {
        $this->actingAs($this->peternak)->get(route('dashboard'))->assertSee(route('laporan.index'), false);

        $abk = User::factory()->abk($this->peternak)->create();
        $this->actingAs($abk)->get(route('dashboard'))->assertDontSee(route('laporan.index'), false);
    }

    public function test_laporan_siklus_berjalan_tanpa_panen_menampilkan_strip(): void
    {
        $this->actingAs($this->peternak)->get(route('laporan.siklus', $this->siklus))->assertOk()
            ->assertSee('Belum ada catatan panen')->assertSee('Biaya produksi (catatan peternak)')->assertSee('menunggu panen');
    }

    // ---- Halaman galat ----

    public function test_halaman_404_memakai_desain_dan_tidak_butuh_login(): void
    {
        $this->get('/halaman-yang-tidak-ada')->assertNotFound()->assertSee('Halaman tidak ditemukan (404)')->assertSee('Ke Dashboard')->assertSee('Periksa tautan');
        $this->actingAs($this->peternak)->get(route('siklus.show', 99999))->assertNotFound()->assertSee('Halaman tidak ditemukan (404)');
    }

    public function test_halaman_sesi_habis_419_dan_500_dan_503_dapat_dirender(): void
    {
        $this->assertStringContainsString('Sesi Anda telah berakhir', view('errors.419')->render());
        $this->assertStringContainsString('Masuk kembali', view('errors.419')->render());
        $this->assertStringContainsString('Terjadi kesalahan di server', view('errors.500')->render());
        $this->assertStringContainsString('Muat ulang', view('errors.500')->render());
        $this->assertStringContainsString('Data Anda yang sudah tersimpan tidak hilang', view('errors.500')->render());
        $this->assertStringContainsString('pemeliharaan', view('errors.503')->render());
    }
}
