<?php

namespace App\Http\Controllers;

use App\Models\Siklus;
use App\Services\Rekonsiliasi;
use App\Services\RingkasanPerforma;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Alur status siklus setelah panen: berjalan -> menunggu RHPP -> rekonsiliasi -> ditutup,
 * serta dua jalan keluar: tutup tanpa RHPP dan hentikan dini. Semua perpindahan hanya untuk peternak pemilik.
 */
class SiklusAlurController extends Controller
{
    public const ALASAN_TANPA_RHPP = ['RHPP tidak diterbitkan inti', 'Inti menolak menerbitkan RHPP', 'Lainnya'];

    public const ALASAN_HENTI = ['Wabah penyakit', 'Bencana atau kerusakan kandang', 'Keputusan inti', 'Lainnya'];

    public function __construct(private RingkasanPerforma $performa, private Rekonsiliasi $rekonsiliasi) {}

    /** Panen selesai: status Berjalan menjadi Menunggu RHPP. Kandang tetap tertahan sampai siklus ditutup. */
    public function panenSelesai(Request $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);

        if ($siklus->status !== Siklus::STATUS_BERJALAN) {
            return back()->withErrors(['siklus' => 'Siklus ini sudah tidak berjalan.']);
        }
        if (! $siklus->panen()->exists()) {
            return back()->withErrors(['siklus' => 'Catat minimal satu tahap panen sebelum menandai panen selesai.']);
        }

        $data = $request->validate([
            'tanggal_panen' => ['required', 'date', 'after_or_equal:'.$siklus->tanggal_masuk->toDateString(), 'before_or_equal:today'],
        ], [], ['tanggal_panen' => 'tanggal panen terakhir']);

        $siklus->update(['status' => Siklus::STATUS_MENUNGGU_RHPP, 'tanggal_panen' => $data['tanggal_panen']]);

