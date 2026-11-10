@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false])

<div>
    <x-input-label :for="$name" :value="$label" class="text-base" />
    <x-text-input :id="$name" :name="$name" :type="$type" :required="$required"
        :value="old(str_replace(['[', ']'], ['.', ''], $name), $value)"
        {{ $attributes->merge(['class' => 'mt-1 block w-full min-h-11 text-base']) }} />
    @if ($hint)
        <p class="mt-1 text-sm text-gray-600">{{ $hint }}</p>
    @endif
    <x-input-error class="mt-2" :messages="$errors->get(str_replace(['[', ']'], ['.', ''], $name))" />
</div>
