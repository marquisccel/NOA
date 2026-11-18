# Penayangan (deploy)

Ada dua jalur. Untuk tugas kuliah, **pilih satu**, dan **uji sebelum hari presentasi**.

| | Vercel (jalur A) | Render + Docker (jalur B, cadangan) |
|---|---|---|
| Dukungan PHP | Runtime komunitas `vercel-php` (bukan resmi) | Docker biasa, PHP 8.3 + Apache |
| Basis data | **Neon** (PostgreSQL gratis, di luar Vercel) | PostgreSQL gratis bawaan Render |
| Unggah berkas | Tidak tersimpan (disk sementara) → dinonaktifkan | Juga dinonaktifkan di mode demo |
| Sesi | Cookie terenkripsi (tanpa tabel) | Basis data |
| Risiko | Cold start, batas waktu fungsi, runtime komunitas | Layanan gratis "tidur" setelah tidak dipakai (bangun ±30 detik) |

> Kedua jalur memakai **mode demo** (`NOA_DEMO=true`): halaman masuk menampilkan tombol "Coba demo". Data simulasi dibuat otomatis oleh Render saat start pertama; di Vercel dibuat lewat perintah di langkah 4 (atau otomatis saat tombol demo pertama ditekan, ±10 detik).

## Jalur A — Vercel + Neon

1. **Neon**: buat proyek PostgreSQL di <https://neon.tech>, salin *connection string* (`postgresql://...?sslmode=require`).
2. **Vercel**: *Add New → Project* → impor repositori GitHub ini. Biarkan *Framework Preset* = *Other*. `vercel.json` sudah mengatur build (`npm ci && npm run build`), fungsi PHP (`api/index.php`), dan variabel dasar.
3. **Environment Variables** (Settings → Environment Variables), tambahkan:

   | Nama | Nilai |
   |---|---|
   | `APP_KEY` | hasil `php artisan key:generate --show` di komputer Anda |
   | `APP_URL` | alamat proyek Vercel, mis. `https://noa.vercel.app` |
   | `DB_CONNECTION` | `pgsql` |
   | `DB_URL` | connection string Neon |

4. **Migrasi dan data demo** (sekali, dari komputer Anda, mengarah ke basis data Neon):

   ```bash
   DB_CONNECTION=pgsql DB_URL="postgresql://..." php artisan migrate --force
   DB_CONNECTION=pgsql DB_URL="postgresql://..." php artisan noa:siapkan-demo
   ```

   (Di PowerShell: `$env:DB_URL="..."; php artisan migrate --force`.)
5. **Deploy**, lalu buka alamat Vercel dan tekan **Masuk sebagai peternak**.

Bila fungsi gagal dimuat (halaman 500 atau tertulis runtime tidak dikenal), periksa *Logs* di dashboard Vercel, lalu pakai **Jalur B**.

## Jalur B — Render (Docker)

1. Dorong repositori ke GitHub.
2. <https://render.com> → *New → Blueprint* → pilih repositori. `render.yaml` membuat layanan web (Docker) dan basis data PostgreSQL.
3. Setelah *build* selesai, buka alamat layanan. `docker/entrypoint.sh` otomatis menjalankan migrasi dan membuat data demo pada start pertama.
4. Isi `APP_URL` di *Environment* dengan alamat layanan (opsional, untuk tautan absolut).

## Variabel penting

| Variabel | Fungsi |
|---|---|
| `NOA_DEMO=true` | Tombol "Coba demo", banner mode demo, tombol "Kembalikan data demo" |
| `NOA_UNGGAH=false` | Sembunyikan unggah foto BTPA dan lampiran RHPP (penyimpanan tidak permanen) |
| `SESSION_DRIVER=cookie` | Sesi tanpa tabel (serverless) |
| `APP_ENV=production`, `APP_DEBUG=false` | Mode produksi: HTTPS dipaksa, galat tidak membocorkan detail |

## Catatan keamanan

Mode demo membuka dua akun contoh tanpa kata sandi **hanya untuk data contoh**. Jangan aktifkan `NOA_DEMO=true` pada penayangan yang berisi data sungguhan.

## Daftar periksa sebelum presentasi

- [ ] Buka alamat publik dari ponsel dan laptop lain
- [ ] Tombol **Masuk sebagai peternak** dan **ABK** berfungsi
- [ ] Dashboard memuat "Perlu perhatian" dan periode aktif
- [ ] **Kembalikan data demo** berhasil (butuh ±10 detik)
- [ ] Cadangkan tangkapan layar dan rekaman layar demo bila koneksi di ruang presentasi buruk
