<?php

namespace Tests\Feature;

use App\Models\Kandang;
use App\Models\User;
use App\Services\LinkPeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Link Google Maps kandang: diisi di form, dan peta di detail kandang mengikutinya. */
class KandangLinkGmapsTest extends TestCase
{
    use RefreshDatabase;

    private User $peternak;

    protected function setUp(): void
    {
        parent::setUp();

        $this->peternak = User::factory()->create();
    }

    private function simpan(array $tambahan = []): void
    {
        $this->actingAs($this->peternak)->post(route('kandang.store'), ['nama_farm' => 'Kandang Peta', 'alamat' => 'Desa Uji, Kec. Uji', 'kapasitas' => 1000] + $tambahan);
    }

    public function test_form_menampilkan_input_link_gmaps_opsional(): void
    {
        $this->actingAs($this->peternak)->get(route('kandang.create'))->assertOk()->assertSee('Link Google Maps (opsional)')->assertSee('name="link_gmaps"', false);
    }

    public function test_link_panjang_dengan_koordinat_disimpan_dan_dipakai_peta_detail(): void
    {
        Http::fake();
        $link = 'https://www.google.com/maps/place/Dusun+Jajang/@-7.9123,112.5123,15z/data=!3m1!4b1!4m6!3m5!1s0x0:0x0!8m2!3d-7.9001234!4d112.5234567';
        $this->simpan(['link_gmaps' => $link]);

        $k = Kandang::firstOrFail();
        $this->assertSame($link, $k->link_gmaps);
        $this->assertSame('-7.9001234,112.5234567', $k->peta_query);
        Http::assertNothingSent();

        $html = $this->actingAs($this->peternak)->get(route('kandang.show', $k))->assertOk()->getContent();
        $this->assertStringContainsString('maps?q='.rawurlencode('-7.9001234,112.5234567'), $html);
        $this->assertStringContainsString('href="'.e($link).'"', $html);
    }

    public function test_link_pendek_diikuti_sekali_saat_disimpan(): void
    {
        Http::fake([
            'maps.app.goo.gl/*' => Http::response('', 302, ['Location' => 'https://www.google.com/maps/place/Kandang/@-7.5,112.1,17z']),
        ]);
        $this->simpan(['link_gmaps' => 'https://maps.app.goo.gl/abc123']);

        $k = Kandang::firstOrFail();
        $this->assertSame('-7.5,112.1', $k->peta_query);
        $this->assertSame('https://maps.app.goo.gl/abc123', $k->link_gmaps);
    }

    public function test_link_pendek_yang_dialihkan_ke_luar_google_tidak_diikuti(): void
    {
        Http::fake([
            'maps.app.goo.gl/*' => Http::response('', 302, ['Location' => 'https://situs-lain.example/jebakan']),
            '*' => Http::response('', 200),
        ]);
        $this->simpan(['link_gmaps' => 'https://maps.app.goo.gl/abc123']);

        $this->assertNull(Kandang::firstOrFail()->peta_query);
        Http::assertSentCount(1);
    }

    public function test_link_pendek_gagal_diikuti_tetap_tersimpan_dan_peta_pakai_alamat(): void
    {
        Http::fake(['*' => Http::response('', 500)]);
        $this->simpan(['link_gmaps' => 'https://maps.app.goo.gl/rusak']);

        $k = Kandang::firstOrFail();
        $this->assertSame('https://maps.app.goo.gl/rusak', $k->link_gmaps);
        $this->assertNull($k->peta_query);

        $html = $this->actingAs($this->peternak)->get(route('kandang.show', $k))->getContent();
        $this->assertStringContainsString('maps?q='.rawurlencode('Desa Uji, Kec. Uji'), $html);
        $this->assertStringContainsString('href="https://maps.app.goo.gl/rusak"', $html);
    }

    public function test_link_di_luar_google_maps_ditolak(): void
    {
        foreach (['https://situs-lain.example/maps', 'http://www.google.com/maps/@-7.5,112.1,15z', 'javascript:alert(1)', 'https://google.com.jahat.example/maps'] as $buruk) {
            $this->actingAs($this->peternak)->post(route('kandang.store'), ['nama_farm' => 'X', 'alamat' => 'Y', 'kapasitas' => 10, 'link_gmaps' => $buruk])
                ->assertSessionHasErrors('link_gmaps');
        }
        $this->assertSame(0, Kandang::count());
    }

    public function test_detail_otomatis_berubah_saat_link_diubah_atau_dikosongkan(): void
    {
        $this->simpan(['link_gmaps' => 'https://www.google.com/maps/@-7.1,112.2,15z']);
        $k = Kandang::firstOrFail();

        $this->actingAs($this->peternak)->put(route('kandang.update', $k), ['nama_farm' => 'Kandang Peta', 'alamat' => 'Desa Uji, Kec. Uji', 'kapasitas' => 1000, 'link_gmaps' => 'https://www.google.com/maps/@-7.3,112.4,15z'])
            ->assertRedirect(route('kandang.show', $k));
        $this->assertSame('-7.3,112.4', $k->fresh()->peta_query);
        $this->actingAs($this->peternak)->get(route('kandang.show', $k))->assertSee(rawurlencode('-7.3,112.4'), false);

        $this->actingAs($this->peternak)->put(route('kandang.update', $k), ['nama_farm' => 'Kandang Peta', 'alamat' => 'Desa Uji, Kec. Uji', 'kapasitas' => 1000, 'link_gmaps' => ''])
            ->assertRedirect(route('kandang.show', $k));
        $k->refresh();
        $this->assertNull($k->link_gmaps);
        $this->assertNull($k->peta_query);
        $this->actingAs($this->peternak)->get(route('kandang.show', $k))->assertSee(rawurlencode('Desa Uji, Kec. Uji'), false);
    }

    public function test_tanpa_link_perilaku_lama_tetap_dipakai(): void
    {
        $this->simpan();

        $k = Kandang::firstOrFail();
        $this->assertNull($k->link_gmaps);
        $this->actingAs($this->peternak)->get(route('kandang.show', $k))->assertSee('Buka di Google Maps')->assertSee('maps/search/?api=1', false);
    }

    public function test_pengurai_link_memahami_beragam_bentuk_url(): void
    {
        $p = new LinkPeta;

        $this->assertSame('-7.1,112.2', $p->dariUrl('https://www.google.com/maps/@-7.1,112.2,15z'));
        $this->assertSame('-7.1,112.2', $p->dariUrl('https://www.google.com/maps?q=-7.1,112.2'));
        $this->assertSame('-7.1,112.2', $p->dariUrl('https://www.google.com/maps/search/?api=1&query=-7.1%2C112.2'));
        $this->assertSame('Kandang Ayam Pujon', $p->dariUrl('https://www.google.com/maps/place/Kandang+Ayam+Pujon/data=abc'));
        $this->assertNull($p->dariUrl('https://www.google.com/maps'));
        $this->assertTrue(LinkPeta::hostGoogle('https://maps.app.goo.gl/x'));
        $this->assertFalse(LinkPeta::hostGoogle('https://google.com.jahat.example/x'));
    }
}
