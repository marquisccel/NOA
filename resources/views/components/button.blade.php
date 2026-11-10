@props(['variant' => 'primary', 'href' => null, 'type' => 'submit', 'loading' => false])

@php
    $varian = [
        'primary' => 'bg-brand text-ink-on-primary hover:bg-brand-hover active:bg-brand-active',
        'secondary' => 'border border-line-control bg-surface text-ink hover:bg-state-hover active:bg-state-pressed',
        'ghost' => 'text-ink-brand hover:bg-state-hover active:bg-state-pressed',
        'danger' => 'bg-danger-text text-white hover:opacity-90 active:opacity-100',
    ][$variant] ?? '';

    $kelas = 'inline-flex min-h-[46px] items-center justify-center gap-2 rounded-md px-[18px] text-sm font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-60 '.$varian;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $kelas]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $kelas]) }}>{{ $slot }}</button>
@endif
