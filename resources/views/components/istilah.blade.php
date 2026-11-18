@props(['k'])

@php
    $data = config('istilah.'.$k);
@endphp

{{-- Istilah teknis dengan tanda tanya: ketuk untuk melihat arti dalam bahasa sehari-hari. Penjelasan lengkap ada di halaman Bantuan. --}}
@if ($data)
    <span class="relative inline-flex items-center gap-1" x-data="{ buka: false }" @keydown.escape="buka = false" @click.outside="buka = false">
        <span>{{ $slot }}</span>
        <button type="button" @click.stop="buka = ! buka" :aria-expanded="buka.toString()" aria-label="Apa arti {{ $data[0] }}?"
            class="inline-grid size-5 shrink-0 place-items-center rounded-full border border-line-control text-[11px] font-bold text-ink-muted hover:bg-state-hover">?</button>
        <span x-cloak :class="buka ? 'block' : 'hidden'" role="tooltip" class="absolute left-0 top-full z-30 mt-1.5 w-64 space-y-1 rounded-md border border-line bg-surface p-3 text-left text-xs font-normal normal-case leading-[18px] tracking-normal text-ink shadow-lg">
            <strong class="block text-[13px]">{{ $data[0] }} — {{ $data[1] }}</strong>
            <span class="block text-ink-secondary">{{ $data[2] }}</span>
            @if ($data[3])<span class="block text-ink-secondary">{{ $data[3] }}</span>@endif
            <a href="{{ route('bantuan') }}#{{ $k }}" class="inline-flex min-h-8 items-center font-semibold text-ink-brand underline">Lihat semua istilah</a>
        </span>
    </span>
@else
    {{ $slot }}
@endif
