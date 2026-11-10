@props(['value' => null])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold text-ink']) }}>
    {{ $value ?? $slot }}
</label>
