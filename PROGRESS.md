# NOA — Progress

Sumber: *NOA — Product Requirements Document v3* (rilis bertahap M1–M6, ±1 minggu per tahap).
Setiap fase dikonfirmasi ruang lingkupnya sebelum dikerjakan. Fase selesai bila fungsinya bisa
diperagakan dari antarmuka, rumus terkait lulus uji otomatis, dan tidak ada galat di alur utama.

| Fase | PRD | Lingkup | Status |
|------|-----|---------|--------|
| 1 | M1 | Fondasi: Laravel 13 + Breeze, PostgreSQL (Docker), skema, model, relasi, seeder siklus contoh, kalkulator performa + uji | Selesai |
| 2 | M2 | Kandang, siklus, kontrak (F1.1–F1.9, F8.1): CRUD, skema bonus dapat dipilih, unggah standar CSV | Selesai |
| 3 | M3 | Recording harian (F2.x): entri < 1 menit, angka berjalan, validasi R1/R5 | Selesai |
| 4 | M4 | Sampling bobot dan OVK (F3.x, F4.x): ABW, ADG, peringatan sampel < 5% | Selesai |
| 5 | M5 | Panen bertahap dan performa (F5.x): FCR, IP, umur panen berbobot, selisih populasi | Belum |
| 6 | M6 | Rekonsiliasi RHPP, laporan cetak, otorisasi ABK (F6.x, F7.x, F8.2) | Selesai |

## Antarmuka (UI) — mengikuti `NOA_V.01.pen`

Fase 1–6 di bawah menyelesaikan fungsi dengan tampilan bawaan Breeze. Tahap UI menerapkan desain tim
(117 layar, 14 section) di atas fungsi itu. Berkas desain mentah ada di `design/` (tidak ikut git).

| UI | Lingkup | Status |
|----|---------|--------|
| 1 | Token, font, komponen dasar, shell (sidebar, topbar, navigasi bawah), autentikasi | Selesai |
| 2 | Dashboard, Kandang, Akun dan ABK, Pengaturan profil | Selesai |
| 3 | Siklus: wizard inisialisasi, ringkasan, tab, status siklus 7 tahap | Selesai |
| 4 | Recording (desktop dan ponsel), Sampling, OVK | Belum |
| 5 | Panen, RHPP dan rekonsiliasi, laporan dan penutupan, halaman global dan galat | Belum |
| 6 | Peran Pengawas (baca-saja) dan akses per siklus | Belum |

### UI Fase 1 — Fondasi dan autentikasi (selesai)

- [x] Token desain (warna terang dan gelap, status, border, radius) sebagai variabel CSS dan nama semantik Tailwind (`bg-surface`, `text-ink`, `bg-brand`, `border-line`, `bg-success-soft`, ...)
- [x] Font Space Grotesk dan Manrope dihosting sendiri (subset latin, ±47 KB), tanpa layanan font pihak ketiga
- [x] Logo dioptimalkan dari PNG sumber 1 MB menjadi WebP 8 KB (tanda), 7 KB (wordmark), 52 KB (panel merek)
- [x] Ikon Lucide sebagai peta PHP kecil (`resources/icons/lucide.php`) dan komponen `<x-icon>`
- [x] Komponen: `x-button` (primary, secondary, ghost, danger), `x-field` (ikon, tampilkan kata sandi, galat, bantuan), `x-alert`, `x-badge`, `x-logo`, serta komponen Breeze lama yang digayakan ulang
- [x] Shell: topbar dengan remah roti dan judul, sidebar per peran (penuh mulai 1280 px, ikon saja di tablet atau bila diciutkan dan diingat), drawer dan navigasi bawah di ponsel
- [x] Menu per peran di `App\Support\Navigasi` (Peternak: Dashboard, Kandang dan Siklus, Pengguna dan Akses, Pengaturan; ABK: Siklus Ditugaskan); hanya halaman yang sudah ada
- [x] Autentikasi: masuk (galat, memproses, ponsel), lupa kata sandi dan "Cek email Anda", atur ulang dan "Kata sandi diperbarui", registrasi peternak, konfirmasi kata sandi, verifikasi email
- [x] Terjemahan Indonesia untuk validasi, auth, dan passwords (`lang/id`), awalan huruf kapital
- [x] Lupa kata sandi menjawab sama untuk email terdaftar atau tidak (tidak membocorkan akun)
- [x] Syarat kata sandi: minimal 8 karakter, memuat huruf dan angka (ditegakkan server; daftar syarat di layar mengikuti)
- [x] Halaman Fase 1–6 tetap berfungsi di dalam shell baru; palet indigo lama dipetakan ke hijau merek sampai tiap halaman disesuaikan
- [x] Uji: 35 baru, total 253 lulus; diperiksa di browser desktop 1440, tablet 1024, dan ponsel 390

