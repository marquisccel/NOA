<?php

namespace App\Http\Controllers;

use App\Http\Requests\SamplingRequest;
use App\Models\SamplingBobot;
use App\Models\Siklus;
use App\Services\RingkasanSampling;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SamplingController extends Controller
{
    public function __construct(private RingkasanSampling $ringkasan) {}

    public function index(Siklus $siklus): View
    {
        Gate::authorize('view', $siklus);

        $baris = $this->ringkasan->baris($siklus, $siklus->samplings()->get(), $siklus->standarHarian()->get());

        return view('sampling.index', [
            'siklus' => $siklus->load('kandang'),
            'baris' => array_reverse($baris),
            'terakhir' => $baris ? end($baris) : null,
            'ayamHidup' => $siklus->populasi - (int) $siklus->recordings()->sum('deplesi_harian'),
            'standarAda' => $siklus->standarHarian()->exists(),
        ]);
    }

    public function store(SamplingRequest $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);

        $sampling = $siklus->samplings()->create($request->validated() + ['hari_ke' => $request->hariKe($siklus)]);

        return $this->setelahSimpan($siklus, $sampling);
    }

    public function edit(SamplingBobot $sampling): View
    {
        Gate::authorize('update', $sampling);

        return view('sampling.edit', [
            'siklus' => $sampling->siklus->load('kandang'),
            'sampling' => $sampling,
            'ayamHidup' => $sampling->siklus->populasi - (int) $sampling->siklus->recordings()->sum('deplesi_harian'),
        ]);
    }

    public function update(SamplingRequest $request, SamplingBobot $sampling): RedirectResponse
    {
        Gate::authorize('update', $sampling);

        $siklus = $sampling->siklus;
        $sampling->update($request->validated() + ['hari_ke' => $request->hariKe($siklus)]);

        return $this->setelahSimpan($siklus, $sampling);
    }

    public function destroy(SamplingBobot $sampling): RedirectResponse
    {
        Gate::authorize('delete', $sampling);

        $siklus = $sampling->siklus;
        $sampling->delete();

        return redirect()->route('sampling.index', $siklus)->with('status', 'Sampling dihapus.');
    }

    /** F3.2-F3.4: tampilkan ABW dan pertambahan bobot harian; F3.3 sampel < 5 persen jadi peringatan. */
    private function setelahSimpan(Siklus $siklus, SamplingBobot $sampling): RedirectResponse
    {
        $b = collect($this->ringkasan->baris($siklus, $siklus->samplings()->get(), $siklus->standarHarian()->get()))
            ->first(fn ($baris) => $baris['sampling']->is($sampling));

        $pesan = "Sampling hari ke-{$sampling->hari_ke} tersimpan. ABW ".Format::angka($b['abw_gram'], 0).' g';
        $pesan .= $b['adg_gram'] !== null ? ', pertambahan '.Format::angka($b['adg_gram'], 1).' g/hari.' : '.';

        $redirect = redirect()->route('sampling.index', $siklus)->with('status', $pesan);

        if (! $b['representatif']) {
            $redirect->with('peringatan', 'Sampel hanya '.Format::angka($b['persen_populasi']).' persen dari '.Format::angka($b['ayam_hidup'], 0)
                .' ayam hidup, di bawah batas 5 persen. Hasil ditandai kurang representatif.');
        }

        return $redirect;
    }
}
