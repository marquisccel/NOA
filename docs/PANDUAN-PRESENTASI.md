# Panduan tanya-jawab presentasi dan penjelasan kode

Untuk mata kuliah Pengembangan Aplikasi Web Lanjut. Bagian 1 adalah pertanyaan yang kemungkinan muncul beserta jawaban singkat. Bagian 2 adalah **tur kode 7 perhentian**: kalau penguji menyuruh membuka kode, buka berkas-berkas ini berurutan. Nomor baris dari cabang `main` per 24 November 2026; bila bergeser sedikit, cari nama fungsinya.

## 1. Kemungkinan pertanyaan dan jawaban singkat

### A. Konsep dan tujuan
| Pertanyaan | Jawaban singkat |
|---|---|
| Masalah apa yang diselesaikan? | Peternak plasma tidak punya catatan sendiri untuk memeriksa RHPP inti. NOA memberi catatan harian cepat, menghitung FCR/IP otomatis, lalu mencocokkannya dengan RHPP: Sama atau Berbeda. |
| Siapa penggunanya? | Dua peran: Plasma (peternak) dan ABK (pencatat). Inti sengaja bukan pengguna agar catatan tetap netral. |
| Apa bedanya dengan aplikasi CRUD biasa? | Status periode 7 tahap dengan aturan, aturan bisnis yang dijaga basis data, rekonsiliasi 17 baris, rumus performa yang transparan, dan panduan untuk pengguna awam. |
| Mengapa bonus tidak dihitung? | Rumus bonus tiap inti berbeda dan tidak diketahui NOA. Menebak lebih berbahaya daripada tidak menghitung. |
| Sudah diuji ke peternak? | Belum. Ada uji coba pertama internal (18 Nov) yang menghasilkan perbaikan istilah dan alur. Metrik lapangan belum diukur; ini keterbatasan yang kami akui. |

### B. Arsitektur dan MVC
| Pertanyaan | Jawaban singkat |
|---|---|
| Pola arsitekturnya? | MVC Laravel: route → controller → (form request, policy, service) → model → view Blade. Logika hitung ada di `app/Services`, bukan di controller atau view. |
| Mengapa ada folder Services? | Controller dijaga tipis. Rumus ada di `PerformanceCalculator` (murni, tanpa basis data) sehingga mudah diuji dengan contoh dari PRD. |
| Mengapa Blade + Alpine, bukan React atau Livewire? | Aplikasi berbasis formulir, dipakai di ponsel dan jaringan 3G. Blade merender di server (halaman ringan), Alpine hanya untuk interaksi kecil. Livewire membutuhkan bolak-balik server tiap interaksi; SPA memberatkan unduhan. |
| Apa itu komponen Blade yang dipakai? | Komponen anonim di `resources/views/components` (`x-kpi`, `x-button`, `x-field`, `x-alert`, `x-badge`, `x-empty-state`). Dipakai ulang di semua halaman supaya tampilan konsisten. |
| Bagaimana alur satu request? | `routes/web.php` → middleware (`auth`, `peternak`) → controller → `Gate::authorize` (policy) → validasi (FormRequest) → model/service → redirect atau view. |

### C. Basis data
| Pertanyaan | Jawaban singkat |
|---|---|
| Mengapa PostgreSQL? | Mendukung CHECK constraint, indeks unik parsial, kolom turunan (generated), dan tipe desimal murni untuk FCR tiga angka. |
| Aturan apa yang dijaga basis data? | Satu catatan per hari umur (indeks unik parsial), satu periode terbuka per kandang, status hanya salah satu dari 7 (CHECK), populasi dan bobot sak harus positif, `deplesi_harian` kolom turunan dari mati + afkir. |
| Mengapa tidak pakai `enum`? | Mengubah daftar nilai enum di PostgreSQL menyulitkan migrasi. Dipakai string dengan CHECK, daftar nilainya dikunci di konstanta model. |
| Apa itu indeks unik parsial? | Indeks unik dengan syarat, contoh `UNIQUE (siklus_id, hari_ke) WHERE deleted_at IS NULL`. Data yang dihapus lunak tidak ikut memblokir entri baru. |
| Mengapa stok pakan dan FCR tidak disimpan? | Nilai turunan selalu dihitung dari catatan dasar, jadi tidak bisa basi atau tidak sinkron saat ada entri mundur. |
| Apa itu soft delete dan mengapa dipakai? | Data ditandai terhapus (`deleted_at`), tidak dibuang. Riwayat tetap ada untuk pembuktian bila ada sengketa dengan inti. |
| Relasi antar-tabel? | Kandang 1–N Siklus; Siklus 1–N Recording, Sampling, OVK, Panen; Siklus 1–1 Kontrak dan RHPP. User peternak 1–N Kandang dan 1–N ABK. Lihat `docs/ERD.md`. |

