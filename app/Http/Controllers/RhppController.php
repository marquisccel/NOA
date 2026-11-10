<?php

namespace App\Http\Controllers;

use App\Http\Requests\RhppRequest;
use App\Models\Rhpp;
use App\Models\Siklus;
use App\Services\Rekonsiliasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Rekonsiliasi RHPP (F6.x): halaman ini memuat angka rupiah dan selisih komersial,
 * sehingga hanya untuk peternak (R13); ABK ditolak oleh middleware dan kebijakan.
 */
class RhppController extends Controller
{
    public function __construct(private Rekonsiliasi $rekonsiliasi) {}

    public function show(Siklus $siklus): View
    {
        Gate::authorize('view', $siklus);

        return view('rhpp.show', $this->data($siklus));
    }

    /** F6.4: lembar perbandingan untuk dibawa musyawarah dengan inti. */
    public function cetak(Siklus $siklus): View
    {
        Gate::authorize('view', $siklus);

        return view('rhpp.cetak', $this->data($siklus));
    }

    /** F6.1: simpan angka RHPP apa adanya; satu RHPP per siklus. */
    public function simpan(RhppRequest $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);

        $siklus->rhpp()->withTrashed()->first()?->restore();
        $siklus->rhpp()->updateOrCreate([], $request->validated());

        $hasil = $this->rekonsiliasi->hitung($siklus->fresh(['kontrak']), $siklus->rhpp()->first());
        $pesan = 'Angka RHPP disimpan. '.($hasil['jumlah_melebihi'] === 0
            ? 'Tidak ada baris yang melebihi ambang selisih wajar.'
            : $hasil['jumlah_melebihi'].' baris melebihi ambang selisih wajar.');

        return redirect()->route('rhpp.show', $siklus)->with('status', $pesan);
    }

    public function hapus(Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);

        $siklus->rhpp()->first()?->delete();

        return redirect()->route('rhpp.show', $siklus)->with('status', 'Angka RHPP dihapus. Kolom rekonsiliasi dikosongkan.');
    }

    /** F6.3: ambang selisih wajar per metrik; isian kosong kembali ke bawaan. */
    public function simpanAmbang(Request $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);

        $aturan = [];
        foreach (array_keys(Rekonsiliasi::BARIS) as $kunci) {
            $aturan["ambang.{$kunci}"] = ['nullable', 'numeric', 'min:0', 'max:9999999'];
        }
        $data = $request->validate($aturan, [], ['ambang.*' => 'ambang']);

        $bawaan = config('noa.ambang_rekonsiliasi');
        $override = [];
        foreach ($data['ambang'] ?? [] as $kunci => $nilai) {
            // Hanya simpan yang berbeda dari bawaan, supaya perubahan bawaan di masa depan tetap berlaku.
            if ($nilai !== null && (float) $nilai !== (float) ($bawaan[$kunci] ?? 0)) {
                $override[$kunci] = (float) $nilai;
            }
        }

        $siklus->update(['ambang_rekonsiliasi' => $override ?: null]);

        return redirect()->route('rhpp.show', $siklus)->with('status', 'Ambang selisih wajar disimpan.');
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
            'ambang' => $this->rekonsiliasi->ambang($siklus),
            'ambangBawaan' => config('noa.ambang_rekonsiliasi'),
            'baris' => Rekonsiliasi::BARIS,
        ];
    }
}
