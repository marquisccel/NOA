@php
    use App\Support\Format as F;

    $s = $draf['standar'] ?? null;
    $adaFile = $s && ! $s['dilewati'] && $s['nama'];
    $galat = $s['errors'] ?? [];
    $rows = $s['rows'] ?? [];
    $pratinjau = array_slice($rows, 0, 7);
    $koma = fn ($n, $d = 0) => $n === null ? '—' : F::angka($n, $d);
@endphp

<x-wizard-siklus :kandang="$kandang" :langkah="4" :konteks="$konteks">
    <h2 class="text-lg font-bold">Standar harian kemitraan</h2>
    <p class="mt-1 text-[13px] text-ink-secondary">Unggah tabel standar dari kemitraan Anda (disalin ke siklus ini). Dipakai untuk membandingkan catatan harian dengan standar.</p>

    <div class="mt-5 space-y-5">
        <div class="rounded-md border border-dashed border-line-control bg-page p-4">
            <p class="text-[13px] text-ink-secondary">Format: <code class="text-xs">hari, bobot_std_gram, feed_intake_std_gram, deplesi_std_persen</code></p>
            <p class="mt-1 text-xs text-ink-muted">Format PRD (hari_ke, pakan_std_sak, bobot_std_gram, fcr_std, feed_intake_std_gram) juga diterima. Feed intake dicatat kumulatif per ekor.</p>
            <a href="{{ route('siklus.standar.template') }}" class="mt-2 inline-flex min-h-11 items-center gap-1.5 text-[13px] font-semibold text-ink-brand hover:underline"><x-icon name="download" class="size-4" /> Unduh template CSV</a>

            <form method="POST" action="{{ route('siklus.baru.standar', $kandang) }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-end gap-3">
                @csrf
                <div class="min-w-0 flex-1">
                    <x-input-label for="file" :value="$adaFile ? 'Ganti file' : 'File CSV'" />
                    <input id="file" name="file" type="file" accept=".csv,text/csv,text/plain" required class="mt-2 block w-full text-base">
                </div>
                <x-button variant="secondary">{{ $adaFile ? 'Unggah ulang' : 'Unggah' }}</x-button>
            </form>
            @foreach ($errors->get('file') as $pesan)
                <p class="mt-2 text-xs text-danger-text" role="alert">{{ $pesan }}</p>
            @endforeach
        </div>

        @if ($adaFile)
            <section aria-label="Pratinjau standar">
                <p class="flex flex-wrap items-center gap-2 text-[13px]">
                    <x-icon name="file-text" class="size-4 text-ink-brand" /> <strong>{{ $s['nama'] }}</strong>
                    <span class="text-ink-secondary">
                        @if ($galat) {{ $s['jumlah_baris'] }} baris · {{ count($galat) }} bermasalah @else {{ count($rows) }} baris (hari {{ $rows[0]['hari_ke'] }}–{{ end($rows)['hari_ke'] }}) · terbaca @endif
                    </span>
                </p>

                @if ($galat)
                    <x-alert variant="error" class="mt-3">
                        <p class="font-semibold">{{ count($galat) }} masalah pada file. Perbaiki file lalu unggah ulang, atau lewati dan isi nanti.</p>
                        <ul class="mt-1.5 list-disc space-y-0.5 ps-4">@foreach (array_slice($galat, 0, 10) as $g)<li>{{ $g }}</li>@endforeach</ul>
                    </x-alert>
                @endif

                @if ($pratinjau)
                    <p class="mt-3 text-xs text-ink-secondary">Pratinjau ({{ count($pratinjau) }} dari {{ count($rows) }} baris)</p>
                    <div class="mt-1.5 overflow-x-auto rounded-md border border-line">
                        <table class="tabel">
                            <caption class="sr-only">Pratinjau tabel standar</caption>
                            <thead><tr><th scope="col">Hari</th><th scope="col">Bobot standar (g)</th><th scope="col">Feed intake standar (g/ekor)</th><th scope="col">Deplesi standar (%)</th></tr></thead>
                            <tbody>
                                @foreach ($pratinjau as $r)
                                    <tr><th scope="row">{{ $r['hari_ke'] }}</th><td>{{ $koma($r['bobot_std_gram'], 0) }}</td><td>{{ $koma($r['feed_intake_std_gram'], 0) }}</td><td>{{ $koma($r['deplesi_std_persen'] ?? null, 2) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @elseif ($s && $s['dilewati'])
            <x-alert variant="info">Tabel standar dilewati. Kolom standar tampil “–” sampai tabelnya diunggah dari Pengaturan Siklus.</x-alert>
        @endif

        <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-md border border-line-subtle p-4"><p class="text-sm font-bold">Parameter manajemen per rentang umur</p><p class="mt-0.5 text-xs text-ink-secondary">Suhu, kepadatan, frekuensi pakan, TMA/TRA</p><p class="mt-2 text-xs text-ink-muted">Opsional · diatur di Pengaturan Siklus setelah siklus dibuka.</p></div>
            <div class="rounded-md border border-line-subtle p-4"><p class="text-sm font-bold">Jadwal vaksin per hari umur</p><p class="mt-0.5 text-xs text-ink-secondary">Nama vaksin dan hari ke-</p><p class="mt-2 text-xs text-ink-muted">Opsional · diatur di Pengaturan Siklus setelah siklus dibuka.</p></div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line-subtle pt-4">
            <x-button variant="secondary" :href="route('siklus.baru.langkah', [$kandang, 3])">Kembali</x-button>
            <div class="flex flex-wrap gap-2.5">
                <form method="POST" action="{{ route('siklus.baru.lewati', $kandang) }}">@csrf<x-button variant="secondary">Lewati, isi nanti</x-button></form>
                <form method="POST" action="{{ route('siklus.baru.simpan', [$kandang, 4]) }}">@csrf<x-button :disabled="! $adaFile || $galat">Lanjut: Konfirmasi <x-icon name="arrow-right" class="size-4" /></x-button></form>
            </div>
        </div>
    </div>
</x-wizard-siklus>