Penyimpangan dari desain (disengaja, perlu persetujuan):
- Teks isian 16 px (desain 14 px) karena PRD mewajibkan minimal 16 px; tinggi isian tetap 46 px
- Panel merek login memakai Manrope untuk teks (desain memakai Inter) agar tidak membawa font ketiga
- Tombol notifikasi di topbar tidak dibuat (tidak ada dasar di PRD; audit desain juga menyarankan menyembunyikannya)
- Item sidebar "Laporan" belum ada sampai halaman Laporan global dibuat (UI Fase 5)
- Tema gelap baru berupa token; belum ada pengalih dan halaman lama belum disesuaikan
- Syarat "mengandung huruf besar" pada layar atur ulang desain tidak dipakai (audit menyebutnya contoh yang belum dikonfirmasi)

## Fase 1 — Fondasi (selesai)

- [x] Laravel 13 + Breeze (Blade, Alpine, Tailwind), locale `id`, zona waktu `Asia/Jakarta`
- [x] PostgreSQL 17 via `docker-compose.yml` (data di `.docker/`, tidak di-commit)
- [x] Migrasi: users.role, kandang, siklus, standar_harian, parameter_umur, jadwal_vaksin,
      kontrak_siklus, recording_harian, sampling_bobot, pemberian_ovk, panen, rhpp
- [x] Aturan di level DB: CHECK constraint (tanpa enum), partial unique index R1 dan R2,
      generated column `deplesi_harian` (R3), soft delete, nominal `decimal`
- [x] Model Eloquent + relasi + konstanta daftar nilai
- [x] `App\Services\PerformanceCalculator` (deplesi, liveability, FCR, ABW, umur panen berbobot, IP,
      feed intake, sampling, ADG, stok gudang, selisih populasi)
- [x] Seeder `SiklusContohSeeder`: siklus 3.000 DOC dari contoh PRD (FCR 1,652, IP 339,2)
- [x] Uji: 14 unit (rumus) + 7 feature (integritas skema)

### Catatan temuan PRD

- Contoh perhitungan PRD menulis total pakan 420 sak (21.000 kg) tetapi FCR 1,652 dan IP 339,2.
  21.000 / 5.750 = 3,652, jadi tidak konsisten. FCR 1,652 dan IP 339,2 cocok dengan ±9.500 kg
  (190 sak), dan itu yang dipakai seeder dan uji. Perlu dikonfirmasi ke penulis PRD.
- Rumus IP harus berupa pengaturan, bukan konstanta (risiko di PRD); ditunda ke fase 5/6.

## Fase 2 — Kandang, siklus, kontrak (selesai)

