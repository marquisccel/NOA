@props(['compact' => false])

{{-- Logo NOA: tanda (ayam dan daun) tanpa kartu, ditambah wordmark berwarna emas (topeng CSS dari WebP transparan, ikut tema). --}}
<span {{ $attributes->class(['inline-flex items-center gap-2.5']) }}>
    <img src="{{ asset('images/noa-mark.webp') }}" alt="" width="26" height="34" class="h-[34px] w-[26px] shrink-0">
    @unless ($compact)
        <span role="img" aria-label="NOA" class="block h-[22px] w-[62px] bg-gold" style="-webkit-mask: url('{{ asset('images/noa-wordmark.webp') }}') center / contain no-repeat; mask: url('{{ asset('images/noa-wordmark.webp') }}') center / contain no-repeat;"></span>
    @endunless
</span>
