<?php

namespace App\Exceptions;

use DomainException;

/** Recording tidak boleh diubah: baris sudah diparaf PPL (R11) atau siklus sudah selesai (R12). */
class RecordingTerkunci extends DomainException
{
    public static function diparaf(): self
    {
        return new self('Baris ini sudah diparaf PPL dan tidak dapat disunting.');
    }

    public static function siklusSelesai(): self
    {
        return new self('Siklus sudah selesai; recording dikunci dari penyuntingan.');
    }
}
