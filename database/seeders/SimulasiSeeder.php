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
use App\Services\LaporanSiklus;
use App\Services\PerformanceCalculator;
use App\Services\RingkasanPerforma;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Data simulasi satu peternak dengan empat kandang dan ±14 bulan riwayat siklus, supaya semua layar bisa dicoba dengan isi.
 *
 *   php artisan db:seed --class=SimulasiSeeder
 *
 * Aman dijalankan ulang: hanya akun simulasi@noa.test (beserta kandang dan datanya) yang dibuat ulang; akun lain tidak disentuh.
 * Masuk dengan simulasi@noa.test atau abk.simulasi@noa.test, kata sandi `password`. Tanggal dihitung relatif terhadap hari ini.
 *
 * Isi data (angka dibangkitkan dengan benih tetap, jadi hasilnya sama setiap kali):
 *  - Kandang A: 5 siklus selesai dengan performa naik-turun, satu RHPP banyak selisih, siklus berjalan (hari ke-23) dengan hari terlewat.
 *  - Kandang B: siklus ditutup tanpa RHPP, siklus dihentikan karena wabah, siklus selesai, dan siklus menunggu RHPP.
 *  - Kandang C: dua siklus selesai dan satu siklus tahap rekonsiliasi (RHPP sudah diinput, ada catatan selisih).
 *  - Kandang D: belum pernah ada siklus.
 */
class SimulasiSeeder extends Seeder
{
    private CarbonImmutable $hariIni;

    public function run(): void
    {
        mt_srand(20261118);
        $this->hariIni = CarbonImmutable::today();

        $peternak = $this->akun();
        $kandang = [
            'A' => $this->kandang($peternak, 'Kandang A', 'Desa Sumberejo, Kec. Pujon, Kab. Malang', 8000),
            'B' => $this->kandang($peternak, 'Kandang B', 'Desa Pandesari, Kec. Pujon, Kab. Malang', 5000),
            'C' => $this->kandang($peternak, 'Kandang C', 'Desa Ngroto, Kec. Pujon, Kab. Malang', 6000),
            'D' => $this->kandang($peternak, 'Kandang D (Baru)', 'Desa Tawangsari, Kec. Pujon, Kab. Malang', 4000),
        ];

        // Kandang A
        $this->siklus($kandang['A'], ['lalu' => 420, 'umur' => 33, 'pop' => 7800, 'fcr' => 1.68, 'depl' => 0.045, 'bobot' => 1.95, 'rhpp' => 'sama', 'strain' => 'Cobb 500']);
        $this->siklus($kandang['A'], ['lalu' => 335, 'umur' => 34, 'pop' => 7900, 'fcr' => 1.62, 'depl' => 0.035, 'bobot' => 2.02, 'rhpp' => 'sama', 'strain' => 'Cobb 500']);
        $this->siklus($kandang['A'], ['lalu' => 250, 'umur' => 35, 'pop' => 8000, 'fcr' => 1.58, 'depl' => 0.030, 'bobot' => 2.10, 'rhpp' => 'sama', 'strain' => 'CP 707']);
        $this->siklus($kandang['A'], ['lalu' => 165, 'umur' => 33, 'pop' => 7800, 'fcr' => 1.66, 'depl' => 0.052, 'bobot' => 1.92, 'rhpp' => 'beda', 'strain' => 'CP 707']);
        $this->siklus($kandang['A'], ['lalu' => 80, 'umur' => 34, 'pop' => 8000, 'fcr' => 1.60, 'depl' => 0.033, 'bobot' => 2.05, 'rhpp' => 'sama', 'strain' => 'CP 707']);
        $berjalan = $this->siklus($kandang['A'], ['lalu' => 22, 'umur' => 34, 'pop' => 8000, 'fcr' => 1.61, 'depl' => 0.034, 'bobot' => 2.04, 'rhpp' => 'berjalan', 'strain' => 'CP 707', 'lewati' => [12, 22, 23]]);

        // Kandang B
        $this->siklus($kandang['B'], ['lalu' => 390, 'umur' => 35, 'pop' => 4900, 'fcr' => 1.75, 'depl' => 0.062, 'bobot' => 1.90, 'rhpp' => 'sama', 'strain' => 'Lohmann', 'tanpaStandar' => true]);
        $this->siklus($kandang['B'], ['lalu' => 305, 'umur' => 34, 'pop' => 5000, 'fcr' => 1.70, 'depl' => 0.050, 'bobot' => 1.95, 'rhpp' => 'tanpa', 'strain' => 'Lohmann']);
        $this->siklus($kandang['B'], ['lalu' => 220, 'umur' => 16, 'pop' => 4900, 'fcr' => 1.4, 'depl' => 0.18, 'bobot' => 0.5, 'rhpp' => 'henti', 'strain' => 'Cobb 500']);
        $this->siklus($kandang['B'], ['lalu' => 150, 'umur' => 34, 'pop' => 5000, 'fcr' => 1.64, 'depl' => 0.040, 'bobot' => 2.00, 'rhpp' => 'sama', 'strain' => 'Cobb 500']);
        $this->siklus($kandang['B'], ['lalu' => 46, 'umur' => 34, 'pop' => 5000, 'fcr' => 1.63, 'depl' => 0.038, 'bobot' => 2.03, 'rhpp' => 'menunggu', 'strain' => 'Cobb 500']);

        // Kandang C
        $this->siklus($kandang['C'], ['lalu' => 300, 'umur' => 34, 'pop' => 5900, 'fcr' => 1.61, 'depl' => 0.031, 'bobot' => 2.06, 'rhpp' => 'sama', 'strain' => 'CP 707']);
        $this->siklus($kandang['C'], ['lalu' => 200, 'umur' => 33, 'pop' => 6000, 'fcr' => 1.63, 'depl' => 0.036, 'bobot' => 2.01, 'rhpp' => 'sama', 'strain' => 'CP 707']);
        $this->siklus($kandang['C'], ['lalu' => 60, 'umur' => 33, 'pop' => 6000, 'fcr' => 1.65, 'depl' => 0.042, 'bobot' => 1.98, 'rhpp' => 'rekon', 'strain' => 'CP 707']);

        // ABK contoh ditugaskan ke siklus berjalan.
        (new User)->forceFill([
            'name' => 'Andi (ABK)', 'email' => 'abk.simulasi@noa.test', 'password' => 'password',
            'role' => User::ROLE_ABK, 'peternak_id' => $peternak->id, 'siklus_id' => $berjalan->id, 'email_verified_at' => now(),
        ])->save();

        $this->command?->info('Simulasi siap. Masuk: simulasi@noa.test / password (ABK: abk.simulasi@noa.test / password).');
    }

