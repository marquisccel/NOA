@props(['siklus'])

@php
    use App\Models\Siklus;

    $hari = $siklus->umurHari();
    $punyaPanen = $siklus->panen()->exists();
    $catatHariIni = $siklus->recordings()->where('hari_ke', $hari)->exists();
    $punyaRhpp = $siklus->rhpp()->exists();

    // [judul, penjelasan, label tombol, tujuan, tombol kedua [label, tujuan]|null]
    $langkah = match ($siklus->status) {
        Siklus::STATUS_BERJALAN => $punyaPanen
            ? ['Ayam sudah mulai dipanen', 'Catat setiap tahap panen dengan nomor BTPA. Bila panen terakhir sudah selesai, tandai panen selesai supaya periode menunggu RHPP dari inti.', 'Ke halaman Panen', route('panen.index', $siklus), null]
            : (! $catatHariIni
                ? ['Catatan hari ini belum diisi', 'Isi pakan terpakai, ayam mati, dan ayam afkir hari ini. Cukup tiga isian dan kurang dari satu menit.', 'Isi catatan hari ini', route('recording.create', $siklus), ['Sudah waktunya panen?', route('panen.create', $siklus)]]
                : ['Catatan hari ini sudah terisi', 'Besok isi lagi. Sekitar seminggu sekali, timbang sampel ayam; catat juga setiap kali memberi obat atau vaksin.', 'Timbang sampel', route('sampling.create', $siklus), ['Catat obat/vaksin', route('ovk.create', $siklus)]]),
        Siklus::STATUS_PANEN_SELESAI, Siklus::STATUS_MENUNGGU_RHPP => ['Menunggu RHPP dari inti', 'Saat RHPP terbit, salin angkanya ke NOA. Bila inti tidak menerbitkannya, periode tetap bisa ditutup tanpa RHPP.', 'Input RHPP', route('rhpp.form', $siklus), ['Tutup tanpa RHPP', route('siklus.tutup-tanpa-rhpp', $siklus)]],
        Siklus::STATUS_REKONSILIASI => ['Cocokkan dengan RHPP', 'Lihat baris yang berbeda dengan catatan Anda, tambahkan keterangan bila perlu, cetak lembarnya untuk musyawarah, lalu tutup periode.', 'Lihat perbandingan', route('rhpp.show', $siklus), ['Tutup periode', route('siklus.tutup', $siklus)]],
        default => ['Periode ini sudah selesai', 'Semua data terkunci. Anda bisa mencetak laporan, atau membuka periode baru di kandang yang sama.', 'Lihat laporan', route('laporan.siklus', $siklus), ['Buka periode baru', route('siklus.baru', $siklus->kandang_id)]],
    };
@endphp

<section aria-labelledby="langkah-berikutnya" class="flex flex-wrap items-center justify-between gap-4 rounded-[16px] border border-brand-border bg-tint p-5">
    <div class="min-w-0 flex-1 basis-64 space-y-1">
        <p class="text-[11px] font-bold uppercase tracking-[1.2px] text-ink-brand">Langkah berikutnya</p>
        <h2 id="langkah-berikutnya" class="font-heading text-lg font-semibold">{{ $langkah[0] }}</h2>
        <p class="text-[13px] leading-[19px] text-ink-secondary">{{ $langkah[1] }}</p>
    </div>
    <div class="flex flex-wrap gap-2.5">
        @if ($langkah[4])<x-button variant="secondary" :href="$langkah[4][1]">{{ $langkah[4][0] }}</x-button>@endif
        <x-button :href="$langkah[3]">{{ $langkah[2] }}</x-button>
    </div>
</section>
