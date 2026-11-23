# NOA — Product Requirements Document

Nitip, Openi, Adil — catatan kandang plasma yang bisa dicocokkan dengan RHPP inti

Versi 4.0 · 23 November 2026 · Tim NOA

| Atribut | Isi |
|---|---|
| Nama produk | NOA |
| Versi dokumen | 4.0 (menggantikan "PRD v3", versi dokumen 2.1 tertanggal 25 Oktober 2026) |
| Komoditas | Ayam broiler pola kemitraan inti-plasma |
| Status | Dibangun, diuji otomatis, dan siap didemokan (proyek mata kuliah Pengembangan Aplikasi Web Lanjut). Belum diuji pada peternak sungguhan. |
| Platform | Aplikasi web responsif (ponsel, tablet, desktop), tema terang dan gelap |
| Teknologi | Laravel 13, PHP 8.3, PostgreSQL 17, Blade, Alpine.js, Tailwind CSS 3, Vite, Chart.js (hanya modul garis), Laravel Breeze |
| Penayangan | Vercel (runtime PHP komunitas, belum diuji) atau Render/Docker; CI GitHub Actions |
| Repositori | github.com/marquisccel/NOA (cabang `main`) |
| Pengembangan | 10–23 November 2026 (±2 minggu; PRD lama merencanakan 6 minggu) |

## 1. Tentang dokumen ini

