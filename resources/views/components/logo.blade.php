@props(['compact' => false])

{{-- Logo NOA: tanda (ayam dan daun) di kotak krem, ditambah wordmark. Gambar WebP kecil, bukan PNG sumber 1 MB. --}}
<span {{ $attributes->class(['inline-flex items-center gap-2.5 px-1']) }}>
    <span class="grid size-10 shrink-0 place-items-center rounded-[10px] bg-[#FAF8F0] ring-1 ring-inset ring-line-subtle">
        <img src="{{ asset('images/noa-mark.webp') }}" alt="" width="26" height="34" class="h-[34px] w-[26px]">
    </span>
    @unless ($compact)
        <img src="{{ asset('images/noa-wordmark.webp') }}" alt="NOA" width="62" height="22" class="h-[22px] w-[62px]">
    @endunless
</span>