### D. Autentikasi, otorisasi, keamanan
| Pertanyaan | Jawaban singkat |
|---|---|
| Bagaimana login? | Laravel Breeze: sesi, kata sandi di-hash (bukan disimpan polos), minimal 8 karakter dengan huruf dan angka. Lupa kata sandi menjawab sama untuk email terdaftar atau tidak, agar tidak membocorkan akun. |
| Beda autentikasi dan otorisasi? | Autentikasi = siapa Anda (login). Otorisasi = apa yang boleh Anda lakukan. Otorisasi di NOA memakai Policy per sumber daya, bukan hanya cek peran. |
| Bagaimana ABK dibatasi? | Middleware `peternak` menutup halaman komersial (403). Untuk recording, `RecordingHarianPolicy` memeriksa bahwa ABK ditugaskan ke periode itu. ABK tidak bisa menghapus atau memaraf. |
| Bagaimana mencegah peternak A melihat data peternak B? | Policy memeriksa kepemilikan, contoh `KandangPolicy::view`: `kandang->user_id === user->id`. |
| Perlindungan dari serangan umum? | CSRF (token di tiap form, `@csrf`), XSS (Blade `{{ }}` otomatis di-escape), SQL injection (Eloquent memakai parameter terikat), mass assignment: data yang disimpan selalu dari `$request->validated()`, bukan seluruh input. Catatan jujur: sebagian besar model memakai `$guarded = []`, jadi disiplin memakai `validated()` itulah yang menjaganya; bisa diperketat dengan `$fillable`. |
| Bagaimana foto BTPA diamankan? | Disimpan di disk privat dengan nama acak, hanya bisa diakses lewat rute yang memeriksa otorisasi. |
| Ada celah keamanan yang diketahui? | Ya: pendaftaran publik masih terbuka dan surel belum dikonfigurasi (`MAIL_MAILER=log`). Keduanya perlu diatur sebelum dipakai peternak sungguhan. |
| Apa itu SSRF dan hubungannya dengan link Google Maps? | Link pendek Google Maps diikuti server sekali saat disimpan. `LinkPeta` hanya mengikuti domain Google dan membatasi jumlah lompatan, supaya pengguna tidak bisa menyuruh server mengakses alamat sembarang. |

### E. Validasi dan formulir
| Pertanyaan | Jawaban singkat |
|---|---|
| Di mana validasi? | `FormRequest` per fitur (`RecordingRequest`, `PanenRequest`, `RhppRequest`, ...). Aturan di `rules()`, pesan Indonesia di `lang/id`. Alpine hanya memberi umpan balik visual; angka tersimpan selalu dihitung ulang di server. |
| Mengapa validasi di server padahal ada Alpine? | Sisi klien bisa dilewati. Server adalah satu-satunya yang dipercaya. |
| Bagaimana mencegah entri ganda? | Dua lapis: aplikasi (validasi) dan basis data (indeks unik). Lapis basis data tetap menjaga bila ada dua permintaan bersamaan. |

### F. Frontend
| Pertanyaan | Jawaban singkat |
|---|---|
| Apa itu Vite dan Tailwind di sini? | Vite membangun CSS dan JS (`npm run build`). Tailwind memberi kelas utilitas; warna memakai token CSS (`bg-surface`, `text-ink`, `bg-brand`) sehingga tema terang dan gelap tinggal mengganti variabel. |
| Bagaimana mode gelap bekerja? | Atribut `data-theme="dark"` di `<html>` mengganti variabel CSS. Pilihan disimpan di `localStorage` dan mengikuti pengaturan sistem. |
| Bagaimana grafik dibuat? | Chart.js (hanya modul garis), dimuat hanya di halaman yang butuh agar halaman entri harian tetap ringan. |
| Bagaimana tampilan ponsel? | Desain responsif (navigasi bawah, drawer), isian 16 px agar tidak diperbesar otomatis, tombol minimal 44 px. |
| Apa itu maskot ayam? | Gambar render utuh; hanya bola mata yang dipisah dan digerakkan CSS/JS mengikuti kursor (`resources/js/ayam2d.js`). Bukan model 3D sungguhan. |

