@php
    use App\Support\Format as F;

    $rp = fn ($n) => ($n < 0 ? '−' : '+').'Rp '.F::angka(abs($n), 0);
    $nilai = fn ($kolom) => old($kolom, $rhpp?->{$kolom});
    $kolomRhpp = [
        'total_pakan_kg' => ['Total pakan ditagih (kg)', 'decimal', '0.01', 'Tagihan sapronak: seluruh pakan yang dikirim inti.'],
        'pakan_retur_sak' => ['Pakan retur (sak)', 'decimal', '0.01', null],
        'total_ekor' => ['Total ekor panen', 'numeric', '1', null],
        'total_tonase' => ['Total tonase panen (kg)', 'decimal', '0.01', null],
        'abw' => ['Bobot rata-rata / ABW (kg)', 'decimal', '0.001', null],
        'umur_panen' => ['Umur panen rata-rata (hari)', 'decimal', '0.01', null],
        'deplesi_persen' => ['Deplesi (%)', 'decimal', '0.01', null],
        'fcr' => ['FCR', 'decimal', '0.001', null],
        'ip' => ['Indeks Performa (IP)', 'decimal', '0.01', null],
        'pendapatan' => ['Pendapatan menurut RHPP (Rp)', 'decimal', '0.01', 'Opsional; hanya untuk perbandingan informatif.'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rekonsiliasi RHPP · {{ $siklus->kandang->nama_farm }}</h2>
                <p class="mt-1 text-base text-gray-700">
                    Masuk {{ $siklus->tanggal_masuk->translatedFormat('j M Y') }} · {{ $siklus->nama_inti ?: 'inti belum diisi' }}
                    · <a href="{{ route('siklus.show', $siklus) }}" class="text-indigo-700 underline">Detail siklus</a>
                </p>
            </div>
            @if ($rhpp)
                <a href="{{ route('rhpp.cetak', $siklus) }}" target="_blank" rel="noopener" class="inline-flex items-center min-h-11 px-4 py-2 bg-gray-800 rounded-md font-semibold text-sm text-white hover:bg-gray-700">Cetak lembar perbandingan</a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (! $rhpp)
                <p class="rounded-md bg-gray-50 border border-gray-200 p-4 text-base text-gray-800">
                    RHPP belum dimasukkan. Bila inti belum atau tidak menerbitkannya, siklus tetap bisa ditutup dan kolom rekonsiliasi dibiarkan kosong.
                    Ketik angka dari lembar RHPP apa adanya di bawah; NOA tidak mengisinya dari catatan Anda.
                </p>
            @else
                <div class="rounded-md border p-4 text-base {{ $r['jumlah_melebihi'] === 0 ? 'bg-green-50 border-green-300 text-green-900' : 'bg-amber-50 border-amber-400 text-amber-900' }}" role="status">
                    @if ($r['jumlah_melebihi'] === 0)
                        <strong>Tidak ada baris yang melebihi ambang selisih wajar.</strong>
                    @else
                        <strong>{{ $r['jumlah_melebihi'] }} baris melebihi ambang selisih wajar</strong> dan ditandai di bawah.
                    @endif
                    @if ($r['total_dampak_rp'] !== null)
                        Dampak rupiah yang bisa dihitung dari kontrak: <strong>{{ $rp($r['total_dampak_rp']) }}</strong>
                        ({{ $r['total_dampak_rp'] < 0 ? 'merugikan Anda' : 'menguntungkan Anda' }}).
                    @endif
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-4 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-lg font-semibold text-gray-900">Perbandingan baris demi baris</h3>
                        <p class="text-base text-gray-700">RHPP terbit {{ $rhpp->tanggal_terbit->translatedFormat('j M Y') }}</p>
                    </div>
                    <div class="mt-4 overflow-x-auto">
                        @include('rhpp._tabel')
                    </div>
                    <p class="mt-4 text-sm text-gray-600">
                        Dampak rupiah dihitung dari kontrak siklus untuk tagihan pakan, kredit retur, dan tonase.
                        Bonus FCR, deplesi, dan IP bergantung pada tabel bonus inti yang belum ada di NOA, jadi hanya ditandai.
                    </p>
                </div>
            @endif

            <section>
                <h3 class="mb-3 text-lg font-semibold text-gray-900">{{ $rhpp ? 'Ubah angka RHPP' : 'Masukkan angka RHPP' }}</h3>
                <form method="POST" action="{{ route('rhpp.simpan', $siklus) }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                    @csrf @method('PUT')
                    <x-field name="tanggal_terbit" label="Tanggal RHPP terbit" type="date" :value="$rhpp?->tanggal_terbit?->toDateString()"
                             min="{{ $siklus->tanggal_masuk->toDateString() }}" max="{{ now()->toDateString() }}" required />
                    <div class="grid gap-6 sm:grid-cols-2">
                        @foreach ($kolomRhpp as $nama => [$label, $mode, $step, $hint])
                            <x-field :name="$nama" :label="$label" type="number" :step="$step" min="0" :inputmode="$mode" :value="$nilai($nama)" :hint="$hint" />
                        @endforeach
                    </div>
                    <p class="text-sm text-gray-600">Kolom yang tidak dicetak inti boleh dikosongkan; barisnya ditandai belum bisa dibandingkan.</p>
                    <x-primary-button class="min-h-11 text-sm">Simpan RHPP</x-primary-button>
                </form>
                @if ($rhpp)
                    <form method="POST" action="{{ route('rhpp.hapus', $siklus) }}" class="mt-3" onsubmit="return confirm('Hapus angka RHPP? Kolom rekonsiliasi dikosongkan.')">
                        @csrf @method('DELETE')
                        <button class="min-h-11 text-base text-red-700 underline">Hapus angka RHPP</button>
                    </form>
                @endif
            </section>

            <section class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Ambang selisih wajar</h3>
                <p class="mt-1 text-base text-gray-700">
                    Selisih mutlak yang masih dianggap wajar (pembulatan, selisih timbang kecil). Di atas ambang, baris ditandai.
                    Angka bawaan adalah perkiraan awal, bukan hasil wawancara lapangan; sesuaikan dengan kebiasaan inti Anda.
                </p>
                <form method="POST" action="{{ route('rhpp.ambang', $siklus) }}" class="mt-4">
                    @csrf @method('PATCH')
                    <div class="grid gap-6 sm:grid-cols-2">
                        @foreach ($baris as $kunci => [$label, $satuan, $desimal])
                            <x-field :name="'ambang['.$kunci.']'" :label="$label.($satuan ? ' ('.$satuan.')' : '')" type="number" step="any" min="0" inputmode="decimal"
                                     :value="$ambang[$kunci]" :hint="'Bawaan: '.F::angka($ambangBawaan[$kunci], $desimal + 1)" />
                        @endforeach
                    </div>
                    <x-secondary-button type="submit" class="mt-4 min-h-11">Simpan ambang</x-secondary-button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
