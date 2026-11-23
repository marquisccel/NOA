<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Mengolah link Google Maps yang ditempel peternak menjadi kueri peta sematan (koordinat atau nama tempat).
 *
 * Link pendek dari tombol Bagikan (maps.app.goo.gl) tidak bisa disematkan, jadi diikuti sekali saat disimpan.
 * Yang diikuti hanya alamat milik Google, tidak lebih dari beberapa lompatan, sehingga pengguna tidak bisa
 * menyuruh server mengakses alamat sembarang.
 */
class LinkPeta
{
    private const BATAS_LOMPATAN = 5;

    /** Host pendek Google Maps; sisanya harus domain google.* */
    private const HOST_PENDEK = ['maps.app.goo.gl', 'goo.gl', 'g.co'];

    /** Apakah ini alamat milik Google Maps (dipakai untuk validasi dan untuk membatasi lompatan). */
    public static function hostGoogle(?string $url): bool
    {
        $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));

        if ($host === '' || ! str_starts_with((string) $url, 'https://')) {
            return false;
        }

        if (in_array($host, self::HOST_PENDEK, true)) {
            return true;
        }

        return (bool) preg_match('/(^|\.)google\.(com|co\.id)$/', $host);
    }

    /** Kueri untuk peta sematan: "lat,lng" atau nama tempat; null bila tidak dapat ditentukan. */
    public function kueri(?string $link): ?string
    {
        $link = trim((string) $link);

        if ($link === '' || ! self::hostGoogle($link)) {
            return null;
        }

        $langsung = $this->dariUrl($link);
        $host = strtolower((string) parse_url($link, PHP_URL_HOST));

        // Hanya link pendek yang perlu diikuti; link panjang tanpa koordinat tidak diambil dari luar.
        if ($langsung !== null || ! in_array($host, self::HOST_PENDEK, true)) {
            return $langsung;
        }

        return $this->dariUrl($this->ikuti($link));
    }

    /** Koordinat atau nama tempat yang tertulis di URL, bila ada. */
    public function dariUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $url = rawurldecode($url);
        $angka = '(-?\d{1,3}\.\d+)';

        // Urutan: penanda tempat (!3d!4d), parameter q/ll, pusat tampilan (@), lalu nama tempat.
        foreach (["/!3d{$angka}!4d{$angka}/", "/[?&](?:q|query|ll|destination)={$angka},\s*{$angka}/", "/@{$angka},{$angka}/"] as $pola) {
            if (preg_match($pola, $url, $m)) {
                return $m[1].','.$m[2];
            }
        }

        if (preg_match('#/maps/place/([^/@?]+)#', $url, $m)) {
            $nama = trim(str_replace('+', ' ', $m[1]));

            return $nama !== '' ? Str::limit($nama, 480, '') : null;
        }

        return null;
    }

    /** Mengikuti pengalihan link pendek, hanya ke alamat Google, hingga URL panjang ditemukan. */
    private function ikuti(string $url): ?string
    {
        try {
            for ($i = 0; $i < self::BATAS_LOMPATAN; $i++) {
                $respons = Http::timeout(5)->withoutRedirecting()
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; NOA)'])
                    ->get($url);

                $tujuan = $respons->header('Location');

                if (! $respons->redirect() || ! $tujuan) {
                    return $respons->successful() ? $url : null;
                }

                // Halaman persetujuan cookie Google membawa alamat tujuan di parameter "continue".
                parse_str((string) parse_url($tujuan, PHP_URL_QUERY), $param);
                if (str_contains((string) parse_url($tujuan, PHP_URL_HOST), 'consent.google.') && isset($param['continue'])) {
                    $tujuan = $param['continue'];
                }

                if (! self::hostGoogle($tujuan)) {
                    return null;
                }

                if ($this->dariUrl($tujuan)) {
                    return $tujuan;
                }

                $url = $tujuan;
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }
}
