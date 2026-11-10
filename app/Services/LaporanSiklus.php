<?php

namespace App\Services;

use App\Models\Siklus;

/**
 * Biaya produksi (F7.1) dan data grafik bobot dan deplesi (F7.2) satu siklus.
 */
class LaporanSiklus
{
    public function __construct(
        private RingkasanPerforma $performa,
        private RingkasanRecording $recording,
    ) {}

    /**
     * Biaya produksi = DOC + pakan tertagih (pakan masuk dikurangi sisa) + OVK.
     * Komponen yang harganya belum diisi di kontrak bernilai null dan membuat total ditandai tidak lengkap.
     *
     * @param  array<string, mixed>|null  $p  hasil RingkasanPerforma::hitung bila sudah ada
     * @return array<string, mixed>
     */
    public function biaya(Siklus $siklus, ?array $p = null): array
    {
        $p ??= $this->performa->hitung($siklus);
        $kontrak = $siklus->kontrak;

        $doc = $kontrak?->harga_doc !== null ? (float) $kontrak->harga_doc * $siklus->populasi : null;
        $pakan = $kontrak?->harga_pakan_sak !== null ? (float) $kontrak->harga_pakan_sak * $p['pakan_bersih_sak'] : null;
        $ovk = (float) $siklus->pemberianOvk()->sum('biaya');

        $lengkap = $doc !== null && $pakan !== null;
        $total = ($doc ?? 0) + ($pakan ?? 0) + $ovk;

        return [
            'doc' => $doc,
            'pakan' => $pakan,
            'ovk' => $ovk,
            'total' => $total,
            'lengkap' => $lengkap,
            'per_kg' => $p['total_tonase'] > 0 ? $total / $p['total_tonase'] : null,
            'per_ekor' => $p['total_ekor'] > 0 ? $total / $p['total_ekor'] : null,
            // Margin kasar sebelum bonus dan biaya operasional lain (listrik, tenaga kerja, sekam).
            'margin_kasar' => $lengkap && $p['jumlah_tahap'] > 0 ? $p['pendapatan'] - $total : null,
        ];
    }

    /**
     * Seri per hari umur untuk grafik: bobot aktual, bobot standar, dan deplesi kumulatif.
     *
     * @return array{labels: list<int>, bobot: list<float|null>, bobot_standar: list<float|null>, deplesi: list<float|null>}
     */
    public function grafik(Siklus $siklus): array
    {
        $baris = $this->recording->baris(
            $siklus,
            $siklus->recordings()->orderBy('hari_ke')->get(),
            $siklus->standarHarian()->get(),
            collect(),
            $siklus->samplings()->get(),
        );

        $standar = $siklus->standarHarian()->orderBy('hari_ke')->pluck('bobot_std_gram', 'hari_ke');
        $akhir = max($baris ? array_key_last($baris) : 0, $standar->keys()->max() ?? 0);

        $out = ['labels' => [], 'bobot' => [], 'bobot_standar' => [], 'deplesi' => []];
        for ($hari = 1; $hari <= $akhir; $hari++) {
            $b = $baris[$hari] ?? null;
            $out['labels'][] = $hari;
            $out['bobot'][] = $b && $b['bobot_gram'] !== null ? round($b['bobot_gram'], 2) : null;
            $out['bobot_standar'][] = $standar->has($hari) ? round((float) $standar[$hari], 2) : null;
            $out['deplesi'][] = $b ? round($b['deplesi_kum_persen'], 2) : null;
        }

        return $out;
    }
}
