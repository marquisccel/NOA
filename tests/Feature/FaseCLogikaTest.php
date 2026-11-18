<?php

namespace Tests\Feature;

use App\Models\Kandang;
use App\Models\RecordingHarian;
use App\Models\Siklus;
use App\Models\User;
use App\Services\PenjelasRumus;
use Database\Seeders\SiklusContohSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Fase C: rumus transparan, kalkulator "bagaimana kalau", dan input cepat (samakan kemarin, tombol +/−, isi banyak hari). */
class FaseCLogikaTest extends TestCase
{
    use RefreshDatabase;

    private User $peternak;

    private Siklus $siklus;

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

    private function contoh(): void
    {
        $this->seed(SiklusContohSeeder::class);
        $this->siklus = Siklus::where('nama_inti', 'PT Inti Contoh')->firstOrFail();
        $this->peternak = $this->siklus->kandang->user;
    }

    /** Siklus berjalan hari ke-6 tanpa recording, untuk menguji input cepat. */
    private function berjalan(): void
    {
        $this->peternak = User::factory()->create();
        $k = Kandang::create(['user_id' => $this->peternak->id, 'nama_farm' => 'K', 'kapasitas' => 5000]);
        $this->siklus = Siklus::create(['kandang_id' => $k->id, 'tanggal_masuk' => '2026-11-05', 'populasi' => 3000, 'status' => 'berjalan', 'bobot_sak_kg' => 50]);
    }

    private function rekam(int $hari, array $o = []): void
    {
        RecordingHarian::create(array_merge(['siklus_id' => $this->siklus->id, 'tanggal' => Carbon::parse('2026-11-05')->addDays($hari - 1)->toDateString(), 'hari_ke' => $hari, 'pakan_pakai_sak' => 6, 'mati' => 2, 'afkir' => 1, 'sebab_afkir' => 'sakit'], $o));
    }

    // ---- Rumus transparan ----

    public function test_penjelasan_rumus_memakai_angka_siklus_dan_sama_dengan_hasil_sistem(): void
    {
        $this->contoh();

        $d = collect(app(PenjelasRumus::class)->untukSiklus($this->siklus))->keyBy('kunci');

        $this->assertSame(['deplesi', 'abw', 'fcr', 'ip', 'biaya'], $d->keys()->all());
        $this->assertSame('(78 + 18) ÷ 3.000 × 100', $d['deplesi']['angka']);
        $this->assertSame('3,20%', $d['deplesi']['hasil']);
        $this->assertSame('5.750 kg ÷ 2.904 ekor', $d['abw']['angka']);
        $this->assertSame('1,652', $d['fcr']['hasil']);
        $this->assertStringContainsString('9.500 kg ÷ 5.750 kg', $d['fcr']['angka']);
        $this->assertStringContainsString('Rp 105.415.000 ÷ 5.750 kg', $d['biaya']['angka']);
    }

    public function test_penjelasan_tidak_ada_sebelum_ada_panen(): void
    {
        $this->berjalan();

        $this->assertSame([], app(PenjelasRumus::class)->untukSiklus($this->siklus));
        $this->actingAs($this->peternak)->get(route('siklus.show', $this->siklus))->assertOk()->assertDontSee('Kenapa angkanya begini?');
    }

    public function test_kartu_penjelasan_tampil_di_ringkasan_dan_laporan(): void
    {
        $this->contoh();

        foreach ([route('siklus.show', $this->siklus), route('laporan.siklus', $this->siklus)] as $url) {
            $this->actingAs($this->peternak)->get($url)->assertOk()->assertSee('Kenapa angkanya begini?')->assertSee('Rumus: pakan yang terpakai (kg) ÷ total berat panen (kg)')
                ->assertSee('Angka Anda:')->assertSee('(78 + 18) ÷ 3.000 × 100');
        }
    }

    // ---- Kalkulator bagaimana kalau ----

    public function test_bahan_simulasi_memuat_harga_kontrak_dan_angka_dasar(): void
    {
        $this->contoh();

        $b = app(PenjelasRumus::class)->bahanSimulasi($this->siklus);

        $this->assertEqualsWithDelta(1.652, $b['fcr'], 0.001);
        $this->assertEqualsWithDelta(5750, $b['tonase'], 0.01);
        $this->assertSame(3000, $b['populasi']);
        $this->assertEqualsWithDelta(430000 / 50, $b['hargaPakanKg'], 0.01);
        $this->assertEqualsWithDelta(20500, $b['hargaAyamKg'], 0.01);
    }

