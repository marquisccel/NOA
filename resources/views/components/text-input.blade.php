@props(['disabled' => false, 'invalid' => false])

{{-- Teks 16 px (syarat PRD untuk isian di kandang) dan tinggi 46 px sesuai desain. --}}
<input @disabled($disabled) {{ $attributes->merge(['class' => 'block w-full min-h-[46px] rounded-md bg-surface px-3.5 text-base text-ink placeholder:text-ink-muted focus:ring-1 disabled:bg-state-hover '
    .($invalid
        ? 'border-danger focus:border-danger focus:ring-danger'
        : 'border-line-control focus:border-brand focus:ring-brand')]) }}>
