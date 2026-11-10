<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Navigasi;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * UI Fase 1: token desain, komponen dasar, shell per peran, dan layar autentikasi.
 */
class UiFondasiTest extends TestCase
{
    use RefreshDatabase;

    private User $peternak;

    private User $abk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->peternak = User::factory()->create(['name' => 'Pak Budi Santoso', 'email' => 'budi@noa.test']);
        $this->abk = User::factory()->abk($this->peternak)->create(['name' => 'Andi']);
    }

    // ---- Shell ----

    public function test_shell_peternak_memuat_menu_peran_badge_dan_inisial(): void
    {
        $html = $this->actingAs($this->peternak)->get(route('dashboard'))->assertOk()->getContent();

        foreach (['Utama', 'Manajemen', 'Dashboard', 'Kandang &amp; Siklus', 'Pengguna &amp; Akses', 'Pengaturan', 'Peternak Plasma', 'Pak Budi Santoso', '>PB<', 'Ciutkan sidebar'] as $teks) {
            $this->assertStringContainsString($teks, $html, "Shell peternak tidak memuat: {$teks}");
        }
    }

    public function test_menu_aktif_ditandai_dengan_aria_current(): void
    {
        $this->actingAs($this->peternak)->get(route('dashboard'))->assertSee('aria-current="page"', false);
        $this->actingAs($this->peternak)->get(route('kandang.index'))->assertSee('aria-current="page"', false);
    }

    public function test_navigasi_peternak_tidak_memuat_tautan_buntu(): void
    {
        foreach (Navigasi::grup($this->peternak) as $g) {
            foreach ($g['items'] as $item) {
                $this->assertTrue(\Route::has($item['rute']), "Rute {$item['rute']} tidak ada");
            }
        }
        foreach (Navigasi::bawah($this->peternak) as $item) {
            $this->assertTrue(\Route::has($item['rute']));
        }
    }

    public function test_shell_abk_hanya_operasional_tanpa_navigasi_bawah_dan_menu_komersial(): void
    {
        $html = $this->actingAs($this->abk)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Siklus Ditugaskan', $html);
        $this->assertStringContainsString('Operasional', $html);
        $this->assertMatchesRegularExpression('/>\s*ABK\s*</', $html);
        $this->assertStringNotContainsString('Pengguna &amp; Akses', $html);
        $this->assertStringNotContainsString('Kandang &amp; Siklus', $html);
        $this->assertStringNotContainsString('aria-label="Navigasi bawah"', $html);
    }

    public function test_navigasi_bawah_ponsel_untuk_peternak(): void
    {
        $this->actingAs($this->peternak)->get(route('dashboard'))
            ->assertSee('aria-label="Navigasi bawah"', false)
            ->assertSee('Lainnya');
    }

    public function test_drawer_dan_menu_pengguna_memuat_keluar(): void
    {
        $html = $this->actingAs($this->peternak)->get(route('dashboard'))->getContent();

        $this->assertSame(2, substr_count($html, route('logout')), 'Keluar ada di menu pengguna dan drawer ponsel');
        $this->assertStringContainsString('id="drawer-menu"', $html);
    }

    public function test_judul_halaman_dan_remah_roti(): void
    {
        $this->actingAs($this->peternak)->get(route('dashboard'))
            ->assertSee('<title>Dashboard · NOA</title>', false)
            ->assertSee('Jejak halaman', false);
    }

    public function test_judul_halaman_lama_diambil_dari_slot_header(): void
    {
        $this->actingAs($this->peternak)->get(route('kandang.index'))->assertSee('<title>Kandang · NOA</title>', false);
    }

    public function test_halaman_tidak_memuat_font_atau_aset_pihak_ketiga(): void
    {
        foreach ([route('login'), route('register'), route('password.request')] as $url) {
            $this->get($url)->assertOk()->assertDontSee('fonts.googleapis', false)->assertDontSee('fonts.bunny', false)->assertDontSee('cdn.tailwindcss', false);
        }
        $this->actingAs($this->peternak)->get(route('dashboard'))->assertDontSee('fonts.googleapis', false)->assertDontSee('fonts.bunny', false);
    }

    public function test_logo_dan_font_ringan(): void
    {
        foreach (['noa-mark.webp' => 15_000, 'noa-wordmark.webp' => 15_000, 'noa-logo.webp' => 80_000] as $berkas => $maks) {
            $path = public_path("images/{$berkas}");
            $this->assertFileExists($path);
            $this->assertLessThan($maks, filesize($path), "{$berkas} terlalu besar untuk jaringan 3G");
        }

        // Font hanya subset latin (varian lain tidak ikut dibundel).
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $font = collect($manifest)->flatMap(fn ($e) => $e['assets'] ?? [])->filter(fn ($a) => str_ends_with($a, '.woff2'));
        $this->assertNotEmpty($font, 'Font woff2 harus ada di build');
        foreach ($font as $berkas) {
            $this->assertLessThan(40_000, filesize(public_path('build/'.$berkas)));
        }
    }

    public function test_inisial_dan_label_peran(): void
    {
        $this->assertSame('PB', $this->peternak->inisial());
        $this->assertSame('A', $this->abk->inisial());
        $this->assertSame('Peternak Plasma', $this->peternak->labelPeran());
        $this->assertSame('ABK', $this->abk->labelPeran());
    }

    // ---- Komponen ----

    public function test_ikon_dirender_dan_ikon_tak_dikenal_aman(): void
    {
        $ok = Blade::render('<x-icon name="check" />');
        $this->assertStringContainsString('<svg', $ok);
        $this->assertStringContainsString('<path', $ok);
        $this->assertStringContainsString('aria-hidden="true"', $ok);
        $this->assertStringContainsString('size-5', $ok);

        $kecil = Blade::render('<x-icon name="check" class="size-4" />');
        $this->assertStringContainsString('size-4', $kecil);
        $this->assertStringNotContainsString('size-5', $kecil);

        $this->assertStringContainsString('<svg', Blade::render('<x-icon name="tidak-ada" />'));
    }

    public function test_semua_ikon_desain_tersedia(): void
    {
        $ikon = require resource_path('icons/lucide.php');
        foreach (['layout-dashboard', 'warehouse', 'users', 'settings', 'panel-left-close', 'menu', 'ellipsis', 'mail', 'lock', 'eye', 'eye-off', 'loader-circle', 'circle-check', 'circle-alert', 'arrow-left', 'arrow-right', 'log-out'] as $nama) {
            $this->assertArrayHasKey($nama, $ikon);
        }
    }

    public function test_komponen_tombol_varian_dan_tautan(): void
    {
        $this->assertStringContainsString('bg-brand', Blade::render('<x-button>Simpan</x-button>'));
        $this->assertStringContainsString('border-line-control', Blade::render('<x-button variant="secondary">Batal</x-button>'));
        $tautan = Blade::render('<x-button href="/x">Ke X</x-button>');
        $this->assertStringContainsString('<a href="/x"', $tautan);
        $this->assertStringContainsString('min-h-[46px]', $tautan);
    }

    public function test_komponen_alert_peran_aksesibilitas(): void
    {
        $this->assertStringContainsString('role="alert"', Blade::render('<x-alert variant="error">Gagal</x-alert>'));
        $this->assertStringContainsString('role="alert"', Blade::render('<x-alert variant="warning">Awas</x-alert>'));
        $this->assertStringContainsString('role="status"', Blade::render('<x-alert variant="success">Ok</x-alert>'));
    }

    public function test_komponen_badge_status_berupa_kata(): void
    {
        $html = Blade::render('<x-badge variant="success" :dot="true">Berjalan</x-badge>');

        $this->assertStringContainsString('Berjalan', $html);
        $this->assertStringContainsString('bg-success', $html);
    }

    public function test_field_menampilkan_label_galat_dan_state_tidak_valid(): void
    {
        view()->share('errors', (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag(['email' => ['Format email tidak valid.']])));
        $html = Blade::render('<x-field name="email" label="Email" type="email" />');

        $this->assertStringContainsString('for="email"', $html);
        $this->assertStringContainsString('Format email tidak valid.', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('border-danger', $html);
    }

    public function test_field_kata_sandi_punya_tombol_tampilkan_dengan_label_aksesibel(): void
    {
        view()->share('errors', new \Illuminate\Support\ViewErrorBag);
        $html = Blade::render('<x-field name="password" label="Kata sandi" type="password" />');

        $this->assertStringContainsString('aria-label="Tampilkan atau sembunyikan kata sandi"', $html);
        $this->assertStringContainsString('x-bind:type="tampil ?', $html);
    }

    public function test_field_biasa_tidak_ikut_mengikat_type(): void
    {
        view()->share('errors', new \Illuminate\Support\ViewErrorBag);
        $html = Blade::render('<x-field name="tanggal" label="Tanggal" type="date" />');

        $this->assertStringNotContainsString('x-bind:type', $html);
        $this->assertStringContainsString('type="date"', $html);
    }

    // ---- Autentikasi ----

    public function test_halaman_masuk_sesuai_desain(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('Masuk ke NOA')
            ->assertSee('Satu pintu masuk untuk Peternak dan ABK')
            ->assertSee('Lupa kata sandi?')
            ->assertSee('Ingat saya di perangkat ini')
            ->assertSee('Peternak baru?')
            ->assertSee('Daftar akun')
            ->assertSee('Pantau siklus secara real-time')
            ->assertSee('Nitip, Openi, Adil');
    }

    public function test_gagal_masuk_menampilkan_alert_sekali_dalam_bahasa_indonesia(): void
    {
        $html = $this->from('/login')->followingRedirects()->post('/login', ['email' => 'budi@noa.test', 'password' => 'salah-total-1'])->getContent();

        $this->assertStringContainsString('Email atau kata sandi salah. Periksa kembali lalu coba lagi.', $html);
        $this->assertSame(1, substr_count($html, 'Email atau kata sandi salah.'), 'Pesan tidak boleh muncul dua kali');
        $this->assertStringContainsString('role="alert"', $html);
    }

    public function test_format_email_salah_tampil_di_bawah_isian_bukan_alert_gagal_masuk(): void
    {
        $html = $this->from('/login')->followingRedirects()->post('/login', ['email' => 'bukan-email', 'password' => 'apa-saja-1'])->getContent();

        $this->assertStringContainsString('Format email tidak valid.', $html);
        $this->assertStringNotContainsString('Email atau kata sandi salah', $html);
    }

    public function test_masuk_berhasil_ke_dashboard(): void
    {
        $this->post('/login', ['email' => 'budi@noa.test', 'password' => 'password'])->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_lupa_kata_sandi_jawaban_sama_untuk_email_terdaftar_dan_tidak(): void
    {
        Notification::fake();

        $terdaftar = $this->from('/forgot-password')->followingRedirects()->post('/forgot-password', ['email' => 'budi@noa.test'])->getContent();
        $tidak = $this->from('/forgot-password')->followingRedirects()->post('/forgot-password', ['email' => 'tidak-ada@noa.test'])->getContent();

        foreach ([[$terdaftar, 'budi@noa.test'], [$tidak, 'tidak-ada@noa.test']] as [$html, $email]) {
            $this->assertStringContainsString('Cek email Anda', $html);
            $this->assertStringContainsString("Jika email <strong class=\"font-semibold text-ink\">{$email}</strong> terdaftar", $html);
            $this->assertStringContainsString('Kirim ulang', $html);
        }

        Notification::assertSentToTimes($this->peternak, ResetPassword::class, 1);
        Notification::assertNothingSentTo(new User(['email' => 'tidak-ada@noa.test']));
    }

    public function test_lupa_kata_sandi_wajib_email_valid(): void
    {
        $this->post('/forgot-password', ['email' => ''])->assertSessionHasErrors('email');
        $this->post('/forgot-password', ['email' => 'bukan'])->assertSessionHasErrors('email');
    }

    public function test_atur_ulang_kata_sandi_berujung_pada_layar_berhasil(): void
    {
        Notification::fake();
        $this->post('/forgot-password', ['email' => 'budi@noa.test']);

        Notification::assertSentTo($this->peternak, ResetPassword::class, function ($n) {
            $this->get('/reset-password/'.$n->token)->assertOk()
                ->assertSee('Buat kata sandi baru')->assertSee('Minimal 8 karakter')->assertSee('Mengandung huruf')->assertSee('Mengandung angka');

            $this->from('/reset-password/x')->followingRedirects()->post('/reset-password', [
                'token' => $n->token, 'email' => 'budi@noa.test', 'password' => 'KataBaru2026', 'password_confirmation' => 'KataBaru2026',
            ])->assertSee('Kata sandi diperbarui')->assertSee('Masuk ke NOA');

            return true;
        });

        $this->assertTrue(\Hash::check('KataBaru2026', $this->peternak->fresh()->password));
    }

    public function test_token_atur_ulang_tidak_valid_menampilkan_pesan_indonesia(): void
    {
        $html = $this->from('/reset-password/salah')->followingRedirects()->post('/reset-password', [
            'token' => 'salah', 'email' => 'budi@noa.test', 'password' => 'KataBaru2026', 'password_confirmation' => 'KataBaru2026',
        ])->getContent();

        $this->assertStringContainsString('tidak valid atau sudah kedaluwarsa', $html);
    }

    public function test_syarat_kata_sandi_ditegakkan_server_dengan_pesan_indonesia(): void
    {
        $dasar = ['name' => 'Siti', 'email' => 'siti@noa.test'];

        $this->post('/register', $dasar + ['password' => 'abcdefgh', 'password_confirmation' => 'abcdefgh'])
            ->assertSessionHasErrors(['password' => 'Kata sandi harus mengandung minimal satu angka.']);
        $this->post('/register', $dasar + ['password' => '12345678', 'password_confirmation' => '12345678'])
            ->assertSessionHasErrors(['password' => 'Kata sandi harus mengandung minimal satu huruf.']);
        $this->post('/register', $dasar + ['password' => 'abc123', 'password_confirmation' => 'abc123'])
            ->assertSessionHasErrors('password');
        $this->post('/register', $dasar + ['password' => 'Aman2026x', 'password_confirmation' => 'beda'])
            ->assertSessionHasErrors(['password' => 'Kata sandi dan konfirmasinya tidak sama.']);
    }

    public function test_registrasi_peternak_sesuai_desain(): void
    {
        $this->get(route('register'))->assertOk()
            ->assertSee('Daftar sebagai Peternak')
            ->assertSee('Akun ABK dibuat oleh Peternak')
            ->assertSee('Nama lengkap')->assertSee('Konfirmasi kata sandi')->assertSee('Buat akun')->assertSee('Sudah punya akun?');

        $this->post('/register', ['name' => 'Siti Aminah', 'email' => 'siti@noa.test', 'password' => 'Aman2026x', 'password_confirmation' => 'Aman2026x'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertSame(User::ROLE_PETERNAK, User::where('email', 'siti@noa.test')->firstOrFail()->role);
    }

    public function test_pesan_validasi_bahasa_indonesia_diawali_huruf_kapital(): void
    {
        $this->post('/register', [])->assertSessionHasErrors([
            'name' => 'Nama wajib diisi.',
            'email' => 'Email wajib diisi.',
            'password' => 'Kata sandi wajib diisi.',
        ]);
    }

    public function test_email_sudah_dipakai_pesan_indonesia(): void
    {
        $this->post('/register', ['name' => 'X', 'email' => 'budi@noa.test', 'password' => 'Aman2026x', 'password_confirmation' => 'Aman2026x'])
            ->assertSessionHasErrors(['email' => 'Email sudah dipakai.']);
    }

    public function test_halaman_akar_mengarahkan_sesuai_status_masuk(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->actingAs($this->peternak)->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_pengguna_masuk_tidak_bisa_membuka_halaman_masuk_lagi(): void
    {
        $this->actingAs($this->peternak)->get(route('login'))->assertRedirect();
    }

    public function test_layar_verifikasi_dan_konfirmasi_memakai_gaya_baru(): void
    {
        $belumTerverifikasi = User::factory()->unverified()->create();

        $this->actingAs($belumTerverifikasi)->get(route('verification.notice'))->assertOk()->assertSee('Verifikasi email Anda');
        $this->actingAs($this->peternak)->get(route('password.confirm'))->assertOk()->assertSee('Konfirmasi kata sandi');
    }
}
