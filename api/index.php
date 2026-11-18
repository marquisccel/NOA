<?php

/*
 * Titik masuk untuk Vercel (runtime PHP komunitas "vercel-php").
 * Disk Vercel hanya-baca kecuali /tmp, jadi folder tulis Laravel disiapkan di sana sebelum aplikasi dimulai.
 */

foreach (['storage/framework/views', 'storage/framework/cache', 'storage/framework/sessions', 'storage/logs'] as $folder) {
    $jalur = '/tmp/'.$folder;
    if (! is_dir($jalur)) {
        @mkdir($jalur, 0777, true);
    }
}

require __DIR__.'/../public/index.php';
