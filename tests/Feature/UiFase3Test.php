<?php

namespace Tests\Feature;

use App\Models\Kandang;
use App\Models\RecordingHarian;
use App\Models\Siklus;
use App\Models\User;
use App\Services\StandarHarianCsv;
use Database\Seeders\SiklusContohSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * UI Fase 3: status siklus 7 tahap, wizard buka siklus, ringkasan, pengaturan siklus.
 */
class UiFase3Test extends TestCase
{
    use RefreshDatabase;

    private User $peternak;

    private Kandang $kandang;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 08:00:00');
        $this->peternak = User::factory()->create();
        $this->kandang = Kandang::create(['user_id' => $this->peternak->id, 'nama_farm' => 'Kandang 4', 'alamat' => 'Desa Uji', 'kapasitas' => 4000, 'tipe' => 'closed_house']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function siklus(string $status = 'berjalan', string $masuk = '2026-10-15', ?Kandang $k = null): Siklus
    {
        return Siklus::create(['kandang_id' => ($k ?? $this->kandang)->id, 'tanggal_masuk' => $masuk, 'populasi' => 4000, 'status' => $status, 'bobot_sak_kg' => 50, 'nama_ppl' => 'Rina']);
    }

    private function csvDesain(): UploadedFile
    {
        $isi = "hari,bobot_std_gram,feed_intake_std_gram,deplesi_std_persen\n1,62,13,0.10\n2,78,30,0.18\n3,96,50,0.25\n";

        return UploadedFile::fake()->createWithContent('standar.csv', $isi);
    }

    /** @return array<int, array<string, mixed>> */
    private function datasetLangkah(): array
    {
        return [
            1 => ['tanggal_masuk' => '2026-11-09', 'populasi' => 4000, 'strain_doc' => 'CP 707', 'jenis_pakan' => 'BR-1', 'nama_ppl' => 'Rina', 'nama_inti' => 'PT Sumber', 'bobot_sak_kg' => 50],
            2 => ['harga_doc' => 6500, 'harga_pakan_sak' => 412500, 'harga_ovk' => '', 'harga_kontrak_ayam' => 19500],
            3 => ['bonus_fcr' => 1, 'nilai_bonus_fcr' => 200, 'bonus_ip' => 1, 'nilai_bonus_ip' => 150],
        ];
    }

    private function isiWizardSampaiKonfirmasi(bool $dengan_standar = true): void
    {
        foreach ($this->datasetLangkah() as $no => $data) {
            $this->actingAs($this->peternak)->post(route('siklus.baru.simpan', [$this->kandang, $no]), $data)->assertSessionDoesntHaveErrors();
        }
        if ($dengan_standar) {
            $this->actingAs($this->peternak)->post(route('siklus.baru.standar', $this->kandang), ['file' => $this->csvDesain()]);
            $this->actingAs($this->peternak)->post(route('siklus.baru.simpan', [$this->kandang, 4]))->assertSessionDoesntHaveErrors();
        } else {
            $this->actingAs($this->peternak)->post(route('siklus.baru.lewati', $this->kandang));
        }
    }

    // ---- Status siklus 7 tahap ----

    public function test_tujuh_status_punya_label_dan_variasi_badge(): void
    {
        $this->assertCount(7, Siklus::STATUSES);
        $label = collect(Siklus::STATUSES)->map(fn ($s) => (new Siklus(['status' => $s]))->labelStatus())->all();

        $this->assertSame(['Berjalan', 'Panen selesai', 'Menunggu RHPP', 'Rekonsiliasi', 'Ditutup', 'Ditutup tanpa RHPP', 'Dihentikan'], $label);
        $this->assertSame('warning', (new Siklus(['status' => 'menunggu_rhpp']))->varianStatus());
    }

    public function test_siklus_terbuka_dan_tertutup_dipisah(): void
    {
        foreach (Siklus::TERBUKA as $s) {
            $this->assertTrue((new Siklus(['status' => $s]))->isTerbuka());
            $this->assertFalse((new Siklus(['status' => $s]))->isTerkunci());
        }
        foreach (Siklus::TERTUTUP as $s) {
            $this->assertFalse((new Siklus(['status' => $s]))->isTerbuka());
            $this->assertTrue((new Siklus(['status' => $s]))->isTerkunci());
        }
    }

    public function test_siklus_menunggu_rhpp_tetap_menahan_kandang_di_basis_data(): void
    {
        $this->siklus('menunggu_rhpp', '2026-09-01');

        $this->expectException(QueryException::class);
        $this->siklus('berjalan', '2026-11-01');
    }

    public function test_kandang_boleh_punya_siklus_baru_setelah_semua_tertutup(): void
    {
        $this->siklus('ditutup_tanpa_rhpp', '2026-01-01');
        $this->siklus('dihentikan', '2026-04-01');
        $this->siklus('selesai', '2026-07-01');

        $this->siklus('berjalan', '2026-11-01');
        $this->assertSame(4, $this->kandang->siklus()->count());
    }

    public function test_status_di_luar_tujuh_ditolak_basis_data(): void
    {
        $this->expectException(QueryException::class);
        $this->siklus('asal');
    }

    public function test_recording_terkunci_untuk_tiga_status_tertutup(): void
    {
        foreach (['selesai', 'ditutup_tanpa_rhpp', 'dihentikan'] as $i => $status) {
            $k = Kandang::create(['user_id' => $this->peternak->id, 'nama_farm' => "K{$i}", 'kapasitas' => 100]);
            $s = $this->siklus($status, '2026-10-01', $k);

            $this->actingAs($this->peternak)->post(route('recording.store', $s), ['tanggal' => '2026-10-02', 'pakan_pakai_sak' => 1, 'mati' => 0, 'afkir' => 0])->assertForbidden();
        }
    }

    public function test_recording_tetap_terbuka_saat_panen_selesai_dan_menunggu_rhpp(): void
    {
        $s = $this->siklus('menunggu_rhpp', '2026-10-01');

        $this->actingAs($this->peternak)->post(route('recording.store', $s), ['tanggal' => '2026-10-02', 'pakan_pakai_sak' => 1, 'mati' => 0, 'afkir' => 0])->assertSessionDoesntHaveErrors();
    }

    public function test_dashboard_menampilkan_siklus_menunggu_rhpp_tetapi_tidak_menghitungnya_berjalan(): void
    {
        $this->siklus('menunggu_rhpp', '2026-09-25');

        $this->actingAs($this->peternak)->get(route('dashboard'))->assertSee('Menunggu RHPP');
        $ringkas = app(\App\Services\RingkasanDashboard::class)->peternak($this->peternak);
        $this->assertSame([1, 0, 0], [$ringkas['kandang_aktif'], $ringkas['siklus_berjalan'], $ringkas['populasi_aktif']]);
    }

    // ---- Wizard buka siklus ----

    public function test_siklus_create_lama_mengarahkan_ke_wizard(): void
    {
        $this->actingAs($this->peternak)->get(route('siklus.create', ['kandang_id' => $this->kandang->id]))->assertRedirect(route('siklus.baru', $this->kandang));
        $this->actingAs($this->peternak)->get(route('siklus.baru', $this->kandang))->assertRedirect(route('siklus.baru.langkah', [$this->kandang, 1]));
    }

    public function test_langkah_satu_memuat_konteks_kandang_dan_stepper(): void
    {
        $this->siklus('selesai', '2026-06-02');

        $this->actingAs($this->peternak)->get(route('siklus.baru.langkah', [$this->kandang, 1]))->assertOk()
            ->assertSee('Identitas &amp; DOC', false)->assertSee('Tanggal DOC masuk *')->assertSee('Kapasitas kandang 4.000 ekor.')
            ->assertSee('Siklus berikutnya')->assertSee('Siklus 2')->assertSee('Siklus terakhir')->assertSee('Ditutup')
            ->assertSee('Langkah 5')->assertSee('Isian tersimpan sebagai draf');
    }

    public function test_wizard_tidak_bisa_melompati_langkah(): void
    {
        $this->actingAs($this->peternak)->get(route('siklus.baru.langkah', [$this->kandang, 3]))->assertRedirect(route('siklus.baru.langkah', [$this->kandang, 1]));
        $this->actingAs($this->peternak)->get(route('siklus.baru.langkah', [$this->kandang, 5]))->assertRedirect(route('siklus.baru.langkah', [$this->kandang, 1]));
        $this->actingAs($this->peternak)->get(route('siklus.baru.langkah', [$this->kandang, 9]))->assertNotFound();
    }

    public function test_langkah_satu_validasi_dengan_pesan_desain(): void
    {
        $this->actingAs($this->peternak)->post(route('siklus.baru.simpan', [$this->kandang, 1]), [
            'populasi' => 4500, 'strain_doc' => 'CP 707', 'jenis_pakan' => 'BR-1', 'nama_ppl' => 'Rina', 'nama_inti' => 'PT S', 'bobot_sak_kg' => 0,
        ])->assertSessionHasErrors([
            'tanggal_masuk' => 'Tanggal DOC masuk wajib diisi.',
            'populasi' => 'Melebihi kapasitas kandang (4.000 ekor).',
            'bobot_sak_kg' => 'Bobot per sak harus lebih dari 0.',
        ]);
    }

    public function test_langkah_dua_wajib_harga_doc_pakan_dan_ayam_tetapi_ovk_opsional(): void
    {
        $this->actingAs($this->peternak)->post(route('siklus.baru.simpan', [$this->kandang, 1]), $this->datasetLangkah()[1]);

        $this->actingAs($this->peternak)->post(route('siklus.baru.simpan', [$this->kandang, 2]), [])
            ->assertSessionHasErrors(['harga_doc', 'harga_pakan_sak', 'harga_kontrak_ayam'])->assertSessionDoesntHaveErrors('harga_ovk');
    }

    public function test_langkah_tiga_nilai_wajib_hanya_untuk_bonus_aktif(): void
    {
        foreach ([1, 2] as $no) {
            $this->actingAs($this->peternak)->post(route('siklus.baru.simpan', [$this->kandang, $no]), $this->datasetLangkah()[$no]);
        }

        $this->actingAs($this->peternak)->post(route('siklus.baru.simpan', [$this->kandang, 3]), ['bonus_fcr' => 1])->assertSessionHasErrors('nilai_bonus_fcr');
        $this->actingAs($this->peternak)->post(route('siklus.baru.simpan', [$this->kandang, 3]), [])->assertSessionDoesntHaveErrors();
        $this->actingAs($this->peternak)->get(route('siklus.baru.langkah', [$this->kandang, 4]))->assertOk();
    }

    public function test_langkah_empat_unggah_format_desain_dan_pratinjau(): void
    {
        foreach ($this->datasetLangkah() as $no => $data) {
            $this->actingAs($this->peternak)->post(route('siklus.baru.simpan', [$this->kandang, $no]), $data);
        }

        $this->actingAs($this->peternak)->post(route('siklus.baru.standar', $this->kandang), ['file' => $this->csvDesain()])->assertRedirect(route('siklus.baru.langkah', [$this->kandang, 4]));

        $this->actingAs($this->peternak)->get(route('siklus.baru.langkah', [$this->kandang, 4]))->assertOk()
            ->assertSee('standar.csv')->assertSee('3 baris')->assertSee('Pratinjau')->assertSee('Bobot standar (g)')->assertSee('0,25');
    }

    public function test_langkah_empat_baris_bermasalah_dilaporkan_dan_menghalangi_lanjut(): void
    {
        foreach ($this->datasetLangkah() as $no => $data) {
            $this->actingAs($this->peternak)->post(route('siklus.baru.simpan', [$this->kandang, $no]), $data);
        }
        $isi = "hari,bobot_std_gram,feed_intake_std_gram,deplesi_std_persen\n3,96,50,0.25\n4,1l7,73,0.31\n12,447,,0.55\n";
        $this->actingAs($this->peternak)->post(route('siklus.baru.standar', $this->kandang), ['file' => UploadedFile::fake()->createWithContent('rusak.csv', $isi)]);

        $this->actingAs($this->peternak)->get(route('siklus.baru.langkah', [$this->kandang, 4]))
            ->assertSee('Baris 3: bobot_std_gram berisi “1l7” — harus angka.')->assertSee('Baris 4: feed_intake_std_gram kosong.')->assertSee('2 masalah');

        $this->actingAs($this->peternak)->post(route('siklus.baru.simpan', [$this->kandang, 4]))->assertSessionHasErrors('file');
        $this->actingAs($this->peternak)->get(route('siklus.baru.langkah', [$this->kandang, 5]))->assertRedirect(route('siklus.baru.langkah', [$this->kandang, 4]));
    }

    public function test_langkah_empat_tanpa_file_atau_lewati_ditolak_lalu_boleh_dilewati(): void
    {
        foreach ($this->datasetLangkah() as $no => $data) {
            $this->actingAs($this->peternak)->post(route('siklus.baru.simpan', [$this->kandang, $no]), $data);
        }

        $this->actingAs($this->peternak)->post(route('siklus.baru.simpan', [$this->kandang, 4]))->assertSessionHasErrors('file');
        $this->actingAs($this->peternak)->post(route('siklus.baru.lewati', $this->kandang))->assertRedirect(route('siklus.baru.langkah', [$this->kandang, 5]));
        $this->actingAs($this->peternak)->get(route('siklus.baru.langkah', [$this->kandang, 5]))->assertSee('Belum ada — dilewati')->assertSee('Kolom standar tampil “–” sampai diunggah');
    }

    public function test_konfirmasi_menampilkan_ringkasan_dengan_tautan_ubah(): void
    {
        $this->isiWizardSampaiKonfirmasi();

        $this->actingAs($this->peternak)->get(route('siklus.baru.langkah', [$this->kandang, 5]))->assertOk()
            ->assertSee('Rp 6.500 / ekor')->assertSee('Rp 412.500 / sak')->assertSee('Aktif · Rp 200 / kg')->assertSee('Nonaktif')
            ->assertSee('CP 707 · BR-1')->assertSee('Rina · PT Sumber')->assertSee('Ubah · Langkah 1')->assertSee('Ubah · Langkah 4')->assertSee('Buka Siklus');
    }

    public function test_buka_siklus_membuat_siklus_kontrak_nilai_bonus_dan_standar_turunan(): void
    {
        $this->isiWizardSampaiKonfirmasi();

        $r = $this->actingAs($this->peternak)->post(route('siklus.baru.buka', $this->kandang));

        $siklus = Siklus::firstOrFail();
        $r->assertRedirect(route('siklus.dibuka', $siklus));
        $this->assertSame([Siklus::STATUS_BERJALAN, 4000, 'CP 707'], [$siklus->status, $siklus->populasi, $siklus->strain_doc]);
        $this->assertSame('6500.00', $siklus->kontrak->harga_doc);
        $this->assertNull($siklus->kontrak->harga_ovk);
        $this->assertTrue($siklus->kontrak->bonus_fcr);
        $this->assertSame('200.00', $siklus->kontrak->nilai_bonus_fcr);
        $this->assertFalse($siklus->kontrak->bonus_deplesi);
        $this->assertNull($siklus->kontrak->nilai_bonus_deplesi);

        $std = $siklus->standarHarian()->orderBy('hari_ke')->get();
        $this->assertCount(3, $std);
        // Pakan kumulatif (sak) = feed intake 30 g x 4.000 ekor / 1000 / 50 kg = 2,4; FCR std = 30 / 78.
        $this->assertSame('2.40', $std[1]->pakan_std_sak);
        $this->assertEqualsWithDelta(30 / 78, (float) $std[1]->fcr_std, 0.001);
        $this->assertSame('0.18', $std[1]->deplesi_std_persen);
    }

    public function test_buka_siklus_tanpa_standar_dan_draf_dibersihkan(): void
    {
        $this->isiWizardSampaiKonfirmasi(false);

        $this->actingAs($this->peternak)->post(route('siklus.baru.buka', $this->kandang));

        $this->assertSame(0, Siklus::firstOrFail()->standarHarian()->count());
        $this->assertNull(session('siklus_baru.'.$this->kandang->id));
        $this->actingAs($this->peternak)->get(route('siklus.baru.langkah', [$this->kandang, 2]))->assertOk()->assertSee('masih punya siklus yang belum ditutup');
    }

    public function test_buka_siklus_sebelum_langkah_lengkap_ditolak(): void
    {
        $this->actingAs($this->peternak)->post(route('siklus.baru.buka', $this->kandang))->assertRedirect(route('siklus.baru.langkah', [$this->kandang, 1]));
        $this->assertSame(0, Siklus::count());
    }

    public function test_halaman_sukses_menampilkan_nomor_dan_pengalihan(): void
    {
        $this->isiWizardSampaiKonfirmasi(false);
        $this->actingAs($this->peternak)->post(route('siklus.baru.buka', $this->kandang));
        $siklus = Siklus::firstOrFail();

        $this->actingAs($this->peternak)->get(route('siklus.dibuka', $siklus))->assertOk()
            ->assertSee('Siklus 1 di Kandang 4 berhasil dibuka')->assertSee('9 Nov 2026')->assertSee('Unggah standar')->assertSee('Ke Ringkasan Siklus')
            ->assertSee('http-equiv="refresh"', false);
    }

    public function test_wizard_menampilkan_gagal_bila_kandang_masih_punya_siklus_terbuka(): void
    {
        $this->siklus('menunggu_rhpp', '2026-09-25')->update(['tanggal_panen' => '2026-10-30', 'nama_inti' => 'PT Sumber Unggas']);

        foreach ([route('siklus.baru', $this->kandang), route('siklus.baru.langkah', [$this->kandang, 1])] as $url) {
            $this->actingAs($this->peternak)->get($url)->assertOk()
                ->assertSee('Kandang 4 masih punya siklus yang belum ditutup')->assertSee('Menunggu RHPP')->assertSee('menunggu RHPP dari PT Sumber Unggas')->assertSee('Buka Siklus 1');
        }
        $this->actingAs($this->peternak)->post(route('siklus.baru.buka', $this->kandang))->assertOk()->assertSee('masih punya siklus yang belum ditutup');
        $this->assertSame(1, Siklus::count());
    }

    public function test_wizard_hanya_untuk_pemilik_kandang(): void
    {
        $lain = User::factory()->create();
        $abk = User::factory()->abk($this->peternak)->create();

        foreach ([$lain, $abk] as $user) {
            $this->actingAs($user)->get(route('siklus.baru.langkah', [$this->kandang, 1]))->assertForbidden();
            $this->actingAs($user)->post(route('siklus.baru.simpan', [$this->kandang, 1]), $this->datasetLangkah()[1])->assertForbidden();
            $this->actingAs($user)->post(route('siklus.baru.buka', $this->kandang))->assertForbidden();
        }
    }

    public function test_draf_wizard_terpisah_per_kandang(): void
    {
        $k2 = Kandang::create(['user_id' => $this->peternak->id, 'nama_farm' => 'Kandang 5', 'alamat' => 'X', 'kapasitas' => 4000, 'tipe' => 'open_house']);
        $this->actingAs($this->peternak)->post(route('siklus.baru.simpan', [$this->kandang, 1]), $this->datasetLangkah()[1]);

        $this->actingAs($this->peternak)->get(route('siklus.baru.langkah', [$k2, 2]))->assertRedirect(route('siklus.baru.langkah', [$k2, 1]));
    }

    // ---- CSV standar ----

    public function test_csv_format_prd_tetap_diterima_dan_tidak_diturunkan(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'std');
        file_put_contents($path, "hari_ke,pakan_std_sak,bobot_std_gram,fcr_std,feed_intake_std_gram\n1,0.96,42,0.23,14.4\n");

        $csv = new StandarHarianCsv;
        $hasil = $csv->parse($path);
        $lengkap = $csv->lengkapi($hasil['rows'], 4000, 50);

        $this->assertSame([], $hasil['errors']);
        $this->assertSame(0.96, $lengkap[0]['pakan_std_sak']);
        $this->assertSame(0.23, $lengkap[0]['fcr_std']);
        unlink($path);
    }

