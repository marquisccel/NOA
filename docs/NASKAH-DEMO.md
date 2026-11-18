# Naskah demo (±8 menit)

Tujuan: dalam delapan menit penonton paham **masalahnya**, **cara NOA membantu**, dan melihat **fitur yang berbeda dari proyek biasa**. Siapkan akun demo (tombol **Masuk sebagai peternak**) dan laptop dengan jendela peramban 1366 px.

| Menit | Layar | Yang dikatakan | Yang diperagakan |
|---|---|---|---|
| 0:00 | Halaman masuk | "Peternak plasma memelihara ayam milik inti, tetapi tidak punya catatan sendiri untuk memeriksa RHPP. NOA menyelesaikan itu." | Tekan **Masuk sebagai peternak** (tanpa mengetik sandi). |
| 0:40 | Dashboard | "Peternak langsung tahu apa yang perlu diurus." | Tunjuk **Perlu perhatian** (hari terlewat, stok tipis, paraf). Tur panduan: tekan **Tur panduan** di bilah atas, lewati 2 langkah. |
| 1:30 | Periode berjalan → tab Recording | "Catatan harian harus kurang dari satu menit." | Buka **Isi recording hari ini**. Tunjuk **Samakan dengan kemarin**, tombol **+/−**, hitung langsung deplesi dan stok. Simpan, tunjuk halaman *tersimpan* dan angka berjalan. |
| 2:30 | Recording → Isi banyak hari | "Kalau ketinggalan, tidak perlu form berulang." | Tekan **Isi 3 hari sekaligus**, isi dua baris, simpan. |
| 3:15 | Ringkasan periode | "Setiap angka bisa dijelaskan." | Tunjuk kartu **Langkah berikutnya**, tanda **?** pada FCR, lalu **Kurva harian** (bobot vs target). |
| 4:15 | Periode selesai → Laporan | "Orang awam sering tidak percaya angka tanpa rumus." | Buka **Kenapa angkanya begini?** (rumus + angka sendiri). Geser kalkulator **Bagaimana kalau?** — FCR turun → rupiah hemat. |
| 5:15 | Periode rekonsiliasi → RHPP | "Inilah inti produknya: mencocokkan catatan peternak dengan RHPP inti." | Tabel 15 baris **Sama/Berbeda**, tambah catatan di satu baris, **Cetak lembar** (A4 + tanda tangan). Tekan **Unduh rekap pembanding**. |
| 6:15 | Performa | "Peternak bisa melihat naik-turun hasil dari waktu ke waktu." | Pilih periode, sparkline di kartu, tren IP/FCR, lalu **Bandingkan dua periode**. |
| 7:00 | Peran dan keamanan | "ABK dibuat peternak dan hanya bisa mencatat." | Keluar, **Masuk sebagai ABK**: hanya satu periode, tanpa biaya; buka `/performa` → halaman 403. |
| 7:30 | Mode gelap + ponsel | "Dipakai di kandang, di ponsel, malam hari." | Ganti ke tema gelap; perkecil jendela / buka di ponsel. |
| 8:00 | Penutup | "450 tes otomatis, aturan bisnis dijaga basis data, dan seluruh dokumentasi ada di repositori." | Tampilkan README + ERD. |

## Cadangan bila ada masalah

- **Koneksi buruk:** tayangkan tangkapan layar di `docs/img/` atau rekaman layar yang disiapkan sebelumnya.
- **Data berubah:** tekan **Kembalikan data demo** di bilah atas (±10 detik).
- **Peramban kehilangan sesi:** tekan **Masuk sebagai peternak** lagi.

## Pertanyaan yang mungkin muncul

| Pertanyaan | Jawaban singkat |
|---|---|
| Mengapa tidak ada akun untuk inti? | NOA adalah catatan independen peternak untuk dibandingkan dengan RHPP. Memberi inti akses lihat melemahkan netralitas; sempat dibangun lalu sengaja ditiadakan (lihat KEPUTUSAN-DESAIN). |
| Mengapa bonus tidak dihitung? | Rumus bonus tiap inti berbeda dan tidak diketahui NOA; menebak lebih berbahaya daripada tidak menghitung. |
| Bagaimana menjaga angka tetap benar? | Nilai turunan tidak disimpan; selalu dihitung ulang dari catatan dasar. Aturan utama dijaga basis data. |
| Apa yang membedakan dari CRUD biasa? | Status periode 7 tahap dengan aturan, rekonsiliasi 17 baris, rumus yang transparan, simulasi "bagaimana kalau", dan UX yang memandu pengguna awam. |
| Bagaimana dengan sinyal lemah di kandang? | Server merender halaman ringan (Blade + Alpine), grafik hanya dimuat saat dibutuhkan; mode offline penuh adalah pengembangan lanjutan. |
