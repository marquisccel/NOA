@props(['variant' => 'info'])

@php
    [$warna, $ikon, $peran] = [
        'error' => ['border-danger-border bg-danger-soft text-danger-text', 'circle-alert', 'alert'],
        'warning' => ['border-warning-border bg-warning-soft text-warning-text', 'triangle-alert', 'alert'],
        'success' => ['border-success-border bg-success-soft text-success-text', 'circle-check', 'status'],
        'info' => ['border-info-border bg-info-soft text-info-text', 'info', 'status'],
    ][$variant] ?? ['border-info-border bg-info-soft text-info-text', 'info', 'status'];
@endphp

<div role="{{ $peran }}" {{ $attributes->merge(['class' => "flex gap-2.5 rounded-md border px-3.5 py-3 text-[13px] leading-[19px] {$warna}"]) }}>
    <x-icon :name="$ikon" class="mt-px size-4" />
    <div class="min-w-0 flex-1">{{ $slot }}</div>
</div>