- [x] F1.1 CRUD kandang (soft delete; tidak bisa dihapus bila ada siklus berjalan)
- [x] F1.2 Buka siklus: tanggal masuk, populasi, strain DOC, jenis pakan, PPL, inti, bobot sak
- [x] F1.3 Kontrak siklus: harga DOC, pakan per sak, OVK, harga kontrak ayam
- [x] F1.4 Skema bonus (FCR, deplesi, IP, harga pasar) dapat diaktifkan terpisah
- [x] F1.5 Unggah tabel standar harian per siklus (CSV, koma/titik koma, semua-atau-tidak-sama-sekali)
- [x] F1.6 Parameter manajemen per rentang umur (suhu, kepadatan, frekuensi pakan, TMA, TRA)
- [x] F1.7 Jadwal vaksin per hari umur
- [x] F1.8 Beberapa siklus bersamaan di kandang berbeda (R2 divalidasi dengan pesan jelas)
- [x] F1.9 Tandai siklus selesai atau dihentikan, tanggal panen dan re-chick in
- [x] F8.1 Login Breeze; middleware `peternak` + policy: ABK dan peternak lain tidak bisa mengakses
- [x] Uji: 23 feature (kandang, siklus, kontrak, CSV, parameter, otorisasi)

Catatan: penguncian recording untuk siklus selesai (R12) diterapkan di Fase 3 saat recording dibuat.

## Fase 3 — Recording harian (selesai)

- [x] F2.1–F2.3 pakan masuk, terpakai, sisa fisik; stok gudang diturunkan dari akumulasi (R5), bukan kolom
- [x] F2.4–F2.5 ayam mati dan afkir; sebab afkir wajib bila ada afkir
- [x] F2.6 nilai standar di bawah nilai aktual (pakan kumulatif, bobot, FCR, feed intake)
- [x] F2.7–F2.8 feed intake kumulatif per ekor, deplesi kumulatif, FCR berjalan; ditampilkan setelah simpan
- [x] F2.9 suhu pagi/sore dibandingkan rentang target umur (ditulis sebagai kata, bukan hanya warna)
- [x] F2.10 OVK pagi dan sore terpisah
- [x] F2.11 / R11 paraf PPL + tanggal; baris diparaf terkunci di kebijakan dan di model
- [x] F2.12 subtotal mingguan pakan dan deplesi
- [x] F2.13 entri ganda pada hari umur sama ditolak (aplikasi dan indeks unik DB)
- [x] F2.14 pakan terpakai melebihi stok: peringatan, bukan penghalang
- [x] F2.15 entri mundur; hari terlewat ditampilkan dengan tautan "Isi sekarang"
- [x] R12 siklus selesai dikunci dari entri baru dan penyuntingan (paraf tetap boleh); penjaga di model
- [x] Form 4 bidang wajib, isian lain dalam "Isian lain"; umpan balik Alpine (deplesi, stok) hanya petunjuk, angka tersimpan dihitung server
- [x] Uji: 33 feature (aturan R1/R5/R11/R12, angka berjalan, subtotal, otorisasi), total 100 lulus

Keputusan yang perlu dikonfirmasi:
- Siklus berstatus *dihentikan* tidak ikut terkunci (R12 PRD hanya menyebut *selesai*).
- FCR berjalan memakai bobot aktual hari itu dan hanya tampil pada hari yang bobotnya dicatat.
- Akses ABK ke entri harian menunggu Fase 6 (otorisasi ABK); sementara hanya peternak.

## Fase 4 — Sampling bobot dan OVK (selesai)

- [x] F3.1 catat sampling: tanggal, jumlah ekor ditimbang, total bobot (kg); ubah dan hapus (soft delete)
- [x] F3.2 ABW = total bobot / jumlah sampel
- [x] F3.3 / R8 sampel < 5% ayam hidup pada hari itu: peringatan dan penanda "kurang representatif", tetap tersimpan
- [x] F3.4 pertambahan bobot harian (ADG) terhadap sampling sebelumnya
- [x] F3.5 ABW dibandingkan dengan standar pada umur sama (selisih gram dan persen); kosong bila standar belum diunggah
- [x] F4.1 catat obat, vaksin, atau kimia: tanggal, jenis, produk, dosis, biaya
- [x] F4.2 biaya OVK dijumlahkan per jenis dan total (masuk biaya produksi di laporan Fase 6)
- [x] F4.3 riwayat OVK satu siklus dalam satu halaman
- [x] ABW sampling dipakai sebagai bobot untuk FCR berjalan pada hari sampling yang sama (bobot aktual recording didahulukan)
- [x] Validasi: sampel tidak boleh melebihi ayam hidup; bobot rata-rata > 10 kg ditolak (salah satuan)
- [x] Uji: 28 baru (27 feature + 1 unit), total 128 lulus

