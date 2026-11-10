<?php

namespace App\Http\Controllers;

use App\Exceptions\RecordingTerkunci;
use App\Http\Requests\RecordingRequest;
use App\Models\RecordingHarian;
use App\Models\Siklus;
use App\Services\RingkasanRecording;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RecordingController extends Controller
{
    public function __construct(private RingkasanRecording $ringkasan) {}

    public function index(Siklus $siklus): View
    {
        Gate::authorize('view', $siklus);

        $baris = $this->hitung($siklus);
        $mingguan = $this->ringkasan->mingguan($baris);
        $terakhir = $baris ? end($baris) : null;

        $umurHariIni = $this->umurHariIni($siklus);
        $rentang = max($baris ? array_key_last($baris) : 0, $siklus->status === Siklus::STATUS_BERJALAN ? $umurHariIni : 0);

        // Susun daftar hari 1..rentang: hari terisi, hari yang terlewat, dan subtotal tiap minggu (F2.12, F2.15).
        $daftar = [];
        for ($hari = 1; $hari <= $rentang; $hari++) {
            $daftar[] = isset($baris[$hari])
                ? ['jenis' => 'hari', 'baris' => $baris[$hari]]
                : ['jenis' => 'kosong', 'hari_ke' => $hari, 'tanggal' => $siklus->tanggal_masuk->copy()->addDays($hari - 1)];

            if ($hari % 7 === 0 || $hari === $rentang) {
                $minggu = intdiv($hari - 1, 7) + 1;
                if (isset($mingguan[$minggu])) {
                    $daftar[] = ['jenis' => 'minggu'] + $mingguan[$minggu];
                }
            }
        }

        return view('recording.index', [
            'siklus' => $siklus->load('kandang'),
            'daftar' => $daftar,
            'terakhir' => $terakhir,
            'bisaIsi' => Gate::allows('create', [RecordingHarian::class, $siklus]),
            'standarAda' => $siklus->standarHarian()->exists(),
        ]);
    }

    public function create(Request $request, Siklus $siklus): View|RedirectResponse
    {
        Gate::authorize('create', [RecordingHarian::class, $siklus]);

        if ($siklus->tanggal_masuk->isFuture()) {
            return redirect()->route('recording.index', $siklus)
                ->withErrors(['recording' => 'Tanggal masuk siklus belum tiba; belum ada hari yang bisa dicatat.']);
        }

        $tanggal = $this->tanggalEntri($request, $siklus);
        $hariKe = $this->hariKe($siklus, $tanggal);

        if ($ada = $siklus->recordings()->where('hari_ke', $hariKe)->first()) {
            return redirect()->route('recording.edit', $ada)
                ->with('status', "Hari ke-{$hariKe} sudah punya entri; ubah entri yang ada.");
        }

        return view('recording.form', $this->dataForm($siklus, new RecordingHarian(['mati' => 0, 'afkir' => 0]), $hariKe, $tanggal));
    }

    public function store(RecordingRequest $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('create', [RecordingHarian::class, $siklus]);

        $data = $request->validated();
        $data['hari_ke'] = $request->hariKe($siklus);
        $data['pakan_masuk_sak'] ??= 0;

        try {
            $siklus->recordings()->create($data);
        } catch (RecordingTerkunci $e) {
            return back()->withInput()->withErrors(['recording' => $e->getMessage()]);
        }

        return $this->setelahSimpan($siklus, $data['hari_ke']);
    }

    public function edit(RecordingHarian $recording): View|RedirectResponse
    {
        Gate::authorize('view', $recording);

        $bacaSaja = false;

        if (Gate::denies('update', $recording)) {
            // Siklus selesai: isi terkunci, tetapi baris yang belum diparaf masih bisa ditandai.
            if (Gate::denies('paraf', $recording)) {
                return redirect()->route('recording.index', $recording->siklus)
                    ->withErrors(['recording' => RecordingTerkunci::diparaf()->getMessage()]);
            }

            $bacaSaja = true;
        }

        return view('recording.form', $this->dataForm($recording->siklus, $recording, $recording->hari_ke, $recording->tanggal) + ['bacaSaja' => $bacaSaja]);
    }

    public function update(RecordingRequest $request, RecordingHarian $recording): RedirectResponse
    {
        Gate::authorize('update', $recording);

        $data = $request->validated();
        $data['pakan_masuk_sak'] ??= 0;

        try {
            $recording->update($data);
        } catch (RecordingTerkunci $e) {
            return back()->withInput()->withErrors(['recording' => $e->getMessage()]);
        }

        return $this->setelahSimpan($recording->siklus, $recording->hari_ke);
    }

    public function destroy(RecordingHarian $recording): RedirectResponse
    {
        Gate::authorize('delete', $recording);

        $siklus = $recording->siklus;
        $hariKe = $recording->hari_ke;
        $recording->delete();

        return redirect()->route('recording.index', $siklus)->with('status', "Entri hari ke-{$hariKe} dihapus.");
    }

    /** F2.11: tandai baris sudah diparaf PPL beserta tanggalnya; sesudahnya baris tidak dapat disunting (R11). */
    public function paraf(Request $request, RecordingHarian $recording): RedirectResponse
    {
        Gate::authorize('paraf', $recording);

        $data = $request->validate([
            'tanggal_paraf' => ['required', 'date', 'after_or_equal:'.$recording->tanggal->toDateString(), 'before_or_equal:today'],
        ], [], ['tanggal_paraf' => 'tanggal paraf']);

        $recording->update(['diparaf_ppl' => true, 'tanggal_paraf' => $data['tanggal_paraf']]);

        return redirect()->route('recording.index', $recording->siklus)
            ->with('status', "Hari ke-{$recording->hari_ke} ditandai sudah diparaf PPL.");
    }

    /** @return array<int, array<string, mixed>> */
    private function hitung(Siklus $siklus): array
    {
        return $this->ringkasan->baris(
            $siklus,
            $siklus->recordings()->orderBy('hari_ke')->get(),
            $siklus->standarHarian()->get(),
            $siklus->parameterUmur()->get(),
            $siklus->samplings()->get(),
        );
    }

    /** Angka berjalan langsung ditampilkan setelah entri disimpan (F2.8); stok negatif jadi peringatan (F2.14). */
    private function setelahSimpan(Siklus $siklus, int $hariKe): RedirectResponse
    {
        $b = $this->hitung($siklus)[$hariKe];

        $pesan = "Hari ke-{$hariKe} tersimpan. Deplesi kumulatif ".Format::angka($b['deplesi_kum_persen']).' persen';
        $pesan .= $b['fcr_berjalan'] !== null
            ? ', FCR berjalan '.Format::angka($b['fcr_berjalan'], 3).'.'
            : '. FCR berjalan belum bisa dihitung tanpa bobot aktual.';

        $redirect = redirect()->route('recording.index', $siklus)->with('status', $pesan);

        if ($b['stok_sak'] < 0) {
            $redirect->with('peringatan', 'Pakan terpakai melebihi stok gudang ('.Format::angka($b['stok_sak']).' sak). Mungkin ada pengiriman pakan yang belum dicatat.');
        }

        return $redirect;
    }

    private function umurHariIni(Siklus $siklus): int
    {
        if ($siklus->tanggal_masuk->isFuture()) {
            return 0;
        }

        return min(60, $this->hariKe($siklus, Carbon::today()));
    }

    private function hariKe(Siklus $siklus, Carbon $tanggal): int
    {
        return (int) $siklus->tanggal_masuk->copy()->startOfDay()->diffInDays($tanggal->copy()->startOfDay()) + 1;
    }

    /** Tanggal dari parameter bila sah; jika tidak, hari pertama yang terlewat; jika tidak ada, hari ini. */
    private function tanggalEntri(Request $request, Siklus $siklus): Carbon
    {
        $masuk = $siklus->tanggal_masuk->copy()->startOfDay();
        $hariIni = Carbon::today();

        if ($request->filled('tanggal')) {
            try {
                $t = Carbon::parse($request->query('tanggal'))->startOfDay();
                if ($t->betweenIncluded($masuk, $hariIni) && $this->hariKe($siklus, $t) <= 60) {
                    return $t;
                }
            } catch (\Throwable) {
                // tanggal tidak terbaca: pakai bawaan
            }
        }

        $terisi = $siklus->recordings()->pluck('hari_ke')->all();
        $batas = $this->umurHariIni($siklus);
        for ($hari = 1; $hari <= $batas; $hari++) {
            if (! in_array($hari, $terisi, true)) {
                return $masuk->copy()->addDays($hari - 1);
            }
        }

        return $masuk->copy()->addDays(max($batas, 1) - 1);
    }

    /** @return array<string, mixed> */
    private function dataForm(Siklus $siklus, RecordingHarian $recording, int $hariKe, Carbon $tanggal): array
    {
        $sebelum = $this->ringkasan->totalSebelum($siklus, $hariKe, $recording->exists ? $recording->id : null);

        return [
            'siklus' => $siklus->load('kandang'),
            'recording' => $recording,
            'hariKe' => $hariKe,
            'tanggal' => $tanggal,
            'standar' => $siklus->standarHarian()->where('hari_ke', $hariKe)->first(),
            'target' => $siklus->parameterUmur()->where('umur_awal', '<=', $hariKe)->where('umur_akhir', '>=', $hariKe)->first(),
            'sebelum' => $sebelum,
            'bisaParaf' => $recording->exists && Gate::allows('paraf', $recording),
        ];
    }
}
