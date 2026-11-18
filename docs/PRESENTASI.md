# Kerangka presentasi (12 slide, ±10 menit + demo)

Saran: slide sedikit teks, banyak gambar (gunakan `docs/img/`). Waktu tiap slide ±40 detik.

| # | Judul slide | Isi | Gambar |
|---|---|---|---|
| 1 | **NOA — Nitip, Openi, Adil** | Nama proyek, tim, mata kuliah | Logo NOA |
| 2 | **Masalah** | Plasma memelihara ayam inti, tidak punya catatan sendiri untuk memeriksa RHPP; angka sulit dinegosiasikan | Ilustrasi alur inti → plasma → RHPP |
| 3 | **Solusi** | Catatan harian cepat → performa otomatis → cocokkan dengan RHPP → cetak untuk musyawarah | `docs/img/06-rekonsiliasi-rhpp.jpg` |
| 4 | **Pengguna** | Plasma (pemilik), ABK (pencatat). Alasan inti tidak jadi pengguna: netralitas catatan | Diagram dua peran |
| 5 | **Alur periode** | 7 status, 4 terbuka + 3 terkunci | Diagram `ALUR-SIKLUS.md` |
| 6 | **Catatan harian < 1 menit** | 3 isian, Samakan kemarin, +/−, isi banyak hari, ponsel | `11-input-ponsel.jpg`, `04-recording.jpg` |
| 7 | **Mudah dipahami awam** | Tanda tanya istilah, Bantuan, Langkah berikutnya, Perlu perhatian, tur, rumus dengan angka sendiri | `01-dashboard.jpg`, `09-bantuan.jpg` |
| 8 | **Insight** | Performa, kurva vs target, tren, bandingkan periode, "Bagaimana kalau?" | `02-performa.jpg`, `05-kurva-harian.jpg`, `07-simulasi-bagaimana-kalau.jpg` |
| 9 | **Arsitektur** | Laravel 13 + PostgreSQL; Blade + Alpine + Tailwind; Policy; layanan perhitungan | ERD dari `ERD.md` |
| 10 | **Kualitas** | Aturan bisnis R1–R13 dijaga basis data; 451 tes; CI; data simulasi | Tabel `PENGUJIAN.md` |
| 11 | **Tantangan dan keputusan** | Menolak role inti, tidak menghitung bonus, nilai turunan tidak disimpan, Vercel + PHP | `KEPUTUSAN-DESAIN.md` |
| 12 | **Penutup dan demo** | Ringkasan, pengembangan lanjutan (offline, pengingat, paraf digital PPL), tautan repositori + demo langsung | QR ke alamat demo |

## Pengembangan lanjutan (untuk slide penutup)

1. Mode offline dan pengingat harian (WhatsApp atau notifikasi peramban)
2. Paraf digital PPL dan riwayat perubahan catatan sebagai bukti
3. Foto nota pakan, DOC, dan OVK
4. Dashboard inti yang bersifat **opt-in** dengan hanya metrik operasional

## Pembagian peran penyaji (contoh untuk kelompok)

| Penyaji | Bagian |
|---|---|
| 1 | Slide 1–4 (masalah dan pengguna) |
| 2 | Slide 5–8 (fitur) + demo |
| 3 | Slide 9–12 (arsitektur, kualitas, penutup) |
