<?php

namespace App\Http\Controllers;

use App\Http\Requests\OvkRequest;
use App\Models\PemberianOvk;
use App\Models\Siklus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OvkController extends Controller
{
    /** F4.3: riwayat OVK satu siklus dalam satu halaman, dengan total biaya (F4.2). */
    public function index(Siklus $siklus): View
    {
        Gate::authorize('view', $siklus);

        $riwayat = $siklus->pemberianOvk()->orderByDesc('tanggal')->orderByDesc('id')->get();

        return view('ovk.index', [
            'siklus' => $siklus->load('kandang'),
            'riwayat' => $riwayat,
            'totalBiaya' => (float) $riwayat->sum('biaya'),
            'biayaPerJenis' => $riwayat->groupBy('jenis')->map(fn ($g) => (float) $g->sum('biaya')),
        ]);
    }

    public function store(OvkRequest $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);

        $siklus->pemberianOvk()->create($request->validated());

        return redirect()->route('ovk.index', $siklus)->with('status', 'Pemberian OVK dicatat.');
    }

    public function edit(PemberianOvk $ovk): View
    {
        Gate::authorize('update', $ovk);

        return view('ovk.edit', ['siklus' => $ovk->siklus->load('kandang'), 'ovk' => $ovk]);
    }

    public function update(OvkRequest $request, PemberianOvk $ovk): RedirectResponse
    {
        Gate::authorize('update', $ovk);

        $ovk->update($request->validated());

        return redirect()->route('ovk.index', $ovk->siklus)->with('status', 'Pemberian OVK diperbarui.');
    }

    public function destroy(PemberianOvk $ovk): RedirectResponse
    {
        Gate::authorize('delete', $ovk);

        $siklus = $ovk->siklus;
        $ovk->delete();

        return redirect()->route('ovk.index', $siklus)->with('status', 'Pemberian OVK dihapus.');
    }
}