    public function test_kalkulator_tampil_di_laporan_dan_tidak_untuk_siklus_tanpa_harga_atau_panen(): void
    {
        $this->contoh();
        $this->actingAs($this->peternak)->get(route('laporan.siklus', $this->siklus))->assertOk()
            ->assertSee('Bagaimana kalau hasilnya lebih baik?')->assertSee('id="sim-fcr"', false)->assertSee('id="sim-depl"', false)->assertSee('Pakan yang dihemat')->assertSee('Ayam tambahan terjual')->assertSee('Perkiraan selisih')
            ->assertSee('Bonus dan potongan dari inti tidak ikut dihitung');

        $this->siklus->kontrak->update(['harga_pakan_sak' => null]);
        $this->actingAs($this->peternak)->get(route('laporan.siklus', $this->siklus))->assertDontSee('Bagaimana kalau hasilnya lebih baik?');
        $this->assertNull(app(PenjelasRumus::class)->bahanSimulasi($this->siklus->fresh()));
    }

    public function test_hitungan_kalkulator_sesuai_rumus_penghematan(): void
    {
        $this->contoh();
        $b = app(PenjelasRumus::class)->bahanSimulasi($this->siklus);

        // FCR turun 0,05: hemat 0,05 x 5.750 kg = 287,5 kg pakan x Rp8.600/kg.
        $this->assertEqualsWithDelta(287.5 * 8600, ($b['fcr'] - 1.602) * $b['tonase'] * $b['hargaPakanKg'], 600);
        // Deplesi turun 1 poin dari 3.000 ekor = 30 ekor x ABW x harga ayam.
        $this->assertEqualsWithDelta(30 * $b['abw'] * 20500, 0.01 * 3000 * $b['abw'] * $b['hargaAyamKg'], 1);
    }

    // ---- Input cepat ----

    public function test_samakan_dengan_kemarin_muncul_bila_ada_catatan_sebelumnya(): void
    {
        $this->berjalan();
        $this->rekam(1, ['pakan_pakai_sak' => 7.5, 'mati' => 4, 'afkir' => 2]);

        $this->actingAs($this->peternak)->get(route('recording.create', [$this->siklus, 'tanggal' => '2026-11-06']))->assertOk()
            ->assertSee('Samakan dengan kemarin')->assertSee('7,50 sak · mati 4 · afkir 2')->assertSee('kemarin.pakai', false);

        // Hari pertama tidak punya "kemarin".
        $this->siklus->recordings()->delete();
        $this->actingAs($this->peternak)->get(route('recording.create', [$this->siklus, 'tanggal' => '2026-11-05']))->assertOk()->assertDontSee('Samakan dengan kemarin');
    }

    public function test_tombol_tambah_kurang_ada_di_isian_utama_recording(): void
    {
        $this->berjalan();

        $this->actingAs($this->peternak)->get(route('recording.create', [$this->siklus, 'tanggal' => '2026-11-05']))->assertOk()
            ->assertSee('Tambah 0.5 pada Pakan terpakai', false)->assertSee('Kurangi 1 pada Ayam mati', false)->assertSee('Tambah 1 pada Ayam afkir', false);
    }

    // ---- Isi banyak hari ----

    public function test_halaman_isi_banyak_hari_menampilkan_hari_terlewat_dan_tautan_dari_riwayat(): void
    {
        $this->berjalan();
        $this->rekam(2);

        $this->actingAs($this->peternak)->get(route('recording.massal', $this->siklus))->assertOk()
            ->assertSee('Isi banyak hari sekaligus')->assertSee('Hari ke-1')->assertSee('Hari ke-3')->assertSee('Hari ke-6')->assertDontSee('Hari ke-2<')
            ->assertSee('baris[3][pakan_pakai_sak]', false);
        $this->actingAs($this->peternak)->get(route('recording.index', $this->siklus))->assertSee('Isi 5 hari sekaligus');
    }