    public function test_csv_kolom_wajib_hilang_dan_baris_duplikat(): void
    {
        $csv = new StandarHarianCsv;
        $a = tempnam(sys_get_temp_dir(), 'std');
        file_put_contents($a, "hari,bobot_std_gram\n1,62\n");
        $this->assertStringContainsString('feed_intake_std_gram', $csv->parse($a)['errors'][0]);

        file_put_contents($a, "hari,bobot_std_gram,feed_intake_std_gram\n1,62,13\n1,63,14\n");
        $this->assertStringContainsString('hari_ke 1 muncul lebih dari sekali', $csv->parse($a)['errors'][0]);
        unlink($a);
    }

    // ---- Ringkasan, header, tab ----

    public function test_header_siklus_dan_tab_di_halaman_siklus(): void
    {
        $s = $this->siklus();

        foreach ([route('siklus.show', $s), route('recording.index', $s), route('sampling.index', $s), route('ovk.index', $s), route('panen.index', $s), route('rhpp.show', $s), route('laporan.siklus', $s), route('siklus.pengaturan', $s)] as $url) {
            $this->actingAs($this->peternak)->get($url)->assertOk()
                ->assertSee('data-siklus-header', false)->assertSee('Siklus 1')->assertSee('Berjalan')->assertSee('Tab siklus')
                ->assertSee('Pengaturan Siklus')->assertSee('Kandang 4');
        }
    }

