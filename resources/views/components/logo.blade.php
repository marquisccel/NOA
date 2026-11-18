@props(['compact' => false, 'ukuran' => 36, 'animasi' => false])

@php
    // ID gradien unik per pemakaian supaya beberapa logo di satu halaman tidak saling menimpa.
    $id = 'lg'.substr(md5(uniqid('', true)), 0, 6);
@endphp

{{-- Logo NOA: tanda N dari tiga garis naik (data tumbuh) dengan titik emas (telur/matahari) di kotak hijau,
     lalu wordmark berupa teks sehingga tajam di semua ukuran dan warnanya mengikuti konteks (currentColor). --}}
<span {{ $attributes->class(['inline-flex items-center gap-2.5']) }}>
    <svg viewBox="0 0 40 40" width="{{ $ukuran }}" height="{{ $ukuran }}" class="shrink-0 drop-shadow-sm" role="img" aria-label="Logo NOA" @if ($animasi) data-logo-animasi @endif>
        <defs>
            <linearGradient id="{{ $id }}-a" x1="4" y1="2" x2="36" y2="40" gradientUnits="userSpaceOnUse">
                <stop offset="0" stop-color="#3fae92" />
                <stop offset="1" stop-color="#16503f" />
            </linearGradient>
            <linearGradient id="{{ $id }}-b" x1="0" y1="0" x2="0" y2="22" gradientUnits="userSpaceOnUse">
                <stop offset="0" stop-color="#fff" stop-opacity=".28" />
                <stop offset="1" stop-color="#fff" stop-opacity="0" />
            </linearGradient>
            <radialGradient id="{{ $id }}-c" cx="50%" cy="40%" r="60%">
                <stop offset="0" stop-color="#ffe29a" />
                <stop offset="1" stop-color="#e0a92e" />
            </radialGradient>
        </defs>
        <rect width="40" height="40" rx="11" fill="url(#{{ $id }}-a)" />
        <rect width="40" height="22" rx="11" fill="url(#{{ $id }}-b)" />
        <path d="M11.5 28.5V13.5L28.5 28.5V19" fill="none" stroke="#fff" stroke-width="4.2" stroke-linecap="round" stroke-linejoin="round" pathLength="100" data-garis />
        <circle cx="28.5" cy="11.5" r="3.4" fill="url(#{{ $id }}-c)" data-titik />
    </svg>
    @unless ($compact)
        <span class="font-heading text-[22px] font-bold leading-none tracking-[-0.03em]" aria-hidden="true">NOA</span>
    @endunless
</span>
