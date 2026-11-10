# NOA — Progress

Sumber: *NOA — Product Requirements Document v3* (rilis bertahap M1–M6, ±1 minggu per tahap).
Setiap fase dikonfirmasi ruang lingkupnya sebelum dikerjakan. Fase selesai bila fungsinya bisa
diperagakan dari antarmuka, rumus terkait lulus uji otomatis, dan tidak ada galat di alur utama.

| Fase | PRD | Lingkup | Status |
|------|-----|---------|--------|
| 1 | M1 | Fondasi: Laravel 13 + Breeze, PostgreSQL (Docker), skema, model, relasi, seeder siklus contoh, kalkulator performa + uji | Selesai |
| 2 | M2 | Kandang, siklus, kontrak (F1.1–F1.9, F8.1): CRUD, skema bonus dapat dipilih, unggah standar CSV | Selesai |
| 3 | M3 | Recording harian (F2.x): entri < 1 menit, angka berjalan, validasi R1/R5 | Belum |
| 4 | M4 | Sampling bobot dan OVK (F3.x, F4.x): ABW, ADG, peringatan sampel < 5% | Belum |
| 5 | M5 | Panen bertahap dan performa (F5.x): FCR, IP, umur panen berbobot, selisih populasi | Belum |
| 6 | M6 | Rekonsiliasi RHPP, laporan cetak, otorisasi ABK (F6.x, F7.x, F8.2) | Belum |

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

## Menjalankan

```bash
docker compose up -d
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
```

Akun contoh (seeder): `peternak@noa.test` dan `abk@noa.test`, kata sandi `password`.
Uji memakai database `noa_test` (buat sekali: `docker exec noa-pgsql psql -U noa -d postgres -c "CREATE DATABASE noa_test OWNER noa;"`).