    public function test_tab_aktif_ditandai_aria_current(): void
    {
        $s = $this->siklus();

        $html = $this->actingAs($this->peternak)->get(route('ovk.index', $s))->getContent();
        $this->assertMatchesRegularExpression('/aria-current="page"[^>]*>\s*OVK\s*</', $html);
    }

    public function test_abk_melihat_konteks_siklus_tanpa_tab_komersial(): void
    {
        $s = $this->siklus();
        $abk = User::factory()->abk($this->peternak)->create();
        $abk->forceFill(['siklus_id' => $s->id])->save();

        $this->actingAs($abk)->get(route('recording.index', $s))->assertOk()->assertSee('data-siklus-header', false)
            ->assertDontSee('Tab siklus')->assertDontSee('Pengaturan Siklus')->assertDontSee('RHPP');
    }

    public function test_ringkasan_siklus_tanpa_recording_menampilkan_tanda_strip_dan_peringatan(): void
    {
        $s = $this->siklus('berjalan', '2026-11-10');

        $this->actingAs($this->peternak)->get(route('siklus.show', $s))->assertOk()
            ->assertSee('Belum ada recording tersimpan')->assertSee('Isi recording hari ke-1')->assertSee('Populasi awal (DOC)')
            ->assertSee('Grafik muncul setelah sampling bobot pertama')->assertSee('Grafik muncul setelah recording harian pertama')
            ->assertSee('Standar harian belum diunggah')->assertSee('Pakan masuk belum dicatat')->assertSee('Recording hari ke-1 belum diisi');
    }

