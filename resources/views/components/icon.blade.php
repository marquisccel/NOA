@props(['name'])

@php
    $inner = (require resource_path('icons/lucide.php'))[$name] ?? '';
    // Ukuran bawaan 20 px hanya dipakai bila pemanggil tidak menentukan kelas sendiri.
    $kelas = $attributes->get('class');
@endphp

<svg {{ $attributes->except('class')->merge([
    'xmlns' => 'http://www.w3.org/2000/svg',
    'viewBox' => '0 0 24 24',
    'fill' => 'none',
    'stroke' => 'currentColor',
    'stroke-width' => '2',
    'stroke-linecap' => 'round',
    'stroke-linejoin' => 'round',
    'aria-hidden' => 'true',
    'class' => $kelas ? "shrink-0 {$kelas}" : 'size-5 shrink-0',
]) }}>{!! $inner !!}</svg>
