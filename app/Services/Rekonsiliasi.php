<?php

namespace App\Services;

use App\Models\Rhpp;
use App\Models\Siklus;
use App\Support\Format as F;

/**
 * Rekonsiliasi RHPP: catatan peternak dibandingkan baris demi baris dengan RHPP inti.
 *
 * Selisih = angka RHPP dikurangi angka peternak. Penanda "Berbeda" hanya berarti angka tidak sama:
 * tanpa ambang selisih wajar dan tanpa dampak rupiah. Bonus dan hasil bersih hanya dicatat dari RHPP
 * (tidak dihitung NOA) dan tidak dibandingkan. Angka RHPP selalu diterima apa adanya dari dokumen inti.
 */
class Rekonsiliasi
{
    public const SAMA = 'sama';

    public const BERBEDA = 'berbeda';

    public const TIDAK = 'tidak';

    /** Baris yang dibandingkan, urut tampil; dua baris terakhir hanya mencatat angka RHPP. */
    public const BARIS = [
        'populasi_doc' => 'Populasi DOC',
        'ekor_panen' => 'Ayam dipanen',
        'deplesi' => 'Deplesi',
        'tonase' => 'Tonase panen',
        'abw' => 'ABW panen',
        'umur_panen' => 'Umur panen',
        'pakan_masuk' => 'Pakan masuk (tagihan sapronak)',
        'pakan_terpakai' => 'Pakan terpakai',
        'pakan_retur' => 'Pakan retur',
        'fcr' => 'FCR',
        'ip' => 'IP',
        'biaya_doc' => 'Biaya DOC',
        'biaya_pakan' => 'Biaya pakan',
        'biaya_ovk' => 'Biaya OVK',
        'pendapatan' => 'Pendapatan',
        'bonus' => 'Bonus',
        'hasil_bersih' => 'Hasil bersih',
    ];

    public function __construct(private RingkasanPerforma $performa, private LaporanSiklus $laporan) {}

