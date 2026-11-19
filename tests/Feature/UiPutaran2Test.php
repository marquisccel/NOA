<?php

namespace Tests\Feature;

use App\Models\Kandang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Putaran penyempurnaan UI/UX kedua: mode gelap, logo, istilah awam, urutan menu, kartu demo.
 */
class UiPutaran2Test extends TestCase
{
    use RefreshDatabase;

    private User $peternak;

    protected function setUp(): void
    {
        parent::setUp();

        $this->peternak = User::factory()->create(['name' => 'Pak Budi']);
    }

    public function test_urutan_menu_dashboard_kandang_aktivitas(): void
    {
        $html = $this->actingAs($this->peternak)->get(route('dashboard'))->assertOk()->getContent();

        $menu = substr($html, (int) strpos($html, 'aria-label="Menu utama"'));
        $this->assertTrue(strpos($menu, '>Dashboard<') < strpos($menu, '>Kandang<'));
        $this->assertTrue(strpos($menu, '>Kandang<') < strpos($menu, '>Aktivitas<'));
        $this->assertStringNotContainsString('Kandang &amp; Periode', $html);
    }

    public function test_logo_tanpa_kartu_dan_wordmark_berwarna_hijau(): void
    {
        $html = $this->actingAs($this->peternak)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('noa-mark.webp', $html);
        $this->assertStringContainsString('noa-wordmark.webp', $html);
        $this->assertStringContainsString('dark:bg-brand-mint', $html);
        $this->assertStringNotContainsString('bg-[#FAF8F0] ring-1', $html);
    }

    public function test_kartu_demo_hanya_judul_dan_dua_tombol_bertumpuk(): void
    {
        config(['noa.demo' => true]);

        $html = $this->get(route('login'))->assertOk()->getContent();

        $this->assertStringContainsString('Coba demo tanpa daftar', $html);
        $this->assertLessThan(strpos($html, 'Masuk sebagai ABK'), strpos($html, 'Masuk sebagai peternak'));
        $this->assertStringNotContainsString('Masuk satu klik ke akun contoh', $html);
        $this->assertStringNotContainsString('anak buah kandang yang hanya mencatat', $html);
        $this->assertStringNotContainsString('sm:grid-cols-2', substr($html, (int) strpos($html, 'data-demo'), 1200));
    }

    public function test_istilah_awam_periode_dan_tipe_kandang(): void
    {
        $this->assertSame('Kandang terbuka', Kandang::TIPE[Kandang::TIPE_OPEN_HOUSE][0]);
        $this->assertSame('Kandang tertutup', Kandang::TIPE[Kandang::TIPE_CLOSED_HOUSE][0]);

        $html = $this->actingAs($this->peternak)->get(route('bantuan'))->assertOk()->getContent();
        $this->assertStringContainsString('Periode', $html);
        $this->assertStringNotContainsString('Siklus', $html);
        $this->assertStringNotContainsString('Closed House', $html);
        $this->assertStringNotContainsString('Open House', $html);
    }

    public function test_tabel_dan_form_memakai_token_warna_bukan_warna_terang_tertanam(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        $tabel = substr($css, (int) strpos($css, '.tabel {'), 900);

        $this->assertStringNotContainsString('#EDF1EE', $tabel);
        $this->assertStringNotContainsString('#E1E6E2', $tabel);
        $this->assertStringNotContainsString('#f9fbf9', $tabel);
        $this->assertStringContainsString("input[type='checkbox']", $css);
        $this->assertStringContainsString('-webkit-autofill', $css);
    }

    public function test_daftar_abk_tanpa_avatar_inisial(): void
    {
        $abk = User::factory()->abk($this->peternak)->create(['name' => 'Andi Pratama']);

        $html = $this->actingAs($this->peternak)->get(route('abk.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Andi Pratama', $html);
        $this->assertStringNotContainsString('rounded-full bg-tint text-xs font-bold text-ink-brand', $html);
        $this->assertSame('AP', $abk->inisial());
    }

    public function test_perlu_perhatian_berupa_daftar_baris_yang_bisa_diketuk(): void
    {
        $view = file_get_contents(resource_path('views/dashboard/peternak.blade.php'));

        $this->assertStringContainsString('divide-y', $view);
        $this->assertStringNotContainsString('md:grid-cols-2 xl:grid-cols-3">' . "\n" . '                        @foreach ($perhatian', $view);
    }

    public function test_detail_kandang_menampilkan_peta_dan_riwayat_ayam_dipanen(): void
    {
        $kandang = Kandang::create(['user_id' => $this->peternak->id, 'nama_farm' => 'Kandang Peta', 'alamat' => 'Desa Uji, Kec. Uji', 'kapasitas' => 5000, 'tipe' => Kandang::TIPE_CLOSED_HOUSE]);

        $html = $this->actingAs($this->peternak)->get(route('kandang.show', $kandang))->assertOk()->getContent();

        $this->assertStringContainsString('maps.google.com/maps?q=Desa%20Uji%2C%20Kec.%20Uji', $html);
        $this->assertStringContainsString('Buka di Google Maps', $html);

        $kandang->update(['alamat' => null]);
        $this->actingAs($this->peternak)->get(route('kandang.show', $kandang))->assertOk()->assertDontSee('Buka di Google Maps');
    }

    public function test_halaman_masuk_memuat_panel_gambar_ayam_3d_latar_aurora_dan_kartu_kaca(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        foreach (['class="latar"', 'data-hero', 'panel-gambar', 'statistik', 'kaca-form', 'Recording harian', 'Pantau kandang', 'Cocokkan RHPP', 'jujur', 'adil', 'Nitip, Openi, Adil', 'data-ayam', 'data-colek', 'images/ayam/badan.webp', 'images/ayam/ekor.webp', 'images/ayam/jengger.webp'] as $penanda) {
            $this->assertStringContainsString($penanda, $html, "Tidak ada: {$penanda}");
        }
        $this->assertStringContainsString('noa-mark.webp', $html);
        $this->assertStringContainsString('noa-wordmark.webp', $html);
        $this->assertFileExists(resource_path('js/masuk.js'));
        $this->assertFileExists(resource_path('js/ayam2d.js'));
        $this->assertFileExists(public_path('images/ayam/mata1.webp'));
        $this->assertStringContainsString("import('./ayam2d.js')", file_get_contents(resource_path('js/masuk.js')));
        $this->assertStringContainsString('prefers-reduced-motion', file_get_contents(resource_path('css/app.css')));
    }
}
