# Aturan bisnis dan rumus

Aturan ini diturunkan dari kebutuhan peternak plasma dan **dijaga di kode serta basis data** (bukan hanya di tampilan). Setiap aturan punya tes otomatis; kode R1–R13 dipakai di komentar kode dan nama tes.

## Aturan data

| Kode | Aturan | Dijaga di |
|---|---|---|
| R1 | Satu catatan per hari umur per periode. Mengisi hari yang sama mengarah ke catatan yang ada. | Indeks unik parsial `(siklus_id, hari_ke)` + `RecordingRequest` |
| R2 | Satu kandang hanya punya **satu periode terbuka** (Berjalan, Panen selesai, Menunggu RHPP, Rekonsiliasi). | Indeks unik parsial + `SiklusBaruController` |
| R3 | Deplesi = (ayam mati + afkir) ÷ jumlah DOC × 100. | `PerformanceCalculator::deplesiPersen` |
| R4 | Pakan dicatat dalam **sak**, dikonversi ke kg memakai bobot per sak milik periode. | `PerformanceCalculator::sakKeKg` |
| R5 | Stok pakan = akumulasi pakan masuk − pakan terpakai, **boleh negatif** (dengan peringatan, karena biasanya ada kiriman yang belum dicatat). | Dihitung, tidak disimpan |
| R7 | Ayam panen + deplesi harus sama dengan populasi awal. Bila tidak, selisih **ditandai dan ditampilkan**, tidak dipaksa seimbang. | `RingkasanPerforma`, halaman Selisih jumlah ayam |
| R8 | Sampel timbang di bawah 5% populasi hidup ditandai "kurang representatif" (tetap boleh disimpan). | `PerformanceCalculator::samplingRepresentatif` |
| R9 | Umur panen = Σ(ekor × umur) ÷ Σ ekor (rata-rata berbobot jumlah ekor). | `PerformanceCalculator::umurPanenRataRata` |
| R10 | FCR dan IP **tidak dihitung** sebelum ada catatan panen (tampil "–", bukan 0). | `RingkasanPerforma` |

## Aturan akses dan keutuhan

| Kode | Aturan | Dijaga di |
|---|---|---|
| R11 | Baris yang sudah **diparaf PPL** terkunci dan tidak bisa diubah (bukti sah saat dicocokkan dengan RHPP). | `RecordingHarian` (model) + `RecordingHarianPolicy` |
| R12 | Periode yang sudah **ditutup, ditutup tanpa RHPP, atau dihentikan** terkunci: recording, sampling, OVK, panen, dan RHPP tidak bisa diubah. | Model + controller (403) |
| R13 | **ABK tidak melihat** kontrak, biaya, laporan, rekonsiliasi, atau halaman peternak lain. ABK hanya mencatat di **satu periode** yang ditugaskan. | `SiklusPolicy`, middleware `peternak` |

## Rumus performa

| Ukuran | Rumus | Catatan |
|---|---|---|
| **Deplesi %** | (mati + afkir) ÷ populasi awal × 100 | Daya hidup = 100 − deplesi |
| **ABW panen** | total tonase ÷ total ekor | kg per ekor |
| **FCR** | pakan terpakai (kg) ÷ total tonase (kg) | pakan bersih = sak × bobot sak − retur |
| **IP** | (daya hidup % × ABW kg) ÷ (FCR × umur panen) × 100 | rumus mengikuti pengaturan kemitraan |
| **Biaya per kg** | (biaya DOC + biaya pakan + biaya OVK) ÷ total tonase | DOC = ekor × harga; pakan = sak masuk × harga sak |
| **FCR gabungan (Performa)** | Σ pakan ÷ Σ tonase semua periode periode | bukan rata-rata angka FCR tiap periode |
| **Selisih rekonsiliasi** | angka RHPP − angka peternak | "Sama" bila sama pada ketelitian tampilan |

### Contoh hitung (periode contoh)

- 3.000 DOC, mati 78, afkir 18 → deplesi (78 + 18) ÷ 3.000 × 100 = **3,20%**
- Panen 2.904 ekor, 5.750 kg → ABW = 5.750 ÷ 2.904 = **1,980 kg**
- Pakan terpakai 190 sak × 50 kg = 9.500 kg → FCR = 9.500 ÷ 5.750 = **1,652**

Setiap angka juga bisa dilihat rumusnya dengan angka sendiri lewat panel **Kenapa angkanya begini?** di aplikasi.

## Yang sengaja tidak dihitung NOA

- **Bonus dan hasil bersih**: rumus tiap inti berbeda dan tidak dapat diketahui NOA. Keduanya hanya dicatat dari RHPP dan tidak dibandingkan.
- **Dampak rupiah dari selisih**: tidak ada ambang "wajar"; NOA hanya menunjukkan selisih, bukan menilai siapa yang benar.