Keputusan yang perlu dikonfirmasi:
- Sampling dan OVK tidak dikunci saat siklus selesai (R12 PRD hanya menyebut recording).
- Sampling dua kali pada hari yang sama diizinkan; ADG antar keduanya kosong.
- ABW sampling hanya dipinjam untuk FCR berjalan pada hari yang sama, tidak dibawa ke hari lain agar tidak basi.

## Fase 5 — Panen bertahap dan performa (selesai)

- [x] F5.1–F5.2 tahap panen: tanggal (umur hari dihitung otomatis), jumlah ekor, tonase, harga per kg per tahap, nomor BTPA
- [x] F5.3 foto BTPA: JPG/PNG/WebP maks 4 MB, disimpan di disk privat dengan nama acak, hanya lewat rute terautentikasi dan terotorisasi
- [x] F5.4 total ekor dan tonase; ABW panen
- [x] F5.5 / R9 umur panen rata-rata berbobot jumlah ekor tiap tahap
- [x] F5.6 / R7 selisih populasi (panen + deplesi vs populasi awal) ditandai, tidak dipaksa seimbang; peringatan bila melebihi populasi
- [x] R10 FCR dan IP kosong sebelum ada panen; FCR 3 desimal, deplesi 2 desimal
- [x] IP memakai rumus per siklus (`siklus.rumus_ip`, registri di `PerformanceCalculator::RUMUS_IP`; baru ada `standar`)
- [x] Pakan sisa/retur akhir siklus sebagai pengurang pakan terpakai (kolom `siklus.pakan_retur_sak`)
- [x] Pendapatan dijumlahkan per tahap (harga berbeda tiap tahap); tahap tanpa harga/BTPA ditandai
- [x] Siklus dihentikan dini: dihitung dari data yang ada, ditandai "tidak sebanding"
- [x] Menutup siklus tanpa panen atau dengan selisih populasi memunculkan peringatan
- [x] Uji: 32 baru, total 160 lulus; siklus contoh seeder menghasilkan FCR 1,652, IP 339,2, ABW 1,98, umur 34,2

Keputusan yang perlu dikonfirmasi:
- Pakan sisa/retur mengurangi pakan terpakai harian (sesuai kasus khusus PRD). Bila catatan harian sudah tidak menghitung pakan tersisa, nilainya harus 0 agar tidak dihitung dua kali; seeder memakai 0.
- Rumus IP baru satu (standar PRD). Mekanisme pilihan per siklus sudah ada; rumus inti lain ditambahkan saat rumusnya diketahui dari kontrak.
- Panen tidak dikunci saat siklus selesai (sejalan dengan sampling dan OVK).
- Nomor BTPA dan harga per kg boleh kosong saat simpan, tetapi ditandai di daftar.
- Foto BTPA lama dihapus dari disk saat diganti atau dikosongkan; saat tahap panen dihapus (soft delete), berkas dipertahankan.

## Fase 6 — Rekonsiliasi RHPP, laporan, akses ABK (selesai)

