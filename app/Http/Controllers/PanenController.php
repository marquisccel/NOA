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

        return view('panen.index', [
            'siklus' => $siklus->load('kandang'),
            'panen' => $siklus->panen()->orderBy('tanggal')->orderBy('id')->get(),
            'p' => $this->performa->hitung($siklus),
        ]);
    }

    public function store(PanenRequest $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);

        $data = Arr::except($request->validated(), ['foto_btpa', 'hapus_foto']);
        $data['umur_hari'] = $request->umurHari($siklus);

        if ($request->hasFile('foto_btpa')) {
            $data['foto_btpa'] = $request->file('foto_btpa')->store(self::FOLDER, self::DISK);
        }

        $panen = $siklus->panen()->create($data);

        return $this->setelahSimpan($siklus, "Panen hari ke-{$panen->umur_hari} tersimpan.");
    }

    public function edit(Panen $panen): View
    {
        Gate::authorize('update', $panen);

        return view('panen.edit', ['siklus' => $panen->siklus->load('kandang'), 'panen' => $panen]);
    }

    public function update(PanenRequest $request, Panen $panen): RedirectResponse
    {
        Gate::authorize('update', $panen);

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

        $data = $request->validate([
            'pakan_retur_sak' => ['required', 'numeric', 'min:0', 'max:99999'],
        ], [], ['pakan_retur_sak' => 'pakan sisa/retur']);

        $siklus->update($data);

        return $this->setelahSimpan($siklus, 'Pakan sisa/retur disimpan.');
    }

    /** F5.4-F5.6: tampilkan total dan performa; selisih populasi negatif jadi peringatan. */
    private function setelahSimpan(Siklus $siklus, string $pesan): RedirectResponse
    {
        $p = $this->performa->hitung($siklus);

        $pesan .= ' Total panen '.Format::angka($p['total_ekor'], 0).' ekor, '.Format::angka($p['total_tonase']).' kg';
        $pesan .= $p['fcr'] !== null ? ', FCR '.Format::angka($p['fcr'], 3).'.' : '.';

        $redirect = redirect()->route('panen.index', $siklus)->with('status', $pesan);

        if ($p['selisih_populasi'] < 0) {
            $redirect->with('peringatan', 'Ekor panen ditambah deplesi melebihi populasi awal sebanyak '.Format::angka(abs($p['selisih_populasi']), 0).' ekor. Periksa jumlah ekor panen atau catatan deplesi.');
        }

        return $redirect;
    }
}