    public function test_ringkasan_siklus_berjalan_membandingkan_dengan_standar(): void
    {
        $s = $this->siklus('berjalan', '2026-11-01');
        $s->standarHarian()->create(['hari_ke' => 2, 'pakan_std_sak' => 5, 'bobot_std_gram' => 78, 'fcr_std' => 0.4, 'feed_intake_std_gram' => 30, 'deplesi_std_persen' => 0.10]);
        RecordingHarian::create(['siklus_id' => $s->id, 'tanggal' => '2026-11-01', 'hari_ke' => 1, 'pakan_masuk_sak' => 20, 'pakan_pakai_sak' => 3, 'mati' => 4]);
        RecordingHarian::create(['siklus_id' => $s->id, 'tanggal' => '2026-11-02', 'hari_ke' => 2, 'pakan_pakai_sak' => 3, 'mati' => 4, 'bobot_aktual_gram' => 80]);

        $r = app(\App\Services\RingkasanSiklus::class)->hitung($s);

        $this->assertSame(3992, $r['populasi_kini']);
        $this->assertEqualsWithDelta(0.20, $r['deplesi_persen'], 0.0001);
        $this->assertSame('di atas standar', $r['deplesi_banding']);
        $this->assertEqualsWithDelta(14.0, $r['stok_sak'], 0.001);
        $this->assertSame(2, $r['fcr']['hari']);
        $this->assertEqualsWithDelta(0.4, $r['fcr']['std'], 0.0001);
        $this->assertTrue($r['standar_ada']);
        $this->actingAs($this->peternak)->get(route('siklus.show', $s))->assertSee('Standar 0,10% · di atas standar');
    }

