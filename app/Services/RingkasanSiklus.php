<?php

namespace App\Services;

use App\Models\Siklus;
use Illuminate\Support\Carbon;

/**
 * Bahan halaman Ringkasan Siklus: angka berjalan terhadap standar, dua grafik batang,
 * daftar "Perlu perhatian", dan (untuk siklus tertutup) angka final serta rangkuman penutupan.
 * Semua dihitung dari recording, sampling, dan panen; tidak ada angka yang disimpan terpisah.
 */
class RingkasanSiklus
{
    /** Stok dianggap tipis bila cukup untuk kurang dari ini (hari pemakaian rata-rata). */
    public const STOK_TIPIS_HARI = 3;

    public function __construct(
        private RingkasanRecording $recording,
        private RingkasanSampling $sampling,
        private RingkasanPerforma $performa,
    ) {}

    /** @return array<string, mixed> */
    public function hitung(Siklus $siklus): array
    {
        $standar = $siklus->standarHarian()->get();
        $stdPerHari = $standar->keyBy('hari_ke');

        $baris = $this->recording->baris(
            $siklus,
            $siklus->recordings()->orderBy('hari_ke')->get(),
            $standar,
            collect(),
            $siklus->samplings()->get(),
        );
        $akhir = $baris ? end($baris) : null;
        $samplingBaris = $this->sampling->baris($siklus, $siklus->samplings()->get(), $standar);
        $samplingAkhir = $samplingBaris ? end($samplingBaris) : null;

        $deplesiStd = $akhir ? $this->angkaStd($stdPerHari->get($akhir['hari_ke'])?->deplesi_std_persen) : null;
        $fcr = $this->fcrTerakhir($baris, $stdPerHari);
        $stdAkhir = $akhir ? $stdPerHari->get($akhir['hari_ke']) : null;

        $totalMasuk = array_sum(array_column($baris, 'pakan_masuk_sak'));
        $totalPakai = $akhir ? $akhir['pakan_kum_sak'] : 0.0;

        $hasil = [
            'ada_recording' => $akhir !== null,
            'hari_terakhir' => $akhir['hari_ke'] ?? null,
            'tanggal_terakhir' => $akhir['tanggal'] ?? null,
            'standar_ada' => $standar->isNotEmpty(),

            'populasi_kini' => $akhir ? $akhir['ayam_hidup'] : $siklus->populasi,
            'deplesi_persen' => $akhir['deplesi_kum_persen'] ?? null,
            'deplesi_std' => $deplesiStd,
            'deplesi_banding' => $akhir && $deplesiStd !== null ? $this->banding($akhir['deplesi_kum_persen'], $deplesiStd) : null,
            'fcr' => $fcr,
            'feed_intake' => $akhir['feed_intake_gram'] ?? null,
            'feed_intake_std' => $stdAkhir ? $this->angkaStd($stdAkhir->feed_intake_std_gram) : null,
            'stok_sak' => $akhir['stok_sak'] ?? null,
            'pakan_masuk_sak' => $totalMasuk,
            'pakan_terpakai_sak' => $totalPakai,
            'abw' => $samplingAkhir ? ['gram' => $samplingAkhir['abw_gram'], 'hari' => $samplingAkhir['hari_ke'], 'std' => $samplingAkhir['std_gram'], 'adg' => $samplingAkhir['adg_gram']] : null,

            'grafik_bobot' => array_map(fn ($b) => ['label' => 'Hari '.$b['hari_ke'], 'aktual' => $b['abw_gram'], 'std' => $b['std_gram']], $samplingBaris),
            'grafik_deplesi' => $this->deplesiMingguan($baris, $stdPerHari),
            'perhatian' => $siklus->isTerbuka() ? $this->perhatian($siklus, $baris, $standar->isNotEmpty(), $totalMasuk) : [],
        ];

        $hasil['final'] = $siklus->isTerkunci() || $siklus->status !== Siklus::STATUS_BERJALAN ? $this->performa->hitung($siklus) : null;
        $hasil['penutupan'] = $siklus->isTerkunci() ? $this->penutupan($siklus) : null;

        return $hasil;
    }

    /** @param  array<int, array<string, mixed>>  $baris */
    private function fcrTerakhir(array $baris, $stdPerHari): ?array
    {
        foreach (array_reverse($baris, true) as $hari => $b) {
            if ($b['fcr_berjalan'] !== null) {
                return ['nilai' => $b['fcr_berjalan'], 'hari' => $hari, 'std' => $this->angkaStd($stdPerHari->get($hari)?->fcr_std)];
            }
        }

        return null;
    }

