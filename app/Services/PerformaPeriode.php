<?php

namespace App\Services;

use App\Models\Kandang;
use App\Models\Siklus;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Ringkasan performa peternak untuk satu periode (bulan atau tahun), berdasarkan tanggal panen terakhir tiap siklus.
 * Hanya siklus yang sudah punya catatan panen ikut dihitung; periode tanpa panen menghasilkan nol siklus (tampil "–").
 */
class PerformaPeriode
{
    public function __construct(private RingkasanPerforma $performa, private LaporanSiklus $laporan) {}

    /** Periode valid berbentuk "2026" atau "2026-11"; selain itu tahun berjalan. */
    public function normalisasi(?string $periode): string
    {
        if ($periode && preg_match('/^(20\d{2})(-(0[1-9]|1[0-2]))?$/', $periode)) {
            return $periode;
        }

        return now()->format('Y');
    }

    /** @return array{0: Carbon, 1: Carbon} awal dan akhir periode */
    public function rentang(string $periode): array
    {
        $bulanan = strlen($periode) === 7;
        $awal = $bulanan ? Carbon::createFromFormat('Y-m-d', $periode.'-01')->startOfDay() : Carbon::create((int) $periode, 1, 1)->startOfDay();

        return [$awal, $bulanan ? $awal->copy()->endOfMonth() : $awal->copy()->endOfYear()];
    }

    public function label(string $periode): string
    {
        return strlen($periode) === 7 ? $this->rentang($periode)[0]->translatedFormat('F Y') : 'Tahun '.$periode;
    }

    public function sebelumnya(string $periode): string
    {
        return strlen($periode) === 7
            ? $this->rentang($periode)[0]->subMonth()->format('Y-m')
            : (string) ((int) $periode - 1);
    }

    /**
     * Pilihan periode: bulan ini dan 11 bulan sebelumnya, lalu tahun-tahun dari siklus tertua sampai tahun ini.
     *
     * @return array{bulan: array<string, string>, tahun: array<string, string>}
     */
    public function pilihan(User $user): array
    {
        $bulan = [];
        for ($i = 0; $i < 12; $i++) {
            $b = now()->startOfMonth()->subMonths($i);
            $bulan[$b->format('Y-m')] = $b->translatedFormat('F Y').($i === 0 ? ' (bulan ini)' : '');
        }

        $tertua = Siklus::whereIn('kandang_id', Kandang::where('user_id', $user->id)->pluck('id'))->min('tanggal_masuk');
        $tahun = [];
        for ($t = (int) now()->format('Y'); $t >= ($tertua ? (int) Carbon::parse($tertua)->format('Y') : (int) now()->format('Y')); $t--) {
            $tahun[(string) $t] = 'Tahun '.$t.($t === (int) now()->format('Y') ? ' (tahun ini)' : '');
        }

        return ['bulan' => $bulan, 'tahun' => $tahun];
    }

    /**
     * @return array{periode: string, label: string, jumlah_siklus: int, total_ekor: int, total_tonase: float, deplesi_persen: float|null, fcr: float|null, ip: float|null, biaya_per_kg: float|null}
     */
    public function hitung(User $user, string $periode): array
    {
        [$awal, $akhir] = $this->rentang($periode);

        $siklus = Siklus::whereIn('kandang_id', Kandang::where('user_id', $user->id)->pluck('id'))
            ->whereHas('panen')
            ->with(['kontrak', 'panen'])
            ->get()
            ->filter(function (Siklus $s) use ($awal, $akhir) {
                $terakhir = Carbon::parse($s->panen->max('tanggal'))->startOfDay();

                return $terakhir->betweenIncluded($awal, $akhir);
            });

        $ekor = 0;
        $tonase = 0.0;
        $deplesi = 0;
        $populasi = 0;
        $pakanKg = 0.0;
        $ipDaftar = [];
        $biayaLengkap = 0.0;
        $tonaseBiaya = 0.0;

        foreach ($siklus as $s) {
            $p = $this->performa->hitung($s);
            $ekor += $p['total_ekor'];
            $tonase += $p['total_tonase'];
            $deplesi += $p['deplesi'];
            $populasi += $p['populasi'];
            $pakanKg += $p['pakan_bersih_kg'];
            if ($p['ip'] !== null) {
                $ipDaftar[] = $p['ip'];
            }

            $biaya = $this->laporan->biaya($s, $p);
            if ($biaya['lengkap'] && $p['total_tonase'] > 0) {
                $biayaLengkap += $biaya['total'];
                $tonaseBiaya += $p['total_tonase'];
            }
        }

        return [
            'periode' => $periode,
            'label' => $this->label($periode),
            'jumlah_siklus' => $siklus->count(),
            'total_ekor' => $ekor,
            'total_tonase' => $tonase,
            'deplesi_persen' => $populasi > 0 ? $deplesi / $populasi * 100 : null,
            // FCR gabungan: total pakan dibagi total tonase (bukan rata-rata angka FCR tiap siklus).
            'fcr' => $tonase > 0 ? $pakanKg / $tonase : null,
            'ip' => $ipDaftar ? array_sum($ipDaftar) / count($ipDaftar) : null,
            'biaya_per_kg' => $tonaseBiaya > 0 ? $biayaLengkap / $tonaseBiaya : null,
        ];
    }
}
