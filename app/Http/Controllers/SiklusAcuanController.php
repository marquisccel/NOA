<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcuanSiklusRequest;
use App\Models\Siklus;
use App\Services\StandarHarianCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tiga tabel acuan per siklus: standar harian (F1.5), parameter umur (F1.6), jadwal vaksin (F1.7).
 */
class SiklusAcuanController extends Controller
{
    public function standar(Siklus $siklus): View
    {
        Gate::authorize('view', $siklus);

        return view('siklus.standar', [
            'siklus' => $siklus,
            'standar' => $siklus->standarHarian()->orderBy('hari_ke')->get(),
        ]);
    }

    /** Mengganti seluruh tabel standar siklus; semua baris sah atau tidak ada yang tersimpan. */
    public function unggahStandar(Request $request, Siklus $siklus, StandarHarianCsv $csv): RedirectResponse
    {
        Gate::authorize('update', $siklus);

        $request->validate(['file' => ['required', 'file', 'max:512', 'mimes:csv,txt']], [], ['file' => 'file CSV']);

        $hasil = $csv->parse($request->file('file')->getRealPath());

        if ($hasil['errors']) {
            return back()->withErrors(['file' => array_slice($hasil['errors'], 0, 10)]);
        }

        DB::transaction(function () use ($siklus, $hasil) {
            $siklus->standarHarian()->delete();
            $siklus->standarHarian()->createMany($hasil['rows']);
        });

        return redirect()->route('siklus.standar', $siklus)
            ->with('status', count($hasil['rows']).' baris standar diunggah.');
    }

    public function templateStandar(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, StandarHarianCsv::KOLOM);
            fputcsv($out, [1, 0.96, 42, 0.23, 14.4]);
            fclose($out);
        }, 'template-standar-harian.csv', ['Content-Type' => 'text/csv']);
    }

    public function acuan(Siklus $siklus): View
    {
        Gate::authorize('view', $siklus);

        return view('siklus.acuan', [
            'siklus' => $siklus,
            'parameter' => $siklus->parameterUmur()->orderBy('umur_awal')->get(),
            'vaksin' => $siklus->jadwalVaksin()->orderBy('hari_ke')->get(),
        ]);
    }

    public function simpanAcuan(AcuanSiklusRequest $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);

        $data = $request->validated();

        DB::transaction(function () use ($siklus, $data) {
            $siklus->parameterUmur()->delete();
            $siklus->parameterUmur()->createMany(array_values($data['parameter'] ?? []));

            $siklus->jadwalVaksin()->delete();
            $siklus->jadwalVaksin()->createMany(array_values($data['vaksin'] ?? []));
        });

        return redirect()->route('siklus.show', $siklus)->with('status', 'Parameter umur dan jadwal vaksin disimpan.');
    }
}