- [x] F6.1 input angka RHPP apa adanya (satu per siklus; kolom boleh kosong; hapus = soft delete, simpan ulang memulihkan)
- [x] F6.2 perbandingan baris demi baris: versi peternak, versi RHPP, selisih (RHPP − peternak); selisih nol tampil sebagai nol
- [x] Baris: pakan masuk (tagihan), pakan terpakai, retur, total ekor, tonase, ABW, umur panen, deplesi, FCR, IP; pendapatan sebagai baris informatif
- [x] F6.3 ambang selisih wajar per metrik (bawaan di `config/noa.php`, dapat ditimpa per siklus); baris di atas ambang ditandai dengan kata
- [x] F6.4 lembar perbandingan cetak A4 (CSS print, tanpa pustaka PDF) dengan kolom tanda tangan peternak dan inti
- [x] F6.5 dampak rupiah dari kontrak untuk tagihan pakan, kredit retur, dan tonase; bonus FCR/deplesi/IP hanya ditandai
- [x] F7.1 laporan siklus: deplesi, FCR, ABW, umur panen, IP, biaya produksi (DOC + pakan tertagih + OVK), pendapatan, margin kasar
- [x] F7.2 grafik bobot (aktual vs standar) dan deplesi kumulatif dengan Chart.js (hanya modul garis; dimuat khusus halaman laporan)
- [x] F7.3 perbandingan performa antar-siklus di kandang yang sama; dihentikan/belum panen ditandai
- [x] F7.4 laporan siklus bisa dicetak satu halaman (print CSS, navigasi disembunyikan)
- [x] F8.2 / R13 akun ABK dibuat peternak (peran dan pemilik ditentukan server); ABK mengisi dan memperbaiki recording harian, tidak bisa menghapus entri atau memberi paraf; ditolak di kontrak, biaya, panen, sampling, rekonsiliasi, laporan
- [x] Uji: 58 baru, total 218 lulus (termasuk uji bahwa halaman ABK tidak memuat harga atau kata kontrak)

F8.3 (pengawas inti baca-saja) berstatus C di PRD dan tidak dikerjakan.

Keputusan yang perlu dikonfirmasi:
- Angka ambang selisih wajar bawaan adalah perkiraan saya, bukan hasil wawancara. Ganti sesuai kebiasaan inti di lapangan.
- Tafsir RHPP: `total_pakan_kg` = tagihan sapronak (semua pakan dikirim), terpakai = tagihan − retur. Seeder disesuaikan (9.750 kg, retur 5 sak).
- Dampak rupiah bonus FCR, deplesi, dan IP tidak dihitung karena tabel bonus inti tidak ada di model data; hanya ditandai.
- ABK terikat ke seluruh kandang milik satu peternak (bukan per kandang).
- Biaya produksi = DOC + pakan tertagih + OVK tercatat; belum termasuk listrik, tenaga kerja, sekam.
- Pakan versi peternak untuk "retur" diturunkan dari pakan masuk dikurangi pakan terpakai bersih.

## Menjalankan

```bash
docker compose up -d
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
```

Akun contoh (seeder): `peternak@noa.test` dan `abk@noa.test` (ABK milik peternak itu), kata sandi `password`.
Peternak membuat akun ABK baru di menu ABK.
Uji memakai database `noa_test` (buat sekali: `docker exec noa-pgsql psql -U noa -d postgres -c "CREATE DATABASE noa_test OWNER noa;"`).

### UI Fase 2 — Dashboard, Kandang, Akun dan ABK, Pengaturan (selesai)

- [x] Dashboard peternak: empat kartu angka (kandang, kandang aktif, siklus berjalan, populasi aktif), siklus aktif, aktivitas terbaru lintas recording, sampling, OVK, panen; keadaan kosong dan tanpa siklus; sapaan menurut jam
- [x] Dashboard ABK: hanya siklus yang ditugaskan, tanpa data komersial (R13)
- [x] Nomor siklus per kandang ("Siklus 7") dihitung dari urutan tanggal masuk, tidak disimpan
- [x] Kandang: daftar (cari nama atau alamat, filter status siklus dan tipe, tabel dan kartu ponsel), tambah dan ubah (alamat dan tipe wajib, pilihan Open atau Closed House), detail (siklus saat ini atau kosong), riwayat siklus dengan filter status, dialog "Buka siklus baru"
- [x] Pengguna dan Akses: ABK ditugaskan ke satu siklus berjalan, nonaktif dan aktif lagi (nonaktif memblokir masuk, memutus sesi, mencabut penugasan), atur ulang kata sandi (sandi acak tampil sekali), panel hak akses, halaman 403
- [x] Pengaturan: profil dan ubah kata sandi dengan daftar syarat; hapus akun sendiri dihapus (tidak ada di desain, akan menghapus semua data peternak)
- [x] Migrasi: `users.aktif`, `users.siklus_id` (hanya ABK), `kandang.tipe` dibatasi `open_house` atau `closed_house`
- [x] Seeder siklus berjalan (umur 17 hari) di kandang kedua dan ABK contoh ditugaskan ke sana
- [x] Uji: 41 baru, total 292 lulus; dicek di browser desktop dan ponsel

