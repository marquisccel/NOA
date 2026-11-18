<?php

namespace App\Services;

use App\Models\Siklus;
use App\Support\Format as F;

/**
 * Menjelaskan tiap angka performa dengan rumusnya dan angka milik siklus itu sendiri, supaya mudah dipahami awam
 * dan bisa diperiksa ulang dengan kalkulator. Tidak menghitung apa pun yang baru: hanya menyusun ulang hasil RingkasanPerforma.
 */
class PenjelasRumus
{
    public function __construct(private RingkasanPerforma $performa, private LaporanSiklus $laporan) {}

    /**
     * @return list<array{kunci: string, judul: string, rumus: string, angka: string, hasil: string, catatan: string|null}>
     */
    public function untukSiklus(Siklus $siklus): array
    {
        $siklus->loadMissing('kontrak');
        $p = $this->performa->hitung($siklus);

        if ($p['jumlah_tahap'] === 0) {
            return [];
        }

        $biaya = $this->laporan->biaya($siklus, $p);
        $bobotSak = (float) $siklus->bobot_sak_kg;
        $daya = $p['liveability'];

        $daftar = [
            [
                'kunci' => 'deplesi',
                'judul' => 'Deplesi',
                'rumus' => '(ayam mati + ayam afkir) ÷ jumlah ayam awal × 100',
                'angka' => '('.F::angka($p['mati'], 0).' + '.F::angka($p['afkir'], 0).') ÷ '.F::angka($p['populasi'], 0).' × 100',
                'hasil' => F::angka($p['deplesi_persen']).'%',
                'catatan' => 'Daya hidup = 100% − deplesi = '.F::angka($daya).'%. Makin kecil deplesi makin baik.',
            ],
            [
                'kunci' => 'abw',
                'judul' => 'Bobot rata-rata panen (ABW)',
                'rumus' => 'total berat panen ÷ jumlah ayam dipanen',
                'angka' => F::angka($p['total_tonase'], 0).' kg ÷ '.F::angka($p['total_ekor'], 0).' ekor',
                'hasil' => F::angka($p['abw'], 3).' kg per ekor',
                'catatan' => null,
            ],
            [
                'kunci' => 'fcr',
                'judul' => 'Konversi pakan (FCR)',
                'rumus' => 'pakan yang terpakai (kg) ÷ total berat panen (kg)',
                'angka' => '('.F::angka($p['pakan_terpakai_sak']).' sak × '.F::angka($bobotSak, 0).' kg'.($p['pakan_retur_sak'] > 0 ? ' − '.F::angka($p['pakan_retur_sak']).' sak retur' : '').') = '
                    .F::angka($p['pakan_bersih_kg'], 0).' kg ÷ '.F::angka($p['total_tonase'], 0).' kg',
                'hasil' => F::angka($p['fcr'], 3),
                'catatan' => 'Artinya '.F::angka($p['fcr'], 2).' kg pakan menjadi 1 kg ayam. Makin kecil makin hemat.',
            ],
            [
                'kunci' => 'ip',
                'judul' => 'Indeks Performa (IP)',
                'rumus' => '(daya hidup % × bobot rata-rata kg) ÷ (FCR × umur panen) × 100',
                'angka' => '('.F::angka($daya).' × '.F::angka($p['abw'], 3).') ÷ ('.F::angka($p['fcr'], 3).' × '.F::angka($p['umur_panen']).') × 100',
                'hasil' => F::angka($p['ip'], 0),
                'catatan' => 'Rumus mengikuti pengaturan kemitraan siklus ini. Makin besar makin baik.',
            ],
        ];

        if ($biaya['per_kg'] !== null) {
            $daftar[] = [
                'kunci' => 'biaya',
                'judul' => 'Biaya produksi per kg',
                'rumus' => '(biaya DOC + biaya pakan + biaya OVK) ÷ total berat panen',
                'angka' => 'Rp '.F::angka($biaya['total'], 0).' ÷ '.F::angka($p['total_tonase'], 0).' kg',
                'hasil' => 'Rp '.F::angka($biaya['per_kg'], 0).' per kg',
                'catatan' => $biaya['lengkap'] ? 'Biaya DOC dan pakan dari harga di kontrak; OVK dari catatan pemberian.' : 'Belum lengkap: harga DOC atau pakan di kontrak belum diisi.',
            ];
        }

        return $daftar;
    }

    /**
     * Bahan kalkulator "bagaimana kalau": semua angka dasar dari siklus, siap dipakai di peramban.
     *
     * @return array<string, float|int>|null null bila belum ada panen atau harga belum lengkap
     */
    public function bahanSimulasi(Siklus $siklus): ?array
    {
        $siklus->loadMissing('kontrak');
        $p = $this->performa->hitung($siklus);
        $k = $siklus->kontrak;

        if ($p['jumlah_tahap'] === 0 || $p['fcr'] === null || ! $k || $k->harga_pakan_sak === null || $k->harga_kontrak_ayam === null || (float) $siklus->bobot_sak_kg <= 0) {
            return null;
        }

        return [
            'fcr' => round($p['fcr'], 3),
            'tonase' => round($p['total_tonase'], 2),
            'ekor' => $p['total_ekor'],
            'abw' => round((float) $p['abw'], 3),
            'populasi' => $p['populasi'],
            'deplesi' => round($p['deplesi_persen'], 2),
            'hargaPakanKg' => round((float) $k->harga_pakan_sak / (float) $siklus->bobot_sak_kg, 2),
            'hargaAyamKg' => round((float) $k->harga_kontrak_ayam, 2),
        ];
    }
}
