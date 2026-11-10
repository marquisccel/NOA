@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="min-w-0 space-y-1">
        <h1 class="font-heading text-2xl font-semibold text-ink">{{ $title }}</h1>
        @if ($subtitle)<p class="text-sm text-ink-secondary">{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2.5">{{ $actions }}</div>
    @endisset
</div>