        return redirect()->route('rhpp.show', $siklus)->with('status', 'Panen ditandai selesai. Siklus menunggu RHPP.');
    }

    public function tutupForm(Siklus $siklus): View|RedirectResponse
    {
        return $this->formPenutupan($siklus, false);
    }

    public function tutupTanpaRhppForm(Siklus $siklus): View|RedirectResponse
    {
        return $this->formPenutupan($siklus, true);
    }

    /** Tutup siklus (setelah RHPP diinput dan direkonsiliasi): semua data dikunci. */
    public function tutup(Request $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);

        if ($siklus->status !== Siklus::STATUS_REKONSILIASI) {
            return back()->withErrors(['siklus' => 'Siklus hanya bisa ditutup setelah RHPP diinput. Tanpa RHPP, pakai “Tutup tanpa RHPP”.']);
        }

        $data = $this->dataPenutupan($request, $siklus);
        $siklus->update($data + ['status' => Siklus::STATUS_SELESAI, 'ditutup_pada' => now()->toDateString()]);

        return redirect()->route('siklus.show', $siklus)->with('status', 'Siklus ditutup. Semua data dikunci dan laporan tetap bisa dicetak.');
    }

    /** Tutup tanpa RHPP: rekonsiliasi tercatat kosong; wajib alasan. */
    public function tutupTanpaRhpp(Request $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);

        if (! in_array($siklus->status, [Siklus::STATUS_MENUNGGU_RHPP, Siklus::STATUS_REKONSILIASI], true)) {
            return back()->withErrors(['siklus' => 'Siklus ini tidak sedang menunggu RHPP.']);
        }

        $data = $this->dataPenutupan($request, $siklus) + $request->validate([
            'alasan_penutupan' => ['required', Rule::in(self::ALASAN_TANPA_RHPP)],
            'keterangan_penutupan' => ['nullable', 'string', 'max:1000'],
        ], [], ['alasan_penutupan' => 'alasan']);

        $siklus->update($data + ['status' => Siklus::STATUS_DITUTUP_TANPA_RHPP, 'ditutup_pada' => now()->toDateString()]);

        return redirect()->route('siklus.show', $siklus)->with('status', 'Siklus ditutup tanpa RHPP. Semua data dikunci.');
    }

    public function hentikanForm(Siklus $siklus): View|RedirectResponse
    {
        Gate::authorize('update', $siklus);

        if ($siklus->status !== Siklus::STATUS_BERJALAN) {
            return redirect()->route('siklus.show', $siklus);
        }

        return view('siklus.hentikan', ['siklus' => $siklus->load('kandang'), 'alasan' => self::ALASAN_HENTI] + $this->ringkasHenti($siklus));
    }

    /** Hentikan dini (misalnya wabah): angka tetap dihitung tetapi ditandai tidak sebanding. */
    public function hentikan(Request $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('update', $siklus);

        if ($siklus->status !== Siklus::STATUS_BERJALAN) {
            return back()->withErrors(['siklus' => 'Hanya siklus yang berjalan yang bisa dihentikan.']);
        }

        $data = $request->validate([
            'alasan_penutupan' => ['required', Rule::in(self::ALASAN_HENTI)],
            'keterangan_penutupan' => ['required', 'string', 'max:1000'],
            'tanggal_dihentikan' => ['required', 'date', 'after_or_equal:'.$siklus->tanggal_masuk->toDateString(), 'before_or_equal:today'],
        ], [
            'keterangan_penutupan.required' => 'Keterangan wajib diisi.',
        ], ['alasan_penutupan' => 'alasan', 'keterangan_penutupan' => 'keterangan', 'tanggal_dihentikan' => 'tanggal dihentikan']);

        $siklus->update([
            'status' => Siklus::STATUS_DIHENTIKAN,
            'alasan_penutupan' => $data['alasan_penutupan'],
            'keterangan_penutupan' => $data['keterangan_penutupan'],
            'ditutup_pada' => $data['tanggal_dihentikan'],
        ]);

        return redirect()->route('siklus.show', $siklus)->with('status', 'Siklus dihentikan. Data terkunci.');
    }

    private function formPenutupan(Siklus $siklus, bool $tanpaRhpp): View|RedirectResponse
    {
        Gate::authorize('update', $siklus);

        if (! in_array($siklus->status, [Siklus::STATUS_MENUNGGU_RHPP, Siklus::STATUS_REKONSILIASI], true)) {
            return redirect()->route('siklus.show', $siklus);
        }

        $rhpp = $siklus->rhpp()->first();

        return view('siklus.tutup', [
            'siklus' => $siklus->load('kandang'),
            'rhpp' => $rhpp,
            'p' => $this->performa->hitung($siklus),
            'r' => $this->rekonsiliasi->hitung($siklus, $rhpp),
            'tanpaRhpp' => $tanpaRhpp,
            'alasan' => self::ALASAN_TANPA_RHPP,
        ]);
    }

    /** @return array{tanggal_panen: string, tanggal_rechick: string|null} */
    private function dataPenutupan(Request $request, Siklus $siklus): array
    {
        return $request->validate([
            'tanggal_panen' => ['required', 'date', 'after_or_equal:'.$siklus->tanggal_masuk->toDateString()],
            'tanggal_rechick' => ['nullable', 'date', 'after_or_equal:tanggal_panen'],
        ], [], ['tanggal_panen' => 'tanggal panen terakhir', 'tanggal_rechick' => 'rencana re-chick-in']);
    }

    /** @return array<string, mixed> */
    private function ringkasHenti(Siklus $siklus): array
    {
        $deplesi = (int) $siklus->recordings()->sum('deplesi_harian');
        $terakhir = $siklus->recordings()->orderByDesc('hari_ke')->first();
        $sampling = $siklus->samplings()->orderByDesc('hari_ke')->first();

        return [
            'deplesi' => $deplesi,
            'deplesi_persen' => $siklus->populasi > 0 ? $deplesi / $siklus->populasi * 100 : 0,
            'tersisa' => $siklus->populasi - $deplesi,
            'hari_terakhir' => $terakhir?->hari_ke,
            'pakan_sak' => (float) $siklus->recordings()->sum('pakan_pakai_sak'),
            'abw_gram' => $sampling ? (float) $sampling->total_bobot_kg / $sampling->jumlah_sampel * 1000 : null,
            'abw_hari' => $sampling?->hari_ke,
        ];
    }
}