PRD v3 ditulis sebelum pengembangan dimulai. Selama dikerjakan, tim menyepakati dan mengeksekusi beberapa perubahan. Dokumen ini adalah PRD yang **sudah disesuaikan dengan sistem yang berjalan saat ini** (cabang `main` setelah PR #1 digabung pada 23 November 2026). Jadi isinya menggambarkan apa yang benar-benar ada, bukan lagi rencana.

Setiap kebutuhan diberi status:

- **Selesai**: ada di sistem dan diuji otomatis.
- **Diubah**: ada, tetapi bentuknya berbeda dari PRD v3 (alasannya dicatat).
- **Tidak dikerjakan** atau **Dibatalkan**: sengaja tidak ada.
- **Baru**: tidak ada di PRD v3, ditambahkan selama pengembangan.

## 2. Ringkasan perubahan dari PRD v3

| # | Area | PRD v3 | Sistem sekarang | Alasan |
|---|---|---|---|---|
| 1 | Peran pengguna | Tiga peran: Peternak, ABK, Pengawas inti (baca-saja, opsional, F8.3 berprioritas C) | **Dua peran**: Plasma (peternak) dan ABK. Peran, tabel, halaman, dan kebijakan Pengawas dibuang | Memberi inti akses lihat melemahkan netralitas catatan plasma. NOA adalah alat rekap plasma, bukan alat inti. |
| 2 | Istilah | "Siklus", "open/closed house", "culling", "re-chick in" | **Periode**, **Kandang terbuka/tertutup**, **afkir**, "ayam masuk berikutnya" | Uji coba pertama (18 Nov): istilah teknis sulit dipahami orang awam. Nama di kode tetap `siklus`. |
| 3 | Status siklus | Berjalan, selesai, dihentikan | **7 status**: Berjalan, Panen selesai (cadangan di basis data), Menunggu RHPP, Rekonsiliasi, Ditutup, Ditutup tanpa RHPP, Dihentikan | Alur nyata dari panen sampai RHPP perlu tahap yang jelas. Empat status pertama "terbuka", tiga terakhir "terkunci". |
| 4 | Penguncian data (R12) | Hanya recording pada siklus berstatus selesai | **Recording, sampling, OVK, panen, dan RHPP** terkunci pada status Ditutup, Ditutup tanpa RHPP, dan Dihentikan | Mencegah perubahan setelah periode ditutup; paraf PPL tetap berlaku. |
| 5 | Akses ABK (R13) | ABK tanpa akses kontrak, biaya, rekonsiliasi | Sama, dipertegas: ABK **ditugaskan ke satu periode berjalan**, hanya mengisi dan memperbaiki recording, tidak bisa menghapus atau memaraf. Akun ABK dinonaktifkan, tidak dihapus | Menjaga data komersial dan bukti tetap utuh. |
| 6 | Rekonsiliasi (F6.3, F6.5) | Tandai baris yang selisihnya melewati "ambang wajar"; tampilkan dampak rupiah ke bonus | **17 baris** (15 dibandingkan, bonus dan hasil bersih hanya dicatat). Penanda **Sama/Berbeda** saja, dengan keterangan per baris. Ambang dan dampak rupiah **dibuang** | NOA tidak menilai siapa yang benar. Rumus bonus tiap inti berbeda dan tidak diketahui NOA. |
| 7 | Skema bonus (F1.4) | Skema bisa dipilih | Nilai bonus **disimpan**, tetapi bonus tidak dihitung menjadi rupiah | Alasan sama dengan nomor 6. |
| 8 | Isian harian | Empat bidang wajib: pakan terpakai, mati, culling, tanggal | **Tiga isian**: pakan terpakai, ayam mati, ayam afkir (tanggal otomatis, bisa diganti untuk entri mundur) | Lebih cepat: target di bawah satu menit. |
| 9 | Kandang | Nama farm, alamat, kapasitas, tipe | Nama farm, alamat, kapasitas, dan **link Google Maps (opsional)**. Pilihan tipe kandang dihapus dari tampilan (kolom basis data dibiarkan) | Peta di detail kandang mengikuti link; tipe kandang tidak dipakai di perhitungan (PR #1). |
| 10 | Grafik (F7.2) | Grafik bobot dan deplesi sepanjang siklus | **Kurva harian** (bobot aktual vs target, deplesi) dengan Chart.js garis, tren IP/FCR, sparkline. Dua grafik batang di ringkasan periode dihapus | Mengurangi pengulangan dan beban halaman (PR #1). |
| 11 | Performa antar periode (F7.3) | Bandingkan siklus di kandang sama | Menu **Performa** sendiri (filter rentang waktu, kandang, status; FCR gabungan; tren) dan **Bandingkan dua periode** | Peternak butuh melihat tren lintas periode. |
| 12 | Pemahaman awam | Tidak ada | Tanda tanya istilah, halaman **Bantuan**, kartu **Langkah berikutnya**, **Mulai di sini**, **Perlu perhatian**, tur panduan, panel **Kenapa angkanya begini?** | Pengguna baru jarang tahu harus mulai dari mana. |
| 13 | Kalkulator "Bagaimana kalau?" | Tidak ada | Dibuat, lalu **disembunyikan dari tampilan** (kode tetap ada, bisa diaktifkan lagi) | Keputusan tim di PR #1. |
| 14 | Contoh perhitungan | Total pakan 420 sak (21.000 kg), tetapi FCR 1,652 dan IP 339,2 | **Dikoreksi**: 190 sak (9.500 kg). 21.000 ÷ 5.750 = 3,652, jadi angka lama tidak konsisten | Temuan saat membuat uji otomatis (Fase 1). |
| 15 | Tampilan | Tidak diatur | Satu aliran desain "kaca" di atas latar aurora; maskot ayam di halaman masuk; token warna terang/gelap; aurora diam dan kartu tanpa `backdrop-filter` di dalam aplikasi demi kinerja | Konsistensi dan kinerja. |
| 16 | Demo dan penayangan | Tidak ada | Tombol **Coba demo** (Plasma/ABK satu klik), banner demo, tombol kembalikan data, data simulasi 14 periode, berkas penayangan Vercel dan Render, CI | Penilaian lewat demo dan presentasi. |
| 17 | Unggah foto (F5.3) | Foto BTPA | Foto BTPA dan lampiran RHPP; dapat dimatikan (`NOA_UNGGAH`) di penyimpanan serverless | Vercel tidak menyimpan berkas permanen. |
| 18 | Lokasi dan metrik uji coba | Uji coba di Tulungagung, metrik retensi, kelengkapan, selisih | **Belum dilakukan** pada peternak nyata. Bukti kualitas diganti metrik teknis (lihat bagian 12) | Konteks tugas kuliah; waktu dan akses lapangan terbatas. |

## 3. Ringkasan produk

NOA adalah aplikasi web pencatatan harian kandang ayam broiler yang keluarannya adalah **rekap tandingan peternak plasma**: FCR, deplesi, ABW, Indeks Performa, dan biaya produksi versi peternak sendiri, untuk dibandingkan dengan **RHPP** yang diterbitkan perusahaan inti.

Dalam kemitraan inti-plasma, seluruh angka dipegang inti. Peternak menerima RHPP sebagai hasil jadi tanpa alat pembanding, padahal angka-angka itu menentukan bonus yang diterimanya. NOA memberi peternak catatan sendiri yang berdiri sejajar dengan catatan inti.

NOA adalah akronim **Nitip, Openi, Adil**: inti menitipkan DOC dan sapronak, plasma memeliharanya, hasilnya dibagi menurut kontrak. Tiga tahap itu menjadi alur produk.

## 4. Pernyataan masalah

Peternak plasma mencatat pakan, kematian, dan bobot di papan tulis atau buku kandang. Catatan itu jarang diolah menjadi FCR atau IP, sehingga saat RHPP keluar 3–7 hari setelah panen terakhir, peternak tidak punya dasar untuk mempertanyakan satu angka pun.

Selisihnya bukan hal sepele. Satu studi pada populasi 10.000 ekor menemukan selisih deplesi 1,43 persen, selisih FCR 0,016, dan selisih Indeks Performa 20 poin antara catatan peternak dan angka standar kontrak, semuanya berdampak pada bonus.

### Tujuan produk

1. Peternak plasma punya angka FCR, deplesi, ABW, dan IP versinya sendiri sebelum RHPP terbit.
2. Selisih antara catatan peternak dan RHPP terlihat sebagai angka, bukan sebagai perasaan dirugikan.
3. Tonase hasil timbang lapangan (BTPA) terekam per panen bertahap dan dapat dijumlahkan ulang.
4. Pengguna awam bisa memakainya tanpa membaca manual (tambahan di versi 4.0).

### Bukan tujuan produk

NOA bukan sistem kontrol kandang IoT, bukan penyedia sapronak, bukan marketplace, dan bukan aplikasi kemitraan milik inti. Ruang itu sudah diisi BroilerX dan Chickin. NOA berdiri di sisi plasma, dan alasan keberadaannya adalah verifikasi. NOA juga **tidak menghitung bonus** dan **tidak menilai** siapa yang benar; ia hanya memperlihatkan selisih.

## 5. Pengguna dan kebutuhannya

Pengguna utama satu: peternak plasma. Satu peran lain bersifat pendukung.

| Peran | Profil | Kebutuhan utama | Kendala |
|---|---|---|---|
| Plasma (peternak) | Pemilik kandang 3.000–20.000 ekor, mengelola 5–8 periode setahun | Tahu FCR dan deplesi berjalan sebelum panen; punya dasar saat RHPP keluar | Sibuk mengurus kandang; catatan masih di papan tulis |
| ABK (anak buah kandang) | Pekerja harian yang menimbang pakan dan memungut bangkai | Entri cepat dengan isian sedikit | Tangan kotor, sinyal lemah di kandang |

**Pengawas inti dihapus** (PRD v3 menjadikannya peran ketiga). Alasannya: bila inti dapat melihat catatan, nilai NOA sebagai catatan tandingan hilang, dan inti memang bukan pengguna produk ini. Bila kelak diperlukan, akses tamu per periode dapat ditambahkan tanpa membongkar yang ada.

### Implikasi rancangan

1. **Entri harian di bawah satu menit.** Tiga isian wajib: pakan terpakai, ayam mati, ayam afkir. Sisanya opsional dan terlipat.
2. **Angka berjalan segera tampil.** Deplesi kumulatif, stok pakan, dan FCR berjalan muncul setelah setiap entri.
3. **Satu kandang, satu periode terbuka.** Peternak dengan beberapa kandang menjalankan periode yang tidak serentak.
4. **Mati dan afkir dicatat terpisah.** Keduanya masuk deplesi, tetapi afkir bisa dinegosiasikan dengan inti. Afkir wajib disertai sebab.
5. **Bahasa sehari-hari.** Istilah teknis (DOC, FCR, IP, RHPP, BTPA) tetap dipakai karena itu bahasa peternak, tetapi selalu ada tanda tanya berisi arti, contoh, dan cara membacanya.

### Skenario pemakaian

- **Harian.** Pak Yanto menutup kandang pukul lima sore, membuka NOA di ponselnya, memasukkan 14 sak pakan terpakai, 7 ekor mati, 2 ekor afkir. Layar langsung menampilkan deplesi kumulatif dan stok pakan.
- **Mingguan.** Ia menimbang 150 ekor dari 3.000 populasi, memasukkan total bobot sampel, dan sistem menghitung ABW serta pertambahan bobot harian terhadap standar.
- **Panen.** Panen dilakukan tiga tahap. Setiap tahap dicatat dengan nomor BTPA, jumlah ekor, dan tonase.
- **Pasca-panen.** RHPP datang lima hari kemudian. Pak Yanto menyalin angkanya, dan NOA menampilkan Sama/Berbeda baris demi baris terhadap catatannya sendiri, lalu mencetak lembar untuk musyawarah.

## 6. Ruang lingkup

### Masuk dan sudah dibangun

- Pendataan kandang (dengan link Google Maps opsional) dan periode pemeliharaan (wizard 5 langkah)
- Kontrak per periode: harga DOC, pakan, OVK, harga kontrak ayam, skema bonus (nilai disimpan)
- Standar harian (CSV), parameter manajemen per umur, jadwal vaksin
- Recording harian: pakan masuk/terpakai/sisa, mati, afkir, suhu, OVK pagi/sore, paraf PPL
- Sampling bobot (ABW, ADG) dan OVK beserta biaya
- Panen bertahap dengan nomor BTPA dan foto
- Perhitungan otomatis: deplesi, daya hidup, FCR, ABW, umur panen berbobot, IP, biaya per kg
- Rekonsiliasi RHPP baris demi baris dan lembar cetak A4
- Laporan periode (tampilan, cetak A4, CSV), halaman Performa, bandingkan dua periode
- Akun Plasma dan ABK dengan otorisasi per sumber daya
- Bantuan, tur, mode demo, mode gelap

### Dikecualikan dari versi ini

| Yang dikecualikan | Alasan |
|---|---|
| Peran Pengawas/inti dan dashboard inti | Menjaga netralitas catatan plasma (lihat bagian 5) |
| Perhitungan bonus dan dampak rupiah dari selisih | Rumus bonus tiap inti berbeda |
| Sensor IoT dan kontrol iklim | Sudah dikuasai BroilerX dan Chickin |
| Ayam petelur, kambing, domba, sapi | Model data berbeda total |
| Pembelian sapronak di dalam aplikasi | NOA berdiri di sisi plasma |
| Nekropsi dan diagnosa penyakit | Butuh keahlian dokter hewan |
| Mode luring penuh dan PWA | Ditunda; entri bisa menunggu sinyal |
| Pengingat harian (WhatsApp/notifikasi) | Ditunda |
| Riwayat perubahan baris, foto nota pakan/DOC/OVK | Ditunda |
| Akun admin NOA, ekspor semua data, pencatatan galat | Ditunda |
| Pengaturan pendaftaran dan surel sungguhan untuk lupa kata sandi | Belum disiapkan (`MAIL_MAILER=log`; pendaftaran publik masih terbuka) |

Catatan cakupan komoditas: keputusan membatasi ke broiler diambil sadar. Entitas inti broiler adalah populasi dalam satu periode; ternak ruminansia adalah individu bernama sepanjang tahun. Bila diperluas, jalur termudah adalah ayam petelur mandiri.

## 7. Kebutuhan fungsional

Prioritas memakai MoSCoW: M wajib, S sebaiknya, C boleh ditunda. Kolom **Status** menunjukkan kondisi sistem saat ini.

### F1 — Kandang, periode, dan standar acuan

| Kode | User story | P | Status |
|---|---|---|---|
| F1.1 | Mendaftarkan kandang: nama farm, alamat, kapasitas, link Google Maps (opsional) | M | Diubah: tipe kandang dihapus; link Google Maps ditambahkan. Peta di detail kandang mengikuti link. Hanya domain Google yang diterima; link pendek diselesaikan sekali saat disimpan. |
| F1.2 | Membuka periode: tanggal masuk, populasi, strain, jenis pakan, PPL, inti, bobot sak | M | Selesai: wizard 5 langkah (identitas, kontrak, skema bonus, standar harian, konfirmasi) |
| F1.3 | Mencatat kontrak: harga DOC, pakan per sak, OVK, harga kontrak ayam | M | Selesai |
| F1.4 | Mencatat skema bonus (FCR, deplesi, IP, harga pasar) | M | Diubah: nilai bonus disimpan, tidak dihitung menjadi rupiah |
| F1.5 | Mengunggah tabel standar harian (CSV) | M | Selesai: koma atau titik koma, format PRD dan format desain, pratinjau, baris bermasalah dilaporkan, templat CSV dapat diunduh |
| F1.6 | Parameter manajemen per rentang umur (suhu, kepadatan, frekuensi pakan, TMA, TRA) | S | Selesai |
| F1.7 | Jadwal vaksin per hari umur | S | Selesai |
| F1.8 | Beberapa periode di kandang berbeda bersamaan | M | Selesai (R2 menjaga satu periode terbuka per kandang) |
| F1.9 | Menandai periode selesai | M | Diubah: alur 7 status (Tandai panen selesai, Tutup, Tutup tanpa RHPP, Hentikan dini) |

Standar disalin ke tiap periode, bukan dirujuk dari tabel global, supaya perbandingan periode lama tetap sahih ketika standar kemitraan berubah.

### F2 — Recording harian

| Kode | User story | P | Status |
|---|---|---|---|
| F2.1 | Mencatat pakan masuk dari inti (sak) | M | Selesai |
| F2.2 | Mencatat pakan terpakai dan sisa (sak) | M | Selesai |
| F2.3 | Stok gudang = masuk − terpakai (dihitung, tidak disimpan) | M | Selesai |
| F2.4 | Mencatat ayam mati | M | Selesai |
| F2.5 | Mencatat ayam afkir dan sebabnya | M | Selesai: sebab wajib bila ada afkir |
| F2.6 | Menampilkan standar di samping aktual (pakan, bobot, FCR) | M | Selesai; kosong bila standar belum diunggah |
| F2.7 | Feed intake kumulatif per ekor | M | Selesai (ditulis "Pakan per ekor") |
| F2.8 | Deplesi kumulatif dan FCR berjalan setiap simpan | M | Selesai |
| F2.9 | Mencatat suhu dan membandingkan dengan target umur | S | Selesai (ditulis sebagai kata) |
| F2.10 | Mencatat OVK pagi dan sore | S | Diubah: kotak centang Pagi/Sore |
| F2.11 | Menandai baris yang sudah diparaf PPL beserta tanggal | S | Selesai: baris terparaf terkunci (R11) |
| F2.12 | Subtotal mingguan pakan dan deplesi | S | Selesai |
| F2.13 | Menolak entri ganda pada hari umur yang sama | M | Selesai (aplikasi dan indeks unik basis data) |
| F2.14 | Memperingatkan bila pakan terpakai melebihi stok | M | Selesai: informatif, bukan penghalang |
| F2.15 | Mengisi entri yang terlewat secara mundur | S | Selesai: hari terlewat tampil dengan tautan "Isi sekarang" |
| F2.16 | **Baru:** "Samakan dengan kemarin", tombol +/−, isi banyak hari sekaligus | S | Selesai |
| F2.17 | **Baru:** unduh riwayat recording (CSV) | C | Selesai |

### F3 — Sampling bobot

| Kode | User story | P | Status |
|---|---|---|---|
| F3.1 | Mencatat hasil timbang sampel (jumlah ekor, total bobot) | M | Selesai; sampel tidak boleh melebihi ayam hidup, bobot rata-rata di atas 10 kg ditolak |
| F3.2 | Menghitung ABW | M | Selesai |
| F3.3 | Memperingatkan bila sampel kurang dari 5% populasi hidup | M | Selesai: tetap tersimpan, ditandai kurang representatif |
| F3.4 | Pertambahan bobot harian terhadap sampling sebelumnya | M | Selesai (sampling pertama menampilkan "–") |
| F3.5 | Membandingkan ABW dengan standar | S | Selesai |

### F4 — OVK

| Kode | User story | P | Status |
|---|---|---|---|
| F4.1 | Mencatat obat, vaksin, vitamin, atau kimia dengan tanggal, jenis, dosis | M | Selesai: dosis wajib |
| F4.2 | Mencatat biaya OVK | M | Selesai: masuk biaya produksi |
| F4.3 | Riwayat OVK satu periode dalam satu halaman | M | Selesai |

### F5 — Panen dan BTPA

| Kode | User story | P | Status |
|---|---|---|---|
| F5.1 | Mencatat tiap tahap panen (tanggal, ekor, tonase) | M | Selesai |
| F5.2 | Mencatat nomor BTPA per tahap | M | Selesai: nomor BTPA wajib |
| F5.3 | Mengunggah foto BTPA | S | Selesai: disimpan di disk privat dengan nama acak, diakses lewat rute terotorisasi; dapat dimatikan |
| F5.4 | Total ekor dan tonase | M | Selesai |
| F5.5 | Umur panen rata-rata berbobot | M | Selesai |
| F5.6 | Memperingatkan bila panen + deplesi ≠ populasi awal | M | Selesai: ditandai ("Selisih jumlah ayam"), tidak dipaksa seimbang |
| F5.7 | **Baru:** unduh daftar BTPA (CSV) | C | Selesai |

### F6 — Rekonsiliasi RHPP

| Kode | User story | P | Status |
|---|---|---|---|
| F6.1 | Memasukkan angka RHPP dari inti | M | Selesai: satu halaman, 16 isian wajib, lampiran foto/PDF opsional |
| F6.2 | Selisih tiap baris: versi peternak, versi RHPP, selisih | M | Selesai: 17 baris (15 dibandingkan; bonus dan hasil bersih hanya dicatat). Selisih nol tampil sebagai nol |
| F6.3 | Menandai baris yang berbeda | M | Diubah: penanda **Sama/Berbeda/Tidak dibandingkan** dengan keterangan per baris. Ambang "wajar" dibuang |
| F6.4 | Mencetak lembar perbandingan | M | Selesai: A4 dengan tiga kolom tanda tangan (peternak, PPL, inti) |
| F6.5 | Dampak selisih terhadap rupiah bonus | S | **Dibatalkan** (lihat bagian 2, nomor 6) |
| F6.6 | **Baru:** unduh rekap pembanding (CSV) | C | Selesai |

### F7 — Laporan, performa, dan cetak

| Kode | User story | P | Status |
|---|---|---|---|
| F7.1 | Ringkasan periode: deplesi, FCR, ABW, IP, biaya produksi | M | Selesai: ditambah biaya per kg dan per ekor, pendapatan, margin kasar |
| F7.2 | Grafik bobot dan deplesi sepanjang periode | S | Diubah: kurva harian (bobot vs target, deplesi) berupa grafik garis; grafik batang di ringkasan dihapus |
| F7.3 | Membandingkan performa antar-periode | S | Selesai: menu **Performa** dan **Bandingkan dua periode** |
| F7.4 | Mencetak laporan periode satu halaman | M | Selesai; juga unduh CSV |

### F8 — Autentikasi dan otorisasi

| Kode | User story | P | Status |
|---|---|---|---|
| F8.1 | Masuk dengan surel dan kata sandi | M | Selesai: Breeze; kata sandi minimal 8 karakter, huruf dan angka; lupa kata sandi tidak membocorkan akun |
| F8.2 | Memberi akun ABK hak entri tanpa akses kontrak, biaya, rekonsiliasi | M | Selesai: ABK ditugaskan ke satu periode berjalan; dibuat, dinonaktifkan, dan diatur ulang sandinya oleh peternak |
| F8.3 | Pengawas inti baca-saja | C | **Dibatalkan** (lihat bagian 5) |

### F9 — Pemahaman dan panduan pengguna (baru)

| Kode | Kebutuhan | Status |
|---|---|---|
| F9.1 | Dashboard peternak: kartu ringkasan, **Mulai di sini** (6 langkah awal), **Perlu perhatian** (hari terlewat, stok tipis, baris belum diparaf), daftar periode aktif | Selesai |
| F9.2 | Menu **Aktivitas**: catatan terbaru lintas recording, sampling, OVK, panen, per tanggal dengan filter | Selesai |
| F9.3 | **Bantuan**: alur satu periode dalam 7 langkah dan daftar istilah; tanda tanya penjelas di samping istilah | Selesai |
| F9.4 | Kartu **Langkah berikutnya** menurut status periode | Selesai |
| F9.5 | Panel **Kenapa angkanya begini?**: rumus dengan angka milik pengguna | Selesai |
| F9.6 | Tur panduan 3–4 langkah, dapat diulang | Selesai |
| F9.7 | Kalkulator **Bagaimana kalau?** | Dibuat, disembunyikan dari tampilan (keputusan tim) |
| F9.8 | Mode gelap dengan pengalih; mengikuti pengaturan sistem | Selesai |
| F9.9 | Notifikasi toast, bilah kemajuan, tombol sibuk, halaman galat 403/404/419/500 | Selesai |
| F9.10 | Dropdown bertema untuk perangkat berkursor (`<select>` asli tetap sumber nilai) | Selesai |

### F10 — Demo dan penayangan (baru)

| Kode | Kebutuhan | Status |
|---|---|---|
| F10.1 | Tombol **Coba demo** di halaman masuk (Plasma/ABK satu klik), banner mode demo, tombol kembalikan data | Selesai (`NOA_DEMO`) |
| F10.2 | Data simulasi: 4 kandang, 14 periode (±14 bulan), 546 recording, 42 tahap panen; mencakup semua status | Selesai (`SimulasiSeeder`) |
| F10.3 | Berkas penayangan Vercel dan Render/Docker | Dibuat; Vercel belum diuji |
| F10.4 | CI GitHub Actions: migrasi, build, seluruh tes | Selesai |

## 8. Aturan bisnis dan model data

### Aturan bisnis

| Kode | Aturan | Dijaga di |
|---|---|---|
| R1 | Satu periode hanya punya satu entri recording per hari umur | Indeks unik parsial + form request |
| R2 | Satu kandang hanya punya **satu periode terbuka** (Berjalan, Panen selesai, Menunggu RHPP, Rekonsiliasi) | Indeks unik parsial + pengendali |
| R3 | Deplesi = (mati + afkir) ÷ jumlah DOC × 100 | `PerformanceCalculator` |
| R4 | Pakan dicatat dalam sak, dikonversi ke kg dengan bobot sak milik periode | `PerformanceCalculator` |
| R5 | Stok gudang = pakan masuk − terpakai; boleh negatif dengan peringatan | Dihitung, tidak disimpan |
| R7 | Ekor panen + deplesi harus sama dengan populasi awal; selisih ditandai, tidak dipaksa seimbang | `RingkasanPerforma` |
| R8 | Sampel di bawah 5% populasi hidup ditandai kurang representatif | `PerformanceCalculator` |
| R9 | Umur panen = Σ(ekor × umur) ÷ Σ ekor | `PerformanceCalculator` |
| R10 | FCR dan IP tidak dihitung sebelum ada catatan panen (tampil "–") | `RingkasanPerforma` |
| R11 | Baris recording yang sudah diparaf PPL tidak dapat disunting | Model + kebijakan |
| R12 | Periode berstatus **Ditutup, Ditutup tanpa RHPP, atau Dihentikan** terkunci: recording, sampling, OVK, panen, RHPP. *(Diubah: PRD v3 hanya recording pada status selesai.)* | Model + pengendali (403) |
| R13 | ABK tidak melihat kontrak, biaya, laporan, rekonsiliasi, atau data peternak lain; ABK hanya bekerja pada satu periode yang ditugaskan | Kebijakan + middleware |
| R14 | **Baru:** link peta kandang hanya diterima bila domain Google (https); link pendek diikuti sekali saat disimpan dengan batas lompatan | `LinkPeta` + form request |

(R6 PRD v3, "total pakan masuk menjadi dasar pembanding tagihan sapronak", dipenuhi sebagai baris "Pakan masuk (tagihan)" di rekonsiliasi.)

### Rumus performa

| Ukuran | Rumus |
|---|---|
| Deplesi (%) | (ayam mati + afkir) ÷ jumlah DOC × 100 |
| Daya hidup (%) | 100 − deplesi |
| ABW panen (kg) | total tonase ÷ total ekor panen |
| FCR | pakan terpakai bersih (kg) ÷ total tonase (kg); pakan bersih = sak × bobot sak − retur |
| Umur panen berbobot | Σ(ekor × umur) ÷ Σ ekor |
| IP | (daya hidup % × ABW kg) ÷ (FCR × umur panen) × 100; rumus tersimpan per periode (`rumus_ip`), saat ini satu rumus standar |
| Biaya per kg | (biaya DOC + biaya pakan tertagih + biaya OVK) ÷ total tonase |
| Biaya pakan | seluruh pakan masuk × harga per sak (tagihan sapronak) |
| Pendapatan | tonase × harga kontrak ayam (harga per tahap didahulukan bila diisi) |
| FCR gabungan (Performa) | Σ pakan ÷ Σ tonase semua periode (bukan rata-rata FCR) |
| Selisih rekonsiliasi | angka RHPP − angka peternak; "Sama" bila sama pada ketelitian tampilan |

Presisi: FCR tiga angka di belakang koma, deplesi dua. Pembulatan hanya saat menampilkan.

### Contoh perhitungan (dikoreksi)

Periode 3.000 ekor, panen tiga tahap.

| Komponen | Nilai |
|---|---|
| Jumlah DOC | 3.000 ekor |
| Mati / afkir | 78 / 18 ekor |
| Deplesi | 96 ekor, 3,20% |
| Daya hidup | 96,80% |
| Total ekor panen | 2.904 ekor |
| Total tonase | 5.750 kg |
| ABW | 1,980 kg |
| Total pakan | **9.500 kg (190 sak × 50 kg)** |
| FCR | 1,652 |
| Umur panen berbobot | 34,2 hari |
| IP | 339,2 |

PRD v3 menulis 420 sak (21.000 kg), yang bila dihitung memberi FCR 3,652. Angka 190 sak yang konsisten dengan FCR 1,652 dan IP 339,2, dan itu yang dipakai seeder dan uji.

### Keadaan khusus

| Keadaan | Penanganan |
|---|---|
| Panen bertahap dengan harga berbeda | Kolom harga per tahap ada di basis data dan didahulukan bila terisi, tetapi form panen tidak lagi menyediakan isiannya; pendapatan dihitung dari tonase × harga kontrak ayam |
| Ayam hilang tanpa catatan mati | Selisih ditandai, bukan dipaksa seimbang |
| Pakan sisa/retur di akhir periode | Mengurangi pakan terpakai agar FCR tidak melar |
| Periode dihentikan dini (wabah) | Status Dihentikan; angka dihitung dari data yang ada dan ditandai tidak sebanding |
| RHPP tidak pernah terbit | **Tutup tanpa RHPP** (alasan wajib); rekonsiliasi dikosongkan |

### Model data (ringkas)

| Entitas | Atribut utama | Relasi |
|---|---|---|
| User | name, email, role (`peternak` atau `abk`), peternak_id (ABK), siklus_id (penugasan ABK), aktif | Peternak punya banyak kandang dan banyak akun ABK |
| Kandang | nama_farm, alamat, kapasitas, link_gmaps (opsional); `tipe` tidak dipakai lagi di tampilan | Punya banyak periode |
| Siklus (Periode) | tanggal_masuk, populasi, strain_doc, jenis_pakan, nama_ppl, nama_inti, bobot_sak_kg, status (7), tanggal_panen, ditutup_pada, alasan/keterangan_penutupan, pakan_retur_sak, rumus_ip | Milik satu kandang; punya banyak recording, sampling, OVK, panen; satu kontrak, satu RHPP |
| StandarHarian, ParameterUmur, JadwalVaksin | Acuan per hari atau rentang umur (standar: bobot, pakan, FCR, feed intake, deplesi standar) | Disalin per periode |
| KontrakSiklus | harga_doc, harga_pakan_sak, harga_ovk, harga_kontrak_ayam, bonus FCR/deplesi/IP/harga pasar (aktif dan nilai) | Satu per periode |
| RecordingHarian | tanggal, hari_ke (unik), pakan masuk/pakai/sisa, mati, afkir, sebab_afkir, deplesi_harian (kolom turunan), bobot, suhu pagi/sore, OVK pagi/sore, diparaf_ppl, tanggal_paraf | Milik satu periode |
| SamplingBobot | tanggal, hari_ke, jumlah_sampel, total_bobot_kg | Milik satu periode |
| PemberianOvk | tanggal, jenis (obat/vitamin/vaksin/kimia), nama_produk, dosis, biaya | Milik satu periode |
| Panen | tanggal, umur_hari, jumlah_ekor, tonase_kg, nomor_btpa, foto, harga (opsional, tidak ada di form) | Milik satu periode |
| Rhpp | nomor, tanggal_terbit, angka RHPP (ekor, tonase, pakan, FCR, IP, biaya DOC/pakan/OVK, bonus, hasil bersih), lampiran, catatan per baris (`jsonb`) | Satu per periode |

Peran `pengawas` dan tabel `akses_pengawas` (sempat dibuat di UI Fase 6) sudah dihapus lewat migrasi `hapus_peran_pengawas`. Stok pakan, FCR, IP, deplesi persen, dan selisih RHPP tidak disimpan; selalu dihitung dari catatan dasar. Soft delete dipakai pada data inti.

## 9. Alur pengguna utama

Periode punya 7 status. Empat pertama "terbuka" (menahan kandang), tiga terakhir "terkunci" (data hanya dilihat).

| Status | Terbuka | Catat harian | Sampling, OVK, panen | Input RHPP |
|---|---|---|---|---|
| Berjalan | ya | ya | ya | belum |
| Panen selesai (cadangan di basis data, tidak dipakai alur) | ya | ya | ya | ya |
| Menunggu RHPP | ya | ya | ya | ya |
| Rekonsiliasi | ya | ya | ya | ya (ubah) |
| Ditutup | tidak | tidak | tidak | tidak |
| Ditutup tanpa RHPP | tidak | tidak | tidak | tidak |
| Dihentikan | tidak | tidak | tidak | tidak |

Alur satu periode:

1. **Daftarkan kandang** (sekali).
2. **Buka periode**: wizard 5 langkah. DOC masuk adalah hari ke-1.
3. **Catat harian**: pakan terpakai, ayam mati, ayam afkir. Angka berjalan tampil seketika.
4. **Sampling dan OVK**: timbang sampel minimal 5% populasi hidup; catat obat dan vaksin dengan biayanya.
5. **Panen bertahap**: tiap tahap punya tanggal, ekor, tonase, dan nomor BTPA. Lalu **Tandai panen selesai** (status Menunggu RHPP).
6. **Input RHPP**: salin angka dari dokumen inti (status Rekonsiliasi).
7. **Cocokkan dan tutup**: lihat Sama/Berbeda, beri keterangan, cetak lembar untuk musyawarah, **Tutup periode**.

Jalan keluar: **Tutup tanpa RHPP** (alasan wajib) dan **Hentikan dini** (alasan, keterangan, tanggal).

Kartu **Langkah berikutnya** menunjuk tindakan yang sesuai status dan data, jadi pengguna baru tidak perlu menghafal alur.

## 10. Keputusan teknologi

| Lapisan | Pilihan | Catatan versi 4.0 |
|---|---|---|
| Framework | Laravel 13, PHP 8.3 | Sama |
| Basis data | PostgreSQL 17 | CHECK constraint (bukan enum), indeks unik parsial, kolom turunan; pencarian memakai `ilike` |
| Templating | Blade (komponen anonim) | Sama |
| Antarmuka | Alpine.js | Tidak dipercaya untuk angka; nilai tersimpan selalu dihitung PHP dan divalidasi form request |
| Gaya | Tailwind CSS 3 + token desain CSS | Tema terang dan gelap; Vite untuk build |
| Autentikasi | Laravel Breeze + Policy per sumber daya | Sama |
| Grafik | Chart.js (modul garis saja) | Dimuat hanya di halaman grafik |
| Cetak | CSS print media | Tanpa pustaka PDF |
| Impor/ekspor | Unggah CSV (standar), unduhan CSV (recording, BTPA, rekap, laporan) | Ditambah ekspor |
| Pengujian | PHPUnit terhadap PostgreSQL sungguhan | **460 tes** lulus |
| CI | GitHub Actions | Baru |
| Penayangan | Vercel (vercel-php) atau Render/Docker | Baru; Vercel belum diuji |

Yang sengaja tidak dipakai: Livewire (bolak-balik server pada jaringan lambat), Jetstream, Inertia dengan Vue/React, pustaka PDF.

Pekerjaan kinerja (PR #1): N+1 di halaman recording dihilangkan (73 menjadi 13 kueri), total kueri 33 halaman turun dari 527 menjadi 367, HTML hasil identik; di front-end aurora diam di dalam aplikasi dan kartu tanpa `backdrop-filter`.

## 11. Kebutuhan non-fungsional

| Aspek | Kebutuhan | Status |
|---|---|---|
| Perangkat target | Ponsel Android kelas bawah dan laptop | Responsif; diperiksa di 1366, 1024, dan 375 px |
| Waktu entri harian | Di bawah 60 detik | Tiga isian wajib, Samakan dengan kemarin, isi banyak hari |
| Ukuran teks | Minimal 16 px (isian) | Dipenuhi |
| Area sentuh | Minimal 44 px | Dipenuhi |
| Presisi FCR dan deplesi | 3 dan 2 angka di belakang koma | Dipenuhi |
| Ukuran halaman | Di bawah 500 KB | Belum diukur otomatis; grafik dimuat hanya di halaman yang butuh |
| Waktu muat | Di bawah 3 detik pada 3G | Belum diukur |
| Bahasa | Indonesia, istilah kandang lazim | Dipenuhi; kamus di `config/istilah.php` |
| Aksesibilitas | Kontras 4,5:1; status berupa kata; label isian | Dipenuhi pada pemeriksaan HTML; tanpa uji pembaca layar |
| Keamanan | Policy, penguncian di model, foto privat, soft delete | Dipenuhi |
| Cadangan data | Harian, di luar server utama | **Belum ada** |
| Offline | Dikecualikan | Tidak dikerjakan |

## 12. Metrik dan rencana rilis

### Rilis bertahap (selesai)

| Tahap | Lingkup | Status |
|---|---|---|
| M1 | Basis data, model, relasi, seeder | Selesai |
| M2 | Kandang, periode, kontrak | Selesai |
| M3 | Recording harian | Selesai |
| M4 | Sampling dan OVK | Selesai |
| M5 | Panen dan performa | Selesai |
| M6 | Rekonsiliasi RHPP, laporan, akses ABK | Selesai |
| UI 1–5 | Token desain, shell, dashboard, periode, recording, panen, RHPP, laporan, halaman galat | Selesai |
| UI 6 | Peran Pengawas | Dibatalkan |
| Penyempurnaan | Perbaikan setelah uji coba pertama (18 Nov), fitur Performa, mode demo, mode gelap, istilah awam, halaman masuk baru | Selesai |
| PR #1 (23 Nov) | Optimasi kinerja, link Google Maps, hapus tipe kandang, dropdown bertema | Selesai |

Definisi selesai PRD v3 terpenuhi: fungsi dapat diperagakan dari antarmuka, rumus punya uji otomatis dengan angka dari contoh perhitungan, dan tidak ada galat di alur utama.

### Metrik keberhasilan

Metrik lapangan PRD v3 (retensi antar-periode, kelengkapan entri, selisih ditemukan) membutuhkan pemakaian peternak nyata dan **belum diukur**. Untuk versi 4.0 bukti keberhasilan bersifat teknis:

| Metrik | Hasil |
|---|---|
| Tes otomatis | 460 lulus, terhadap PostgreSQL |
| Aturan bisnis dengan tes | R1–R14 |
| Data simulasi | 14 periode, semua 7 status terwakili, RHPP sama dan berbeda |
| Peran | 2, dengan uji bahwa halaman ABK tidak memuat harga atau kata kontrak |
| Alur lengkap | Wizard periode, catat, sampling, panen, RHPP, tutup, teruji lewat HTTP |

## 13. Asumsi, risiko, dan pertanyaan terbuka

### Asumsi yang belum diuji

| Asumsi | Dampak bila salah |
|---|---|
| Peternak plasma merasa dirugikan oleh angka RHPP | Premis produk gugur; NOA menjadi alat evaluasi performa biasa |
| Peternak bersedia mencatat tiap hari bila angka berjalan langsung terlihat | Data tidak lengkap, rekonsiliasi tanpa dasar |
| Peternak punya salinan kontrak dan tahu skema bonusnya | Kolom kontrak kosong |
| BTPA disimpan peternak | Tonase versi peternak tidak dapat diverifikasi |
| Inti tidak melarang aplikasi pihak ketiga | Adopsi terhambat |

### Risiko dan mitigasi

| Risiko | Mitigasi |
|---|---|
| BroilerX dan Chickin sudah menawarkan recording gratis | NOA tidak bersaing di recording; nilainya pada rekonsiliasi RHPP |
| Peternak berhenti mencatat di tengah periode | Tiga isian, angka berjalan sebagai umpan balik, Perlu perhatian |
| Inti menekan peternak | Posisikan sebagai alat pembukuan pribadi |
| Rumus IP berbeda antar-inti | Rumus tersimpan per periode; baru satu rumus |
| Standar strain berbeda | Tabel standar diunggah per periode |
| Klaim produk berlebihan | Nyatakan jelas: NOA membantu pembuktian, bukan menjamin bonus naik |
| **Baru:** runtime PHP komunitas di Vercel belum diuji | Render/Docker sebagai cadangan (`docs/DEPLOY.md`) |
| **Baru:** surel belum dikonfigurasi dan pendaftaran publik terbuka | Wajib diatur sebelum dipakai peternak sungguhan |
| **Baru:** belum ada cadangan data harian dan uji pemulihan | Wajib sebelum produksi |
| **Baru:** tidak ada uji peramban otomatis dan uji beban | Pemeriksaan manual; skala tugas kuliah |

### Pertanyaan terbuka (belum terjawab)

1. Berapa peternak plasma di sekitar lokasi uji yang menyimpan salinan RHPP periode lalu?
2. Seberapa sering selisih catatan peternak dan RHPP benar-benar terjadi, dan berapa besarnya? (Penentu nilai produk.)
3. Apakah tagihan sapronak di RHPP pernah berbeda dari total pakan masuk yang dicatat peternak?
4. Apakah peternak diberi salinan BTPA, atau hanya melihatnya sekilas?
5. Apakah formulir harian yang diparaf PPL disimpan peternak atau dibawa petugas?
6. Skema bonus apa yang dipakai inti di wilayah itu?
7. Siapa yang sebenarnya mencatat di kandang: peternak sendiri atau ABK?
8. Berapa angka ambang selisih yang menurut peternak "wajar"? (Dibuang dari sistem karena tidak ada dasar wawancara.)

## 14. Riwayat versi

| Versi | Perubahan |
|---|---|
| 1.0 | Pencatat akad gaduh ternak ruminansia antar-warga desa |
| 2.1 | Komoditas diubah ke broiler kemitraan inti-plasma atas masukan narasumber ilmu peternakan. Rancangan recording meniru formulir nyata (standar dan aktual berdampingan, paraf PPL, TMA/TRA). Keputusan teknologi ditetapkan |
| 4.0 | Disesuaikan dengan sistem yang berjalan: dua peran, tujuh status periode, rekonsiliasi Sama/Berbeda tanpa ambang dan tanpa dampak rupiah, istilah "Periode", fitur Performa, panduan pengguna, mode demo, kinerja, link Google Maps, koreksi contoh perhitungan (190 sak). Status tiap kebutuhan dicatat |

## Lampiran A — Hal yang belum dikerjakan (pengembangan lanjutan)

1. Hosting HTTPS, cadangan harian, kunci pendaftaran publik, surel sungguhan, kebijakan privasi (UU PDP), dan cara menghapus akun.
2. Pengingat harian, mode luring atau antrean catat-nanti, pasang ke layar utama (PWA).
3. Riwayat perubahan baris recording, foto nota pakan/DOC/OVK, peringatan angka janggal, paraf digital PPL.
4. Akun admin NOA, pencatatan galat, ekspor semua data.
5. Dashboard inti yang bersifat opt-in dengan hanya metrik operasional (bila kelak dibutuhkan).
6. Estimasi bonus (rumus tiap inti berbeda), pilihan rumus IP kemitraan lain.