    public function test_perhatian_stok_tipis_paraf_dan_recording_terlewat(): void
    {
        $s = $this->siklus('berjalan', '2026-11-01');
        $s->standarHarian()->create(['hari_ke' => 1, 'pakan_std_sak' => 1, 'bobot_std_gram' => 40, 'fcr_std' => 0.3, 'feed_intake_std_gram' => 10]);
        RecordingHarian::create(['siklus_id' => $s->id, 'tanggal' => '2026-11-01', 'hari_ke' => 1, 'pakan_masuk_sak' => 30, 'pakan_pakai_sak' => 12]);
        RecordingHarian::create(['siklus_id' => $s->id, 'tanggal' => '2026-11-02', 'hari_ke' => 2, 'pakan_pakai_sak' => 12]);
        // hari 3 dilewati; 4 dan seterusnya tidak ada.

        $judul = collect(app(\App\Services\RingkasanSiklus::class)->hitung($s)['perhatian'])->pluck('judul')->implode(' | ');

        $this->assertStringContainsString('Recording hari ke-3 belum diisi', $judul);
        $this->assertStringContainsString('Stok pakan 6 sak', $judul);
        $this->assertStringContainsString('2 baris belum diparaf PPL', $judul);
        $this->assertStringNotContainsString('Standar harian belum diunggah', $judul);
    }

