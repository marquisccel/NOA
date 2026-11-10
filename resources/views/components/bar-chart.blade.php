@props(['judul', 'sub' => null, 'item' => [], 'satuan' => '', 'desimal' => 0, 'kosong' => 'Belum ada data.', 'stdAda' => true])

@php
    use App\Support\Format as F;

    $maks = max(1, ...array_map(fn ($i) => max($i['aktual'] ?? 0, $i['std'] ?? 0), $item ?: [['aktual' => 1]]));
    $tinggi = fn ($n) => $n === null ? 0 : max(2, round($n / $maks * 100));
@endphp

{{-- Grafik batang ringan (tanpa pustaka): standar dan aktual berdampingan, nilainya juga ditulis sebagai angka. --}}
<section class="rounded-[16px] border border-line bg-surface p-5" aria-label="{{ $judul }}">
    <h3 class="text-sm font-bold">{{ $judul }}</h3>
    @if ($sub)<p class="mt-0.5 text-xs text-ink-secondary">{{ $sub }}</p>@endif

    @if (empty($item))
        <p class="mt-6 rounded-md bg-page p-4 text-center text-[13px] text-ink-secondary">{{ $kosong }}</p>
    @else
        <div class="mt-3 flex items-center gap-4 text-xs text-ink-secondary" aria-hidden="true">
            @if ($stdAda)<span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-neutral-border"></span>Standar</span>@endif
            <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-brand"></span>Aktual</span>
        </div>
        <div class="mt-4 flex h-40 items-end gap-3 overflow-x-auto" role="img" aria-label="{{ $judul }}: {{ collect($item)->map(fn ($i) => $i['label'].' aktual '.F::angka($i['aktual'], $desimal).$satuan)->implode(', ') }}">
            @foreach ($item as $i)
                <div class="flex h-full min-w-12 flex-1 flex-col justify-end gap-1.5">
                    <div class="flex flex-1 items-end justify-center gap-1">
                        @if ($stdAda)
                            <div class="flex w-1/2 flex-col items-center justify-end" style="height: {{ $tinggi($i['std']) }}%">
                                @if ($i['std'] !== null)<span class="mb-0.5 text-[10px] text-ink-muted">{{ F::angka($i['std'], $desimal) }}</span>@endif
                                <div class="w-full flex-1 rounded-t bg-neutral-border {{ $i['std'] === null ? 'hidden' : '' }}"></div>
                            </div>
                        @endif
                        <div class="flex w-1/2 flex-col items-center justify-end" style="height: {{ $tinggi($i['aktual']) }}%">
                            @if ($i['aktual'] !== null)<span class="mb-0.5 text-[10px] font-bold text-ink">{{ F::angka($i['aktual'], $desimal) }}</span>@endif
                            <div class="w-full flex-1 rounded-t bg-brand {{ $i['aktual'] === null ? 'hidden' : '' }}"></div>
                        </div>
                    </div>
                    <p class="text-center text-[11px] text-ink-secondary">{{ $i['label'] }}</p>
                </div>
            @endforeach
        </div>
    @endif
    {{ $slot }}
</section>