    /**
     * @return array{ada_rhpp: bool, baris: list<array<string, mixed>>, jumlah_dibandingkan: int, jumlah_sama: int, jumlah_berbeda: int, berbeda_bercatatan: int}
     */
    public function hitung(Siklus $siklus, ?Rhpp $rhpp): array
    {
        $siklus->loadMissing('kontrak');
        $p = $this->performa->hitung($siklus);
        $biaya = $this->laporan->biaya($siklus, $p);
        $sudahPanen = $p['jumlah_tahap'] > 0;
        $bobotSak = (float) $siklus->bobot_sak_kg;
        $catatan = $rhpp?->catatan ?? [];

        $returRhpp = $rhpp?->pakan_retur_sak !== null ? (float) $rhpp->pakan_retur_sak : 0.0;
        $terpakaiRhpp = $rhpp?->pakan_terpakai_kg !== null ? (float) $rhpp->pakan_terpakai_kg : null;
        // Menurut inti, pakan yang ditagih = yang terpakai ditambah retur.
        $masukRhppKg = $terpakaiRhpp !== null ? $terpakaiRhpp + $returRhpp * $bobotSak : null;

        $baris = [];
        $tambah = function (string $kunci, array $data) use (&$baris, $catatan) {
            $baris[] = ['kunci' => $kunci, 'label' => self::BARIS[$kunci], 'catatan' => $catatan[$kunci] ?? null] + $data;
        };

        $tambah('populasi_doc', $this->angkaBaris($siklus->populasi, $rhpp?->populasi_doc, 0, ' ekor'));
        $tambah('ekor_panen', $this->angkaBaris($sudahPanen ? $p['total_ekor'] : null, $rhpp?->total_ekor, 0, ' ekor'));
        $tambah('deplesi', $this->deplesi($p, $rhpp, $siklus->populasi));
        $tambah('tonase', $this->angkaBaris($sudahPanen ? $p['total_tonase'] : null, $rhpp?->total_tonase, 2, ' kg', true));
        $tambah('abw', $this->angkaBaris($p['abw'], $rhpp?->abw, 3, ' kg'));
        $tambah('umur_panen', $this->angkaBaris($p['umur_panen'], $rhpp?->umur_panen, 2, ' hari'));
        $tambah('pakan_masuk', $this->pakanMasuk($p['pakan_masuk_sak'], $bobotSak, $masukRhppKg));
        $tambah('pakan_terpakai', $this->angkaBaris($p['pakan_bersih_kg'], $terpakaiRhpp, 2, ' kg', true));
        // Retur versi peternak: pakan yang masuk tetapi tidak terpakai (sisa di gudang ditambah retur yang dicatat).
        $tambah('pakan_retur', $this->retur($p['pakan_masuk_sak'] - $p['pakan_bersih_sak'], $bobotSak, $rhpp ? $returRhpp : null));
        $tambah('fcr', $this->angkaBaris($p['fcr'], $rhpp?->fcr, 3));
        $tambah('ip', $this->angkaBaris($p['ip'], $rhpp?->ip, 0));
        $tambah('biaya_doc', $this->rupiah($biaya['doc'], $rhpp?->biaya_doc));
        $tambah('biaya_pakan', $this->rupiah($biaya['pakan'], $rhpp?->biaya_pakan));
        $tambah('biaya_ovk', $this->rupiah($biaya['ovk'] > 0 || $siklus->pemberianOvk()->exists() ? $biaya['ovk'] : null, $rhpp?->biaya_ovk));
        $tambah('pendapatan', $this->rupiah($sudahPanen ? $p['pendapatan'] : null, $rhpp?->pendapatan));
        $tambah('bonus', $this->hanyaRhpp($rhpp?->bonus));
        $tambah('hasil_bersih', $this->hanyaRhpp($rhpp?->hasil_bersih));

        $dibandingkan = collect($baris)->whereIn('status', [self::SAMA, self::BERBEDA]);
        $berbeda = $dibandingkan->where('status', self::BERBEDA);

        return [
            'ada_rhpp' => $rhpp !== null,
            'baris' => $baris,
            'jumlah_dibandingkan' => $rhpp ? count(self::BARIS) - 2 : 0,
            'jumlah_sama' => $dibandingkan->where('status', self::SAMA)->count(),
            'jumlah_berbeda' => $berbeda->count(),
            'berbeda_bercatatan' => $berbeda->filter(fn ($b) => filled($b['catatan']))->count(),
        ];
    }

    /** Angka dengan satuan; sama bila sama pada ketelitian tampilan. */
    private function angkaBaris(null|int|float|string $a, null|int|float|string $b, int $desimal, string $satuan = '', bool $ringkas = false): array
    {
        $a = $a === null ? null : (float) $a;
        $b = $b === null ? null : (float) $b;
        $teks = fn (?float $n) => $n === null ? null : ($ringkas ? $this->ringkasAngka($n) : F::angka($n, $desimal)).$satuan;

        return $this->susun($teks($a), $teks($b), $a, $b, $desimal, fn (float $d) => $this->tandaAngka($d, $desimal, $satuan, $ringkas));
    }

    /** Deplesi: ekor dan persen, dibandingkan pada keduanya. */
    private function deplesi(array $p, ?Rhpp $rhpp, int $populasi): array
    {
        $a = $p['deplesi'];
        $persenA = $p['deplesi_persen'];
        $b = $rhpp?->deplesi_ekor;
        $persenB = $rhpp && $b !== null && ($rhpp->populasi_doc ?: $populasi) > 0 ? $b / ($rhpp->populasi_doc ?: $populasi) * 100 : null;

        $teksA = F::angka($a, 0).' ekor · '.F::angka($persenA).'%';
        $teksB = $b === null ? null : F::angka($b, 0).' ekor · '.F::angka($persenB).'%';

        if ($b === null) {
            return ['peternak' => $teksA, 'rhpp' => null, 'selisih' => null, 'status' => null];
        }

        $beda = (int) $b - (int) $a;
        $poin = $persenB - $persenA;
        $sama = $beda === 0 && round($poin, 2) == 0.0;

        return [
            'peternak' => $teksA,
            'rhpp' => $teksB,
            'selisih' => $sama ? '0' : $this->tanda($beda).F::angka(abs($beda), 0).' ekor · '.$this->tanda($poin).F::angka(abs($poin)).' poin',
            'status' => $sama ? self::SAMA : self::BERBEDA,
        ];
    }

