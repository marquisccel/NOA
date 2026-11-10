@props(['name', 'show' => false, 'maxWidth' => 'md'])

@php
    $lebar = ['sm' => 'max-w-sm', 'md' => 'max-w-md', 'lg' => 'max-w-lg', 'xl' => 'max-w-xl'][$maxWidth] ?? 'max-w-md';
@endphp

{{-- Dialog konfirmasi. Dibuka lewat $dispatch('open-modal', 'nama') dan ditutup dengan Escape, klik latar, atau 'close-modal'. --}}
<div x-data="{ show: @js($show) }" x-cloak
     x-on:open-modal.window="$event.detail === '{{ $name }}' ? (show = true, $nextTick(() => $refs.panel.querySelector('[autofocus], button, a')?.focus())) : null"
     x-on:close-modal.window="$event.detail === '{{ $name }}' ? show = false : null"
     x-on:keydown.escape.window="show = false"
     :class="show ? 'block' : 'hidden'"
     class="fixed inset-0 z-50 print:hidden" role="dialog" aria-modal="true" aria-labelledby="{{ $name }}-judul">
    <div class="absolute inset-0 bg-black/45" @click="show = false"></div>
    <div class="absolute inset-0 grid place-items-center p-4">
        <div x-ref="panel" class="relative w-full {{ $lebar }} space-y-4 rounded-lg border border-line bg-surface p-6 shadow-xl">
            {{ $slot }}
        </div>
    </div>
</div>