    private function akun(): User
    {
        $lama = User::where('email', 'simulasi@noa.test')->first();
        if ($lama) {
            User::where('peternak_id', $lama->id)->delete();
            Kandang::withTrashed()->where('user_id', $lama->id)->get()->each(function (Kandang $k) {
                Siklus::withTrashed()->where('kandang_id', $k->id)->get()->each(fn (Siklus $s) => $s->forceDelete());
                $k->forceDelete();
            });
            $lama->forceDelete();
        }
        User::where('email', 'abk.simulasi@noa.test')->delete();

        $p = (new User)->forceFill([
            'name' => 'Pak Simulasi', 'email' => 'simulasi@noa.test', 'password' => 'password',
            'role' => User::ROLE_PETERNAK, 'email_verified_at' => now(),
        ]);
        $p->save();

        return $p;
    }

    private function kandang(User $p, string $nama, string $alamat, int $kapasitas): Kandang
    {
        return Kandang::create(['user_id' => $p->id, 'nama_farm' => $nama, 'alamat' => $alamat, 'kapasitas' => $kapasitas]);
    }

    /** Bobot standar (g) pada hari d untuk siklus berumur akhir $umur dan bobot akhir $kg. */
    private function bobot(int $d, int $umur, float $kg): float
    {
        return 42 + ($kg * 1000 - 42) * ($d / $umur) ** 1.8;
    }

    private function bobotPakan(int $d): float
    {
        return $d ** 1.3 + 8;
    }

