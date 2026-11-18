<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mode demo
    |--------------------------------------------------------------------------
    |
    | Bila aktif, halaman masuk menampilkan tombol "Coba demo" (masuk sebagai Plasma atau ABK contoh
    | tanpa mengetik sandi), banner mode demo, dan tombol untuk mengembalikan data contoh.
    | Akun demo memakai data dari SimulasiSeeder.
    |
    */
    'demo' => (bool) env('NOA_DEMO', false),

    'akun_demo' => [
        'plasma' => 'simulasi@noa.test',
        'abk' => 'abk.simulasi@noa.test',
    ],

    /*
    |--------------------------------------------------------------------------
    | Unggah berkas (foto BTPA dan lampiran RHPP)
    |--------------------------------------------------------------------------
    |
    | Penyimpanan serverless (misalnya Vercel) tidak menyimpan berkas secara permanen, jadi unggahan
    | dapat dinonaktifkan. Bila nonaktif, kolom unggah disembunyikan dan berkas yang terkirim diabaikan.
    |
    */
    'unggah' => (bool) env('NOA_UNGGAH', true),

];
