@php
    use App\Support\Format as F;

    $i = $draf['identitas'] ?? [];
    $k = $draf['kontrak'] ?? [];
    $b = $draf['bonus'] ?? [];
    $s = $draf['standar'] ?? [];
    $tgl = fn ($t) => $t ? \Illuminate\Support\Carbon::parse($t)->translatedFormat('j M Y') : '–';
    $rp = fn ($n, $satuan = null) => $n === null || $n === '' ? '–' : 'Rp '.F::angka($n, 0).($satuan ? ' / '.$satuan : '');
    $bonus = ['fcr' => 'Bonus FCR', 'deplesi' => 'Bonus deplesi', 'ip' => 'Bonus IP', 'harga_pasar' => 'Bonus harga pasar'];
    $bagian = fn ($judul, $no) => compact('judul', 'no');
@endphp

<x-wizard-siklus :kandang="$kandang" :langkah="5" :konteks="$konteks">
    <h2 class="text-lg font-bold">Konfirmasi</h2>
    <p class="mt-1 text-[13px] text-ink-secondary">Periksa kembali sebelum membuka siklus. Data dapat diubah nanti di tab Pengaturan Siklus.</p>

    <div class="mt-5 space-y-4">
        @foreach ([
            [1, 'Identitas & DOC', [
                ['DOC masuk', $tgl($i['tanggal_masuk'] ?? null)],
                ['Populasi awal', F::angka($i['populasi'] ?? 0, 0).' ekor'],
                ['Strain · pakan', ($i['strain_doc'] ?? '–').' · '.($i['jenis_pakan'] ?? '–')],
                ['PPL · inti', ($i['nama_ppl'] ?? '–').' · '.($i['nama_inti'] ?? '–')],
                ['Bobot per sak', rtrim(rtrim(number_format((float) ($i['bobot_sak_kg'] ?? 0), 2, ',', '.'), '0'), ',').' kg'],
            ]],
            [2, 'Kontrak', [
                ['Harga DOC', $rp($k['harga_doc'] ?? null, 'ekor')],
                ['Harga pakan', $rp($k['harga_pakan_sak'] ?? null, 'sak')],
                ['Harga OVK', $rp($k['harga_ovk'] ?? null)],
                ['Kontrak ayam', $rp($k['harga_kontrak_ayam'] ?? null, 'kg')],
            ]],
            [3, 'Skema bonus', collect($bonus)->map(fn ($label, $j) => [$label, ($b['bonus_'.$j] ?? false) ? 'Aktif · '.$rp($b['nilai_bonus_'.$j] ?? null, 'kg') : 'Nonaktif'])->values()->all()],
            [4, 'Standar harian', [
                ['Tabel standar', ($s['dilewati'] ?? false) ? 'Belum ada — dilewati' : count($s['rows'] ?? []).' baris ('.($s['nama'] ?? '').')'],
                ['Dampak', ($s['dilewati'] ?? false) ? 'Kolom standar tampil “–” sampai diunggah' : 'Kolom standar terisi di semua layar siklus'],
            ]],
        ] as [$no, $judul, $baris])
            <section class="rounded-md border border-line-subtle p-4">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-bold">{{ $judul }}</h3>
                    <a href="{{ route('siklus.baru.langkah', [$kandang, $no]) }}" class="inline-flex min-h-11 items-center text-[13px] font-semibold text-ink-brand hover:underline">Ubah · Langkah {{ $no }}</a>
                </div>
                <dl class="mt-2 grid gap-x-6 gap-y-2.5 text-[13px] sm:grid-cols-2">
                    @foreach ($baris as [$label, $nilai])
                        <div><dt class="text-ink-muted">{{ $label }}</dt><dd class="mt-0.5 font-semibold">{{ $nilai }}</dd></div>
                    @endforeach
                </dl>
            </section>
        @endforeach

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line-subtle pt-4">
            <x-button variant="secondary" :href="route('siklus.baru.langkah', [$kandang, 4])">Kembali</x-button>
            <form method="POST" action="{{ route('siklus.baru.buka', $kandang) }}" x-data="{ memproses: false }" @submit="memproses = true">
                @csrf
                <x-button x-bind:disabled="memproses">Buka Siklus</x-button>
            </form>
        </div>
    </div>
</x-wizard-siklus>
