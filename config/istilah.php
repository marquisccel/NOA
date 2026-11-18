<?php

/*
|--------------------------------------------------------------------------
| Daftar istilah (bahasa sehari-hari)
|--------------------------------------------------------------------------
|
| Dipakai oleh tanda tanya "?" di samping istilah teknis dan oleh halaman Bantuan.
| Tiap istilah: nama lengkap, arti singkat, dan (opsional) cara membacanya.
|
*/

return [
    'doc' => ['DOC', 'Day Old Chick', 'Anak ayam umur satu hari yang dikirim inti ke kandang Anda. Tanggal DOC masuk dihitung sebagai hari ke-1.', null],
    'populasi' => ['Jumlah ayam awal', 'Populasi awal', 'Banyaknya DOC yang masuk ke kandang pada awal periode.', null],
    'deplesi' => ['Deplesi', 'Ayam mati + ayam afkir', 'Jumlah ayam yang hilang selama pemeliharaan, dihitung dari ayam mati ditambah ayam afkir, dibagi jumlah ayam awal.', 'Makin kecil makin baik. Dibandingkan dengan target dari inti.'],
    'afkir' => ['Ayam afkir', 'Ayam yang dibuang', 'Ayam yang sengaja dikeluarkan dari kandang karena sakit, cacat, atau kerdil. Berbeda dengan ayam mati.', null],
    'fcr' => ['FCR', 'Konversi pakan', 'Berapa kilogram pakan yang dihabiskan untuk menghasilkan 1 kilogram daging ayam.', 'Makin kecil makin baik. Contoh: FCR 1,6 berarti 1,6 kg pakan menjadi 1 kg ayam.'],
    'ip' => ['IP', 'Indeks Performa', 'Nilai gabungan yang menilai hasil periode dari daya hidup, bobot, FCR, dan umur panen.', 'Makin besar makin baik.'],
    'abw' => ['ABW', 'Bobot rata-rata per ekor', 'Bobot satu ekor ayam rata-rata, dari total bobot dibagi jumlah ekor.', null],
    'adg' => ['ADG', 'Pertambahan bobot per hari', 'Berapa gram ayam bertambah berat setiap hari, dihitung dari dua kali penimbangan.', null],
    'sampling' => ['Timbang sampel', 'Sampling bobot', 'Menimbang sebagian ayam (minimal 5% dari yang hidup) untuk memperkirakan bobot rata-rata seluruh ayam.', 'Lakukan sekitar seminggu sekali.'],
    'standar' => ['Standar / target harian', 'Tabel standar dari inti', 'Target bobot, pakan, dan deplesi tiap hari yang ditetapkan inti untuk jenis ayam Anda. Dipakai sebagai pembanding catatan harian.', 'Angka oranye berarti melewati target.'],
    'stok' => ['Stok pakan', 'Pakan di gudang', 'Pakan yang masuk dikurangi pakan yang sudah terpakai, dalam sak.', 'Bila negatif, biasanya ada kiriman pakan yang belum dicatat.'],
    'ovk' => ['OVK', 'Obat, Vaksin, dan Kimia', 'Obat, vaksin, vitamin, dan bahan kimia (misalnya desinfektan) yang diberikan ke ayam.', null],
    'btpa' => ['BTPA', 'Berita acara timbang panen', 'Nota timbang yang diterbitkan inti saat ayam dipanen. Nomornya dicatat sebagai bukti.', null],
    'rhpp' => ['RHPP', 'Rekap hasil pemeliharaan dan panen', 'Laporan akhir dari inti berisi hasil periode dan perhitungan biaya serta pendapatan Anda. Terbit beberapa hari setelah panen terakhir.', null],
    'rekonsiliasi' => ['Rekonsiliasi', 'Mencocokkan catatan dengan RHPP', 'Membandingkan angka catatan Anda dengan angka RHPP dari inti, baris demi baris, untuk melihat mana yang sama dan mana yang berbeda.', 'NOA tidak memutuskan siapa yang benar; hanya menunjukkan selisihnya.'],
    'paraf' => ['Paraf PPL', 'Tanda tangan petugas inti', 'Tanda bahwa petugas inti (PPL) sudah menyaksikan angka pada hari itu. Baris yang sudah diparaf dikunci supaya tetap sah sebagai bukti.', null],
    'ppl' => ['PPL', 'Petugas Penyuluh Lapangan', 'Petugas dari inti yang mengunjungi dan mengawasi kandang.', null],
    'selisih_populasi' => ['Selisih jumlah ayam', 'Ayam belum tercatat', 'Jumlah ayam awal dikurangi (ayam panen + ayam mati + ayam afkir). Idealnya nol. Bila tidak nol, ada ayam yang belum tercatat.', 'NOA tidak menyeimbangkan angka sendiri; periksa catatan harian dan panen.'],
    'rechick' => ['Ayam masuk berikutnya', 'Re-chick-in', 'Rencana tanggal DOC berikutnya masuk ke kandang yang sama.', null],
    'siklus' => ['Periode', 'Satu putaran pemeliharaan', 'Satu kali memelihara ayam: dari anak ayam (DOC) masuk sampai panen dan laporan RHPP dari inti. Satu kandang hanya punya satu periode yang sedang berjalan.', null],
];