    public function test_ringkasan_siklus_tertutup_menampilkan_angka_final_dan_penutupan(): void
    {
        $this->seed(SiklusContohSeeder::class);
        $s = Siklus::where('nama_inti', 'PT Inti Contoh')->firstOrFail();
        $s->update(['ditutup_pada' => '2026-10-30', 'tanggal_rechick' => '2026-11-20']);
        $peternak = $s->kandang->user;

        $this->actingAs($peternak)->get(route('siklus.show', $s))->assertOk()
            ->assertSee('Siklus 1 sudah ditutup. Semua data hanya dapat dilihat.')->assertSee('Angka final')->assertSee('2.904 ekor')
            ->assertSee('1,652')->assertSee('3,20%')->assertSee('Cetak laporan')->assertSee('Penutupan siklus')
            ->assertSee('30 Okt 2026')->assertSee('20 Nov 2026')->assertSee('Diterima')->assertSee('Selesai')
            ->assertDontSee('Perlu perhatian');
    }

    // ---- Pengaturan siklus ----

    public function test_pengaturan_siklus_terbuka_menampilkan_kartu_dan_tautan_ubah(): void
    {
        $s = $this->siklus();
        $s->kontrak()->create(['harga_doc' => 6500, 'harga_pakan_sak' => 412500, 'harga_kontrak_ayam' => 19500, 'bonus_fcr' => true, 'nilai_bonus_fcr' => 200]);

        $this->actingAs($this->peternak)->get(route('siklus.pengaturan', $s))->assertOk()
            ->assertSee('Rp 6.500 / ekor')->assertSee('Rp 412.500 / sak')->assertSee('Aktif · Rp 200 / kg')->assertSee('Hanya peternak')
            ->assertSee('Belum diunggah')->assertSee('Belum diisi (opsional)')->assertSee('Ubah')->assertSee('Akhiri siklus')->assertSee('Tentang perubahan')
            ->assertDontSee('Data siklus terkunci');
    }