    private function pakanMasuk(float $sak, float $bobotSak, ?float $rhppKg): array
    {
        $kgA = $sak * $bobotSak;
        $teksA = $this->ringkasAngka($sak).' sak · '.$this->ringkasAngka($kgA).' kg';
        $teksB = $rhppKg === null ? null : ($bobotSak > 0 ? $this->ringkasAngka($rhppKg / $bobotSak).' sak · ' : '').$this->ringkasAngka($rhppKg).' kg';

        return $this->susun($teksA, $teksB, $kgA, $rhppKg, 2, fn (float $d) => $this->tandaAngka($d, 2, ' kg', true));
    }

    private function retur(float $sak, float $bobotSak, ?float $rhppSak): array
    {
        $teksA = $this->ringkasAngka($sak).' sak · '.$this->ringkasAngka($sak * $bobotSak).' kg';
        $teksB = $rhppSak === null ? null : $this->ringkasAngka($rhppSak).' sak';

        return $this->susun($teksA, $teksB, $sak, $rhppSak, 2, fn (float $d) => $this->tandaAngka($d, 2, ' sak', true));
    }

    private function rupiah(null|int|float $a, null|int|float|string $b): array
    {
        $a = $a === null ? null : (float) $a;
        $b = $b === null ? null : (float) $b;
        $teks = fn (?float $n) => $n === null ? null : 'Rp '.F::angka($n, 0);

        return $this->susun($teks($a), $teks($b), $a, $b, 0, fn (float $d) => $this->tanda($d).'Rp '.F::angka(abs($d), 0));
    }

    /** Bonus dan hasil bersih: hanya angka RHPP; NOA tidak menghitungnya. */
    private function hanyaRhpp(null|int|float|string $nilai): array
    {
        return [
            'peternak' => '– (tidak dihitung NOA)',
            'rhpp' => $nilai === null ? null : 'Rp '.F::angka((float) $nilai, 0),
            'selisih' => null,
            'status' => self::TIDAK,
        ];
    }

    /** @param  callable(float): string  $formatSelisih */
    private function susun(?string $teksA, ?string $teksB, ?float $a, ?float $b, int $desimal, callable $formatSelisih): array
    {
        if ($a === null || $b === null) {
            return ['peternak' => $teksA, 'rhpp' => $teksB, 'selisih' => null, 'status' => null];
        }

        $selisih = $b - $a;
        $sama = round($selisih, $desimal) == 0.0;

        return [
            'peternak' => $teksA,
            'rhpp' => $teksB,
            'selisih' => $sama ? '0' : $formatSelisih($selisih),
            'status' => $sama ? self::SAMA : self::BERBEDA,
        ];
    }

    private function tanda(float|int $n): string
    {
        return $n < 0 ? '−' : '+';
    }

    private function tandaAngka(float $d, int $desimal, string $satuan, bool $ringkas): string
    {
        return $this->tanda($d).($ringkas ? $this->ringkasAngka(abs($d)) : F::angka(abs($d), $desimal)).$satuan;
    }

    /** Angka dengan koma desimal tanpa nol di belakang: 4,2 / 16.250 / 2,5. */
    private function ringkasAngka(float $n): string
    {
        $teks = number_format($n, 2, ',', '.');

        return rtrim(rtrim($teks, '0'), ',');
    }
}
