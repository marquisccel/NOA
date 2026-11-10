<?php

namespace App\Services;

use App\Models\Rhpp;
use App\Models\Siklus;

/**
 * Rekonsiliasi RHPP: catatan peternak dibandingkan baris demi baris dengan RHPP yang diterbitkan inti (F6.1-F6.5).
 *
 * Selisih = angka RHPP dikurangi angka peternak. Selisih nol tetap tampil sebagai nol (F6.2).
 * Angka RHPP diterima apa adanya dari lembar inti; tidak pernah diturunkan dari catatan peternak.
 */
class Rekonsiliasi
{
    /** Baris yang dibandingkan: kunci => [label, satuan, desimal tampilan, metrik bonus terkait]. */
    public const BARIS = [
        'pakan_masuk_kg' => ['Pakan masuk (tagihan sapronak)', 'kg', 2, null],
        'pakan_terpakai_kg' => ['Pakan terpakai', 'kg', 2, null],
        'pakan_retur_sak' => ['Pakan retur', 'sak', 2, null],
        'total_ekor' => ['Total ekor panen', 'ekor', 0, null],
        'total_tonase' => ['Total tonase panen', 'kg', 2, null],
        'abw' => ['Bobot rata-rata (ABW)', 'kg', 3, null],
        'umur_panen' => ['Umur panen rata-rata', 'hari', 2, null],
        'deplesi_persen' => ['Deplesi', '%', 2, 'bonus_deplesi'],
        'fcr' => ['FCR', '', 3, 'bonus_fcr'],
        'ip' => ['Indeks Performa (IP)', '', 1, 'bonus_ip'],
    ];

    public function __construct(private RingkasanPerforma $performa) {}

    /** Ambang efektif: bawaan config/noa.php ditimpa isian per siklus. */
    public function ambang(Siklus $siklus): array
    {
        return array_replace(config('noa.ambang_rekonsiliasi'), array_map('floatval', $siklus->ambang_rekonsiliasi ?? []));
    }