    /** @param  array<string, mixed>  $o */
    private function siklus(Kandang $kandang, array $o): Siklus
    {
        $umur = $o['umur'];
        $pop = $o['pop'];
        $rhppMode = $o['rhpp'];
        $masuk = $this->hariIni->subDays($o['lalu']);
        $berjalan = $rhppMode === 'berjalan';
        $hariIni = $berjalan ? (int) $masuk->diffInDays($this->hariIni) + 1 : $umur;
        $lewati = $o['lewati'] ?? [];
        $henti = $rhppMode === 'henti';

        $siklus = Siklus::create([
            'kandang_id' => $kandang->id, 'tanggal_masuk' => $masuk, 'populasi' => $pop, 'strain_doc' => $o['strain'], 'jenis_pakan' => 'BR-1/BR-2',
            'nama_ppl' => 'Rina', 'nama_inti' => 'PT Sumber Unggas', 'bobot_sak_kg' => 50, 'status' => Siklus::STATUS_BERJALAN,
        ]);

        $kontrak = ['harga_doc' => mt_rand(6300, 6700), 'harga_pakan_sak' => mt_rand(405000, 420000), 'harga_kontrak_ayam' => mt_rand(19200, 20200),
            'bonus_fcr' => true, 'bonus_deplesi' => true, 'bonus_ip' => false, 'bonus_harga_pasar' => false, 'nilai_bonus_fcr' => 200, 'nilai_bonus_deplesi' => 150];
        KontrakSiklus::create(['siklus_id' => $siklus->id] + $kontrak);

        // Standar harian (kecuali yang sengaja tidak punya).
        $akhirStandar = max($umur, 35);
        if (empty($o['tanpaStandar']) && ! $henti) {
            $this->standar($siklus, $pop, $akhirStandar, $o['bobot']);
        }

        // 1) Deplesi per hari.
        $hariCatat = $henti ? $umur : ($berjalan ? $hariIni - 1 : $umur);
        $totalDeplesi = (int) round($pop * $o['depl']);
        $perHari = $this->sebarDeplesi($totalDeplesi, $henti ? $umur : $umur, $hariCatat);
        $hidup = [];
        $sisa = $pop;
        for ($d = 1; $d <= $hariCatat; $d++) {
            $sisa -= $perHari[$d];
            $hidup[$d] = $sisa;
        }

        // 2) Rencana panen (tidak ada untuk siklus berjalan atau yang dihentikan).
        $tahap = [];
        if (! $berjalan && ! $henti) {
            $hidupAkhir = $hidup[$umur];
            $kurang = mt_rand(0, 3) === 0 ? mt_rand(5, 25) : 0; // ayam yang tidak tercatat pada beberapa siklus
            $dipanen = $hidupAkhir - $kurang;
            $bagian = [0.34, 0.33];
            $umurTahap = [$umur - 4, $umur - 2, $umur];
            $sudah = 0;
            foreach ($umurTahap as $i => $uT) {
                $ekor = $i < 2 ? (int) round($dipanen * $bagian[$i]) : $dipanen - $sudah;
                $sudah += $ekor;
                $abw = $this->bobot($uT, $umur, $o['bobot']) * (1 + (mt_rand(-8, 8) / 1000));
                $tahap[] = ['umur' => $uT, 'ekor' => $ekor, 'tonase' => round($ekor * $abw / 1000, 2)];
            }
        }

        // 3) Skala pakan agar FCR mendekati target: total pakan = FCR x total tonase.
        $tonaseTotal = $tahap ? array_sum(array_column($tahap, 'tonase')) : 0;
        $bobotTotal = 0.0;
        foreach ($hidup as $d => $h) {
            $bobotTotal += $h * $this->bobotPakan($d);
        }
        if ($tonaseTotal > 0) {
            $skala = $o['fcr'] * $tonaseTotal * 1000 / $bobotTotal; // gram per satuan bobot
        } else {
            // siklus berjalan/dihentikan: pakai bobot ayam hidup pada hari terakhir sebagai dasar tonase
            $ref = $hidup[$hariCatat] * $this->bobot($hariCatat, $umur, $o['bobot']) / 1000;
            $skala = $o['fcr'] * $ref * 1000 / $bobotTotal;
        }

        $pakai = [];
        for ($d = 1; $d <= $hariCatat; $d++) {
            $pakai[$d] = round($hidup[$d] * $skala * $this->bobotPakan($d) / 1000 / 50 * (1 + mt_rand(-30, 30) / 1000), 2);
        }
        $kirim = $this->jadwalPakan($pakai, $hariCatat);

        // 4) Recording harian.
        $stok = 0.0;
        $kumPakai = 0.0;
        $kunjungan = (int) floor(($hariCatat - ($berjalan ? 7 : 0)) / 7) * 7;
        for ($d = 1; $d <= $hariCatat; $d++) {
            $stok += ($kirim[$d] ?? 0);
            $stok -= $pakai[$d];
            $kumPakai += $pakai[$d];
            if (in_array($d, $lewati, true)) {
                $stok += $pakai[$d] - $pakai[$d]; // hari terlewat: tidak ada baris
                continue;
            }
            $dep = $perHari[$d];
            $afkir = $d > 3 && $dep > 0 ? min($dep, (int) round($dep * 0.2 + mt_rand(0, 1) * 0.5)) : 0;
            $diparaf = $d <= $kunjungan;
            $sudahSampling = in_array($d, [7, 14, 21, 28, 35], true) && $d <= $hariCatat;
            RecordingHarian::create([
                'siklus_id' => $siklus->id, 'tanggal' => $masuk->addDays($d - 1), 'hari_ke' => $d,
                'pakan_masuk_sak' => $kirim[$d] ?? 0, 'pakan_pakai_sak' => $pakai[$d], 'pakan_sisa_sak' => round($stok, 2),
                'mati' => $dep - $afkir, 'afkir' => $afkir, 'sebab_afkir' => $afkir > 0 ? ['sakit', 'kerdil', 'cacat'][mt_rand(0, 2)] : null,
                'bobot_aktual_gram' => $sudahSampling ? round($this->bobot($d, $umur, $o['bobot']) * (1 + mt_rand(-10, 10) / 1000), 2) : null,
                'suhu_pagi' => round($d <= 7 ? mt_rand(305, 330) / 10 : mt_rand(255, 295) / 10, 1),
                'suhu_sore' => round($d <= 7 ? mt_rand(300, 325) / 10 : mt_rand(260, 300) / 10, 1),
                'ovk_pagi' => in_array($d, [4, 10, 18], true) ? 'Diberikan' : null,
                'ovk_sore' => in_array($d, [7, 14, 21], true) ? 'Diberikan' : null,
                'diparaf_ppl' => $diparaf, 'tanggal_paraf' => $diparaf ? $masuk->addDays(min($hariCatat, (int) (ceil($d / 7) * 7)) - 1) : null,
            ]);
        }

        // 5) Sampling bobot mingguan.
        foreach ([7, 14, 21, 28, 35] as $d) {
            if ($d > $hariCatat || ($d > $umur)) {
                continue;
            }
            $abw = $this->bobot($d, $umur, $o['bobot']) * (1 + mt_rand(-15, 15) / 1000);
            $sampel = (int) round($hidup[$d] * (mt_rand(52, 60) / 1000));
            SamplingBobot::create(['siklus_id' => $siklus->id, 'tanggal' => $masuk->addDays($d - 1), 'hari_ke' => $d, 'jumlah_sampel' => $sampel, 'total_bobot_kg' => round($sampel * $abw / 1000, 3)]);
        }

        // 6) OVK.
        foreach ([[4, 'vaksin', 'ND-IB', '1 dosis/ekor · tetes mata', 150], [10, 'vaksin', 'Gumboro', '1 dosis/ekor · air minum', 175], [18, 'vaksin', 'ND La Sota', '1 dosis/ekor · air minum', 150],
            [7, 'vitamin', 'Vita Stress', '1 g/L air minum', 60], [14, 'obat', 'Enrofloxacin 10%', '1 ml/L · 3 hari', 110], [21, 'vitamin', 'Elektrolit', '1 g/L air minum', 55]] as [$d, $jenis, $nama, $dosis, $dasar]) {
            if ($d > $hariCatat) {
                continue;
            }
            PemberianOvk::create(['siklus_id' => $siklus->id, 'tanggal' => $masuk->addDays($d - 1), 'jenis' => $jenis, 'nama_produk' => $nama, 'dosis' => $dosis,
                'biaya' => round($dasar * 1000 * $pop / 5000 * (1 + mt_rand(-50, 50) / 1000), -3)]);
        }

        // 7) Panen dan BTPA.
        foreach ($tahap as $i => $t) {
            Panen::create(['siklus_id' => $siklus->id, 'tanggal' => $masuk->addDays($t['umur'] - 1), 'umur_hari' => $t['umur'], 'jumlah_ekor' => $t['ekor'], 'tonase_kg' => $t['tonase'],
                'nomor_btpa' => sprintf('SU-%s-%03d', $masuk->addDays($t['umur'] - 1)->format('md'), mt_rand(100, 999)), 'foto_btpa' => null]);
        }

        // 8) Pengaturan akhir, RHPP, dan status.
        $this->selesaikan($siklus, $o, $masuk, $umur, $tahap);

        return $siklus->fresh();
    }

