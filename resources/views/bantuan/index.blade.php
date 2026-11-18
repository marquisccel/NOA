@php
    $langkah = [
        ['Daftarkan kandang', 'Menu Kandang & Siklus → Tambah Kandang. Isi nama, alamat, kapasitas, dan jenis kandang.'],
        ['Buka siklus baru', 'Di halaman kandang, pilih Buka siklus baru. Isi tanggal DOC masuk, jumlah ayam, dan harga dari kontrak. Tabel target harian dari inti boleh dilewati dulu.'],
        ['Catat setiap hari', 'Di tab Recording, isi pakan terpakai, ayam mati, dan ayam afkir. Cukup tiga isian dan kurang dari satu menit. ABK Anda juga bisa mengisinya dari akun sendiri.'],
        ['Timbang sampel dan catat obat/vaksin', 'Sekitar seminggu sekali timbang sampel ayam (tab Sampling). Setiap kali memberi obat atau vaksin, catat di tab OVK.'],
        ['Catat panen', 'Setiap tahap panen dicatat di tab Panen lengkap dengan nomor BTPA. Setelah panen terakhir, tekan Tandai panen selesai.'],
        ['Masukkan RHPP dan cocokkan', 'Saat RHPP terbit dari inti, salin angkanya di tab RHPP. NOA menunjukkan baris yang sama dan yang berbeda dengan catatan Anda. Cetak lembarnya untuk musyawarah.'],
        ['Tutup siklus', 'Setelah selesai, tutup siklus. Semua data dikunci dan laporan tetap bisa dicetak. Kandang siap dipakai untuk siklus berikutnya.'],
    ];
@endphp

<x-app-layout title="Bantuan">
    <x-page lebar="max-w-3xl">
        <x-page-header title="Bantuan" subtitle="Alur satu siklus dan arti istilah yang dipakai di NOA.">
            <x-slot name="actions">
                <x-button variant="secondary" href="{{ route('dashboard') }}" x-data @click.prevent="try { localStorage.removeItem('noa.tur.selesai') } catch (e) {} ; window.location = '{{ route('dashboard') }}'"><x-icon name="play" class="size-4" /> Ulangi tur panduan</x-button>
            </x-slot>
        </x-page-header>

        <section aria-labelledby="alur" class="space-y-3">
            <h2 id="alur" class="text-base font-bold">Alur satu siklus, langkah demi langkah</h2>
            <ol class="space-y-2.5">
                @foreach ($langkah as $i => [$judul, $isi])
                    <li class="flex gap-3.5 rounded-[16px] border border-line bg-surface p-4">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand text-sm font-bold text-white" aria-hidden="true">{{ $i + 1 }}</span>
                        <div><p class="text-sm font-bold">{{ $judul }}</p><p class="mt-0.5 text-[13px] leading-[19px] text-ink-secondary">{{ $isi }}</p></div>
                    </li>
                @endforeach
            </ol>
            <p class="text-[13px] text-ink-secondary">Di setiap siklus, kartu <strong>“Langkah berikutnya”</strong> di halaman Ringkasan menunjukkan apa yang sebaiknya dilakukan sekarang.</p>
        </section>

        <section aria-labelledby="istilah" class="space-y-3">
            <h2 id="istilah" class="text-base font-bold">Arti istilah</h2>
            <dl class="divide-y divide-line-subtle overflow-hidden rounded-[16px] border border-line bg-surface">
                @foreach ($istilah as $kunci => [$nama, $lengkap, $arti, $baca])
                    <div id="{{ $kunci }}" class="scroll-mt-24 space-y-1 px-5 py-4 target:bg-tint">
                        <dt class="text-sm font-bold">{{ $nama }} <span class="font-normal text-ink-secondary">— {{ $lengkap }}</span></dt>
                        <dd class="text-[13px] leading-[19px] text-ink-secondary">{{ $arti }}@if ($baca) <span class="block text-ink">{{ $baca }}</span>@endif</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </x-page>
</x-app-layout>
