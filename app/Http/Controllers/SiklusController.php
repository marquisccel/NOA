<?php

namespace App\Http\Controllers;

use App\Http\Requests\SelesaiSiklusRequest;
use App\Http\Requests\SiklusRequest;
use App\Models\Kandang;
use App\Models\Siklus;
use App\Services\RingkasanPerforma;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SiklusController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Siklus::class);

        $siklus = Siklus::whereHas('kandang', fn ($q) => $q->where('user_id', auth()->id()))
            ->with('kandang')
            ->orderByRaw("CASE status WHEN 'berjalan' THEN 0 ELSE 1 END")
            ->orderByDesc('tanggal_masuk')
            ->get();

        return view('siklus.index', compact('siklus'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Siklus::class);

        $kandang = Kandang::where('user_id', $request->user()->id)->orderBy('nama_farm')->get();

        return view('siklus.form', [
            'siklus' => new Siklus(['bobot_sak_kg' => 50, 'tanggal_masuk' => now()]),
            'kontrak' => null,
            'kandang' => $kandang,
            'kandangDipilih' => $request->integer('kandang_id') ?: null,
        ]);
    }

    public function store(SiklusRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $siklus = DB::transaction(function () use ($data) {
            $siklus = Siklus::create(collect($data)->except('kontrak')->all() + ['status' => Siklus::STATUS_BERJALAN]);
            $siklus->kontrak()->create($data['kontrak']);

            return $siklus;
        });

        return redirect()->route('siklus.show', $siklus)
            ->with('status', 'Siklus dibuka. Unggah tabel standar harian agar perbandingan harian berjalan.');
    }

    public function show(Siklus $siklus): View
    {
        Gate::authorize('view', $siklus);

        $siklus->load(['kandang', 'kontrak', 'parameterUmur', 'jadwalVaksin']);
        $standarJumlah = $siklus->standarHarian()->count();

        return view('siklus.show', compact('siklus', 'standarJumlah'));
    }

    public function edit(Siklus $siklus): View
    {
        Gate::authorize('update', $siklus);

        return view('siklus.form', [
            'siklus' => $siklus->load('kandang'),
            'kontrak' => $siklus->kontrak,
            'kandang' => collect([$siklus->kandang]),
            'kandangDipilih' => $siklus->kandang_id,
        ]);
    }

    public function update(SiklusRequest $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);

        $data = $request->validated();

        DB::transaction(function () use ($data, $siklus) {
            $siklus->update(collect($data)->except('kontrak')->all());
            $siklus->kontrak()->updateOrCreate([], $data['kontrak']);
        });

        return redirect()->route('siklus.show', $siklus)->with('status', 'Siklus diperbarui.');
    }

    /** F1.9: tandai siklus selesai (atau dihentikan) beserta tanggal panen dan re-chick in. */
    public function selesai(SelesaiSiklusRequest $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);

        if ($siklus->status !== Siklus::STATUS_BERJALAN) {
            return back()->withErrors(['status' => 'Siklus ini sudah tidak berjalan.']);
        }

        $siklus->update($request->validated());

        $redirect = redirect()->route('siklus.show', $siklus)->with('status', 'Siklus ditandai '.$siklus->status.'.');

        // Peringatan saat siklus ditutup: data panen belum lengkap atau populasi tidak seimbang (R7, R10).
        $p = app(RingkasanPerforma::class)->hitung($siklus);
        if ($p['jumlah_tahap'] === 0) {
            $redirect->with('peringatan', 'Belum ada catatan panen, jadi FCR dan IP belum bisa dihitung.');
        } elseif ($p['selisih_populasi'] !== 0) {
            $redirect->with('peringatan', 'Ekor panen ditambah deplesi berselisih '.abs($p['selisih_populasi']).' ekor dari populasi awal. Periksa catatan panen dan deplesi.');
        }

        return $redirect;
    }

    public function destroy(Siklus $siklus): RedirectResponse
    {
        Gate::authorize('delete', $siklus);

        $kandang = $siklus->kandang;
        $siklus->delete();

        return redirect()->route('kandang.show', $kandang)->with('status', 'Siklus dihapus.');
    }
}
