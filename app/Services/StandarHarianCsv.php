<?php

namespace App\Services;

/**
 * Membaca tabel standar harian kemitraan (F1.5) dari CSV. Dua format diterima:
 *
 *  - Format PRD: hari_ke, pakan_std_sak, bobot_std_gram, fcr_std, feed_intake_std_gram (deplesi_std_persen opsional).
 *  - Format wizard: hari, bobot_std_gram, feed_intake_std_gram, deplesi_std_persen.
 *
 * Kolom yang tidak ada (pakan_std_sak, fcr_std) diturunkan dari feed intake kumulatif dan bobot
 * lewat lengkapi(), karena tabel standar tersimpan lengkap per siklus.
 * Pemisah koma atau titik koma; bila titik koma, koma desimal (format Excel Indonesia) diterima.
 */
class StandarHarianCsv
{
    /** Kolom format PRD (urutan juga dipakai untuk templat). */
    public const KOLOM = ['hari_ke', 'pakan_std_sak', 'bobot_std_gram', 'fcr_std', 'feed_intake_std_gram'];

    /** Kolom minimum yang wajib ada di kedua format. */
    private const WAJIB = ['bobot_std_gram', 'feed_intake_std_gram'];

    private const OPSIONAL = ['pakan_std_sak', 'fcr_std', 'deplesi_std_persen'];

    public const MAKS_BARIS = 60;

    /**
     * @return array{rows: list<array<string, int|float|null>>, errors: list<string>, jumlah_baris: int}
     */
    public function parse(string $path): array
    {
        $isi = file_get_contents($path);
        if ($isi === false || trim($isi) === '') {
            return ['rows' => [], 'errors' => ['File kosong.'], 'jumlah_baris' => 0];
        }

        $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi);
        $baris = preg_split('/\r\n|\r|\n/', trim($isi));
        $pemisah = substr_count($baris[0], ';') > substr_count($baris[0], ',') ? ';' : ',';

        $header = array_map(fn ($h) => strtolower(trim($h)), str_getcsv($baris[0], $pemisah));
        $header = array_map(fn ($h) => $h === 'hari' ? 'hari_ke' : $h, $header);

        $hilang = array_diff(['hari_ke', ...self::WAJIB], $header);
        if ($hilang) {
            return ['rows' => [], 'errors' => ['Kolom tidak ditemukan: '.implode(', ', $hilang).'.'], 'jumlah_baris' => 0];
        }

        $rows = [];
        $errors = [];
        $dipakai = [];
        $jumlah = 0;

        foreach (array_slice($baris, 1) as $i => $teks) {
            $nomor = $i + 2;
            if (trim($teks) === '') {
                continue;
            }
            $jumlah++;

            $sel = str_getcsv($teks, $pemisah);
            $ambil = function (string $kolom) use ($sel, $header, $pemisah): ?string {
                $idx = array_search($kolom, $header, true);
                if ($idx === false) {
                    return null;
                }
                $nilai = trim($sel[$idx] ?? '');

                return $pemisah === ';' ? str_replace(',', '.', $nilai) : $nilai;
            };

            $hari = $ambil('hari_ke');
            if (! preg_match('/^\d+$/', (string) $hari) || (int) $hari < 1) {
                $errors[] = "Baris {$nomor}: hari_ke harus bilangan bulat minimal 1.";

                continue;
            }

            $data = ['hari_ke' => (int) $hari];
            $salah = false;
            foreach ([...self::WAJIB, ...self::OPSIONAL] as $kolom) {
                $nilai = $ambil($kolom);
                $wajib = in_array($kolom, self::WAJIB, true);

                if ($nilai === null || $nilai === '') {
                    if ($wajib) {
                        $errors[] = "Baris {$nomor}: {$kolom} kosong.";
                        $salah = true;
                    }
                    $data[$kolom] = null;

                    continue;
                }
                if (! is_numeric($nilai) || (float) $nilai < 0) {
                    $errors[] = "Baris {$nomor}: {$kolom} berisi “{$nilai}” — harus angka.";
                    $salah = true;

                    continue;
                }
                $data[$kolom] = (float) $nilai;
            }

            if ($salah) {
                continue;
            }
            if (isset($dipakai[$data['hari_ke']])) {
                $errors[] = "Baris {$nomor}: hari_ke {$data['hari_ke']} muncul lebih dari sekali.";

                continue;
            }

            $dipakai[$data['hari_ke']] = true;
            $rows[] = $data;
        }

        if ($jumlah > self::MAKS_BARIS) {
            $errors[] = 'Maksimal '.self::MAKS_BARIS.' baris.';
        }
        if (! $rows && ! $errors) {
            $errors[] = 'Tidak ada baris data.';
        }

        usort($rows, fn ($a, $b) => $a['hari_ke'] <=> $b['hari_ke']);

        return ['rows' => $rows, 'errors' => $errors, 'jumlah_baris' => $jumlah];
    }

    /**
     * Mengisi kolom turunan agar baris siap disimpan: pakan kumulatif (sak) dari feed intake x populasi,
     * FCR standar dari feed intake kumulatif / bobot standar.
     *
     * @param  list<array<string, int|float|null>>  $rows
     * @return list<array<string, int|float|null>>
     */
    public function lengkapi(array $rows, int $populasi, float $bobotSakKg): array
    {
        return array_map(function (array $r) use ($populasi, $bobotSakKg) {
            $r['pakan_std_sak'] ??= $bobotSakKg > 0 ? round($r['feed_intake_std_gram'] * $populasi / 1000 / $bobotSakKg, 2) : 0.0;
            $r['fcr_std'] ??= $r['bobot_std_gram'] > 0 ? round($r['feed_intake_std_gram'] / $r['bobot_std_gram'], 3) : 0.0;

            return $r;
        }, $rows);
    }
}
