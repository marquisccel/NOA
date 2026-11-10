@props(['label', 'value', 'caption' => null, 'icon' => 'warehouse'])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 rounded-[16px] border border-line bg-surface p-5']) }}>
    <div class="flex items-center justify-between gap-2">
        <p class="text-[13px] font-semibold text-ink-secondary">{{ $label }}</p>
        <span class="grid size-8 place-items-center rounded-[9px] bg-tint text-ink-brand"><x-icon :name="$icon" class="size-4" /></span>
    </div>
    <p class="font-heading text-[30px] font-semibold leading-none text-ink">{{ $value }}</p>
    @if ($caption)<p class="text-xs text-ink-muted">{{ $caption }}</p>@endif
</div>
