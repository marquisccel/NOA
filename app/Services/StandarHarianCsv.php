<?php

namespace App\Services;

/**
 * Membaca tabel standar harian kemitraan (F1.5) dari CSV.
 *
 * Kolom wajib: hari_ke, pakan_std_sak, bobot_std_gram, fcr_std, feed_intake_std_gram.
 * Pemisah koma atau titik koma; bila titik koma, koma desimal (format Excel Indonesia) diterima.
 */
class StandarHarianCsv
{
    public const KOLOM = ['hari_ke', 'pakan_std_sak', 'bobot_std_gram', 'fcr_std', 'feed_intake_std_gram'];

    public const MAKS_BARIS = 60;

    /**
     * @return array{rows: list<array<string, int|float>>, errors: list<string>}
     */
    public function parse(string $path): array
    {
        $isi = file_get_contents($path);
        if ($isi === false || trim($isi) === '') {
            return ['rows' => [], 'errors' => ['File kosong.']];
        }

        $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi);
        $baris = preg_split('/\r\n|\r|\n/', trim($isi));
        $pemisah = substr_count($baris[0], ';') > substr_count($baris[0], ',') ? ';' : ',';

        $header = array_map(fn ($h) => strtolower(trim($h)), str_getcsv($baris[0], $pemisah));
        $hilang = array_diff(self::KOLOM, $header);
        if ($hilang) {
            return ['rows' => [], 'errors' => ['Kolom tidak ditemukan: '.implode(', ', $hilang).'.']];
        }

        $rows = [];
        $errors = [];
        $dipakai = [];

        foreach (array_slice($baris, 1) as $i => $teks) {
            $nomor = $i + 2;
            if (trim($teks) === '') {
                continue;
            }

            $sel = str_getcsv($teks, $pemisah);
            $data = [];
            foreach (self::KOLOM as $kolom) {
                $nilai = trim($sel[array_search($kolom, $header, true)] ?? '');
                $data[$kolom] = $pemisah === ';' ? str_replace(',', '.', $nilai) : $nilai;
            }

            if (! preg_match('/^\d+$/', $data['hari_ke']) || (int) $data['hari_ke'] < 1) {
                $errors[] = "Baris {$nomor}: hari_ke harus bilangan bulat minimal 1.";

                continue;
            }
            foreach (array_slice(self::KOLOM, 1) as $kolom) {
                if (! is_numeric($data[$kolom]) || (float) $data[$kolom] < 0) {
                    $errors[] = "Baris {$nomor}: {$kolom} harus angka tidak negatif.";

                    continue 2;
                }
            }
            if (isset($dipakai[(int) $data['hari_ke']])) {
                $errors[] = "Baris {$nomor}: hari_ke {$data['hari_ke']} muncul lebih dari sekali.";

                continue;
            }

            $dipakai[(int) $data['hari_ke']] = true;
            $rows[] = [
                'hari_ke' => (int) $data['hari_ke'],
                'pakan_std_sak' => (float) $data['pakan_std_sak'],
                'bobot_std_gram' => (float) $data['bobot_std_gram'],
                'fcr_std' => (float) $data['fcr_std'],
                'feed_intake_std_gram' => (float) $data['feed_intake_std_gram'],
            ];
        }

        if (count($rows) > self::MAKS_BARIS) {
            $errors[] = 'Maksimal '.self::MAKS_BARIS.' baris.';
        }
        if (! $rows && ! $errors) {
            $errors[] = 'Tidak ada baris data.';
        }

        usort($rows, fn ($a, $b) => $a['hari_ke'] <=> $b['hari_ke']);

        return ['rows' => $rows, 'errors' => $errors];
    }
}
