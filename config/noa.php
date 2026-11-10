<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ambang selisih wajar rekonsiliasi RHPP (F6.3)
    |--------------------------------------------------------------------------
    |
    | Selisih mutlak antara angka peternak dan RHPP yang masih dianggap wajar
    | (pembulatan, selisih timbang kecil). Di atas ambang, baris ditandai.
    |
    | PRD tidak menetapkan angkanya. Nilai di bawah ini adalah perkiraan awal,
    | bukan hasil wawancara lapangan; peternak dapat mengubahnya per siklus.
    | Satuan mengikuti kolom: kg, sak, ekor, hari, persen poin, atau tanpa satuan.
    |
    */
    'ambang_rekonsiliasi' => [
        'pakan_masuk_kg' => 25,
        'pakan_terpakai_kg' => 25,
        'pakan_retur_sak' => 0.5,
        'total_ekor' => 0,
        'total_tonase' => 5,
        'abw' => 0.005,
        'umur_panen' => 0.1,
        'deplesi_persen' => 0.01,
        'fcr' => 0.002,
        'ip' => 0.5,
    ],

];