    public function test_simpan_banyak_hari_membuat_recording_dan_melewati_baris_kosong(): void
    {
        $this->berjalan();

        $this->actingAs($this->peternak)->post(route('recording.massal.simpan', $this->siklus), ['baris' => [
            1 => ['pakan_pakai_sak' => '1.5', 'pakan_masuk_sak' => '20', 'mati' => '3', 'afkir' => '', 'sebab_afkir' => ''],
            2 => ['pakan_pakai_sak' => '2', 'mati' => '1', 'afkir' => '2', 'sebab_afkir' => 'cacat'],
            3 => ['pakan_pakai_sak' => '', 'mati' => '', 'afkir' => ''],
        ]])->assertRedirect(route('recording.index', $this->siklus))->assertSessionHas('status', '2 hari tersimpan sekaligus.');

        $r = $this->siklus->recordings()->orderBy('hari_ke')->get();
        $this->assertSame([1, 2], $r->pluck('hari_ke')->all());
        $this->assertSame('2026-11-06', $r[1]->tanggal->toDateString());
        $this->assertSame('20.00', $r[0]->pakan_masuk_sak);
        $this->assertSame(0, $r[0]->afkir);
        $this->assertSame('cacat', $r[1]->sebab_afkir);
        $this->assertSame(3, (int) $r[0]->deplesi_harian);
    }

    public function test_simpan_banyak_hari_validasi_per_baris(): void
    {
        $this->berjalan();

        $this->actingAs($this->peternak)->post(route('recording.massal.simpan', $this->siklus), ['baris' => [
            1 => ['pakan_pakai_sak' => '', 'mati' => '2'],
            2 => ['pakan_pakai_sak' => '3', 'afkir' => '4'],
            3 => ['pakan_pakai_sak' => '-1'],
        ]])->assertSessionHasErrors(['baris.1.pakan_pakai_sak', 'baris.2.sebab_afkir', 'baris.3.pakan_pakai_sak']);
        $this->assertSame(0, $this->siklus->recordings()->count());

        $this->actingAs($this->peternak)->post(route('recording.massal.simpan', $this->siklus), ['baris' => []])->assertSessionHasErrors('baris');
    }

    public function test_simpan_banyak_hari_menolak_hari_yang_sudah_ada_dan_deplesi_melebihi_populasi(): void
    {
        $this->berjalan();
        $this->rekam(1);

        // Hari 1 sudah ada: barisnya diabaikan (bukan dibuat ganda).
        $this->actingAs($this->peternak)->post(route('recording.massal.simpan', $this->siklus), ['baris' => [1 => ['pakan_pakai_sak' => '9', 'mati' => '1']]])->assertSessionHasErrors('baris');
        $this->assertSame(1, $this->siklus->recordings()->count());

        $this->actingAs($this->peternak)->post(route('recording.massal.simpan', $this->siklus), ['baris' => [2 => ['pakan_pakai_sak' => '9', 'mati' => '5000']]])->assertSessionHasErrors('baris');
        $this->assertSame(1, $this->siklus->recordings()->count());
    }

    public function test_isi_banyak_hari_dijaga_otorisasi_dan_tidak_untuk_siklus_tertutup(): void
    {
        $this->berjalan();
        $lain = User::factory()->create();
        $abk = User::factory()->abk($this->peternak)->create();
        $abk->forceFill(['siklus_id' => $this->siklus->id])->save();

        $this->actingAs($lain)->get(route('recording.massal', $this->siklus))->assertForbidden();
        $this->actingAs($lain)->post(route('recording.massal.simpan', $this->siklus), ['baris' => [1 => ['pakan_pakai_sak' => '1']]])->assertForbidden();
        $this->actingAs($abk)->get(route('recording.massal', $this->siklus))->assertOk();

        $this->siklus->update(['status' => 'selesai', 'tanggal_panen' => '2026-11-08']);
        $this->actingAs($this->peternak)->get(route('recording.massal', $this->siklus))->assertForbidden();
    }

    public function test_tanpa_hari_terlewat_diarahkan_kembali_dengan_pesan(): void
    {
        $this->berjalan();
        foreach (range(1, 6) as $h) {
            $this->rekam($h);
        }

        $this->actingAs($this->peternak)->get(route('recording.massal', $this->siklus))->assertRedirect(route('recording.index', $this->siklus))->assertSessionHas('status');
    }
}
