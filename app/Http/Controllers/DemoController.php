<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

/** Mode demo: masuk satu klik ke akun contoh dan kembalikan data contoh ke keadaan awal. Hanya aktif bila NOA_DEMO=true. */
class DemoController extends Controller
{
    public function masuk(Request $request, string $peran): RedirectResponse
    {
        abort_unless(config('noa.demo') && in_array($peran, ['plasma', 'abk'], true), 404);

        $email = config('noa.akun_demo.'.$peran);
        $akun = User::where('email', $email)->first();

        if (! $akun) {
            $this->siapkan(false);
            $akun = User::where('email', $email)->firstOrFail();
        }

        Auth::login($akun);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    /** Kembalikan seluruh data demo (akun demo dibuat ulang dengan tanggal relatif terhadap hari ini). */
    public function reset(Request $request): RedirectResponse
    {
        abort_unless(config('noa.demo') && in_array($request->user()->email, config('noa.akun_demo'), true), 404);

        $peran = $request->user()->isAbk() ? 'abk' : 'plasma';
        $this->siapkan(true);

        Auth::login(User::where('email', config('noa.akun_demo.'.$peran))->firstOrFail());
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Data demo dikembalikan ke keadaan awal.');
    }

    private function siapkan(bool $paksa): void
    {
        set_time_limit(120);
        Artisan::call('noa:siapkan-demo', $paksa ? ['--paksa' => true] : []);
    }
}
