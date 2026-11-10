@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'required' => false,
    'icon' => null,
    'aux' => null,
    'revealable' => null,
    'senyap' => false,
    'bag' => 'default',
])

@php
    $kunci = str_replace(['[', ']'], ['.', ''], $name);
    $pesan = $errors->getBag($bag)->get($kunci);
    $kataSandi = $type === 'password';
    $bisaDilihat = $revealable ?? $kataSandi;
    $idHint = $name.'-hint';
    // Dihitung di sini: ekspresi larik di dalam tag komponen membingungkan kompilator Blade.
    $tambahan = $bisaDilihat && $kataSandi ? ['x-bind:type' => "tampil ? 'text' : 'password'"] : [];
    $kelasInput = [$icon ? 'pl-10' : '', $bisaDilihat ? 'pr-11' : ''];
@endphp

<div @if ($bisaDilihat) x-data="{ tampil: false }" @endif>
    <div class="flex items-center justify-between gap-3">
        <x-input-label :for="$name" :value="$label" />
        {{ $aux }}
    </div>

    <div class="relative mt-2">
        @if ($icon)
            <x-icon :name="$icon" class="pointer-events-none absolute left-3.5 top-1/2 size-[18px] -translate-y-1/2 text-ink-muted" />
        @endif

        <x-text-input :id="$name" :name="$name" :type="$type" :required="$required" :invalid="! empty($pesan)"
            :value="old($kunci, $value)"
            aria-describedby="{{ $idHint }}"
            :aria-invalid="! empty($pesan) ? 'true' : null"
            {{ $attributes->merge($tambahan)->class($kelasInput) }} />

        @if ($bisaDilihat && $kataSandi)
            <button type="button" x-on:click="tampil = ! tampil" x-bind:aria-pressed="tampil.toString()"
                class="absolute right-1 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-md text-ink-muted hover:text-ink"
                aria-label="Tampilkan atau sembunyikan kata sandi">
                <x-icon name="eye" x-show="! tampil" class="size-[18px]" />
                <x-icon name="eye-off" x-show="tampil" x-cloak class="size-[18px]" />
            </button>
        @endif
    </div>

    <div id="{{ $idHint }}">
        @if ($hint && empty($pesan))
            <p class="mt-1.5 text-xs text-ink-muted">{{ $hint }}</p>
        @endif
        @unless ($senyap)
            <x-input-error class="mt-1.5" :messages="$pesan" />
        @endunless
    </div>
</div>
