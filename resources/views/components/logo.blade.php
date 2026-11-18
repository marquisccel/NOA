@props(['compact' => false])

{{-- Logo NOA: ayam di dalam cincin dengan daun dan batang pakan (gambar WebP transparan), ditambah wordmark.
     Keduanya dipakai sebagai topeng CSS sehingga warnanya ikut tema: hijau tua di tema terang, hijau mint di tema gelap. --}}
@php
    $topeng = fn (string $berkas) => "-webkit-mask: url('".asset('images/'.$berkas)."') center / contain no-repeat; mask: url('".asset('images/'.$berkas)."') center / contain no-repeat;";
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-2.5']) }}>
    <span role="img" aria-label="Logo NOA" class="block size-10 shrink-0 bg-[#0b5e2f] dark:bg-brand-mint" style="{{ $topeng('noa-mark.webp') }}"></span>
    @unless ($compact)
        <span role="img" aria-label="NOA" class="block h-[22px] w-[62px] bg-[#0b5e2f] dark:bg-brand-mint" style="{{ $topeng('noa-wordmark.webp') }}"></span>
    @endunless
</span>
