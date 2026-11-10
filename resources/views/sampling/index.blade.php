@php
    use App\Support\Format as F;

    $hasil = $hasilId ? collect($baris)->first(fn ($b) => $b['sampling']->id === $hasilId) : null;
    $tanda = fn ($n, $d = 1) => $n === null ? '–' : ($n >= 0 ? '+' : '−').F::angka(abs($n), $d);
@endphp

<x-app-layout title="Sampling bobot" :siklus="$siklus"
    :crumbs="[['Kandang & Siklus', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['Sampling']]">
    <x-page>
        <x-page-header title="Riwayat sampling"
            :subtitle="count($baris).' sampling'.($interval ? ' · interval '.$interval.' hari' : '')">
            <x-slot name="actions">
                @if ($bisaCatat)
                    <x-button :href="route('sampling.create', $siklus)"><x-icon name="plus" class="size-4" /> Sampling baru</x-button>
                @endif
            </x-slot>
        </x-page-header>

        @if ($hasil)
            <x-alert variant="success">
                <p class="font-semibold">Sampling hari ke-{{ $hasil['hari_ke'] }} tersimpan</p>
                <p>
                    ABW {{ F::angka($hasil['abw_gram'], 0) }} g
                    @if ($hasil['adg_gram'] !== null)· ADG {{ F::angka($hasil['adg_gram'], 0) }} g/hari @endif
                    @if ($hasil['selisih_std_persen'] !== null)· {{ $tanda($hasil['selisih_std_persen']) }}% {{ $hasil['selisih_std_persen'] >= 0 ? 'di atas' : 'di bawah' }} standar @endif.
                    ABW terakhir di Ringkasan ikut diperbarui.
                </p>
            </x-alert>
        @endif

        @unless ($standarAda)
            <x-alert variant="info">
                Tabel standar belum diunggah, jadi perbandingan ABW dengan standar kosong.
                @if (auth()->user()->isPeternak())<a href="{{ route('siklus.standar', $siklus) }}" class="font-semibold underline">Unggah tabel standar</a>@endif
            </x-alert>
        @endunless

        @if (empty($baris))
            <x-empty-state icon="scale" title="Belum ada sampling bobot"
                text="Timbang minimal 5% populasi hidup (± {{ F::angka(ceil($siklus->populasi * 0.05), 0) }} ekor untuk {{ F::angka($siklus->populasi, 0) }} ekor). ABW, ADG, dan perbandingan dengan standar muncul setelah sampling pertama.">
                @if ($bisaCatat)<x-button :href="route('sampling.create', $siklus)">Sampling pertama</x-button>@endif
            </x-empty-state>
        @else
            <div class="overflow-x-auto rounded-[16px] border border-line bg-surface">
                <table class="tabel min-w-[900px]">
                    <caption class="sr-only">Riwayat sampling bobot, terbaru di atas</caption>
                    <thead>
                        <tr>
                            <th scope="col">Hari</th><th scope="col">Tanggal</th><th scope="col" class="text-right">Sampel</th><th scope="col" class="text-right">% populasi</th>
                            <th scope="col" class="text-right">Total bobot</th><th scope="col" class="text-right">ABW</th><th scope="col" class="text-right">Standar</th>
                            <th scope="col" class="text-right">Selisih</th><th scope="col" class="text-right">ADG</th><th scope="col">Keterangan</th>
                            @if ($bisaCatat)<th scope="col">Aksi</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($baris as $b)
                            @php $s = $b['sampling']; @endphp
                            <tr @class(['!bg-tint' => $hasil && $hasil['sampling']->is($s)])>
                                <th scope="row">{{ $b['hari_ke'] }}</th>
                                <td class="whitespace-nowrap">{{ $s->tanggal->translatedFormat('j M') }}</td>
                                <td class="text-right">{{ F::angka($s->jumlah_sampel, 0) }} ekor</td>
                                <td class="text-right {{ $b['representatif'] ? '' : 'font-semibold text-warning-text' }}">{{ F::angka($b['persen_populasi']) }}%</td>
                                <td class="text-right">{{ F::angka($s->total_bobot_kg, 1) }} kg</td>
                                <td class="text-right font-semibold">{{ F::angka($b['abw_gram'], 0) }} g</td>
                                <td class="text-right text-ink-secondary">{{ $b['std_gram'] === null ? '–' : F::angka($b['std_gram'], 0).' g' }}</td>
                                <td class="text-right font-semibold {{ $b['selisih_std_persen'] === null ? '' : ($b['selisih_std_persen'] >= 0 ? 'text-success-text' : 'text-warning-text') }}">{{ $b['selisih_std_persen'] === null ? '–' : $tanda($b['selisih_std_persen']).'%' }}</td>
                                <td class="text-right">{{ $b['adg_gram'] === null ? '–' : F::angka($b['adg_gram'], 0).' g/hari' }}</td>
                                <td>@if (! $b['representatif'])<x-badge variant="warning">Kurang representatif</x-badge>@else<span class="text-ink-muted">–</span>@endif</td>
                                @if ($bisaCatat)
                                    <td><a href="{{ route('sampling.edit', $s) }}" class="inline-flex min-h-11 items-center font-semibold text-ink-brand hover:underline">Ubah</a></td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-ink-muted">ABW = total bobot ÷ jumlah sampel. ADG dihitung dari sampling sebelumnya. Sampel di bawah 5% populasi hidup ditandai kurang representatif.</p>
        @endif
    </x-page>
</x-app-layout>
