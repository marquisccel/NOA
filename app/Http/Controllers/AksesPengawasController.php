<?php

namespace App\Http\Controllers;

use App\Models\AksesPengawas;
use App\Models\Siklus;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Akses hanya-baca Pengawas Inti: peternak membagikan SATU siklus ke satu pengawas dan dapat mencabutnya kapan saja.
 * Pengawas hanya melihat recording, sampling, OVK, dan panen (tanpa kontrak, biaya, bonus, RHPP, rekonsiliasi, dan tanpa paraf).
 */
class AksesPengawasController extends Controller
{
    public function index(Request $request): View
    {
        $peternak = $request->user();
        $siklus = Siklus::whereHas('kandang', fn ($k) => $k->where('user_id', $peternak->id))->with('kandang')->orderByDesc('tanggal_masuk')->get();
        $nomor = Siklus::nomorMap($siklus->pluck('kandang_id')->unique());

        return view('akses.index', [
            'akses' => AksesPengawas::where('peternak_id', $peternak->id)->with(['pengawas', 'siklus.kandang'])->orderByRaw('dicabut_pada IS NOT NULL')->orderByDesc('created_at')->get(),
            'siklus' => $siklus,
            'nomor' => $nomor,
            'jumlahAbk' => $peternak->abk()->count(),
        ]);
    }

    /** Beri akses: email pengawas yang belum punya akun dibuatkan akun Pengawas dengan sandi acak yang tampil sekali. */
    public function store(Request $request): RedirectResponse
    {
        $peternak = $request->user();

        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'siklus_id' => ['required', 'integer'],
        ], [
            'email.required' => 'Email Pengawas wajib diisi.',
            'email.email' => 'Format email belum benar.',
            'siklus_id.required' => 'Pilih siklus yang dibagikan.',
        ], ['email' => 'email Pengawas', 'siklus_id' => 'siklus']);

        $siklus = Siklus::with('kandang')->find($data['siklus_id']);
        if (! $siklus || ! Gate::forUser($peternak)->allows('view', $siklus)) {
            return back()->withInput()->withErrors(['siklus_id' => 'Siklus tidak ditemukan di kandang Anda.']);
        }

        $pengawas = User::where('email', $data['email'])->first();
        $sandiBaru = null;

        if ($pengawas && ! $pengawas->isPengawas()) {
            return back()->withInput()->withErrors(['email' => 'Email ini terdaftar sebagai akun lain, bukan Pengawas Inti.']);
        }

        if (! $pengawas) {
            $sandiBaru = chr(random_int(65, 90)).Str::lower(Str::random(5)).random_int(1000, 9999);
            $pengawas = (new User)->forceFill([
                'name' => Str::of($data['email'])->before('@')->replace(['.', '_', '-'], ' ')->title()->toString(),
                'email' => $data['email'],
                'password' => Hash::make($sandiBaru),
                'role' => User::ROLE_PENGAWAS,
                'aktif' => true,
                'email_verified_at' => now(),
            ]);
            $pengawas->save();
        }

        if (AksesPengawas::aktif()->where('pengawas_id', $pengawas->id)->where('siklus_id', $siklus->id)->exists()) {
            return back()->withInput()->withErrors(['email' => 'Pengawas ini sudah punya akses aktif ke siklus tersebut.']);
        }

        AksesPengawas::create(['pengawas_id' => $pengawas->id, 'siklus_id' => $siklus->id, 'peternak_id' => $peternak->id]);

        $redirect = redirect()->route('akses.index')->with('status', "Akses diberikan ke {$pengawas->name} untuk {$siklus->kandang->nama_farm}.");

        return $sandiBaru ? $redirect->with('sandi_pengawas', ['email' => $pengawas->email, 'sandi' => $sandiBaru]) : $redirect;
    }

    /** Cabut akses: pengawas langsung tidak bisa melihat siklus; data tidak berubah dan akses dapat diberikan lagi. */
    public function cabut(Request $request, AksesPengawas $akses): RedirectResponse
    {
        abort_unless($akses->peternak_id === $request->user()->id, 403);

        if ($akses->masihAktif()) {
            $akses->update(['dicabut_pada' => now()]);
        }

        return redirect()->route('akses.index')->with('status', 'Akses '.$akses->pengawas->name.' dicabut.');
    }
}
