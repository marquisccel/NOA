@php
    use App\Support\Format as F;

    $rp = fn ($n) => 'Rp '.F::angka($n, 0);
    $hariKe = fn ($tanggal) => (int) $siklus->tanggal_masuk->copy()->startOfDay()->diffInDays($tanggal->copy()->startOfDay()) + 1;
    $varian = ['obat' => 'info', 'vitamin' => 'success', 'vaksin' => 'brand', 'kimia' => 'neutral'];
@endphp

<x-app-layout title="OVK" :siklus="$siklus"
    :crumbs="[['Kandang & Siklus', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['OVK']]">
    <x-page>
        <x-page-header title="Riwayat OVK"
            :subtitle="$riwayat->count().' pemberian · total '.$rp($totalBiaya)">
            <x-slot name="actions">
                <x-button variant="secondary" :href="route('recording.index', $siklus)">Lihat di Recording</x-button>
                @if ($bisaCatat)
                    <x-button :href="route('ovk.create', $siklus)"><x-icon name="plus" class="size-4" /> Pemberian OVK baru</x-button>
                @endif
            </x-slot>
        </x-page-header>

        @if ($riwayat->isEmpty())
            <x-empty-state icon="syringe" title="Belum ada pemberian OVK"
                text="Catat obat, vitamin, dan vaksin beserta dosis dan biayanya. Biaya OVK ikut dihitung dalam biaya produksi siklus.">
                @if ($bisaCatat)<x-button :href="route('ovk.create', $siklus)">Pemberian OVK pertama</x-button>@endif
            </x-empty-state>
        @else
            <div class="overflow-x-auto rounded-[16px] border border-line bg-surface">
                <table class="tabel min-w-[820px]">
                    <caption class="sr-only">Riwayat pemberian OVK, terbaru di atas</caption>
                    <thead>
                        <tr>
                            <th scope="col">Hari</th><th scope="col">Tanggal</th><th scope="col">Jenis</th><th scope="col">Nama produk</th><th scope="col">Dosis</th>
                            <th scope="col" class="text-right">Biaya</th><th scope="col">Penanda recording</th>@if ($bisaCatat)<th scope="col">Aksi</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($riwayat as $o)
                            @php $tanda = $penanda[$o->id] ?? null; @endphp
                            <tr>
                                <th scope="row">{{ $hariKe($o->tanggal) }}</th>
                                <td class="whitespace-nowrap">{{ $o->tanggal->translatedFormat('j M') }}</td>
                                <td><x-badge :variant="$varian[$o->jenis] ?? 'neutral'">{{ ucfirst($o->jenis) }}</x-badge></td>
                                <td class="font-semibold">{{ $o->nama_produk }}</td>
                                <td class="text-ink-secondary">{{ $o->dosis ?: '–' }}</td>
                                <td class="whitespace-nowrap text-right font-semibold">{{ $rp($o->biaya) }}</td>
                                <td>
                                    @if ($tanda)
                                        <span class="inline-flex items-center gap-1.5 text-[13px] text-ink-secondary"><x-icon name="check" class="size-3.5 text-success-text" /> {{ $tanda['waktu'] }} (hr {{ $tanda['hari'] }})</span>
                                    @else
                                        <span class="text-ink-muted">–</span>
                                    @endif
                                </td>
                                @if ($bisaCatat)
                                    <td><a href="{{ route('ovk.edit', $o) }}" class="inline-flex min-h-11 items-center font-semibold text-ink-brand hover:underline">Ubah</a></td>
                                @endif
                            </tr>
                        @endforeach
                        <tr class="!bg-tint font-bold">
                            <td colspan="2">Total</td>
                            <td colspan="3">{{ $riwayat->count() }} pemberian</td>
                            <td class="whitespace-nowrap text-right">{{ $rp($totalBiaya) }}</td>
                            <td colspan="{{ $bisaCatat ? 2 : 1 }}"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-ink-muted">Centang OVK pagi atau sore di Recording hanya penanda pemberian; detail produk dan biaya dicatat di halaman ini.</p>
        @endif
    </x-page>
</x-app-layout>
