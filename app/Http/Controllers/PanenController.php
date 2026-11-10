<?php

namespace App\Http\Controllers;

use App\Http\Requests\PanenRequest;
use App\Models\Panen;
use App\Models\Siklus;
use App\Services\RingkasanPerforma;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PanenController extends Controller
{
    /** Foto BTPA disimpan di disk privat (bukan direktori publik) dengan nama acak. */
    private const DISK = 'local';

    private const FOLDER = 'btpa';

    public function __construct(private RingkasanPerforma $performa) {}

    public function index(Siklus $siklus): View
    {
        Gate::authorize('view', $siklus);

        $p = $this->performa->hitung($siklus);

        return view('panen.index', [
            'siklus' => $siklus->load('kandang'),
            'panen' => $siklus->panen()->orderBy('tanggal')->orderBy('id')->get(),
            'p' => $p,
            'bisaCatat' => ! $siklus->isTerkunci(),
            'bisaSelesai' => $siklus->status === Siklus::STATUS_BERJALAN && $p['jumlah_tahap'] > 0,
            'tanggalTerakhir' => $siklus->panen()->max('tanggal'),
        ]);
    }

    /** Ringkasan panen (R10): FCR dan IP baru ada setelah ada catatan panen, beserta cara hitungnya. */
    public function ringkasan(Siklus $siklus): View
    {
        Gate::authorize('view', $siklus);

        return view('panen.ringkasan', [
            'siklus' => $siklus->load('kandang'),
            'panen' => $siklus->panen()->orderBy('tanggal')->orderBy('id')->get(),
            'p' => $this->performa->hitung($siklus),
        ]);
    }

    /** Rincian selisih populasi: panen + deplesi dibandingkan populasi awal; tidak diseimbangkan otomatis (R7). */
    public function selisih(Siklus $siklus): View
    {
        Gate::authorize('view', $siklus);

        return view('panen.selisih', ['siklus' => $siklus->load('kandang'), 'p' => $this->performa->hitung($siklus)]);
    }

    public function create(Siklus $siklus): View
    {
        Gate::authorize('update', $siklus);
        $this->pastikanTerbuka($siklus);

        return view('panen.form', $this->dataForm($siklus, new Panen));
    }

    public function store(PanenRequest $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);
        $this->pastikanTerbuka($siklus);

        $data = Arr::except($request->validated(), ['foto_btpa', 'hapus_foto']);
        $data['umur_hari'] = $request->umurHari($siklus);

        if ($request->hasFile('foto_btpa')) {
            $data['foto_btpa'] = $request->file('foto_btpa')->store(self::FOLDER, self::DISK);
        }

        $panen = $siklus->panen()->create($data);

        return $this->setelahSimpan($siklus, "Tahap panen hari ke-{$panen->umur_hari} tersimpan.");
    }

    public function edit(Panen $panen): View
    {
        Gate::authorize('update', $panen);
        $this->pastikanTerbuka($panen->siklus);

        return view('panen.form', $this->dataForm($panen->siklus, $panen));
    }

    /** @return array<string, mixed> */
    private function dataForm(Siklus $siklus, Panen $panen): array
    {
        $p = $this->performa->hitung($siklus);
        $lainEkor = $p['total_ekor'] - ($panen->exists ? (int) $panen->jumlah_ekor : 0);
        $lainTonase = $p['total_tonase'] - ($panen->exists ? (float) $panen->tonase_kg : 0);

        return [
            'siklus' => $siklus->load('kandang'),
            'panen' => $panen,
            'nomorTahap' => $panen->exists ? $siklus->panen()->where('id', '<=', $panen->id)->count() : $p['jumlah_tahap'] + 1,
            // Ayam hidup tercatat sebelum tahap ini = populasi awal - deplesi recording - panen tahap lain.
            'sisaTercatat' => $siklus->populasi - $p['deplesi'] - $lainEkor,
            'lainEkor' => $lainEkor,
            'lainTonase' => $lainTonase,
            'deplesi' => $p['deplesi'],
        ];
    }

    private function pastikanTerbuka(Siklus $siklus): void
    {
        abort_if($siklus->isTerkunci(), 403, 'Siklus sudah ditutup; data hanya dapat dilihat.');
    }

    public function update(PanenRequest $request, Panen $panen): RedirectResponse
    {
        Gate::authorize('update', $panen);
        $this->pastikanTerbuka($panen->siklus);

        $siklus = $panen->siklus;
        $data = Arr::except($request->validated(), ['foto_btpa', 'hapus_foto']);
        $data['umur_hari'] = $request->umurHari($siklus);

        $fotoLama = $panen->foto_btpa;
        if ($request->hasFile('foto_btpa')) {
            $data['foto_btpa'] = $request->file('foto_btpa')->store(self::FOLDER, self::DISK);
        } elseif ($request->boolean('hapus_foto')) {
            $data['foto_btpa'] = null;
        }

        $panen->update($data);

        if ($fotoLama && $fotoLama !== $panen->foto_btpa) {
            Storage::disk(self::DISK)->delete($fotoLama);
        }

        return $this->setelahSimpan($siklus, "Panen hari ke-{$panen->umur_hari} diperbarui.");
    }

    /** Soft delete: berkas foto dibiarkan agar riwayat tetap dapat dilacak saat sengketa. */
    public function destroy(Panen $panen): RedirectResponse
    {
        Gate::authorize('delete', $panen);
        $this->pastikanTerbuka($panen->siklus);

        $siklus = $panen->siklus;
        $panen->delete();

        return redirect()->route('panen.index', $siklus)->with('status', 'Tahap panen dihapus.');
    }

    /** F5.3: foto BTPA hanya lewat rute terautentikasi dan terotorisasi. */
    public function foto(Panen $panen): StreamedResponse
    {
        Gate::authorize('view', $panen);

        abort_unless($panen->foto_btpa && Storage::disk(self::DISK)->exists($panen->foto_btpa), 404);

        return Storage::disk(self::DISK)->response($panen->foto_btpa, null, [
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Pakan sisa/retur di akhir siklus: pengurang total pakan terpakai. */
    public function simpanRetur(Request $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);
        $this->pastikanTerbuka($siklus);

        $data = $request->validate([
            'pakan_retur_sak' => ['required', 'numeric', 'min:0', 'max:99999'],
        ], [], ['pakan_retur_sak' => 'pakan sisa/retur']);

        $siklus->update($data);

        return $this->setelahSimpan($siklus, 'Pakan sisa/retur disimpan.');
    }

    /** Setelah simpan: kembali ke daftar tahap; selisih populasi negatif jadi peringatan (R7). */
    private function setelahSimpan(Siklus $siklus, string $pesan): RedirectResponse
    {
        $p = $this->performa->hitung($siklus);

        $redirect = redirect()->route('panen.index', $siklus)->with('status', $pesan);

        if ($p['selisih_populasi'] < 0) {
            $redirect->with('peringatan', 'Ekor panen ditambah deplesi melebihi populasi awal sebanyak '.Format::angka(abs($p['selisih_populasi']), 0).' ekor. Periksa jumlah ekor panen atau catatan deplesi.');
        }

        return $redirect;
    }
}
