# Diagram basis data (ERD)

Basis data PostgreSQL. Aturan penting dijaga di basis data (bukan hanya di aplikasi): batasan `CHECK` pengganti enum, indeks unik parsial, dan kolom turunan.

```mermaid
erDiagram
    USERS ||--o{ KANDANG : "memiliki (peternak)"
    USERS ||--o{ USERS : "membuat akun ABK (peternak_id)"
    USERS }o--o| SIKLUS : "ABK ditugaskan (siklus_id)"
    KANDANG ||--o{ SIKLUS : "punya"
    SIKLUS ||--o| KONTRAK_SIKLUS : "harga dan bonus"
    SIKLUS ||--o{ STANDAR_HARIAN : "target dari inti"
    SIKLUS ||--o{ PARAMETER_UMUR : "suhu, kepadatan"
    SIKLUS ||--o{ JADWAL_VAKSIN : "jadwal"
    SIKLUS ||--o{ RECORDING_HARIAN : "catatan harian"
    SIKLUS ||--o{ SAMPLING_BOBOT : "timbang sampel"
    SIKLUS ||--o{ PEMBERIAN_OVK : "obat, vaksin, vitamin"
    SIKLUS ||--o{ PANEN : "tahap panen + BTPA"
    SIKLUS ||--o| RHPP : "angka dari inti"

    USERS {
        bigint id PK
        string name
        string email UK
        string role "peternak | abk (CHECK)"
        bigint peternak_id FK "hanya ABK"
        bigint siklus_id FK "penugasan ABK"
        boolean aktif
    }
    KANDANG {
        bigint id PK
        bigint user_id FK
        string nama_farm
        string alamat
        int kapasitas
        string tipe "open_house | closed_house (CHECK)"
    }
    SIKLUS {
        bigint id PK
        bigint kandang_id FK
        date tanggal_masuk "DOC masuk = hari ke-1"
        int populasi
        string status "7 status (CHECK)"
        decimal bobot_sak_kg
        string nama_inti
        string nama_ppl
        date tanggal_panen
        date ditutup_pada
        string alasan_penutupan
        timestamp deleted_at "soft delete"
    }
    KONTRAK_SIKLUS {
        bigint siklus_id FK
        decimal harga_doc
        decimal harga_pakan_sak
        decimal harga_kontrak_ayam
        boolean bonus_fcr
        decimal nilai_bonus_fcr
    }
    STANDAR_HARIAN {
        bigint siklus_id FK
        int hari_ke
        decimal bobot_std_gram
        decimal pakan_std_sak
        decimal fcr_std
        decimal deplesi_std_persen
    }
    RECORDING_HARIAN {
        bigint id PK
        bigint siklus_id FK
        int hari_ke "unik per periode (R1)"
        date tanggal
        decimal pakan_masuk_sak
        decimal pakan_pakai_sak
        int mati
        int afkir
        int deplesi_harian "kolom turunan mati + afkir"
        boolean diparaf_ppl "mengunci baris (R11)"
    }
    SAMPLING_BOBOT {
        bigint siklus_id FK
        int hari_ke
        int jumlah_sampel
        decimal total_bobot_kg
    }
    PEMBERIAN_OVK {
        bigint siklus_id FK
        date tanggal
        string jenis "obat | vitamin | vaksin | kimia"
        string nama_produk
        decimal biaya
    }
    PANEN {
        bigint siklus_id FK
        date tanggal
        int umur_hari
        int jumlah_ekor
        decimal tonase_kg
        string nomor_btpa
    }
    RHPP {
        bigint siklus_id FK "satu per periode"
        string nomor_rhpp
        date tanggal_terbit
        int total_ekor
        decimal total_tonase
        decimal fcr
        decimal ip
        decimal biaya_doc
        decimal hasil_bersih
        jsonb catatan "keterangan per baris"
    }
```

## Keputusan basis data yang disengaja

| Keputusan | Alasan |
|---|---|
| `CHECK` alih-alih `enum` | Mudah diubah lewat migrasi tanpa menyentuh tipe; nilai tetap dikunci basis data. |
| Indeks unik parsial `siklus_satu_terbuka_per_kandang` (`WHERE status IN (4 status terbuka) AND deleted_at IS NULL`) | Menjaga aturan **satu periode terbuka per kandang** walau ada dua permintaan bersamaan. |
| Indeks unik parsial recording `(siklus_id, hari_ke)` | Menjaga **satu catatan per hari** (R1); data terhapus lunak tidak ikut memblokir. |
| `deplesi_harian` kolom turunan (generated) | Tidak mungkin tidak sinkron dengan `mati + afkir`. |
| Nilai turunan **tidak disimpan** (stok pakan, FCR, IP, deplesi %, selisih RHPP) | Selalu dihitung ulang dari catatan dasar sehingga tidak bisa basi. Lihat [ATURAN-BISNIS.md](ATURAN-BISNIS.md). |
| Soft delete pada data inti | Riwayat tetap ada untuk pembuktian. |
| `rhpp.catatan` bertipe `jsonb` | Keterangan per baris rekonsiliasi fleksibel tanpa tabel tambahan. |