    /** @return array<int, int> deplesi per hari (1..$umurCatat); lebih banyak di minggu pertama. */
    private function sebarDeplesi(int $total, int $umur, int $hariCatat): array
    {
        $bobot = [];
        for ($d = 1; $d <= $umur; $d++) {
            $bobot[$d] = $d <= 7 ? 2.2 : ($d <= 21 ? 0.9 : 0.6);
        }
        $jumlah = array_sum($bobot);
        $hasil = array_fill(1, $umur, 0);
        foreach ($bobot as $d => $w) {
            $hasil[$d] = (int) floor($total * $w / $jumlah);
        }
        $kurang = $total - array_sum($hasil);
        while ($kurang-- > 0) {
            $hasil[mt_rand(1, $umur)]++;
        }

        return array_slice($hasil, 0, $hariCatat, true);
    }

    /**
     * Jadwal pengiriman pakan: tiap pengiriman menutup kebutuhan sampai pengiriman berikutnya, dibulatkan ke 5 sak,
     * dan pengiriman terakhir sengaja lebih besar beberapa sak (sisa gudang).
     *
     * @param  array<int, float>  $pakai
     * @return array<int, int>
     */
    private function jadwalPakan(array $pakai, int $hariCatat): array
    {
        $titik = array_values(array_filter([1, 10, 19, 27, 33], fn ($d) => $d <= $hariCatat));
        $kirim = [];
        foreach ($titik as $i => $d) {
            $sampai = ($titik[$i + 1] ?? $hariCatat + 1) - 1;
            $butuh = 0.0;
            for ($x = $d; $x <= $sampai; $x++) {
                $butuh += $pakai[$x] ?? 0;
            }
            $kirim[$d] = (int) (ceil($butuh / 5) * 5) + ($i === count($titik) - 1 ? mt_rand(2, 6) : 0);
        }

        return $kirim;
    }