    /**
     * Deplesi kumulatif per minggu: aktual pada hari terakhir tercatat dalam minggu itu, dengan standar pada hari yang sama.
     *
     * @param  array<int, array<string, mixed>>  $baris
     * @return list<array{label: string, aktual: float|null, std: float|null}>
     */
    private function deplesiMingguan(array $baris, $stdPerHari): array
    {
        if (! $baris) {
            return [];
        }

        $terakhir = array_key_last($baris);
        $hasil = [];

        for ($minggu = 1; ($minggu - 1) * 7 < $terakhir; $minggu++) {
            $hariAkhir = min($minggu * 7, $terakhir);
            $b = $baris[$hariAkhir] ?? $this->barisTerdekat($baris, $hariAkhir);

            $hasil[] = [
                'label' => $hariAkhir === $minggu * 7 ? 'Mg '.$minggu : 'Hari '.$hariAkhir,
                'aktual' => $b['deplesi_kum_persen'] ?? null,
                'std' => $this->angkaStd($stdPerHari->get($hariAkhir)?->deplesi_std_persen),
            ];
        }

        return $hasil;
    }

    /** Baris recording terdekat pada atau sebelum hari tertentu. */
    private function barisTerdekat(array $baris, int $hari): ?array
    {
        $kandidat = array_filter($baris, fn ($b) => $b['hari_ke'] <= $hari);

        return $kandidat ? end($kandidat) : null;
    }

    /**
     * Hal yang perlu ditindaklanjuti peternak: recording terlewat, standar, pakan masuk, stok tipis, paraf.
     *
     * @param  array<int, array<string, mixed>>  $baris
     * @return list<array{judul: string, teks: string, tautan: string, label: string}>
     */
    private function perhatian(Siklus $siklus, array $baris, bool $standarAda, float $totalMasuk): array
    {
        $daftar = [];
        $umur = min(60, $siklus->umurHari());

        for ($hari = 1; $hari <= $umur; $hari++) {
            if (! isset($baris[$hari])) {
                $tanggal = $siklus->tanggal_masuk->copy()->addDays($hari - 1);
                $daftar[] = [
                    'judul' => "Recording hari ke-{$hari} belum diisi",
                    'teks' => $tanggal->translatedFormat('j M Y').($hari < $umur ? ' · entri mundur diizinkan' : ''),
                    'tautan' => route('recording.create', [$siklus, 'tanggal' => $tanggal->toDateString()]),
                    'label' => 'Isi recording',
                ];
                break;
            }
        }

        if (! $standarAda) {
            $daftar[] = ['judul' => 'Standar harian belum diunggah', 'teks' => 'Kolom standar tampil “–”', 'tautan' => route('siklus.standar', $siklus), 'label' => 'Unggah standar'];
        }

        if ($totalMasuk <= 0) {
            $daftar[] = ['judul' => 'Pakan masuk belum dicatat', 'teks' => 'Stok pakan dihitung dari pakan masuk − terpakai', 'tautan' => route('recording.create', $siklus), 'label' => 'Catat pakan masuk'];
        } elseif ($baris) {
            $akhir = end($baris);
            $tigaTerakhir = array_slice($baris, -3);
            $rata = array_sum(array_column($tigaTerakhir, 'pakan_pakai_sak')) / max(1, count($tigaTerakhir));

            if ($rata > 0 && $akhir['stok_sak'] < self::STOK_TIPIS_HARI * $rata) {
                $daftar[] = [
                    'judul' => 'Stok pakan '.\App\Support\Format::angka($akhir['stok_sak'], 0).' sak',
                    'teks' => 'Rata-rata pemakaian '.count($tigaTerakhir).' hari terakhir '.\App\Support\Format::angka($rata, 0).' sak/hari',
                    'tautan' => route('recording.create', $siklus),
                    'label' => 'Catat pakan masuk',
                ];
            }
        }

        $belumParaf = array_filter($baris, fn ($b) => ! $b['recording']->diparaf_ppl);
        if ($belumParaf) {
            $hari = array_keys($belumParaf);
            $daftar[] = [
                'judul' => count($belumParaf).' baris belum diparaf PPL',
                'teks' => 'Hari ke-'.min($hari).' s.d. '.max($hari).($siklus->nama_ppl ? ' · paraf oleh '.$siklus->nama_ppl : ''),
                'tautan' => route('recording.index', $siklus),
                'label' => 'Lihat recording',
            ];
        }

        return $daftar;
    }

    /** @return array<string, mixed> */
    private function penutupan(Siklus $siklus): array
    {
        $rhpp = $siklus->rhpp()->first();
        $panenTerakhir = $siklus->panen()->max('tanggal');

        return [
            'panen_terakhir' => $panenTerakhir ? Carbon::parse($panenTerakhir) : $siklus->tanggal_panen,
            'ditutup_pada' => $siklus->ditutup_pada,
            'rhpp' => $rhpp?->tanggal_terbit,
            'rhpp_ada' => $rhpp !== null,
            'tanpa_rhpp' => $siklus->status === Siklus::STATUS_DITUTUP_TANPA_RHPP,
            'rekonsiliasi_selesai' => $rhpp !== null,
            'rechick' => $siklus->tanggal_rechick,
        ];
    }

    private function angkaStd(mixed $nilai): ?float
    {
        return $nilai === null ? null : (float) $nilai;
    }

    /** "di atas standar", "di bawah standar", atau "sesuai standar" (toleransi pembulatan 0,005). */
    private function banding(float $aktual, float $std): string
    {
        return abs($aktual - $std) < 0.005 ? 'sesuai standar' : ($aktual > $std ? 'di atas standar' : 'di bawah standar');
    }
}