### G. Kinerja
| Pertanyaan | Jawaban singkat |
|---|---|
| Apa itu N+1 dan pernahkah terjadi? | Satu kueri induk lalu satu kueri per baris anak. Terjadi di halaman recording (73 kueri) dan diperbaiki jadi 13 dengan eager loading dan pengelompokan. Total 33 halaman turun dari 527 ke 367 kueri. |
| Bagaimana menjaga halaman ringan? | Render server, grafik dimuat sesuai kebutuhan, font di-host sendiri dan di-subset, logo WebP kecil, aurora tanpa filter berat di dalam aplikasi. |

### H. Pengujian
| Pertanyaan | Jawaban singkat |
|---|---|
| Bagaimana pengujiannya? | 460 tes PHPUnit terhadap PostgreSQL sungguhan: unit (rumus), basis data (CHECK, indeks), fitur (alur HTTP), otorisasi, dan tampilan (isi HTML). CI GitHub Actions menjalankannya tiap push. |
| Mengapa tidak SQLite? | Aturan penting ada di PostgreSQL (indeks parsial, CHECK, kolom turunan); SQLite tidak menguji itu. |
| Apa yang belum diuji? | Uji peramban otomatis, uji beban, dan penayangan Vercel. |
| Contoh tes yang bermakna? | ABK tidak memuat kata "kontrak" atau harga di halamannya; entri ganda ditolak; periode terkunci memberi 403; contoh PRD menghasilkan FCR 1,652 dan IP 339,2. |

### I. Penayangan
| Pertanyaan | Jawaban singkat |
|---|---|
| Bagaimana deploy? | `vercel.json` + `api/index.php` (runtime PHP komunitas), atau Docker/Render sebagai cadangan. Langkah di `docs/DEPLOY.md`. Vercel belum diuji. |
| Mengapa unggah foto bisa dimatikan? | Penyimpanan serverless tidak permanen; `NOA_UNGGAH=false` menyembunyikan kolom unggah. |
| Apa itu mode demo? | `NOA_DEMO=true` menampilkan tombol masuk satu klik (Plasma/ABK) dan tombol kembalikan data, memakai `SimulasiSeeder`. |

### J. Tim dan proses
| Pertanyaan | Jawaban singkat |
|---|---|
| Pembagian kerja? | Isi sesuai kenyataan tim (nama dan bagian masing-masing). Riwayat git: 80 commit, termasuk PR #1 dari anggota lain. |
| Apa yang berubah dari rencana awal? | Peran Pengawas dibatalkan, ambang selisih dan dampak rupiah dibuang, istilah "siklus" jadi "periode", status periode jadi 7. Alasan tiap perubahan ada di `PRD-NOA-v4.md` bagian 2. |
| Apakah memakai alat bantu AI? | **Jawab jujur sesuai aturan mata kuliah.** Bila ya, siapkan penjelasan bagian mana yang dibantu dan pastikan Anda sendiri bisa menjelaskan kodenya (lihat bagian 2). |

### K. Kelemahan dan pengembangan lanjutan
Siapkan tiga kelemahan yang Anda akui sebelum ditanya: belum diuji ke pengguna nyata, belum ada cadangan data dan surel sungguhan, dan satu rumus IP saja. Pengembangan lanjutan: mode luring, pengingat harian, riwayat perubahan, paraf digital PPL, foto nota.

## 2. Tur kode 7 perhentian (kalau disuruh buka kode)

Prinsip: jangan membaca baris demi baris. Katakan **apa tujuan berkasnya, satu aturan penting di dalamnya, dan mengapa ditaruh di situ**. Tiap perhentian cukup 30–40 detik.

### Perhentian 1 — Rute: `routes/web.php`
- Baris 31–44: grup `auth` untuk recording (peternak dan ABK); baris 46 dan seterusnya: grup `['auth', 'peternak']` untuk kontrak, kandang, panen, RHPP.
- Katakan: "Halaman komersial dikunci middleware `peternak`. Untuk recording, hak akses dijaga Policy karena bergantung pada periode mana yang ditugaskan ke ABK."
- Alias middleware `peternak` didaftarkan di `bootstrap/app.php` (blok `withMiddleware`, sekitar baris 17).
- Tunjukkan juga `php artisan route:list` (113 rute).

### Perhentian 2 — Middleware: `app/Http/Middleware/EnsurePeternak.php`
- Satu baris inti: `abort_unless($request->user()?->isPeternak(), 403);`
- Katakan: "ABK yang mencoba membuka `/performa` atau kontrak langsung mendapat 403."