    private function standar(Siklus $siklus, int $pop, int $umur, float $kg): void
    {
        $jumlah = 0;
        for ($d = 1; $d <= $umur; $d++) {
            $jumlah += $this->bobotPakan($d);
        }
        $skala = 1.55 * $kg * 1000 / $jumlah;
        $kum = 0.0;
        $baris = [];
        for ($d = 1; $d <= $umur; $d++) {
            $kum += $this->bobotPakan($d) * $skala;
            $bobot = $this->bobot($d, $umur, $kg);
            $baris[] = ['hari_ke' => $d, 'pakan_std_sak' => round($kum * $pop / 1000 / 50, 2), 'bobot_std_gram' => round($bobot, 2), 'fcr_std' => round($kum / $bobot, 3),
                'feed_intake_std_gram' => round($kum, 2), 'deplesi_std_persen' => round(0.4 + 2.6 * ($d / $umur) ** 0.8, 2)];
        }
        $siklus->standarHarian()->createMany($baris);
    }

    /** @param  list<array<string, mixed>>  $tahap */
    private function selesaikan(Siklus $siklus, array $o, CarbonImmutable $masuk, int $umur, array $tahap): void
    {
        $mode = $o['rhpp'];
        $terakhir = $tahap ? $masuk->addDays($umur - 1) : null;

        if ($mode === 'berjalan') {
            return;
        }

        if ($mode === 'henti') {
            $siklus->update(['status' => Siklus::STATUS_DIHENTIKAN, 'alasan_penutupan' => 'Wabah penyakit', 'ditutup_pada' => $masuk->addDays($umur),
                'keterangan_penutupan' => 'Kematian mendadak sejak hari ke-12; sudah dicek dokter hewan inti dan populasi dimusnahkan.']);

            return;
        }

        $siklus->update(['tanggal_panen' => $terakhir]);

        if ($mode === 'menunggu') {
            $siklus->update(['status' => Siklus::STATUS_MENUNGGU_RHPP]);

            return;
        }

        if ($mode === 'tanpa') {
            $siklus->update(['status' => Siklus::STATUS_DITUTUP_TANPA_RHPP, 'ditutup_pada' => $terakhir->addDays(14), 'alasan_penutupan' => 'RHPP tidak diterbitkan inti',
                'keterangan_penutupan' => 'Sudah ditanyakan ke PPL Rina dua kali; inti belum menjadwalkan.', 'tanggal_rechick' => $terakhir->addDays(20)]);

            return;
        }

        $this->buatRhpp($siklus, $mode, $terakhir);

        if ($mode === 'rekon') {
            $siklus->update(['status' => Siklus::STATUS_REKONSILIASI]);

            return;
        }

        $siklus->update(['status' => Siklus::STATUS_SELESAI, 'ditutup_pada' => $terakhir->addDays(12), 'tanggal_rechick' => $terakhir->addDays(20)]);
    }