Perubahan perilaku yang perlu dikonfirmasi:
- ABK lama kehilangan akses recording sampai ditugaskan ke siklus (sebelumnya semua kandang peternak)
- Akun ABK tidak bisa dihapus lagi, hanya dinonaktifkan (sesuai desain)
- Tipe kandang lama yang tidak dikenal dikosongkan oleh migrasi dan harus dipilih ulang saat kandang diubah
- Label status siklus di antarmuka: berjalan = "Berjalan", selesai = "Ditutup", dihentikan = "Dihentikan" (7 status desain menunggu UI Fase 3)

### UI Fase 3 — Siklus: wizard, ringkasan, tab, status 7 tahap (selesai)

- [x] Status siklus 7 tahap: Berjalan, Panen selesai, Menunggu RHPP, Rekonsiliasi, Ditutup, Ditutup tanpa RHPP, Dihentikan (badge per status). Empat pertama dianggap terbuka dan menahan kandang (satu siklus terbuka per kandang, dijaga indeks unik parsial); tiga terakhir mengunci data (R12)
- [x] Wizard "Buka Siklus Baru" 5 langkah (Identitas & DOC, Kontrak, Skema bonus, Standar harian, Konfirmasi): draf di sesi per kandang, tidak bisa melompati langkah, validasi sesuai pesan desain (kapasitas kandang, bobot sak > 0, nilai bonus wajib bila aktif), layar sukses dan layar gagal bila kandang masih punya siklus terbuka
- [x] Unggah standar di wizard: format desain (`hari, bobot_std_gram, feed_intake_std_gram, deplesi_std_persen`) dan format PRD sama-sama diterima; pratinjau, daftar baris bermasalah, pakan kumulatif dan FCR standar diturunkan dari populasi dan bobot sak
- [x] Ringkasan Siklus: header siklus dan delapan tab di semua halaman siklus, KPI (populasi, deplesi vs standar, FCR berjalan, feed intake, stok, ABW), grafik bobot dan deplesi, daftar "Perlu perhatian" (recording terlewat, standar belum ada, pakan masuk belum dicatat, stok tipis, baris belum diparaf), angka final dan penutupan untuk siklus tertutup
- [x] Pengaturan Siklus: kartu identitas, kontrak, bonus, standar; terkunci bila siklus tertutup; ubah dan akhiri siklus; tanggal penutupan tercatat
- [x] Migrasi: status baru, indeks unik parsial baru, `siklus.ditutup_pada`, `kontrak_siklus.nilai_bonus_*`, `standar_harian.deplesi_std_persen`
- [x] Uji: 41 baru, total 333 lulus; dicek di browser desktop dan ponsel

Perubahan perilaku yang perlu dikonfirmasi:
- Siklus Dihentikan sekarang ikut terkunci untuk recording (PRD R12 hanya menyebut selesai; desain mengunci semua status tertutup)
- Nilai bonus hanya disimpan; perhitungan rupiah bonus belum ada
- Ambang dan dampak rupiah di rekonsiliasi (Fase 6) bertentangan dengan desain ("Berbeda" saja); akan dibuang di UI Fase 5
- Ukuran teks formulir 16 px (desain 14 px) mengikuti PRD
