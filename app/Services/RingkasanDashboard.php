<?php

namespace App\Services;

use App\Models\Kandang;
use App\Models\PemberianOvk;
use App\Models\Panen;
use App\Models\RecordingHarian;
use App\Models\SamplingBobot;
use App\Models\Siklus;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Angka ringkas dan aktivitas terbaru untuk dashboard peternak.
 * Semua dihitung dari data siklus; tidak ada angka yang disimpan terpisah.
 */
class RingkasanDashboard
{
    /**
     * @return array{total_kandang: int, kandang_aktif: int, siklus_berjalan: int, populasi_aktif: int, siklus: Collection<int, array<string, mixed>>}
     */
    public function peternak(User $user): array
    {
        $kandangIds = Kandang::where('user_id', $user->id)->pluck('id');

        $berjalan = Siklus::whereIn('kandang_id', $kandangIds)
            ->where('status', Siklus::STATUS_BERJALAN)
            ->with('kandang')
            ->orderBy('tanggal_masuk')
            ->get();

        $nomor = Siklus::nomorMap($kandangIds);
        $deplesi = RecordingHarian::whereIn('siklus_id', $berjalan->pluck('id'))
            ->selectRaw('siklus_id, sum(deplesi_harian) as total')
            ->groupBy('siklus_id')
            ->pluck('total', 'siklus_id');

        $kartu = $berjalan->map(function (Siklus $s) use ($nomor, $deplesi) {
            return [
                'siklus' => $s,
                'nomor' => $nomor[$s->id] ?? 1,
                'umur' => $s->umurHari(),
                'populasi_kini' => $s->populasi - (int) ($deplesi[$s->id] ?? 0),
            ];
        });

        return [
            'total_kandang' => $kandangIds->count(),
            'kandang_aktif' => $berjalan->pluck('kandang_id')->unique()->count(),
            'siklus_berjalan' => $berjalan->count(),
            'populasi_aktif' => (int) $kartu->sum('populasi_kini'),
            'siklus' => $kartu,
        ];
    }

    /**
     * Lima catatan terakhir lintas recording, sampling, OVK, dan panen.
     *
     * @return Collection<int, array{jenis: string, ikon: string, keterangan: string, tanggal: Carbon}>
     */
    public function aktivitas(User $user, int $batas = 5): Collection
    {
        $kandangIds = Kandang::where('user_id', $user->id)->pluck('id');
        $siklusIds = Siklus::whereIn('kandang_id', $kandangIds)->pluck('id');
        $nomor = Siklus::nomorMap($kandangIds);

        $sumber = [
            ['Recording harian', 'clipboard-list', RecordingHarian::class, fn ($r) => 'Hari ke-'.$r->hari_ke],
            ['Sampling bobot', 'scale', SamplingBobot::class, fn ($r) => 'Hari ke-'.$r->hari_ke],
            ['Pemberian OVK', 'syringe', PemberianOvk::class, fn ($r) => 'Hari ke-'.($r->siklus->umurHari($r->tanggal))],
            ['Panen', 'truck', Panen::class, fn ($r) => 'umur '.$r->umur_hari.' hari'],
        ];

        $semua = collect();
        foreach ($sumber as [$jenis, $ikon, $model, $detail]) {
            $model::whereIn('siklus_id', $siklusIds)
                ->with('siklus.kandang')
                ->orderByDesc('tanggal')->orderByDesc('id')
                ->limit($batas)
                ->get()
                ->each(fn ($r) => $semua->push([
                    'jenis' => $jenis,
                    'ikon' => $ikon,
                    'keterangan' => $r->siklus->kandang->nama_farm.' · Siklus '.($nomor[$r->siklus_id] ?? 1).' · '.$detail($r),
                    'tanggal' => Carbon::parse($r->tanggal),
                    'urut' => $r->id,
                ]));
        }

        return $semua->sortByDesc(fn ($a) => [$a['tanggal']->timestamp, $a['urut']])->take($batas)->values();
    }
}
