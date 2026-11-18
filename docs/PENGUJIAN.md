# Pengujian

**449 tes otomatis** (PHPUnit) dijalankan terhadap PostgreSQL sungguhan (bukan SQLite), jadi batasan `CHECK`, indeks unik parsial, dan kolom turunan ikut teruji.

```bash
php artisan test              # seluruh tes
php artisan test --filter=RekonsiliasiRhppTest
```

Pipeline **GitHub Actions** (`.github/workflows/tests.yml`) menjalankan migrasi, build aset, dan seluruh tes pada setiap push.

## Strategi

| Lapisan | Yang diuji | Contoh |
|---|---|---|
| **Unit (rumus)** | `PerformanceCalculator` dengan contoh dari PRD | FCR 1,652; IP; deplesi; umur panen berbobot |
| **Basis data** | Aturan yang dijaga PostgreSQL | satu periode terbuka per kandang, status di luar 7 ditolak, satu catatan per hari |
| **Fitur (alur)** | Alur lengkap lewat HTTP | wizard periode → catat → panen → RHPP → tutup |
| **Otorisasi** | Setiap halaman sensitif untuk peran yang salah | ABK dan peternak lain mendapat 403; tamu diarahkan ke login |
| **Tampilan** | Teks, tautan, dan struktur kunci di HTML | tab periode, banner demo, toast, tur, grafik hanya di halaman yang butuh |
| **Data simulasi** | Seeder konsisten dan aman diulang | semua status terwakili, satu recording per hari, panen ≤ populasi |

## Rincian per berkas

| Berkas | Jumlah tes | Fokus |
|---|---|---|
| `UiFase2Test` | 41 | Dashboard, kandang, ABK per periode, pengaturan |
| `UiFase3Test` | 40 | 7 status, wizard periode, ringkasan, pengaturan periode |
| `UiFondasiTest` | 34 | Token desain, komponen, shell, autentikasi |
| `RecordingHarianTest` | 33 | Entri harian, R1, R5, R11, R12 |
| `PanenPerformaTest` | 29 | Panen bertahap, FCR, IP, selisih jumlah ayam, foto BTPA |
| `SamplingOvkTest` | 27 | ABW, ADG, sampel < 5%, biaya OVK |
| `UiFase4Test` | 26 | Riwayat dan form recording, sampling, OVK |
| `UiFase5Test` | 25 | Panen, alur penutupan, hentikan dini, halaman galat |
| `RekonsiliasiRhppTest` | 20 | 17 baris perbandingan, catatan, cetak |
| `KandangSiklusTest`, `AkunAbkTest` | 20 + 20 | Kandang, kontrak, akun ABK, otorisasi R13 |
| `PerformanceCalculatorTest` | 15 | Rumus murni |
| `PerformaUnduhTest` | 15 | Performa per periode, unduhan CSV |
| `FaseCLogikaTest` | 14 | Rumus transparan, kalkulator, input cepat, isi banyak hari |
| `AktivitasBantuanTest` | 14 | Aktivitas, Bantuan, istilah, langkah berikutnya, jam |
| `FaseAPenayanganDemoTest` | 13 | Berkas penayangan, mode demo, Perlu perhatian, toast, tur |
| `LaporanSiklusTest` | 12 | Biaya, grafik, cetak, CSV |
| `FaseBVisualTest` | 8 | Grafik garis, sparkline, bandingkan periode, tema gelap |
| `UiPutaran2Test` | 7 | Urutan menu, logo, kartu demo, istilah awam, token warna mode gelap |
| `SimulasiSeederTest`, `SchemaIntegrityTest`, `ProfileTest` | 7 + 7 + 4 | Data simulasi, skema, profil |

## Pengujian manual yang dilakukan

- Peramban desktop (1366 px), tablet (1024 px), dan ponsel (375 px) untuk layar utama
- Tema terang dan gelap
- Alur demo lengkap (lihat [NASKAH-DEMO.md](NASKAH-DEMO.md))
- Pembacaan HTML untuk atribut aksesibilitas: label isian, `aria-live`, `role`, urutan judul

## Keterbatasan yang diketahui

- Belum ada uji otomatis di peramban (end-to-end); tampilan diuji lewat HTML dan pemeriksaan manual.
- Belum ada uji beban; skala tugas kuliah (satu peternak, belasan periode).
- Penayangan di Vercel menggunakan runtime PHP komunitas dan belum diuji otomatis; lihat [DEPLOY.md](DEPLOY.md).
