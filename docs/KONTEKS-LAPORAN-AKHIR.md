# Konteks untuk laporan akhir NOA

Dokumen kerja untuk menulis laporan akhir. Isinya fakta dan bahan yang bisa diambil langsung, bukan laporan jadi. Semua angka diambil dari repositori pada 23 November 2026 (cabang `main`, setelah PR #1). Sesuaikan struktur dengan format laporan dari dosen.

## 1. Gambaran proyek dalam satu paragraf

NOA ("Nitip, Openi, Adil") adalah aplikasi web untuk peternak plasma ayam broiler. Peternak mencatat kandang setiap hari (kurang dari satu menit), sistem menghitung FCR, deplesi, ABW, IP, dan biaya per kg secara otomatis, lalu angka itu dicocokkan baris demi baris dengan RHPP yang diterbitkan perusahaan inti: mana yang **Sama**, mana yang **Berbeda**, lengkap dengan keterangan dan lembar cetak untuk musyawarah. Aplikasi dibangun dengan Laravel 13, PostgreSQL 17, Blade, Alpine.js, dan Tailwind CSS sebagai proyek mata kuliah Pengembangan Aplikasi Web Lanjut, dan dinilai lewat demo dan presentasi.

Masalah yang dijawab: peternak plasma tidak punya catatan sendiri yang rapi, sehingga angka RHPP (yang menentukan bonus) tidak bisa diperiksa atau dinegosiasikan.

## 2. Angka siap kutip

| Hal | Nilai | Sumber |
|---|---|---|
| Tes otomatis | 460 lulus (PHPUnit, PostgreSQL sungguhan) | `php artisan test`, PR #1 |
| Berkas tes | 22 berkas fitur (+ tes autentikasi) dan 1 berkas unit | `tests/` |
| Rute | 113 | `php artisan route:list` |
| Migrasi | 19 | `database/migrations` |
| Model / pengendali / kebijakan / layanan | 12 / 17 (+ autentikasi) / 7 / 12 | `app/` |
| Baris kode aplikasi (PHP, JS, CSS, Blade, basis data) | ±14.900 | hitung `app`, `resources`, `database` |
| Baris kode tes | ±6.300 | `tests/` |
| Peran | 2 (Plasma, ABK) | `users.role` |
| Status periode | 7 | `docs/ALUR-SIKLUS.md` |
| Aturan bisnis | R1–R13 (`docs/ATURAN-BISNIS.md`) dan R14 (PRD v4, link peta) | dokumen |
| Baris rekonsiliasi RHPP | 17 (15 dibandingkan) | `Rekonsiliasi` |
| Data simulasi | 4 kandang, 14 periode, ±14 bulan, 546 recording, 42 tahap panen | `SimulasiSeeder` |
| Contoh hitung | 3.000 DOC, deplesi 3,20%, ABW 1,980 kg, FCR 1,652, umur panen 34,2 hari, IP 339,2 | `PerformanceCalculatorTest` |
| Kinerja (PR #1) | recording 73 → 13 kueri; total 33 halaman 527 → 367 kueri | deskripsi PR #1 |
| Commit | 80 (77 Ega Yurcel Satriaji, 3 nandzakyy) | `git log` |
| Masa pengembangan | 10–23 November 2026 | `git log` |

Catatan: jumlah tes lama di dokumen lain tertulis 451; yang benar sekarang 460.

## 3. Saran struktur laporan dan bahan tiap bab

Struktur umum laporan proyek perangkat lunak. Ganti urutan atau nama bab bila dosen memberi format sendiri.

### Bab 1 — Pendahuluan
- **Latar belakang**: kemitraan inti-plasma, angka dipegang inti, peternak tanpa alat pembanding. Contoh studi di PRD: selisih deplesi 1,43%, FCR 0,016, IP 20 poin pada 10.000 ekor.
- **Rumusan masalah**: bagaimana peternak plasma mencatat dengan cepat dan membuktikan selisih catatannya dengan RHPP.
- **Tujuan dan manfaat**: lihat PRD bagian 4.
- **Batasan**: broiler saja; tidak menghitung bonus; tidak ada akses inti; belum diuji pada peternak nyata.
- Sumber: `docs/PRD-NOA-v4.md` bagian 3–6.

### Bab 2 — Analisis kebutuhan
- **Pengguna**: Plasma dan ABK. Jelaskan mengapa Pengawas inti dibatalkan (netralitas catatan).
- **Kebutuhan fungsional**: tabel F1–F10 di PRD v4 beserta status.
- **Kebutuhan non-fungsional**: tabel bagian 11 PRD v4 (waktu entri, teks 16 px, sentuh 44 px, presisi FCR).
- **Aturan bisnis**: R1–R13 di `docs/ATURAN-BISNIS.md`; R14 (link peta hanya domain Google) ada di PRD v4.
- Sertakan **tabel perubahan dari PRD v3** (PRD v4 bagian 2). Ini bukti proses: kebutuhan berevolusi dan keputusannya beralasan.

### Bab 3 — Perancangan
- **Arsitektur**: monolit Laravel (Blade di sisi server, Alpine untuk interaksi kecil), PostgreSQL, layanan perhitungan terpisah (`PerformanceCalculator`, `Rekonsiliasi`, `RingkasanPerforma`). Alasan tidak memakai Livewire, Inertia, pustaka PDF: PRD v4 bagian 10.
- **ERD**: `docs/ERD.md` (Mermaid). Soroti keputusan basis data: CHECK pengganti enum, indeks unik parsial, kolom turunan, nilai turunan tidak disimpan, soft delete.
- **Diagram status periode**: `docs/ALUR-SIKLUS.md`.
- **Otorisasi**: kebijakan (Policy) per sumber daya; ABK dibatasi ke satu periode.
- **Desain antarmuka**: token desain (terang/gelap), komponen, shell per peran, alasan di `docs/KEPUTUSAN-DESAIN.md`. Desain mengacu berkas tim `NOA_V.01.pen` (117 layar, 14 bagian; berkas mentah tidak ikut git).

### Bab 4 — Implementasi
Ceritakan per tahap, dengan hasil yang bisa diperagakan:

| Tahap | Isi | Catatan |
|---|---|---|
| M1 | Skema, model, seeder, kalkulator performa | Contoh PRD diuji; ditemukan angka 420 sak yang tidak konsisten, dikoreksi jadi 190 sak |
| M2–M4 | Kandang, periode, kontrak, standar CSV, recording, sampling, OVK | Entri harian < 1 menit, angka berjalan |
| M5–M6 | Panen + BTPA, performa, rekonsiliasi, laporan, ABK | Foto BTPA privat, lembar cetak A4 |
| UI 1–5 | Token desain, shell, dashboard, wizard periode, recording, panen, RHPP, laporan | UI 6 (Pengawas) dibangun lalu dibatalkan |
| 18 Nov | Perbaikan setelah uji coba pertama | Istilah awam, Aktivitas, Bantuan, Langkah berikutnya |
| 18 Nov | Fase 0–D (demo, visual, logika mudah dipahami, dokumentasi) | Mode demo, tur, kurva, rumus transparan |
| 18–20 Nov | Halaman masuk dan maskot ayam, aliran "kaca" ke seluruh aplikasi | Lihat bagian 5 |
| 21–23 Nov | PR #1: optimasi kinerja, link Google Maps, penyegaran UI | Dikerjakan anggota lain |

Tangkapan layar: `docs/img/` (lihat peringatan di bagian 7, beberapa sudah usang).

### Bab 5 — Pengujian
- Strategi lima lapis (unit rumus, basis data, alur fitur, otorisasi, tampilan): `docs/PENGUJIAN.md`.
- Hasil: 460 tes lulus; tes dijalankan terhadap PostgreSQL asli sehingga CHECK dan indeks ikut diuji.
- Contoh uji penting: ABK tidak memuat kata "kontrak" atau harga; entri ganda ditolak di aplikasi dan indeks; periode terkunci memberi 403; selisih jumlah ayam tidak dipaksa seimbang.
- CI GitHub Actions menjalankan migrasi, build, dan seluruh tes tiap push.
- Pengujian manual: desktop 1366, tablet 1024, ponsel 375; tema terang dan gelap; pemeriksaan HTML untuk aksesibilitas.
- **Keterbatasan pengujian** (tulis jujur): belum ada uji peramban otomatis, belum ada uji beban, dan penayangan Vercel belum diuji.

### Bab 6 — Penutup
- **Kesimpulan**: semua kebutuhan wajib (M) PRD v3 terpenuhi atau diubah dengan alasan; dua kebutuhan sengaja dibatalkan (F6.5 dampak rupiah, F8.3 Pengawas).
- **Saran / pengembangan lanjutan**: PRD v4 Lampiran A (cadangan data, surel, pengingat harian, mode luring, riwayat perubahan, paraf digital PPL, foto nota).

## 4. Keputusan penting dan alasannya (bahan pembahasan)

| Keputusan | Alasan | Bukti di repo |
|---|---|---|
| Menolak peran inti/Pengawas | Inti dapat melihat catatan = catatan tandingan kehilangan netralitas. Sempat dibangun lalu dibuang | `PROGRESS.md` "UI Fase 6", migrasi `hapus_peran_pengawas` |
| Tidak menghitung bonus dan dampak rupiah | Rumus bonus tiap inti berbeda; menebak lebih berbahaya daripada tidak menghitung | `docs/ATURAN-BISNIS.md` |
| "Sama/Berbeda" tanpa ambang "wajar" | NOA tidak menilai siapa benar; ambang awal hanya perkiraan tanpa wawancara | `PROGRESS.md` UI Fase 5 |
| Nilai turunan tidak disimpan | Tidak bisa basi atau tidak sinkron; mudah diuji | `docs/ERD.md` |
| Aturan penting di basis data | Tetap benar walau ada bug di aplikasi atau permintaan bersamaan | CHECK, indeks unik parsial |
| Siklus → "Periode" di tampilan | Uji coba: istilah teknis tidak dipahami awam; kode tetap `siklus` | `PROGRESS.md` perbaikan 18 Nov |
| Standar disalin per periode | Perbandingan periode lama tetap sahih bila standar berubah | PRD |
| Blade + Alpine, bukan SPA | Halaman ringan untuk jaringan 3G; tanpa API terpisah | `docs/KEPUTUSAN-DESAIN.md` |
| CSS print, bukan pustaka PDF | Cukup untuk lembar A4; tanpa dependensi | `docs/KEPUTUSAN-DESAIN.md` |
| Kalkulator "Bagaimana kalau?" disembunyikan | Keputusan tim di PR #1; kode tetap ada | `components/simulasi.blade.php` |
| Hapus tipe kandang, tambah link Google Maps | Tipe tidak dipakai perhitungan; peta mengikuti link | PR #1 |

## 5. Tantangan yang bisa diceritakan

1. **Angka contoh PRD tidak konsisten.** Contoh menulis 420 sak (21.000 kg) tetapi FCR 1,652; ternyata FCR dan IP cocok dengan 190 sak. Ditemukan saat menulis uji, lalu dikoreksi. Contoh baik untuk "pengujian menemukan kesalahan dokumen".
2. **Kebutuhan berubah di tengah jalan.** Pengawas inti dibangun lalu dibatalkan; ambang selisih dan dampak rupiah dibuat lalu dibuang; tampilan "kaca" diterapkan lalu disederhanakan demi kinerja. Setiap perubahan tercatat di `PROGRESS.md` beserta alasannya.
3. **Kinerja.** N+1 kueri di halaman recording (73 kueri) dipangkas jadi 13; total kueri 33 halaman turun 30% tanpa mengubah HTML hasil (PR #1).
4. **Maskot ayam di halaman masuk.** Awalnya model 3D (three.js, ±550 KB), lalu pemuat model GLB, lalu dibuang karena berat dan hasilnya kaku. Solusi akhir: satu gambar render utuh, hanya bola mata yang dipisah dan bergerak di dalam wajah, kelopak berupa kulit kepala yang turun, gerak mengikuti kursor dengan pegas. Ekor dan jengger sengaja tidak dipisah karena pemisahan memunculkan sambungan yang terlihat. Pelajaran: mulai dari hasil yang diinginkan, pisahkan hanya yang perlu bergerak. Catatan: maskot di dashboard dihapus di PR #1; kini hanya di halaman masuk.
5. **Penayangan di Vercel.** PHP tidak didukung resmi di Vercel, jadi dipakai runtime komunitas; berkas dan langkahnya ada, tetapi belum diuji. Render/Docker disiapkan sebagai cadangan. Penyimpanan serverless tidak permanen, sehingga unggah foto bisa dimatikan (`NOA_UNGGAH`).
6. **Bahasa untuk orang awam.** Istilah teknis tetap dipakai (itu bahasa peternak) tetapi diberi tanda tanya penjelas, halaman Bantuan, dan panel rumus dengan angka milik pengguna.

## 6. Kelebihan dan keterbatasan (untuk bagian evaluasi)

**Kelebihan**
- Fokus jelas: verifikasi RHPP, bukan sekadar aplikasi catat.
- Aturan bisnis dijaga di basis data dan kode, bukan hanya tampilan; setiap aturan punya tes.
- Perhitungan transparan (rumus dengan angka milik sendiri).
- Cakupan lengkap dari kandang sampai penutupan periode, dengan data simulasi untuk demo.

**Keterbatasan** (tulis apa adanya)
- Belum diuji pada peternak sungguhan; metrik lapangan di PRD v3 belum diukur.
- Bonus tidak dihitung; hanya selisih angka yang ditampilkan.
- Satu rumus IP; rumus kemitraan lain belum ada.
- Belum ada mode luring, pengingat, cadangan data otomatis, surel sungguhan; pendaftaran publik masih terbuka.
- Vercel belum diuji; tidak ada uji peramban otomatis dan uji beban.

## 7. Yang perlu diselesaikan tim sebelum dikumpulkan

Tenggat laporan dan demo: Senin 23 November 2026, 23.59.

1. **Naskah demo usang.** `docs/NASKAH-DEMO.md` menit 4:15 menyuruh menggeser kalkulator "Bagaimana kalau?", padahal sekarang disembunyikan. Ganti langkahnya atau tampilkan lagi komponennya (hapus atribut `hidden` di `resources/views/components/simulasi.blade.php`).
2. **Angka tes tertulis 451** di README, `PENGUJIAN.md`, `PRESENTASI.md`, dan `NASKAH-DEMO.md`; sekarang 460.
3. **Tangkapan layar lama.** `docs/img/*.jpg` dibuat sebelum perubahan tampilan (dashboard berganti rupa, maskot, grafik batang dihapus). Ambil ulang sebelum dimasukkan ke laporan.
4. **README bagian "Tim"** masih berisi "isi nama anggota di sini".
5. **ERD** (`docs/ERD.md`) masih memuat `tipe` kandang dan belum memuat `link_gmaps`.
6. **Vercel belum diuji.** Uji penayangan atau siapkan Render/Docker atau rekaman layar sebagai cadangan demo.
7. Cantumkan di laporan **pembagian tugas** anggota, **tautan repositori**, dan **tautan demo**.
8. Periksa aturan mata kuliah soal **penggunaan alat bantu AI** (apakah harus dinyatakan di laporan) dan sesuaikan.

## 8. Daftar bahan visual yang sudah ada

| Bahan | Lokasi |
|---|---|
| Diagram ERD (Mermaid) | `docs/ERD.md` |
| Diagram status periode dan alur kerja | `docs/ALUR-SIKLUS.md` |
| Tangkapan layar (perlu diperbarui) | `docs/img/00`–`11` |
| Tabel aturan dan rumus | `docs/ATURAN-BISNIS.md` |
| Kerangka 12 slide presentasi | `docs/PRESENTASI.md` |
| Skenario demo ±8 menit | `docs/NASKAH-DEMO.md` (perlu diperbarui, lihat poin 1) |
| PRD terbaru | `docs/PRD-NOA-v4.md` dan `.docx` |

## 9. Cara memakai dokumen ini

- Mulai dari `docs/PRD-NOA-v4.md` untuk bab analisis kebutuhan; tabel perubahannya adalah ringkasan proses terbaik.
- Ambil angka dari bagian 2, keputusan dari bagian 4, dan cerita dari bagian 5.
- Tulis keterbatasan (bagian 6) dengan jujur; penguji biasanya menilai tinggi kejelasan batas klaim.
- Untuk setiap klaim fitur, siapkan satu bukti: tangkapan layar, nama tes, atau nama berkas.
