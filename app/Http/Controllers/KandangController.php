<?php

namespace App\Http\Controllers;

use App\Http\Requests\KandangRequest;
use App\Models\Kandang;
use App\Models\Panen;
use App\Models\Siklus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class KandangController extends Controller
{
    /** Daftar kandang dengan pencarian nama atau alamat, dan filter status siklus serta tipe. */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Kandang::class);

        $q = trim((string) $request->query('q'));
        $status = in_array($request->query('status'), ['berjalan', 'tanpa'], true) ? $request->query('status') : null;
        $tipe = array_key_exists((string) $request->query('tipe'), Kandang::TIPE) ? $request->query('tipe') : null;

        $totalKandang = Kandang::where('user_id', $request->user()->id)->count();

        $kandang = Kandang::where('user_id', $request->user()->id)
            ->with('siklusBerjalan')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('nama_farm', 'ilike', "%{$q}%")->orWhere('alamat', 'ilike', "%{$q}%")))
            ->when($tipe, fn ($w) => $w->where('tipe', $tipe))
            ->when($status === 'berjalan', fn ($w) => $w->whereHas('siklusBerjalan'))
            ->when($status === 'tanpa', fn ($w) => $w->whereDoesntHave('siklusBerjalan'))
            ->orderBy('nama_farm')
            ->get();

        return view('kandang.index', [
            'kandang' => $kandang,
            'nomor' => Siklus::nomorMap($kandang->pluck('id')),
            'totalKandang' => $totalKandang,
            'aktif' => $kandang->filter(fn ($k) => $k->siklusBerjalan)->count(),
            'q' => $q,
            'status' => $status,
            'tipe' => $tipe,
            'adaFilter' => $q !== '' || $status || $tipe,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Kandang::class);

        return view('kandang.form', ['kandang' => new Kandang]);
    }

    public function store(KandangRequest $request): RedirectResponse
    {
        $kandang = $request->user()->kandang()->create($request->validated());

        return redirect()->route('kandang.index')
            ->with('status', "Kandang “{$kandang->nama_farm}” berhasil ditambahkan. Buka siklus baru dari halaman detail kandang.");
    }

    public function show(Kandang $kandang): View
    {
        Gate::authorize('view', $kandang);

        $siklus = $kandang->siklus()->orderByDesc('tanggal_masuk')->orderByDesc('id')->get();
        $nomor = Siklus::nomorMap([$kandang->id]);
        $berjalan = $siklus->firstWhere('status', Siklus::STATUS_BERJALAN);
        $riwayat = $siklus->reject(fn ($s) => $berjalan && $s->is($berjalan))->values();

        return view('kandang.show', [
            'kandang' => $kandang->load('user'),
            'berjalan' => $berjalan,
            'terakhir' => $riwayat->first(),
            'riwayat' => $riwayat->take(3),
            'totalRiwayat' => $riwayat->count(),
            'nomor' => $nomor,
            'nomorBerikutnya' => count($nomor) + 1,
            'panenTerakhir' => $this->panenTerakhir($siklus->pluck('id')),
        ]);
    }

    /** Semua siklus kandang, dengan filter status akhir. */
    public function riwayat(Request $request, Kandang $kandang): View
    {
        Gate::authorize('view', $kandang);

        $status = in_array($request->query('status'), Siklus::STATUSES, true) ? $request->query('status') : null;
        $semua = $kandang->siklus()->orderByDesc('tanggal_masuk')->orderByDesc('id')->get();

        return view('kandang.riwayat', [
            'kandang' => $kandang,
            'siklus' => $status ? $semua->where('status', $status)->values() : $semua,
            'total' => $semua->count(),
            'status' => $status,
            'nomor' => Siklus::nomorMap([$kandang->id]),
            'panenTerakhir' => $this->panenTerakhir($semua->pluck('id')),
        ]);
    }

    public function edit(Kandang $kandang): View
    {
        Gate::authorize('update', $kandang);

        return view('kandang.form', compact('kandang'));
    }

    public function update(KandangRequest $request, Kandang $kandang): RedirectResponse
    {
        Gate::authorize('update', $kandang);

        $kandang->update($request->validated());

        return redirect()->route('kandang.show', $kandang)->with('status', 'Perubahan data kandang berhasil disimpan.');
    }

    public function destroy(Kandang $kandang): RedirectResponse
    {
        Gate::authorize('delete', $kandang);

        if ($kandang->siklus()->where('status', Siklus::STATUS_BERJALAN)->exists()) {
            return back()->withErrors(['kandang' => 'Kandang masih punya siklus berjalan, tidak bisa dihapus.']);
        }

        $kandang->delete();

        return redirect()->route('kandang.index')->with('status', 'Kandang dihapus.');
    }

    /** @return Collection<int, Carbon> id siklus => tanggal panen terakhir */
    private function panenTerakhir(Collection $siklusIds): Collection
    {
        return Panen::whereIn('siklus_id', $siklusIds)
            ->selectRaw('siklus_id, max(tanggal) as terakhir')
            ->groupBy('siklus_id')
            ->pluck('terakhir', 'siklus_id')
            ->map(fn ($t) => Carbon::parse($t));
    }
}
