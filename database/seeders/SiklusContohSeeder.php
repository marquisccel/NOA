<?php

namespace Database\Seeders;

use App\Models\Kandang;
use App\Models\KontrakSiklus;
use App\Models\Panen;
use App\Models\PemberianOvk;
use App\Models\RecordingHarian;
use App\Models\Rhpp;
use App\Models\SamplingBobot;
use App\Models\Siklus;
use App\Models\User;
use App\Services\PerformanceCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Satu siklus contoh mengikuti "Contoh perhitungan" di PRD:
 * 3.000 DOC, mati 78, culling 18, panen 3 tahap (2.904 ekor, 5.750 kg).
 *
 * Tabel standar di sini hanya kurva contoh yang dibangkitkan, bukan tabel kemitraan nyata.
 * Total pakan memakai 190 sak (9.500 kg) agar FCR 1,652 dan IP 339,2 di PRD terpenuhi;
 * angka 420 sak di PRD tidak konsisten dengan FCR tersebut.
 */
class SiklusContohSeeder extends Seeder
{
    private const POPULASI = 3000;

    private const UMUR_MAKS = 35;

    public function run(PerformanceCalculator $calc): void
    {
        // role dan peternak_id sengaja tidak mass-assignable, jadi diisi lewat forceFill.
        $peternak = User::where('email', 'peternak@noa.test')->first();
        if (! $peternak) {
            $peternak = (new User)->forceFill([
                'name' => 'Pak Yanto', 'email' => 'peternak@noa.test', 'password' => 'password',
                'role' => User::ROLE_PETERNAK, 'email_verified_at' => now(),
            ]);
            $peternak->save();
        }

        if (! User::where('email', 'abk@noa.test')->exists()) {
            (new User)->forceFill([
                'name' => 'ABK Contoh', 'email' => 'abk@noa.test', 'password' => 'password',
                'role' => User::ROLE_ABK, 'peternak_id' => $peternak->id, 'email_verified_at' => now(),
            ])->save();
        }

        $kandang = Kandang::create([
            'user_id' => $peternak->id,
            'nama_farm' => 'Kandang Contoh Tulungagung',
            'alamat' => 'Kabupaten Tulungagung, Jawa Timur',
            'kapasitas' => 4000,
            'tipe' => Kandang::TIPE_CLOSED_HOUSE,
        ]);

        $masuk = CarbonImmutable::parse('2026-09-20');

        $siklus = Siklus::create([
            'kandang_id' => $kandang->id,
            'tanggal_masuk' => $masuk,
            'populasi' => self::POPULASI,
            'strain_doc' => 'Cobb 500',
            'jenis_pakan' => 'BR-1/BR-2',
            'nama_ppl' => 'PPL Contoh',
            'bobot_sak_kg' => 50,
            'nama_inti' => 'PT Inti Contoh',
            'status' => Siklus::STATUS_BERJALAN,
        ]);

        KontrakSiklus::create([
            'siklus_id' => $siklus->id,
            'harga_doc' => 7000,
            'harga_pakan_sak' => 430000,
            'harga_ovk' => 1500,
            'harga_kontrak_ayam' => 20500,
            'bonus_fcr' => true,
            'bonus_deplesi' => true,
            'bonus_ip' => false,
            'bonus_harga_pasar' => false,
        ]);

        $standar = $this->standarHarian();
        $siklus->standarHarian()->createMany($standar);

        $siklus->parameterUmur()->createMany([
            ['umur_awal' => 1, 'umur_akhir' => 7, 'suhu_min' => 30, 'suhu_maks' => 33, 'kepadatan_ekor_m2' => 60, 'frekuensi_pakan' => 9, 'jumlah_tma' => 60, 'jumlah_tra' => 60],
            ['umur_awal' => 8, 'umur_akhir' => 18, 'suhu_min' => 27, 'suhu_maks' => 30, 'kepadatan_ekor_m2' => 30, 'frekuensi_pakan' => 6, 'jumlah_tma' => 100, 'jumlah_tra' => 110],
            ['umur_awal' => 19, 'umur_akhir' => 35, 'suhu_min' => 24, 'suhu_maks' => 27, 'kepadatan_ekor_m2' => 8, 'frekuensi_pakan' => 3, 'jumlah_tma' => 140, 'jumlah_tra' => 160],
        ]);

        $siklus->jadwalVaksin()->createMany([
            ['hari_ke' => 4, 'nama_vaksin' => 'ND-IB', 'keterangan' => 'Tetes mata'],
            ['hari_ke' => 10, 'nama_vaksin' => 'Gumboro', 'keterangan' => 'Air minum'],
            ['hari_ke' => 18, 'nama_vaksin' => 'ND Lasota', 'keterangan' => 'Air minum'],
        ]);

        $this->recordings($siklus, $standar, $masuk);
        $this->samplings($siklus, $standar, $masuk, $calc);
        $this->ovk($siklus, $masuk);
        $panen = $this->panen($siklus, $masuk);

        $this->rhpp($siklus, $panen, $calc);

        $siklus->update([
            'tanggal_panen' => $masuk->addDays(self::UMUR_MAKS - 1),
            'status' => Siklus::STATUS_SELESAI,
        ]);
    }

