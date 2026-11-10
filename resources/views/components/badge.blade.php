@props(['variant' => 'neutral', 'dot' => false])

@php
    $warna = [
        'success' => 'border-success-border bg-success-soft text-success-text',
        'warning' => 'border-warning-border bg-warning-soft text-warning-text',
        'danger' => 'border-danger-border bg-danger-soft text-danger-text',
        'info' => 'border-info-border bg-info-soft text-info-text',
        'neutral' => 'border-neutral-border bg-neutral-soft text-neutral-text',
        'brand' => 'border-brand-border bg-brand-soft text-ink-brand',
    ][$variant] ?? '';
    $titik = [
        'success' => 'bg-success', 'warning' => 'bg-warning', 'danger' => 'bg-danger',
        'info' => 'bg-info', 'neutral' => 'bg-neutral', 'brand' => 'bg-brand',
    ][$variant] ?? 'bg-neutral';
@endphp

{{-- Status selalu berupa kata (bukan hanya warna), sesuai aksesibilitas PRD. --}}
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full border px-2 py-[3px] text-[11px] font-bold leading-none {$warna}"]) }}>
    @if ($dot)<span class="size-1.5 rounded-full {{ $titik }}"></span>@endif
    {{ $slot }}
</span>