    /**
     * @return array{ada_rhpp: bool, baris: list<array<string, mixed>>, pendapatan: array<string, mixed>, jumlah_melebihi: int, total_dampak_rp: float|null}
     */
    public function hitung(Siklus $siklus, ?Rhpp $rhpp): array
    {
        $p = $this->performa->hitung($siklus);
        $sudahPanen = $p['jumlah_tahap'] > 0;
        $bobotSak = (float) $siklus->bobot_sak_kg;
        $kontrak = $siklus->kontrak;
        $ambang = $this->ambang($siklus);

        $peternak = [
            'pakan_masuk_kg' => $p['pakan_masuk_sak'] * $bobotSak,
            'pakan_terpakai_kg' => $p['pakan_bersih_kg'],
            // Retur versi peternak: pakan yang masuk tetapi tidak terpakai.
            'pakan_retur_sak' => $p['pakan_masuk_sak'] - $p['pakan_bersih_sak'],
            'total_ekor' => $sudahPanen ? $p['total_ekor'] : null,
            'total_tonase' => $sudahPanen ? $p['total_tonase'] : null,
            'abw' => $p['abw'],
            'umur_panen' => $p['umur_panen'],
            'deplesi_persen' => $p['deplesi_persen'],
            'fcr' => $p['fcr'],
            'ip' => $p['ip'],
        ];

        $returRhpp = $rhpp?->pakan_retur_sak !== null ? (float) $rhpp->pakan_retur_sak : null;
        $tagihanKg = $rhpp?->total_pakan_kg !== null ? (float) $rhpp->total_pakan_kg : null;
        $versiRhpp = [
            'pakan_masuk_kg' => $tagihanKg,
            // Terpakai menurut RHPP: tagihan dikurangi retur.
            'pakan_terpakai_kg' => $tagihanKg !== null && $returRhpp !== null ? $tagihanKg - $returRhpp * $bobotSak : null,
            'pakan_retur_sak' => $returRhpp,
            'total_ekor' => $rhpp?->total_ekor,
            'total_tonase' => $rhpp?->total_tonase,
            'abw' => $rhpp?->abw,
            'umur_panen' => $rhpp?->umur_panen,
            'deplesi_persen' => $rhpp?->deplesi_persen,
            'fcr' => $rhpp?->fcr,
            'ip' => $rhpp?->ip,
        ];

        $baris = [];
        $jumlahMelebihi = 0;
        $totalRp = null;

        foreach (self::BARIS as $kunci => [$label, $satuan, $desimal, $bonus]) {
            $a = $peternak[$kunci] !== null ? (float) $peternak[$kunci] : null;
            $b = $versiRhpp[$kunci] !== null ? (float) $versiRhpp[$kunci] : null;
            $selisih = $a !== null && $b !== null ? $b - $a : null;

            $nolTampil = $selisih !== null && round($selisih, $desimal) == 0.0;
            $melebihi = $selisih !== null && abs($selisih) > ($ambang[$kunci] ?? 0) + 1e-9 && ! $nolTampil;
            $rupiah = $selisih !== null && ! $nolTampil ? $this->dampakRupiah($kunci, $selisih, $bobotSak, $kontrak) : null;

            if ($melebihi) {
                $jumlahMelebihi++;
            }
            if ($rupiah !== null) {
                $totalRp = ($totalRp ?? 0) + $rupiah;
            }

            $baris[] = [
                'kunci' => $kunci,
                'label' => $label,
                'satuan' => $satuan,
                'desimal' => $desimal,
                'peternak' => $a,
                'rhpp' => $b,
                'selisih' => $selisih,
                'nol' => $nolTampil,
                'arah' => $selisih === null ? null : ($nolTampil ? 'sama' : ($selisih > 0 ? 'RHPP lebih tinggi' : 'RHPP lebih rendah')),
                'ambang' => $ambang[$kunci] ?? 0,
                'melebihi' => $melebihi,
                'dampak_rp' => $rupiah,
                // Metrik bonus: rupiahnya butuh tabel bonus inti yang belum ada di NOA.
                'bonus_terkait' => $bonus !== null && $kontrak && $kontrak->{$bonus} && $selisih !== null && ! $nolTampil,
            ];
        }

        $pendapatanRhpp = $rhpp?->pendapatan !== null ? (float) $rhpp->pendapatan : null;

        return [
            'ada_rhpp' => $rhpp !== null,
            'baris' => $baris,
            // Informatif: pendapatan inti bisa memuat bonus dan harga yang tidak tercatat di NOA.
            'pendapatan' => [
                'peternak' => $sudahPanen ? $p['pendapatan'] : null,
                'rhpp' => $pendapatanRhpp,
                'selisih' => $sudahPanen && $pendapatanRhpp !== null ? $pendapatanRhpp - $p['pendapatan'] : null,
            ],
            'jumlah_melebihi' => $jumlahMelebihi,
            'total_dampak_rp' => $totalRp,
        ];
    }

    /**
     * Dampak selisih terhadap rupiah dari sudut peternak (negatif merugikan), hanya untuk baris
     * yang bisa dihitung dari kontrak siklus. Bonus FCR, deplesi, dan IP tidak dihitung: tabel bonus
     * tiap inti tidak ada di data NOA.
     */
    private function dampakRupiah(string $kunci, float $selisih, float $bobotSak, $kontrak): ?float
    {
        $hargaSak = $kontrak?->harga_pakan_sak !== null ? (float) $kontrak->harga_pakan_sak : null;
        $hargaAyam = $kontrak?->harga_kontrak_ayam !== null ? (float) $kontrak->harga_kontrak_ayam : null;

        return match ($kunci) {
            // RHPP menagih lebih banyak pakan daripada yang tercatat: Anda membayar lebih.
            'pakan_masuk_kg' => $hargaSak !== null && $bobotSak > 0 ? -($selisih / $bobotSak) * $hargaSak : null,
            // RHPP mengakui retur lebih sedikit: kredit retur Anda berkurang.
            'pakan_retur_sak' => $hargaSak !== null ? $selisih * $hargaSak : null,
            // RHPP mencatat tonase lebih rendah: pendapatan Anda berkurang.
            'total_tonase' => $hargaAyam !== null ? $selisih * $hargaAyam : null,
            default => null,
        };
    }
}
