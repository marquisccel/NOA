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
        Gate::authorize('lihatCatatan', $siklus);

        $baris = $this->ringkasan->baris($siklus, $siklus->samplings()->get(), $siklus->standarHarian()->get());
        $hari = array_column($baris, 'hari_ke');
        $interval = count($hari) >= 2 ? $hari[count($hari) - 1] - $hari[count($hari) - 2] : null;

        return view('sampling.index', [
            'siklus' => $siklus->load('kandang'),
            'baris' => array_reverse($baris),
            'terakhir' => $baris ? end($baris) : null,
            'interval' => $interval,
            'hasilId' => session('hasil_sampling'),
            'bisaCatat' => Gate::allows('update', $siklus) && ! $siklus->isTerkunci(),
            'standarAda' => $siklus->standarHarian()->exists(),
        ]);
    }

    public function create(Siklus $siklus): View
    {
        Gate::authorize('update', $siklus);
        abort_if($siklus->isTerkunci(), 403, 'Siklus sudah ditutup; data hanya dapat dilihat.');

        return view('sampling.form', $this->dataForm($siklus, new SamplingBobot));
    }

    public function store(SamplingRequest $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);
        abort_if($siklus->isTerkunci(), 403, 'Siklus sudah ditutup; data hanya dapat dilihat.');

        $sampling = $siklus->samplings()->create($request->validated() + ['hari_ke' => $request->hariKe($siklus)]);

        return $this->setelahSimpan($siklus, $sampling);
    }

    public function edit(SamplingBobot $sampling): View
    {
        Gate::authorize('update', $sampling);
        abort_if($sampling->siklus->isTerkunci(), 403, 'Siklus sudah ditutup; data hanya dapat dilihat.');

        return view('sampling.form', $this->dataForm($sampling->siklus, $sampling));
    }

    /**
     * Data untuk hasil langsung di form: standar per hari, sampling terdahulu (untuk ADG), dan ayam hidup.
     *
     * @return array<string, mixed>
     */
    private function dataForm(Siklus $siklus, SamplingBobot $sampling): array
    {
        $baris = $this->ringkasan->baris($siklus, $siklus->samplings()->get(), $siklus->standarHarian()->get());

        return [
            'siklus' => $siklus->load('kandang'),
            'sampling' => $sampling,
            'ayamHidup' => $siklus->populasi - (int) $siklus->recordings()->sum('deplesi_harian'),
            'standarPerHari' => $siklus->standarHarian()->pluck('bobot_std_gram', 'hari_ke')->map(fn ($g) => (float) $g)->all(),
            'sebelumnya' => collect($baris)
                ->reject(fn ($b) => $sampling->exists && $b['sampling']->is($sampling))
                ->map(fn ($b) => ['hari' => $b['hari_ke'], 'abw' => $b['abw_gram']])->values()->all(),
            'standarAda' => $siklus->standarHarian()->exists(),
        ];
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

        $redirect = redirect()->route('sampling.index', $siklus)->with('hasil_sampling', $sampling->id);

        if (! $b['representatif']) {
            $redirect->with('peringatan', 'Sampel hanya '.Format::angka($b['persen_populasi']).' persen dari '.Format::angka($b['ayam_hidup'], 0)
                .' ayam hidup, di bawah batas 5 persen. Hasil ditandai kurang representatif.');
        }

        return $redirect;
    }
}
