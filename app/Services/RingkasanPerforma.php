<?php

namespace App\Services;

use App\Models\Siklus;

/**
 * Performa satu siklus versi peternak: deplesi, FCR, ABW, umur panen berbobot, IP, dan selisih populasi.
 * Siklus adalah entitas pusat; seluruh angka dihitung ulang dari recording dan panen, tidak disimpan.
 */
class RingkasanPerforma
{
    public function __construct(private PerformanceCalculator $calc) {}

    /**
     * @return array<string, mixed>
     */
    public function hitung(Siklus $siklus): array
    {
        $recordings = $siklus->recordings();
        $mati = (int) (clone $recordings)->sum('mati');
        $afkir = (int) (clone $recordings)->sum('afkir');
        $pakanTerpakaiSak = (float) (clone $recordings)->sum('pakan_pakai_sak');
        $pakanMasukSak = (float) (clone $recordings)->sum('pakan_masuk_sak');

        $panen = $siklus->panen()->orderBy('tanggal')->orderBy('id')->get();
        $totalEkor = (int) $panen->sum('jumlah_ekor');
        $totalTonase = (float) $panen->sum('tonase_kg');

        // Pakan sisa/retur di akhir siklus mengurangi pakan terpakai agar FCR tidak melar.
        $returSak = (float) $siklus->pakan_retur_sak;
        $pakanBersihSak = $pakanTerpakaiSak - $returSak;
        $pakanBersihKg = $this->calc->sakKeKg($pakanBersihSak, (float) $siklus->bobot_sak_kg);

        $deplesiPersen = $this->calc->deplesiPersen($mati, $afkir, $siklus->populasi);
        $liveability = $this->calc->liveability($deplesiPersen);
        $abw = $this->calc->abw($totalTonase, $totalEkor);
        $umurPanen = $this->calc->umurPanenRataRata($panen->map(fn ($p) => ['jumlah_ekor' => $p->jumlah_ekor, 'umur_hari' => $p->umur_hari]));
        $fcr = $this->calc->fcr($pakanBersihKg, $totalTonase);

        $tanpaHarga = $siklus->kontrak?->harga_kontrak_ayam !== null ? 0 : $panen->filter(fn ($p) => $p->harga_per_kg === null)->count();

        return [
            'jumlah_tahap' => $panen->count(),
            'populasi' => $siklus->populasi,
            'mati' => $mati,
            'afkir' => $afkir,
            'deplesi' => $mati + $afkir,
            'deplesi_persen' => $deplesiPersen,
            'liveability' => $liveability,

            'pakan_masuk_sak' => $pakanMasukSak,
            'pakan_terpakai_sak' => $pakanTerpakaiSak,
            'pakan_retur_sak' => $returSak,
            'pakan_bersih_sak' => $pakanBersihSak,
            'pakan_bersih_kg' => $pakanBersihKg,

            'total_ekor' => $totalEkor,
            'total_tonase' => $totalTonase,
            'abw' => $abw,
            'umur_panen' => $umurPanen,
            // R10: FCR dan IP tidak dihitung sebelum ada catatan panen.
            'fcr' => $fcr,
            'ip' => $this->calc->indeksPerforma($liveability, $abw, $fcr, $umurPanen, $siklus->rumus_ip ?? PerformanceCalculator::RUMUS_IP_STANDAR),

            // R7: panen + deplesi harus sama dengan populasi awal; selisih ditandai, tidak dipaksa seimbang.
            'selisih_populasi' => $this->calc->selisihPopulasi($siklus->populasi, $totalEkor, $mati + $afkir),

            // Pendapatan = tonase x harga kontrak ayam; tahap yang punya harga sendiri memakai harganya.
            'pendapatan' => $panen->sum(fn ($p) => (float) $p->tonase_kg * (float) ($p->harga_per_kg ?? $siklus->kontrak?->harga_kontrak_ayam ?? 0)),
            'tahap_tanpa_harga' => $tanpaHarga,
            'tahap_tanpa_btpa' => $panen->filter(fn ($p) => blank($p->nomor_btpa))->count(),

            // Siklus yang dihentikan dini tetap dihitung dengan data yang ada tetapi tidak sebanding.
            'tidak_sebanding' => $siklus->status === Siklus::STATUS_DIHENTIKAN,
        ];
    }
}
