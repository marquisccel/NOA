@props(['name', 'label', 'satuan' => null, 'awalan' => null, 'value' => null, 'hint' => null, 'catatan' => null])

@php
    $kunci = str_replace(['[', ']'], ['.', ''], $name);
    $pesan = $errors->get($kunci);
    $bahasa = ! empty($pesan);
@endphp

{{-- Isian angka dengan satuan (ekor, sak, kg) atau awalan (Rp); teks 16 px dan tinggi sentuh besar untuk dipakai di kandang. --}}
<div>
    <div class="flex items-baseline justify-between gap-3">
        <x-input-label :for="$name" :value="$label" />
        @if ($catatan)<span class="text-xs text-ink-muted">{{ $catatan }}</span>@endif
    </div>
    <div class="relative mt-2 flex items-stretch">
        @if ($awalan)
            <span class="inline-flex min-h-[50px] items-center rounded-l-md border border-r-0 border-line-control bg-page px-3.5 text-base text-ink-secondary">{{ $awalan }}</span>
        @endif
        <input id="{{ $name }}" name="{{ $name }}" value="{{ old($kunci, $value) }}" aria-describedby="{{ $name }}-hint"
            @if ($bahasa) aria-invalid="true" @endif
            {{ $attributes->merge(['type' => 'number', 'class' => 'block min-h-[50px] w-full min-w-0 flex-1 bg-surface px-3.5 text-lg font-semibold text-ink placeholder:font-normal placeholder:text-ink-muted focus:z-10 focus:ring-1 '
                .($awalan ? 'rounded-r-md rounded-l-none ' : ($satuan ? 'rounded-l-md rounded-r-none ' : 'rounded-md '))
                .($bahasa ? 'border-danger focus:border-danger focus:ring-danger' : 'border-line-control focus:border-brand focus:ring-brand')]) }}>
        @if ($satuan)
            <span class="inline-flex min-h-[50px] items-center rounded-r-md border border-l-0 border-line-control bg-page px-3.5 text-base text-ink-secondary">{{ $satuan }}</span>
        @endif
    </div>
    <div id="{{ $name }}-hint">
        @if ($hint && ! $bahasa)<p class="mt-1.5 text-xs text-ink-muted">{{ $hint }}</p>@endif
        <x-input-error class="mt-1.5" :messages="$pesan" />
    </div>
</div>