    public function test_pengaturan_siklus_tertutup_terkunci_tanpa_tombol_ubah(): void
    {
        $s = $this->siklus('ditutup_tanpa_rhpp', '2026-06-01');

        $this->actingAs($this->peternak)->get(route('siklus.pengaturan', $s))->assertOk()
            ->assertSee('Data siklus terkunci')->assertSee('Terkunci')->assertDontSee('Akhiri siklus')->assertDontSee(route('siklus.edit', $s), false);
    }

    public function test_edit_dan_update_ditolak_untuk_siklus_tertutup(): void
    {
        $s = $this->siklus('selesai', '2026-06-01');
        $payload = ['tanggal_masuk' => '2026-06-01', 'populasi' => 999, 'bobot_sak_kg' => 50, 'kontrak' => []];

        $this->actingAs($this->peternak)->get(route('siklus.edit', $s))->assertRedirect(route('siklus.pengaturan', $s));
        $this->actingAs($this->peternak)->put(route('siklus.update', $s), $payload)->assertRedirect(route('siklus.pengaturan', $s));
        $this->assertSame(4000, $s->fresh()->populasi);
    }

    public function test_ubah_pengaturan_siklus_terbuka_menyimpan_nilai_bonus(): void
    {
        $s = $this->siklus();

        $this->actingAs($this->peternak)->get(route('siklus.edit', $s))->assertOk()->assertSee('Skema bonus')->assertSee('Nilai (Rp / kg)');
        $this->actingAs($this->peternak)->put(route('siklus.update', $s), [
            'tanggal_masuk' => '2026-10-15', 'populasi' => 3900, 'bobot_sak_kg' => 50,
            'kontrak' => ['harga_doc' => 6600, 'bonus_ip' => 1, 'nilai_bonus_ip' => 150],
        ])->assertRedirect(route('siklus.pengaturan', $s))->assertSessionHas('status', 'Pengaturan siklus berhasil disimpan.');

        $s->refresh();
        $this->assertSame(3900, $s->populasi);
        $this->assertTrue($s->kontrak->bonus_ip);
        $this->assertSame('150.00', $s->kontrak->nilai_bonus_ip);
    }

