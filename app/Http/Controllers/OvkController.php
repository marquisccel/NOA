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
        $recording = $siklus->recordings()->get()->keyBy(fn ($r) => $r->tanggal->toDateString());

        // Penanda pagi/sore dari recording pada tanggal yang sama (hanya penanda; detail ada di sini).
        $penanda = $riwayat->mapWithKeys(function ($o) use ($recording) {
            $r = $recording->get($o->tanggal->toDateString());
            $tanda = [];
            if ($r?->ovk_pagi) {
                $tanda[] = 'Pagi';
            }
            if ($r?->ovk_sore) {
                $tanda[] = 'Sore';
            }

            return [$o->id => $r && $tanda ? ['hari' => $r->hari_ke, 'waktu' => implode(' · ', $tanda)] : null];
        });

        return view('ovk.index', [
            'siklus' => $siklus->load('kandang'),
            'riwayat' => $riwayat,
            'penanda' => $penanda,
            'totalBiaya' => (float) $riwayat->sum('biaya'),
            'biayaPerJenis' => $riwayat->groupBy('jenis')->map(fn ($g) => (float) $g->sum('biaya')),
            'bisaCatat' => ! $siklus->isTerkunci(),
        ] + $this->ringkas($siklus, $riwayat));
    }

    public function create(Siklus $siklus): View
    {
        Gate::authorize('update', $siklus);
        $this->pastikanTerbuka($siklus);

        return view('ovk.form', ['siklus' => $siklus->load('kandang'), 'ovk' => new PemberianOvk] + $this->ringkas($siklus));
    }

    public function store(OvkRequest $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);
        $this->pastikanTerbuka($siklus);

        $siklus->pemberianOvk()->create($request->validated());

        return redirect()->route('ovk.index', $siklus)->with('status', 'Pemberian OVK dicatat.');
    }

    public function edit(PemberianOvk $ovk): View
    {
        Gate::authorize('update', $ovk);
        $this->pastikanTerbuka($ovk->siklus);

        return view('ovk.form', ['siklus' => $ovk->siklus->load('kandang'), 'ovk' => $ovk] + $this->ringkas($ovk->siklus));
    }

    public function update(OvkRequest $request, PemberianOvk $ovk): RedirectResponse
    {
        Gate::authorize('update', $ovk);
        $this->pastikanTerbuka($ovk->siklus);

        $ovk->update($request->validated());

        return redirect()->route('ovk.index', $ovk->siklus)->with('status', 'Pemberian OVK diperbarui.');
    }

    public function destroy(PemberianOvk $ovk): RedirectResponse
    {
        Gate::authorize('delete', $ovk);
        $this->pastikanTerbuka($ovk->siklus);

        $siklus = $ovk->siklus;
        $ovk->delete();

        return redirect()->route('ovk.index', $siklus)->with('status', 'Pemberian OVK dihapus.');
    }

    private function pastikanTerbuka(Siklus $siklus): void
    {
        abort_if($siklus->isTerkunci(), 403, 'Siklus sudah ditutup; data hanya dapat dilihat.');
    }

    /**
     * Ringkasan "sejauh ini": total biaya, jumlah pemberian, vaksin terakhir, biaya per ekor awal.
     *
     * @return array<string, mixed>
     */
    private function ringkas(Siklus $siklus, $riwayat = null): array
    {
        $riwayat ??= $siklus->pemberianOvk()->get();
        $vaksin = $riwayat->where('jenis', 'vaksin');
        $terakhir = $vaksin->sortByDesc('tanggal')->first();
        $total = (float) $riwayat->sum('biaya');

        return [
            'ringkas' => [
                'total' => $total,
                'jumlah' => $riwayat->count(),
                'vaksin' => $vaksin->count(),
                'vaksin_terakhir_hari' => $terakhir ? (int) $siklus->tanggal_masuk->copy()->startOfDay()->diffInDays($terakhir->tanggal->copy()->startOfDay()) + 1 : null,
                'per_ekor' => $siklus->populasi > 0 ? $total / $siklus->populasi : null,
            ],
        ];
    }
}
