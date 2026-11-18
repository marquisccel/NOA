# Keputusan desain

Dokumen ini menjelaskan **mengapa** aplikasi dibuat seperti ini, supaya mudah dijelaskan saat presentasi.

## Pengguna dan prinsip utama

Pengguna utama: **peternak plasma** (sering di ponsel, sinyal 3G, tangan kotor, bukan orang IT). Prinsip:

1. **Catatan harian < 1 menit.** Hanya 3 isian wajib; sisanya opsional dan terlipat.
2. **Bahasa sehari-hari.** Istilah teknis tetap dipakai (DOC, FCR, IP, RHPP, BTPA) karena itu bahasa peternak, tetapi selalu ada tanda tanya "?" berisi arti, contoh, dan cara membacanya.
3. **Selalu tahu langkah berikutnya.** Pengguna baru jarang tahu harus mulai dari mana.
4. **Bisa diperiksa.** Peternak tidak percaya angka yang tidak bisa dijelaskan, jadi setiap angka punya rumus dengan angka miliknya sendiri.
5. **NOA tidak menghakimi.** Rekonsiliasi hanya menunjukkan Sama/Berbeda; tidak ada "ambang wajar" atau klaim siapa yang benar.

## Keputusan UI/UX

| Keputusan | Alasan |
|---|---|
| Isian teks 16 px, tombol sentuh ≥ 44 px, tombol Simpan menempel di ponsel | Syarat keterbacaan dan kemudahan di kandang; mencegah zoom otomatis iOS. |
| **Samakan dengan kemarin**, tombol +/−, **isi banyak hari sekaligus** | Angka harian biasanya mirip; mengejar catatan di buku jangan harus membuka form berulang. |
| Kartu **Langkah berikutnya** per status periode | Alur periode 7 status terlalu panjang untuk dihafal; sistem yang menunjukkan. |
| **Mulai di sini** (checklist 6 langkah) dan **tur panduan** 3–4 langkah | Orientasi pertama kali tanpa membaca manual; tur bisa diulang dari Bantuan. |
| **Perlu perhatian** di Dashboard | Satu tempat untuk hal mendesak lintas periode (hari terlewat, stok tipis, paraf). |
| Warna **selalu disertai kata** (badge "Berbeda", "Sama") | Aksesibilitas: tidak bergantung pada warna saja. |
| Tema terang dan **gelap** dengan token CSS | Nyaman dipakai malam hari; mengikuti pengaturan sistem; pilihan diingat. |
| Notifikasi **toast** yang hilang sendiri + bilah kemajuan + tombol sibuk | Umpan balik jelas pada koneksi lambat. |
| Grafik hanya dimuat di halaman yang butuh (Chart.js ±160 KB) | Halaman entri harian tetap ringan untuk 3G (target < 500 KB). |
| Topbar hanya judul + jam; menu di sidebar | Mengurangi kebisingan; nama menu aktif cukup sebagai petunjuk lokasi. |
| Kalkulator **Bagaimana kalau?** | Mengubah angka abstrak (FCR 1,60 → 1,55) menjadi rupiah yang bisa dibayangkan. |
| Halaman masuk dengan **hero kanvas interaktif** (medan aliran partikel, kartu kaca 3D, grafik contoh) dan logo SVG baru | Kesan pertama profesional saat demo; kanvas ringan (±45 fps, berhenti saat tab tersembunyi, mati pada gerak minimal) dan hanya dimuat di halaman tamu. |
| Istilah awam: **Periode** (bukan siklus), **Kandang terbuka/tertutup** (bukan open/closed house) | Pengguna uji tidak paham istilah teknis. Nama di kode tetap `siklus`; hanya tampilan yang diganti. |
| Menu: Dashboard → Kandang → Aktivitas; tanpa foto/inisial profil di daftar | Urutan mengikuti seberapa sering dipakai; avatar tidak memberi informasi. |
| Tabel dan formulir memakai token warna (tidak ada warna terang tertanam) | Mode gelap konsisten di semua halaman, termasuk isian otomatis peramban. |
| **Perlu perhatian** berupa daftar baris yang bisa diketuk, bukan kartu | Lebih tenang dibaca; satu baris = satu hal, satu ketukan = satu aksi. |

## Keputusan teknis

| Keputusan | Alasan |
|---|---|
| Nilai turunan tidak disimpan, selalu dihitung dari catatan dasar | Tidak bisa basi atau tidak sinkron; mudah diuji. |
| `PerformanceCalculator` sebagai kelas murni tanpa akses basis data | Rumus diuji terpisah dengan contoh dari PRD. |
| Aturan penting di basis data (CHECK, indeks unik parsial, kolom turunan) | Tetap benar walau ada bug di aplikasi atau permintaan bersamaan. |
| Otorisasi dengan **Policy**, bukan hanya middleware peran | Hak akses bergantung pada sumber daya (periode milik siapa, ABK ditugaskan ke mana). |
| Blade + Alpine.js (tanpa SPA) | Halaman cepat, mudah di-cache, tidak butuh API terpisah; cocok untuk 3G. |
| Tes fitur per aturan bisnis (R1–R13) | Setiap aturan punya bukti otomatis. |
| `SimulasiSeeder` dengan benih acak tetap | Demo selalu sama dan semua status/kasus tepi terlihat. |

## Hal yang sengaja ditolak

- **Role "Inti"/Pengawas dengan akses penuh.** NOA adalah catatan independen peternak untuk dibandingkan dengan RHPP; memberi inti akses lihat melemahkan netralitas. Sempat dibangun (Fase 6) lalu ditiadakan.
- **Dashboard memakai Odoo.** ERP berat dan butuh server terpisah; tidak sebanding untuk pengguna ponsel.
- **Estimasi bonus.** Rumus bonus tiap inti berbeda; menebak lebih berbahaya daripada tidak menghitung.
