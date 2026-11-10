<?php

namespace App\Support;

/** Format tampilan angka gaya Indonesia. Pembulatan hanya di sini, tidak saat menyimpan. */
class Format
{
    public static function angka(null|int|float|string $nilai, int $desimal = 2): string
    {
        return $nilai === null || $nilai === '' ? '-' : number_format((float) $nilai, $desimal, ',', '.');
    }
}
