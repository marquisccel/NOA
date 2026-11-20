@props(['icon' => 'info', 'title', 'text' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-3 kartu px-6 py-12 text-center']) }}>
    <span class="chip-ikon size-14 !rounded-[18px]" data-nada="merek"><x-icon :name="$icon" class="size-7" /></span>
    <h2 class="font-heading text-lg font-semibold text-ink">{{ $title }}</h2>
    @if ($text)<p class="max-w-md text-sm leading-[21px] text-ink-secondary">{{ $text }}</p>@endif
    @if (trim((string) $slot) !== '')<div class="pt-2">{{ $slot }}</div>@endif
</div>