    public function test_akhiri_siklus_mencatat_tanggal_penutupan(): void
    {
        $s = $this->siklus();

        $this->actingAs($this->peternak)->patch(route('siklus.selesai', $s), ['status' => 'selesai', 'tanggal_panen' => '2026-11-09'])->assertRedirect(route('siklus.show', $s));

        $s->refresh();
        $this->assertSame('2026-11-10', $s->ditutup_pada->toDateString());
        $this->assertTrue($s->isTerkunci());
    }

    public function test_pengaturan_siklus_dan_ringkasan_hanya_untuk_peternak_pemilik(): void
    {
        $s = $this->siklus();
        $lain = User::factory()->create();
        $abk = User::factory()->abk($this->peternak)->create();

        foreach ([$lain, $abk] as $user) {
            foreach ([route('siklus.show', $s), route('siklus.pengaturan', $s), route('siklus.edit', $s)] as $url) {
                $this->actingAs($user)->get($url)->assertForbidden();
            }
        }
    }

    public function test_nomor_siklus_terlihat_di_judul_ringkasan(): void
    {
        $this->siklus('selesai', '2026-01-01');
        $s = $this->siklus('berjalan', '2026-10-01');

        $this->actingAs($this->peternak)->get(route('siklus.show', $s))->assertSee('Siklus 2')->assertSee('<title>Ringkasan Siklus · NOA</title>', false);
    }
}
