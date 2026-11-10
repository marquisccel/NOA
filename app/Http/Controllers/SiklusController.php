<?php

namespace App\Http\Controllers;

use App\Http\Requests\SelesaiSiklusRequest;
use App\Http\Requests\SiklusRequest;
use App\Models\Siklus;
use App\Services\RingkasanPerforma;
use App\Services\RingkasanSiklus;
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
            ->orderByRaw("CASE WHEN status IN ('berjalan', 'panen_selesai', 'menunggu_rhpp', 'rekonsiliasi') THEN 0 ELSE 1 END")
            ->orderByDesc('tanggal_masuk')
            ->get();

        return view('siklus.index', compact('siklus'));
    }

    /** Membuka siklus baru kini lewat wizard di halaman kandang. */
    public function create(Request $request): RedirectResponse
    {
        Gate::authorize('create', Siklus::class);

        return $request->filled('kandang_id')
            ? redirect()->route('siklus.baru', $request->integer('kandang_id'))
            : redirect()->route('kandang.index');
    }

    /** Pembuatan satu langkah (dipakai pengujian dan integrasi); antarmuka memakai wizard. */
    public function store(SiklusRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $siklus = DB::transaction(function () use ($data) {
            $siklus = Siklus::create(collect($data)->except('kontrak')->all() + ['status' => Siklus::STATUS_BERJALAN]);
            $siklus->kontrak()->create($data['kontrak']);

            return $siklus;
        });

        return redirect()->route('siklus.dibuka', $siklus);
    }

    /** Ringkasan Siklus (tab pertama). */
    public function show(Siklus $siklus, RingkasanSiklus $ringkasan): View
    {
        Gate::authorize('view', $siklus);

        $siklus->load('kandang');

        return view('siklus.show', [
            'siklus' => $siklus,
            'nomor' => Siklus::nomorMap([$siklus->kandang_id])[$siklus->id] ?? 1,
            'r' => $ringkasan->hitung($siklus),
        ]);
    }

    /** Pengaturan Siklus: identitas, kontrak, skema bonus, standar harian; terkunci setelah siklus ditutup. */
    public function pengaturan(Siklus $siklus): View
    {
        Gate::authorize('view', $siklus);

        $siklus->load(['kandang', 'kontrak']);

        return view('siklus.pengaturan', [
            'siklus' => $siklus,
            'kontrak' => $siklus->kontrak,
            'standar' => $siklus->standarHarian()->orderBy('hari_ke')->get(),
            'parameter' => $siklus->parameterUmur()->count(),
            'vaksin' => $siklus->jadwalVaksin()->count(),
        ]);
    }

    public function edit(Siklus $siklus): View|RedirectResponse
    {
        Gate::authorize('update', $siklus);

        if ($siklus->isTerkunci()) {
            return redirect()->route('siklus.pengaturan', $siklus)->withErrors(['recording' => 'Siklus sudah ditutup; datanya terkunci dan hanya dapat dilihat.']);
        }

        return view('siklus.form', ['siklus' => $siklus->load(['kandang', 'kontrak']), 'kontrak' => $siklus->kontrak]);
    }

    public function update(SiklusRequest $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);

        if ($siklus->isTerkunci()) {
            return redirect()->route('siklus.pengaturan', $siklus)->withErrors(['recording' => 'Siklus sudah ditutup; datanya terkunci dan hanya dapat dilihat.']);
        }

        $data = $request->validated();

        DB::transaction(function () use ($data, $siklus) {
            $siklus->update(collect($data)->except('kontrak')->all());
            $siklus->kontrak()->updateOrCreate([], $data['kontrak']);
        });

        return redirect()->route('siklus.pengaturan', $siklus)->with('status', 'Pengaturan siklus berhasil disimpan.');
    }

    /** F1.9: tandai siklus selesai (atau dihentikan) beserta tanggal panen dan re-chick in. */
    public function selesai(SelesaiSiklusRequest $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);

        if ($siklus->status !== Siklus::STATUS_BERJALAN) {
            return back()->withErrors(['status' => 'Siklus ini sudah tidak berjalan.']);
        }

        $siklus->update($request->validated() + ['ditutup_pada' => now()->toDateString()]);

        $redirect = redirect()->route('siklus.show', $siklus)->with('status', 'Siklus ditandai '.mb_strtolower($siklus->labelStatus()).'.');

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