    /** @return list<array<string, float|int>> */
    private function standarHarian(): array
    {
        $raw = [];
        for ($d = 1; $d <= self::UMUR_MAKS; $d++) {
            $raw[$d] = $d ** 1.3 + 8;
        }
        $skala = 3233 / array_sum($raw);

        $rows = [];
        $kumulatif = 0.0;
        foreach ($raw as $d => $nilai) {
            $gram = $nilai * $skala;
            $kumulatif += $gram;
            $bobot = 42 + (1928 - 42) * ($d / self::UMUR_MAKS) ** 1.8;

            $rows[] = [
                'hari_ke' => $d,
                'pakan_std_sak' => round($kumulatif * self::POPULASI / 1000 / 50, 2),
                'bobot_std_gram' => round($bobot, 2),
                'fcr_std' => round($kumulatif / $bobot, 3),
                'feed_intake_std_gram' => round($kumulatif, 2),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, float|int>>  $standar
     */
    private function recordings(Siklus $siklus, array $standar, CarbonImmutable $masuk): void
    {
        $afkirHari = [5, 9, 13, 17, 21, 24, 27, 30, 33];
        $masukHari = [1 => 65, 12 => 65, 23 => 65];

        // Pakai harian = selisih pakan standar kumulatif, diskalakan ke total 190 sak.
        $pakai = [];
        $prev = 0.0;
        foreach ($standar as $row) {
            $pakai[$row['hari_ke']] = $row['pakan_std_sak'] - $prev;
            $prev = $row['pakan_std_sak'];
        }
        $skala = 190 / array_sum($pakai);

        $totalPakai = 0.0;
        $totalMasuk = 0.0;
        for ($d = 1; $d <= self::UMUR_MAKS; $d++) {
            $sak = $d === self::UMUR_MAKS ? round(190 - $totalPakai, 2) : round($pakai[$d] * $skala, 2);
            $totalPakai += $sak;
            $totalMasuk += $masukHari[$d] ?? 0;

            RecordingHarian::create([
                'siklus_id' => $siklus->id,
                'tanggal' => $masuk->addDays($d - 1),
                'hari_ke' => $d,
                'pakan_masuk_sak' => $masukHari[$d] ?? 0,
                'pakan_pakai_sak' => $sak,
                'pakan_sisa_sak' => round($totalMasuk - $totalPakai, 2),
                'mati' => intdiv(78, self::UMUR_MAKS) + ($d <= 78 % self::UMUR_MAKS ? 1 : 0),
                'afkir' => in_array($d, $afkirHari, true) ? 2 : 0,
                'sebab_afkir' => in_array($d, $afkirHari, true) ? 'sakit' : null,
                'bobot_aktual_gram' => $standar[$d - 1]['bobot_std_gram'],
                'diparaf_ppl' => $d % 7 === 0,
                'tanggal_paraf' => $d % 7 === 0 ? $masuk->addDays($d - 1) : null,
            ]);
        }
    }

    /**
     * @param  list<array<string, float|int>>  $standar
     */
    private function samplings(Siklus $siklus, array $standar, CarbonImmutable $masuk, PerformanceCalculator $calc): void
    {
        foreach ([7, 14, 21, 28] as $d) {
            SamplingBobot::create([
                'siklus_id' => $siklus->id,
                'tanggal' => $masuk->addDays($d - 1),
                'hari_ke' => $d,
                'jumlah_sampel' => 150,
                'total_bobot_kg' => round(150 * $standar[$d - 1]['bobot_std_gram'] / 1000, 3),
            ]);
        }
    }

    private function ovk(Siklus $siklus, CarbonImmutable $masuk): void
    {
        $rows = [
            [4, 'vaksin', 'ND-IB', '1 dosis/ekor', 150000],
            [10, 'vaksin', 'Gumboro', '1 dosis/ekor', 175000],
            [18, 'vaksin', 'ND Lasota', '1 dosis/ekor', 150000],
            [12, 'obat', 'Vitamin', '1 g/L', 90000],
        ];

        foreach ($rows as [$d, $jenis, $nama, $dosis, $biaya]) {
            PemberianOvk::create([
                'siklus_id' => $siklus->id,
                'tanggal' => $masuk->addDays($d - 1),
                'jenis' => $jenis,
                'nama_produk' => $nama,
                'dosis' => $dosis,
                'biaya' => $biaya,
            ]);
        }
    }

    /** @return list<Panen> */
    private function panen(Siklus $siklus, CarbonImmutable $masuk): array
    {
        $tahap = [
            [33, 400, 780.00, 20500, 'BTPA-001'],
            [34, 1523, 3015.54, 20500, 'BTPA-002'],
            [35, 981, 1954.46, 20800, 'BTPA-003'],
        ];

        $hasil = [];
        foreach ($tahap as [$umur, $ekor, $tonase, $harga, $btpa]) {
            $hasil[] = Panen::create([
                'siklus_id' => $siklus->id,
                'tanggal' => $masuk->addDays($umur - 1),
                'umur_hari' => $umur,
                'jumlah_ekor' => $ekor,
                'tonase_kg' => $tonase,
                'harga_per_kg' => $harga,
                'nomor_btpa' => $btpa,
            ]);
        }

        return $hasil;
    }

    /** @param  list<Panen>  $panen */
    private function rhpp(Siklus $siklus, array $panen, PerformanceCalculator $calc): void
    {
        $totalEkor = array_sum(array_map(fn (Panen $p) => $p->jumlah_ekor, $panen));
        $totalTonase = array_sum(array_map(fn (Panen $p) => (float) $p->tonase_kg, $panen));
        // Tagihan sapronak = seluruh pakan masuk; terpakai = tagihan dikurangi retur.
        $tagihanKg = 195 * 50.0;
        $returSak = 5;
        $terpakaiKg = $tagihanKg - $returSak * 50.0;

        $deplesi = $calc->deplesiPersen(78, 18, self::POPULASI);
        $abw = $calc->abw($totalTonase, $totalEkor);
        $fcr = $calc->fcr($terpakaiKg, $totalTonase);
        $umur = $calc->umurPanenRataRata(array_map(
            fn (Panen $p) => ['jumlah_ekor' => $p->jumlah_ekor, 'umur_hari' => $p->umur_hari],
            $panen,
        ));

        Rhpp::create([
            'siklus_id' => $siklus->id,
            'nomor_rhpp' => 'RHPP/CONTOH/'.$panen[2]->tanggal->format('Y/m').'/0001',
            'tanggal_terbit' => $panen[2]->tanggal->addDays(5),
            'populasi_doc' => self::POPULASI,
            'deplesi_ekor' => 78 + 18,
            'pakan_terpakai_kg' => $terpakaiKg,
            'biaya_doc' => self::POPULASI * 7000,
            'biaya_pakan' => 195 * 430000,
            'biaya_ovk' => (float) PemberianOvk::where('siklus_id', $siklus->id)->sum('biaya'),
            'total_pakan_kg' => $tagihanKg,
            'pakan_retur_sak' => $returSak,
            'total_ekor' => $totalEkor,
            'total_tonase' => $totalTonase,
            'abw' => $abw,
            'umur_panen' => $umur,
            'deplesi_persen' => $deplesi,
            'fcr' => $fcr,
            'ip' => $calc->indeksPerforma($calc->liveability($deplesi), $abw, $fcr, $umur),
            'pendapatan' => array_sum(array_map(fn (Panen $p) => (float) $p->tonase_kg * (float) $p->harga_per_kg, $panen)),
        ]);
    }
}