### Perhentian 3 — Controller tipis: `app/Http/Controllers/RecordingController.php`, `store()` (baris 120)
Urutan di dalamnya, sebutkan satu per satu:
1. `Gate::authorize('create', ...)` — boleh mengisi periode ini?
2. `$request->validated()` — data sudah lolos `RecordingRequest`.
3. `$siklus->recordings()->create($data)` — simpan lewat relasi.
4. `catch (RecordingTerkunci ...)` — bila periode terkunci, kembali dengan pesan.
- Katakan: "Controller hanya mengatur alur. Aturan ada di Policy, Request, dan Model."

### Perhentian 4 — Policy: `app/Policies/RecordingHarianPolicy.php`
- `update()`: boleh bila punya akses, **belum diparaf (R11)**, dan **periode belum terkunci (R12)**.
- `delete()` hanya peternak; ABK hanya mengisi dan memperbaiki.
- Bandingkan dengan `KandangPolicy::view` (`$kandang->user_id === $user->id`) untuk menjelaskan isolasi data antar-peternak.

### Perhentian 5 — Model sebagai penjaga terakhir: `app/Models/RecordingHarian.php`, `booted()` (baris 28)
- Event `saving`, `updating`, `deleting` melempar `RecordingTerkunci` bila periode ditutup atau baris sudah diparaf.
- Katakan: "Walau seseorang melewati controller, misalnya lewat tinker, model tetap menolak. Aturan dijaga di beberapa lapis: tampilan, Policy, model, dan basis data."

### Perhentian 6 — Rumus murni: `app/Services/PerformanceCalculator.php`
- `deplesiPersen` (baris 30), `fcr` (41), `abw` (46), `umurPanenRataRata` (56), `indeksPerforma` (77).
- Katakan: "Kelas ini tidak menyentuh basis data, jadi bisa diuji dengan angka dari PRD." Buka `tests/Unit/PerformanceCalculatorTest.php` dan tunjukkan FCR 1,652, IP 339,2.
- Rumus IP: (daya hidup × ABW) ÷ (FCR × umur panen) × 100.

### Perhentian 7 — Basis data dan model periode
- `database/migrations/2026_11_10_000006_create_recording_harian_table.php` baris 37–40: CHECK dan indeks unik parsial `(siklus_id, hari_ke) WHERE deleted_at IS NULL`.
- Indeks "satu periode terbuka per kandang" ada di migrasi `2026_11_12_000001_ui3_status_siklus_dan_nilai_bonus`.
- `app/Models/Siklus.php`: konstanta 7 status (baris 43–56), `TERBUKA` (75) dan `TERTUTUP` (78), relasi `recordings()`, `panen()`, `rhpp()` (baris 194–214).
- Katakan: "Dua aturan terpenting dijaga basis data, jadi tetap benar walau ada bug di aplikasi atau dua permintaan bersamaan."

### Cadangan bila diminta menunjukkan fitur andalan
`app/Services/Rekonsiliasi.php`: konstanta `BARIS` (daftar baris yang dibandingkan), `hitung()` baris 50, status `SAMA`/`BERBEDA`/`TIDAK`. Docblock di atas kelas menjelaskan filosofi: selisih = RHPP − peternak, tanpa ambang dan tanpa dampak rupiah.

## 3. Latihan sebelum presentasi

1. Jalankan `php artisan serve`, masuk sebagai peternak, isi satu catatan harian sambil membuka tab Network atau mengikuti kode: route → controller → policy → request → model → redirect.
2. Coba tembus aturannya sendiri: buka `/performa` sebagai ABK (403), ubah baris yang sudah diparaf (ditolak), isi hari yang sama dua kali (diarahkan ke entri yang ada).
3. Latih tur 7 perhentian dengan pengatur waktu; targetnya di bawah 5 menit.
4. Hafalkan tiga angka: **460 tes**, **7 status**, **FCR 1,652 / IP 339,2** pada contoh 3.000 ekor.
5. Siapkan jawaban jujur untuk tiga kelemahan di bagian K.
6. Jalankan `php artisan test --filter=PerformanceCalculatorTest` di depan penguji bila diminta bukti.

## 4. Bila ditanya sesuatu yang tidak Anda ketahui

Jangan mengarang. Katakan apa yang Anda tahu ("Di sini controller memanggil service itu, saya belum membaca detail rumusnya"), lalu tunjukkan di mana jawabannya berada (berkas atau tes). Penguji menilai pemahaman dan kejujuran, bukan hafalan.
