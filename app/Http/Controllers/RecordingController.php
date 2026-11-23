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
        Gate::authorize('catat', $siklus);

        $baris = $this->hitung($siklus);
        $mingguan = $this->ringkasan->mingguan($baris);
        $terakhir = $baris ? end($baris) : null;

        $umurHariIni = $this->umurHariIni($siklus);
        $rentang = max($baris ? array_key_last($baris) : 0, $siklus->status === Siklus::STATUS_BERJALAN ? $umurHariIni : 0);

        // Susun daftar hari 1..rentang: hari terisi, hari yang terlewat, dan subtotal tiap minggu (F2.12, F2.15).
        $stdHari = $this->ringkasan->pakanStdHarian($siklus->standarHarian()->get());
        $stdPersen = $siklus->standarHarian()->pluck('deplesi_std_persen', 'hari_ke')->all();
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
            'stdHari' => $stdHari,
            'stdPersen' => $stdPersen,
            'hariIni' => $umurHariIni,
            'belumDiisi' => $this->hariBelumDiisi($siklus, $baris),
        ]);
    }

    /** Unduh riwayat recording sebagai CSV (angka aktual dan standar). */
    public function csv(Siklus $siklus): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        Gate::authorize('catat', $siklus);

        $stdHari = $this->ringkasan->pakanStdHarian($siklus->standarHarian()->get());
        $baris = [['Hari', 'Tanggal', 'Pakan std (sak)', 'Pakan aktual (sak)', 'Ayam mati', 'Ayam afkir', 'Deplesi std (%)', 'Deplesi aktual (%)', 'Stok pakan (sak)', 'Diparaf PPL', 'Tanggal paraf']];
        foreach ($this->hitung($siklus) as $b) {
            $r = $b['recording'];
            $baris[] = [$b['hari_ke'], $b['tanggal']->toDateString(), $stdHari[$b['hari_ke']] ?? '', $b['pakan_pakai_sak'], $r->mati, $r->afkir,
                $b['standar']?->deplesi_std_persen ?? '', round($b['deplesi_kum_persen'], 2), round($b['stok_sak'], 2), $r->diparaf_ppl ? 'ya' : 'belum', $r->tanggal_paraf?->toDateString() ?? ''];
        }

        return LaporanController::unduhCsv($baris, 'recording-'.str($siklus->kandang->nama_farm)->slug().'.csv');
    }

    /** Halaman sukses setelah simpan: angka berjalan terbaru dan pengingat hari yang terlewat (F2.8). */
    public function tersimpan(Siklus $siklus, int $hari): View
    {
        Gate::authorize('catat', $siklus);

        $baris = $this->hitung($siklus);
        abort_unless(isset($baris[$hari]), 404);

        $b = $baris[$hari];
        $std = $b['standar'];
        $terlewat = $this->hariBelumDiisi($siklus, $baris);

        return view('recording.tersimpan', [
            'siklus' => $siklus->load('kandang'),
            'b' => $b,
            'deplesiStd' => $std?->deplesi_std_persen !== null ? (float) $std->deplesi_std_persen : null,
            'terlewat' => $terlewat,
            'tanggalTerlewat' => $terlewat ? $siklus->tanggal_masuk->copy()->addDays($terlewat - 1) : null,
            'fcrStd' => $std?->fcr_std !== null ? (float) $std->fcr_std : null,
        ]);
    }

    public function create(Request $request, Siklus $siklus): View|RedirectResponse
    {
        Gate::authorize('create', [RecordingHarian::class, $siklus]);

        if ($siklus->tanggal_masuk->isFuture()) {
            return redirect()->route('recording.index', $siklus)
                ->withErrors(['recording' => 'Tanggal masuk periode belum tiba; belum ada hari yang bisa dicatat.']);
        }

        $tanggal = $this->tanggalEntri($request, $siklus);
        $hariKe = $this->hariKe($siklus, $tanggal);

        if ($ada = $siklus->recordings()->where('hari_ke', $hariKe)->first()) {
            return redirect()->route('recording.edit', $ada)
                ->with('peringatan', "Hari ke-{$hariKe} sudah punya recording. Satu periode hanya boleh punya satu entri per hari umur; ubah entri yang ada.");
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
        // Tiap baris memakai objek siklus yang sudah dimuat; policy di view memanggil $recording->siklus->kandang per baris.
        $recordings = $siklus->recordings()->orderBy('hari_ke')->get()
            ->each(fn (RecordingHarian $r) => $r->setRelation('siklus', $siklus));

        return $this->ringkasan->baris(
            $siklus,
            $recordings,
            $siklus->standarHarian()->get(),
            $siklus->parameterUmur()->get(),
            $siklus->samplings()->get(),
        );
    }

    /** Angka berjalan langsung ditampilkan setelah entri disimpan (F2.8); stok negatif jadi peringatan (F2.14). */
    private function setelahSimpan(Siklus $siklus, int $hariKe): RedirectResponse
    {
        $b = $this->hitung($siklus)[$hariKe];

        $redirect = redirect()->route('recording.tersimpan', [$siklus, $hariKe]);

        if ($b['stok_sak'] < 0) {
            $redirect->with('peringatan', 'Pakan terpakai melebihi stok gudang ('.Format::angka($b['stok_sak']).' sak). Mungkin ada pengiriman pakan yang belum dicatat.');
        }

        return $redirect;
    }

    /** Angka hari sebelumnya untuk tombol "Samakan dengan kemarin"; null bila tidak ada. */
    private function dataKemarin(Siklus $siklus, int $hariKe): ?array
    {
        $r = $siklus->recordings()->where('hari_ke', $hariKe - 1)->first();

        return $r ? ['pakai' => (float) $r->pakan_pakai_sak, 'mati' => (int) $r->mati, 'afkir' => (int) $r->afkir] : null;
    }

    /** @return list<int> hari yang sudah lewat (sampai hari ini) tetapi belum punya recording, paling lama dulu. */
    private function hariKosong(Siklus $siklus): array
    {
        $ada = $siklus->recordings()->pluck('hari_ke')->all();
        $kosong = [];
        // Siklus yang sudah tidak berjalan hanya perlu dilengkapi sampai catatan terakhir; setelah panen terakhir tidak ada lagi yang dicatat.
        $batas = $siklus->status === Siklus::STATUS_BERJALAN ? $this->umurHariIni($siklus) : (int) $siklus->recordings()->max('hari_ke');
        $panenAkhir = (int) $siklus->panen()->max('umur_hari');
        if ($panenAkhir > 0) {
            $batas = min($batas, $panenAkhir);
        }
        for ($hari = 1; $hari <= $batas; $hari++) {
            if (! in_array($hari, $ada, true)) {
                $kosong[] = $hari;
            }
        }

        return $kosong;
    }

    /** Isi banyak hari sekaligus: tabel hari terlewat, cocok untuk mengejar catatan di buku. */
    public function massal(Siklus $siklus): View|RedirectResponse
    {
        Gate::authorize('create', [RecordingHarian::class, $siklus]);

        $kosong = array_slice($this->hariKosong($siklus), 0, 14);
        if (! $kosong) {
            return redirect()->route('recording.index', $siklus)->with('status', 'Tidak ada hari yang terlewat. Semua catatan sudah terisi.');
        }

        return view('recording.massal', [
            'siklus' => $siklus->load('kandang'),
            'hari' => $kosong,
            'sisa' => max(0, count($this->hariKosong($siklus)) - count($kosong)),
            'stdHari' => $this->ringkasan->pakanStdHarian($siklus->standarHarian()->get()),
        ]);
    }

    public function simpanMassal(Request $request, Siklus $siklus): RedirectResponse
    {
        Gate::authorize('create', [RecordingHarian::class, $siklus]);

        $kosong = $this->hariKosong($siklus);
        $baris = collect($request->input('baris', []))
            ->filter(fn ($b, $hari) => in_array((int) $hari, $kosong, true) && (filled($b['pakan_pakai_sak'] ?? null) || filled($b['mati'] ?? null) || filled($b['afkir'] ?? null)));

        if ($baris->isEmpty()) {
            return back()->withErrors(['baris' => 'Isi minimal satu hari sebelum menyimpan.']);
        }

        $aturan = [];
        foreach ($baris->keys() as $hari) {
            $aturan["baris.{$hari}.pakan_pakai_sak"] = ['required', 'numeric', 'min:0', 'max:99999'];
            $aturan["baris.{$hari}.pakan_masuk_sak"] = ['nullable', 'numeric', 'min:0', 'max:99999'];
            $aturan["baris.{$hari}.mati"] = ['nullable', 'integer', 'min:0', 'max:1000000'];
            $aturan["baris.{$hari}.afkir"] = ['nullable', 'integer', 'min:0', 'max:1000000'];
            $aturan["baris.{$hari}.sebab_afkir"] = [\Illuminate\Validation\Rule::requiredIf(fn () => (int) ($baris[$hari]['afkir'] ?? 0) > 0), 'nullable', \Illuminate\Validation\Rule::in(RecordingHarian::SEBAB_AFKIR)];
        }

        $data = \Illuminate\Support\Facades\Validator::make(['baris' => $baris->all()], $aturan, [
            'required' => 'Wajib diisi.', 'numeric' => 'Harus berupa angka.', 'integer' => 'Harus bilangan bulat.', 'min' => 'Tidak boleh negatif.',
            'sebab_afkir.required' => 'Pilih sebab afkir.',
        ])->validate();

        $total = (int) $siklus->recordings()->sum('deplesi_harian');
        foreach ($data['baris'] as $b) {
            $total += (int) ($b['mati'] ?? 0) + (int) ($b['afkir'] ?? 0);
        }
        if ($total > $siklus->populasi) {
            return back()->withInput()->withErrors(['baris' => 'Jumlah ayam mati dan afkir melebihi populasi awal ('.Format::angka($siklus->populasi, 0).' ekor). Periksa angkanya.']);
        }

        foreach ($data['baris'] as $hari => $b) {
            $afkir = (int) ($b['afkir'] ?? 0);
            $siklus->recordings()->create([
                'tanggal' => $siklus->tanggal_masuk->copy()->addDays((int) $hari - 1)->toDateString(),
                'hari_ke' => (int) $hari,
                'pakan_masuk_sak' => $b['pakan_masuk_sak'] ?? 0,
                'pakan_pakai_sak' => $b['pakan_pakai_sak'],
                'mati' => (int) ($b['mati'] ?? 0),
                'afkir' => $afkir,
                'sebab_afkir' => $afkir > 0 ? $b['sebab_afkir'] : null,
            ]);
        }

        return redirect()->route('recording.index', $siklus)->with('status', count($data['baris']).' hari tersimpan sekaligus.');
    }

    /** Hari terkecil yang sudah lewat (sampai hari ini) tetapi belum punya recording; null bila tidak ada. */
    private function hariBelumDiisi(Siklus $siklus, array $baris): ?int
    {
        for ($hari = 1; $hari <= $this->umurHariIni($siklus); $hari++) {
            if (! isset($baris[$hari])) {
                return $hari;
            }
        }

        return null;
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
            'kemarin' => $this->dataKemarin($siklus, $hariKe),
            'stdHariSak' => $this->ringkasan->pakanStdHarian($siklus->standarHarian()->get())[$hariKe] ?? null,
            'hariIni' => $this->umurHariIni($siklus),
            'belumDiisi' => $this->hariBelumDiisi($siklus, $this->hitung($siklus)),
            'bisaParaf' => $recording->exists && Gate::allows('paraf', $recording),
        ];
    }
}
