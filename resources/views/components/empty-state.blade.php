@props(['icon' => 'info', 'title', 'text' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-3 rounded-[16px] border border-line bg-surface px-6 py-12 text-center']) }}>
    <span class="grid size-12 place-items-center rounded-full bg-tint text-ink-brand"><x-icon :name="$icon" class="size-6" /></span>
    <h2 class="font-heading text-lg font-semibold text-ink">{{ $title }}</h2>
    @if ($text)<p class="max-w-md text-sm leading-[21px] text-ink-secondary">{{ $text }}</p>@endif
    @if (trim((string) $slot) !== '')<div class="pt-2">{{ $slot }}</div>@endif
</div>
