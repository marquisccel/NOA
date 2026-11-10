@php
    $bisaCatat = $bisaCatat ?? false;
    $cetak = $cetak ?? false;
    $penanda = [
        'sama' => ['success', 'Sama', 'Sama'],
        'berbeda' => ['warning', 'Berbeda', 'Beda'],
        'tidak' => ['neutral', 'Tidak dibandingkan', '–'],
    ];
@endphp

{{-- Tabel rekonsiliasi: catatan peternak vs RHPP inti. Selisih = RHPP − peternak. --}}
<div class="{{ $cetak ? '' : 'overflow-x-auto rounded-[16px] border border-line bg-surface' }}">
    <table class="{{ $cetak ? 'w-full border-collapse text-[11px]' : 'tabel min-w-[960px]' }}">
        <caption class="sr-only">Perbandingan catatan peternak dengan RHPP inti</caption>
        <thead>
            <tr @if ($cetak) class="border-b border-black text-left" @endif>
                <th scope="col">{{ $cetak ? 'Baris' : 'Baris' }}</th>
                <th scope="col">{{ $cetak ? 'Catatan peternak' : 'Catatan peternak' }}</th>
                <th scope="col">RHPP inti</th>
                <th scope="col">Selisih{{ $cetak ? '' : ' (RHPP − peternak)' }}</th>
                <th scope="col">{{ $cetak ? 'Ket.' : 'Penanda' }}</th>
                <th scope="col">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($r['baris'] as $b)
                @php
                    $p = $b['status'] ? $penanda[$b['status']] : null;
                    $boleh = $bisaCatat && $b['status'] === 'berbeda';
                @endphp
                <tr @if ($cetak) class="border-b border-gray-400 align-top" @endif>
                    <th scope="row" class="{{ $cetak ? 'py-1 pr-2 text-left font-semibold' : '' }}">{{ $b['label'] }}</th>
                    <td class="{{ $cetak ? 'py-1 pr-2' : '' }}">{{ $b['peternak'] ?? '–' }}</td>
                    <td class="{{ $cetak ? 'py-1 pr-2' : '' }}">{{ $b['rhpp'] ?? '–' }}</td>
                    <td class="{{ $cetak ? 'py-1 pr-2' : '' }} font-semibold">{{ $b['selisih'] ?? '–' }}</td>
                    <td class="{{ $cetak ? 'py-1 pr-2' : '' }}">
                        @if ($p)
                            @if ($cetak)<span class="font-semibold">{{ $p[2] }}</span>
                            @else<x-badge :variant="$p[0]">{{ $p[1] }}</x-badge>@endif
                        @else
                            <span class="text-ink-muted">–</span>
                        @endif
                    </td>
                    <td class="{{ $cetak ? 'py-1' : 'min-w-64 max-w-sm' }}">
                        @if ($boleh)
                            <div :class="ubah ? 'hidden' : ''">
                                @if (filled($b['catatan']))
                                    <span class="text-[13px] text-ink-secondary">{{ $b['catatan'] }}</span>
                                    <button type="button" @click="ubah = true" class="ml-1 inline-flex min-h-11 items-center text-[13px] font-semibold text-ink-brand hover:underline">Ubah</button>
                                @else
                                    <button type="button" @click="ubah = true" class="inline-flex min-h-11 items-center text-[13px] font-semibold text-ink-brand hover:underline">+ Tambah catatan</button>
                                @endif
                            </div>
                            <div x-cloak :class="ubah ? '' : 'hidden'">
                                <label for="catatan-{{ $b['kunci'] }}" class="sr-only">Keterangan {{ $b['label'] }}</label>
                                <textarea id="catatan-{{ $b['kunci'] }}" name="catatan[{{ $b['kunci'] }}]" rows="2" maxlength="300" placeholder="Tulis keterangan selisih"
                                    class="block w-full rounded-md border-line-control bg-surface px-3 py-2 text-base focus:border-brand focus:ring-1 focus:ring-brand">{{ old('catatan.'.$b['kunci'], $b['catatan']) }}</textarea>
                            </div>
                        @else
                            <span class="{{ $cetak ? '' : 'text-[13px] text-ink-secondary' }}">{{ filled($b['catatan']) ? $b['catatan'] : '–' }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
