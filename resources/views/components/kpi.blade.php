@props(['label', 'value', 'caption' => null, 'icon' => null, 'istilah' => null, 'spark' => null, 'naikBagus' => true, 'nada' => 'merek'])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 kartu p-5']) }}>
    <div class="flex items-center justify-between gap-2">
        <p class="text-[13px] font-semibold text-ink-secondary">@if ($istilah)<x-istilah :k="$istilah">{{ $label }}</x-istilah>@else{{ $label }}@endif</p>
        @if ($icon)<span class="chip-ikon size-9" data-nada="{{ $nada }}"><x-icon :name="$icon" class="size-[18px]" /></span>@endif
    </div>
    <p class="font-heading text-[32px] font-semibold leading-none tracking-[-0.03em] text-ink">{{ $value }}</p>
    @if ($caption)<p class="text-xs text-ink-muted">{{ $caption }}</p>@endif
    @if ($spark && count(array_filter($spark, fn ($n) => $n !== null)) >= 2)
        @php
            $nilai = array_values(array_filter($spark, fn ($n) => $n !== null));
            $min = min($nilai); $maks = max($nilai); $rentang = max($maks - $min, 1e-9);
            $titik = collect($nilai)->map(fn ($n, $i) => round($i / (count($nilai) - 1) * 100, 1).','.round(26 - ($n - $min) / $rentang * 22, 1))->implode(' ');
            $naik = end($nilai) >= $nilai[0];
            $bagus = $naik === $naikBagus;
        @endphp
        <svg viewBox="0 -2 100 32" preserveAspectRatio="none" class="h-8 w-full {{ $bagus ? 'text-success' : 'text-warning' }}" role="img" aria-label="Tren {{ $label }}: {{ $naik ? 'naik' : 'turun' }}">
            <polyline points="{{ $titik }}" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
        </svg>
    @endif
    {{ $slot }}
</div>