    private function buatRhpp(Siklus $siklus, string $mode, CarbonImmutable $terakhir): void
    {
        $calc = app(PerformanceCalculator::class);
        $siklus->load('kontrak');
        $p = app(RingkasanPerforma::class)->hitung($siklus);
        $biaya = app(LaporanSiklus::class)->biaya($siklus, $p);
        $bobotSak = (float) $siklus->bobot_sak_kg;
        $beda = in_array($mode, ['beda', 'rekon'], true);

        $ekor = $p['total_ekor'];
        $tonase = $p['total_tonase'] * ($beda ? 0.996 : 1);
        // Inti kadang menghitung ayam yang tidak tercatat sebagai deplesi.
        $deplesi = $p['deplesi'] + ($beda ? max(0, $p['selisih_populasi']) + mt_rand(0, 6) : 0);
        $masukKg = $p['pakan_masuk_sak'] * $bobotSak;
        $retur = $beda ? 0.0 : round($p['pakan_masuk_sak'] - $p['pakan_bersih_sak'], 2);
        $terpakai = $beda ? $masukKg : $p['pakan_bersih_kg'];
        $fcr = $tonase > 0 ? $terpakai / $tonase : null;
        $abw = $ekor > 0 ? $tonase / $ekor : null;
        $deplesiPersen = $siklus->populasi > 0 ? $deplesi / $siklus->populasi * 100 : 0;
        $ip = $calc->indeksPerforma($calc->liveability($deplesiPersen), $abw, $fcr, $p['umur_panen']);
        $ovk = $biaya['ovk'] * ($beda ? 1.06 : 1);
        $pendapatan = $tonase * (float) ($siklus->kontrak->harga_kontrak_ayam ?? 0);
        $bonus = ($fcr !== null && $fcr < 1.65) ? round($tonase * 130, -3) : 0;
        $hasilBersih = $pendapatan - ($biaya['doc'] ?? 0) - ($biaya['pakan'] ?? 0) - $ovk + $bonus;

        Rhpp::create([
            'siklus_id' => $siklus->id,
            'nomor_rhpp' => sprintf('RHPP/SU/%s/%04d', $terakhir->addDays(6)->format('Y/m'), mt_rand(100, 999)),
            'tanggal_terbit' => $terakhir->addDays(6),
            'populasi_doc' => $siklus->populasi, 'total_ekor' => $ekor, 'deplesi_ekor' => $deplesi, 'total_tonase' => round($tonase, 2),
            'pakan_terpakai_kg' => round($terpakai, 2), 'total_pakan_kg' => round($masukKg, 2), 'pakan_retur_sak' => $retur,
            'fcr' => $fcr === null ? null : round($fcr, 3), 'ip' => $ip === null ? null : round($ip, 2), 'abw' => $abw === null ? null : round($abw, 3),
            'umur_panen' => round($p['umur_panen'], 2), 'deplesi_persen' => round($deplesiPersen, 2),
            'biaya_doc' => round($biaya['doc'] ?? 0), 'biaya_pakan' => round($biaya['pakan'] ?? 0), 'biaya_ovk' => round($ovk), 'pendapatan' => round($pendapatan),
            'bonus' => $bonus ?: null, 'hasil_bersih' => round($hasilBersih),
            'catatan' => $mode === 'beda' ? [
                'deplesi' => 'Sebagian ayam belum tercatat di NOA; inti menghitungnya sebagai deplesi.',
                'pakan_terpakai' => 'Inti memakai pakan masuk sebagai pakan terpakai (tanpa retur).',
            ] : null,
        ]);
    }
}
