<?php

namespace Tests\Feature;

use App\Models\Kandang;
use App\Models\Siklus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class KandangSiklusTest extends TestCase
{
    use RefreshDatabase;

    private User $peternak;

    protected function setUp(): void
    {
        parent::setUp();

        $this->peternak = User::factory()->create(['role' => User::ROLE_PETERNAK]);
    }

    private function kandang(?User $user = null): Kandang
    {
        return Kandang::create([
            'user_id' => ($user ?? $this->peternak)->id,
            'nama_farm' => 'Farm Uji',
            'kapasitas' => 4000,
        ]);
    }

    /** @return array<string, mixed> */
    private function dataSiklus(Kandang $kandang, array $override = []): array
    {
        return array_replace_recursive([
            'kandang_id' => $kandang->id,
            'tanggal_masuk' => '2026-11-01',
            'populasi' => 3000,
            'strain_doc' => 'Cobb 500',
            'jenis_pakan' => 'BR-1',
            'nama_ppl' => 'PPL Uji',
            'nama_inti' => 'Inti Uji',
            'bobot_sak_kg' => 50,
            'kontrak' => [
                'harga_doc' => 7000,
                'harga_pakan_sak' => 430000,
                'harga_ovk' => 1500,
                'harga_kontrak_ayam' => 20500,
                'bonus_fcr' => 1,
            ],
        ], $override);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('kandang.index'))->assertRedirect(route('login'));
    }

    public function test_abk_tidak_boleh_membuka_halaman_kandang_dan_siklus(): void
    {
        $abk = User::factory()->abk()->create();

        $this->actingAs($abk)->get(route('kandang.index'))->assertForbidden();
        $this->actingAs($abk)->get(route('siklus.index'))->assertForbidden();
    }

    public function test_peternak_menambah_kandang(): void
    {
        $this->actingAs($this->peternak)
            ->post(route('kandang.store'), ['nama_farm' => 'Farm Baru', 'alamat' => 'Desa Uji, Kec. Uji', 'kapasitas' => 5000, 'tipe' => 'closed_house'])
            ->assertRedirect();

        $this->assertDatabaseHas('kandang', ['nama_farm' => 'Farm Baru', 'user_id' => $this->peternak->id]);
    }

    public function test_kandang_wajib_punya_nama_dan_kapasitas_positif(): void
    {
        $this->actingAs($this->peternak)
            ->post(route('kandang.store'), ['nama_farm' => '', 'kapasitas' => 0])
            ->assertSessionHasErrors(['nama_farm', 'kapasitas']);
    }

    public function test_kandang_milik_orang_lain_tidak_bisa_dibuka(): void
    {
        $lain = $this->kandang(User::factory()->create());

        $this->actingAs($this->peternak)->get(route('kandang.show', $lain))->assertForbidden();
        $this->actingAs($this->peternak)->delete(route('kandang.destroy', $lain))->assertForbidden();
    }

    public function test_hapus_kandang_memakai_soft_delete(): void
    {
        $kandang = $this->kandang();

        $this->actingAs($this->peternak)->delete(route('kandang.destroy', $kandang))->assertRedirect(route('kandang.index'));

        $this->assertSoftDeleted('kandang', ['id' => $kandang->id]);
    }

    public function test_kandang_dengan_siklus_berjalan_tidak_bisa_dihapus(): void
    {
        $kandang = $this->kandang();
        $this->actingAs($this->peternak)->post(route('siklus.store'), $this->dataSiklus($kandang));

        $this->actingAs($this->peternak)->delete(route('kandang.destroy', $kandang))->assertSessionHasErrors('kandang');

        $this->assertNotSoftDeleted('kandang', ['id' => $kandang->id]);
    }

    public function test_membuka_siklus_menyimpan_kontrak_dan_skema_bonus(): void
    {
        $kandang = $this->kandang();

        $this->actingAs($this->peternak)->post(route('siklus.store'), $this->dataSiklus($kandang))->assertRedirect();

        $siklus = Siklus::firstOrFail();
        $this->assertSame(Siklus::STATUS_BERJALAN, $siklus->status);
        $this->assertSame('20500.00', $siklus->kontrak->harga_kontrak_ayam);
        $this->assertTrue($siklus->kontrak->bonus_fcr);
        // Checkbox yang tidak dikirim berarti skema itu tidak aktif.
        $this->assertFalse($siklus->kontrak->bonus_deplesi);
        $this->assertFalse($siklus->kontrak->bonus_ip);
    }

    public function test_r2_siklus_kedua_di_kandang_yang_sama_ditolak_dengan_pesan(): void
    {
        $kandang = $this->kandang();
        $this->actingAs($this->peternak)->post(route('siklus.store'), $this->dataSiklus($kandang));

        $this->actingAs($this->peternak)
            ->post(route('siklus.store'), $this->dataSiklus($kandang))
            ->assertSessionHasErrors('kandang_id');

        $this->assertSame(1, Siklus::count());
    }

    public function test_f1_8_kandang_berbeda_boleh_berjalan_bersamaan(): void
    {
        $this->actingAs($this->peternak)->post(route('siklus.store'), $this->dataSiklus($this->kandang()))->assertRedirect();
        $this->actingAs($this->peternak)->post(route('siklus.store'), $this->dataSiklus($this->kandang()))->assertRedirect();

        $this->assertSame(2, Siklus::where('status', Siklus::STATUS_BERJALAN)->count());
    }

    public function test_tidak_bisa_membuka_siklus_di_kandang_orang_lain(): void
    {
        $lain = $this->kandang(User::factory()->create());

        $this->actingAs($this->peternak)
            ->post(route('siklus.store'), $this->dataSiklus($lain))
            ->assertSessionHasErrors('kandang_id');
    }

    public function test_ubah_siklus_memperbarui_kontrak(): void
    {
        $kandang = $this->kandang();
        $this->actingAs($this->peternak)->post(route('siklus.store'), $this->dataSiklus($kandang));
        $siklus = Siklus::firstOrFail();

        $payload = $this->dataSiklus($kandang, ['populasi' => 3500, 'kontrak' => ['harga_doc' => 7200]]);
        unset($payload['kandang_id'], $payload['kontrak']['bonus_fcr']);
        $payload['kontrak']['bonus_ip'] = 1;

        $this->actingAs($this->peternak)->put(route('siklus.update', $siklus), $payload)->assertRedirect();

        $siklus->refresh();
        $this->assertSame(3500, $siklus->populasi);
        $this->assertSame('7200.00', $siklus->kontrak->harga_doc);
        $this->assertFalse($siklus->kontrak->bonus_fcr);
        $this->assertTrue($siklus->kontrak->bonus_ip);
    }

    public function test_f1_9_menandai_siklus_selesai_dengan_tanggal_panen_dan_rechick(): void
    {
        $kandang = $this->kandang();
        $this->actingAs($this->peternak)->post(route('siklus.store'), $this->dataSiklus($kandang));
        $siklus = Siklus::firstOrFail();

        $this->actingAs($this->peternak)->patch(route('siklus.selesai', $siklus), [
            'status' => 'selesai',
            'tanggal_panen' => '2026-12-06',
            'tanggal_rechick' => '2026-12-20',
        ])->assertRedirect();

        $siklus->refresh();
        $this->assertTrue($siklus->isTerkunci());
        $this->assertSame('2026-12-06', $siklus->tanggal_panen->toDateString());
        $this->assertSame('2026-12-20', $siklus->tanggal_rechick->toDateString());

        // Kandang kini boleh membuka siklus baru.
        $this->actingAs($this->peternak)->post(route('siklus.store'), $this->dataSiklus($kandang))->assertSessionDoesntHaveErrors();
        $this->assertSame(2, Siklus::count());
    }

    public function test_tanggal_panen_tidak_boleh_sebelum_tanggal_masuk(): void
    {
        $kandang = $this->kandang();
        $this->actingAs($this->peternak)->post(route('siklus.store'), $this->dataSiklus($kandang));

        $this->actingAs($this->peternak)->patch(route('siklus.selesai', Siklus::firstOrFail()), [
            'status' => 'selesai',
            'tanggal_panen' => '2026-10-01',
        ])->assertSessionHasErrors('tanggal_panen');
    }

    public function test_siklus_dihentikan_dini_tetap_bisa_ditandai(): void
    {
        $kandang = $this->kandang();
        $this->actingAs($this->peternak)->post(route('siklus.store'), $this->dataSiklus($kandang));
        $siklus = Siklus::firstOrFail();

        $this->actingAs($this->peternak)->patch(route('siklus.selesai', $siklus), [
            'status' => 'dihentikan',
            'tanggal_panen' => '2026-11-20',
        ])->assertRedirect();

        $this->assertSame(Siklus::STATUS_DIHENTIKAN, $siklus->fresh()->status);
    }

    public function test_unggah_standar_csv_mengganti_seluruh_tabel(): void
    {
        $kandang = $this->kandang();
        $this->actingAs($this->peternak)->post(route('siklus.store'), $this->dataSiklus($kandang));
        $siklus = Siklus::firstOrFail();

        $csv = "hari_ke,pakan_std_sak,bobot_std_gram,fcr_std,feed_intake_std_gram\n1,0.96,42,0.23,14.4\n2,2.1,58,0.30,31.0\n";
        $this->actingAs($this->peternak)
            ->post(route('siklus.standar.unggah', $siklus), ['file' => UploadedFile::fake()->createWithContent('std.csv', $csv)])
            ->assertRedirect(route('siklus.standar', $siklus));
        $this->assertSame(2, $siklus->standarHarian()->count());

        $csv2 = "hari_ke,pakan_std_sak,bobot_std_gram,fcr_std,feed_intake_std_gram\n1,1,43,0.25,15\n";
        $this->actingAs($this->peternak)
            ->post(route('siklus.standar.unggah', $siklus), ['file' => UploadedFile::fake()->createWithContent('std.csv', $csv2)]);
        $this->assertSame(1, $siklus->standarHarian()->count());
    }

    public function test_csv_titik_koma_dengan_koma_desimal_diterima(): void
    {
        $kandang = $this->kandang();
        $this->actingAs($this->peternak)->post(route('siklus.store'), $this->dataSiklus($kandang));
        $siklus = Siklus::firstOrFail();

        $csv = "hari_ke;pakan_std_sak;bobot_std_gram;fcr_std;feed_intake_std_gram\r\n1;0,96;42;0,23;14,4\r\n";
        $this->actingAs($this->peternak)
            ->post(route('siklus.standar.unggah', $siklus), ['file' => UploadedFile::fake()->createWithContent('std.csv', $csv)])
            ->assertSessionHasNoErrors();

        $this->assertSame('0.96', $siklus->standarHarian()->first()->pakan_std_sak);
    }

    public function test_csv_rusak_ditolak_dan_tabel_lama_tidak_berubah(): void
    {
        $kandang = $this->kandang();
        $this->actingAs($this->peternak)->post(route('siklus.store'), $this->dataSiklus($kandang));
        $siklus = Siklus::firstOrFail();
        $siklus->standarHarian()->create(['hari_ke' => 1, 'pakan_std_sak' => 1, 'bobot_std_gram' => 42, 'fcr_std' => 0.23, 'feed_intake_std_gram' => 14]);

        $csv = "hari_ke,pakan_std_sak,bobot_std_gram,fcr_std,feed_intake_std_gram\n1,abc,42,0.23,14.4\n1,1,42,0.23,14.4\n";
        $this->actingAs($this->peternak)
            ->post(route('siklus.standar.unggah', $siklus), ['file' => UploadedFile::fake()->createWithContent('std.csv', $csv)])
            ->assertSessionHasErrors('file');

        $this->assertSame(1, $siklus->standarHarian()->count());
        $this->assertSame('1.00', $siklus->standarHarian()->first()->pakan_std_sak);
    }

    public function test_csv_tanpa_kolom_wajib_ditolak(): void
    {
        $kandang = $this->kandang();
        $this->actingAs($this->peternak)->post(route('siklus.store'), $this->dataSiklus($kandang));

        $this->actingAs($this->peternak)
            ->post(route('siklus.standar.unggah', Siklus::firstOrFail()), ['file' => UploadedFile::fake()->createWithContent('std.csv', "hari_ke,bobot\n1,42\n")])
            ->assertSessionHasErrors('file');
    }

    public function test_parameter_umur_dan_vaksin_disimpan_sebagai_rentang(): void
    {
        $kandang = $this->kandang();
        $this->actingAs($this->peternak)->post(route('siklus.store'), $this->dataSiklus($kandang));
        $siklus = Siklus::firstOrFail();

        $this->actingAs($this->peternak)->put(route('siklus.acuan.simpan', $siklus), [
            'parameter' => [
                ['umur_awal' => 1, 'umur_akhir' => 3, 'suhu_min' => 30, 'suhu_maks' => 33, 'kepadatan_ekor_m2' => 60, 'frekuensi_pakan' => 9, 'jumlah_tma' => 60, 'jumlah_tra' => 60],
                ['umur_awal' => 19, 'umur_akhir' => 35, 'jumlah_tma' => 140, 'jumlah_tra' => 160],
            ],
            'vaksin' => [['hari_ke' => 4, 'nama_vaksin' => 'ND-IB']],
        ])->assertRedirect(route('siklus.show', $siklus));

        $this->assertSame(2, $siklus->parameterUmur()->count());
        $this->assertSame(1, $siklus->jadwalVaksin()->count());
    }

    public function test_rentang_umur_terbalik_ditolak(): void
    {
        $kandang = $this->kandang();
        $this->actingAs($this->peternak)->post(route('siklus.store'), $this->dataSiklus($kandang));

        $this->actingAs($this->peternak)->put(route('siklus.acuan.simpan', Siklus::firstOrFail()), [
            'parameter' => [['umur_awal' => 10, 'umur_akhir' => 5]],
        ])->assertSessionHasErrors('parameter.0.umur_akhir');
    }

    public function test_siklus_milik_orang_lain_tidak_bisa_diakses(): void
    {
        $lain = User::factory()->create();
        $siklus = Siklus::create([
            'kandang_id' => $this->kandang($lain)->id,
            'tanggal_masuk' => '2026-11-01',
            'populasi' => 1000,
        ]);

        $this->actingAs($this->peternak)->get(route('siklus.show', $siklus))->assertForbidden();
        $this->actingAs($this->peternak)->get(route('siklus.standar', $siklus))->assertForbidden();
        $this->actingAs($this->peternak)->put(route('siklus.acuan.simpan', $siklus), [])->assertForbidden();
    }

    public function test_halaman_utama_dapat_dirender(): void
    {
        $kandang = $this->kandang();
        $this->actingAs($this->peternak)->post(route('siklus.store'), $this->dataSiklus($kandang));
        $siklus = Siklus::firstOrFail();

        foreach ([
            route('dashboard'), route('kandang.index'), route('kandang.create'), route('kandang.show', $kandang),
            route('kandang.edit', $kandang), route('siklus.index'), route('siklus.show', $siklus), route('siklus.pengaturan', $siklus),
            route('siklus.edit', $siklus), route('siklus.standar', $siklus), route('siklus.acuan', $siklus),
        ] as $url) {
            $this->actingAs($this->peternak)->get($url)->assertOk();
        }
    }
}
