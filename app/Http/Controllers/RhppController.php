<?php

namespace App\Http\Controllers;

use App\Http\Requests\RhppRequest;
use App\Models\Rhpp;
use App\Models\Siklus;
use App\Services\Rekonsiliasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * RHPP dan rekonsiliasi: halaman ini memuat angka rupiah, jadi hanya untuk peternak (R13).
 * Penanda "Berbeda" hanya berarti angka tidak sama; tidak ada ambang dan tidak ada dampak rupiah.
 */
class RhppController extends Controller
{
    private const DISK = 'local';

    private const FOLDER = 'rhpp';

    public function __construct(private Rekonsiliasi $rekonsiliasi) {}

    public function show(Siklus $siklus): View
    {
        Gate::authorize('view', $siklus);

        return view('rhpp.show', $this->data($siklus));
    }

    /** Lembar perbandingan untuk dibawa musyawarah dengan inti. */
    public function cetak(Siklus $siklus): View
    {
        Gate::authorize('view', $siklus);

        return view('rhpp.cetak', $this->data($siklus));
    }

    public function form(Siklus $siklus): View|RedirectResponse
    {
        Gate::authorize('update', $siklus);

        if (! $this->bisaInput($siklus)) {
            return redirect()->route('rhpp.show', $siklus);
        }

        return view('rhpp.form', ['siklus' => $siklus->load('kandang'), 'rhpp' => $siklus->rhpp()->first()]);
    }

    /** Simpan angka RHPP apa adanya; satu RHPP per siklus. Status menjadi Rekonsiliasi. */
    public function simpan(RhppRequest $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);

        if (! $this->bisaInput($siklus)) {
            return redirect()->route('rhpp.show', $siklus)->withErrors(['rhpp' => 'RHPP hanya bisa diinput setelah panen ditandai selesai dan sebelum siklus ditutup.']);
        }

        $data = Arr::except($request->validated(), ['lampiran']);
        $data['deplesi_persen'] = $data['populasi_doc'] > 0 ? min(100, $data['deplesi_ekor'] / $data['populasi_doc'] * 100) : 0;

        $ada = $siklus->rhpp()->withTrashed()->first();
        $ada?->restore();
        $lama = $ada?->lampiran;

        if ($request->hasFile('lampiran')) {
            $data['lampiran'] = $request->file('lampiran')->store(self::FOLDER, self::DISK);
        }

        $siklus->rhpp()->updateOrCreate([], $data);

        if (isset($data['lampiran']) && $lama && $lama !== $data['lampiran']) {
            Storage::disk(self::DISK)->delete($lama);
        }

        if ($siklus->status === Siklus::STATUS_MENUNGGU_RHPP) {
            $siklus->update(['status' => Siklus::STATUS_REKONSILIASI]);
        }

        return redirect()->route('rhpp.show', $siklus)->with('status', 'RHPP tersimpan. Siklus masuk tahap Rekonsiliasi.');
    }

    /** Keterangan selisih per baris; dicetak di lembar perbandingan. */
    public function catatan(Request $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);

        $rhpp = $siklus->rhpp()->first();
        abort_if(! $rhpp || $siklus->isTerkunci(), 403);

        $data = $request->validate([
            'catatan' => ['nullable', 'array'],
            'catatan.*' => ['nullable', 'string', 'max:300'],
        ]);

        $catatan = collect($data['catatan'] ?? [])
            ->filter(fn ($teks, $kunci) => array_key_exists($kunci, Rekonsiliasi::BARIS) && filled($teks))
            ->map(fn ($teks) => trim($teks))
            ->all();

        $rhpp->update(['catatan' => $catatan ?: null]);

        return redirect()->route('rhpp.show', $siklus)->with('status', 'Catatan selisih tersimpan.');
    }

    /** Lampiran RHPP hanya lewat rute terautentikasi dan terotorisasi. */
    public function lampiran(Siklus $siklus): StreamedResponse
    {
        Gate::authorize('view', $siklus);

        $rhpp = $siklus->rhpp()->first();
        abort_unless($rhpp?->lampiran && Storage::disk(self::DISK)->exists($rhpp->lampiran), 404);

        return Storage::disk(self::DISK)->response($rhpp->lampiran, null, [
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function bisaInput(Siklus $siklus): bool
    {
        return in_array($siklus->status, [Siklus::STATUS_MENUNGGU_RHPP, Siklus::STATUS_REKONSILIASI], true);
    }

    /** @return array<string, mixed> */
    private function data(Siklus $siklus): array
    {
        $siklus->load(['kandang', 'kontrak']);
        $rhpp = $siklus->rhpp()->first();

        return [
            'siklus' => $siklus,
            'rhpp' => $rhpp,
            'r' => $this->rekonsiliasi->hitung($siklus, $rhpp),
            'bisaInput' => $this->bisaInput($siklus),
        ];
    }
}
