@props(['grup', 'drawer' => false])

@php use App\Support\Navigasi; @endphp

{{-- Daftar menu per peran. Di sidebar, label disembunyikan saat sidebar ciut; di drawer ponsel selalu tampil. --}}
<nav aria-label="Menu utama" class="flex flex-col gap-5">
    @foreach ($grup as $g)
        <div class="flex flex-col gap-1" role="group" aria-label="{{ $g['label'] }}">
            <p @class(['px-2.5 pb-1.5 text-[11px] font-bold uppercase tracking-[1.2px] text-ink-muted', 'md:hidden xl:block' => ! $drawer])
               @unless ($drawer) :class="collapsed ? 'xl:!hidden' : ''" @endunless>{{ $g['label'] }}</p>

            @foreach ($g['items'] as $item)
                @php $aktif = Navigasi::aktif($item); @endphp
                <a href="{{ route($item['rute']) }}"
                   title="{{ $item['label'] }}"
                   @if ($aktif) aria-current="page" @endif
                   @class([
                       'flex h-11 items-center gap-3 rounded-[14px] px-2.5 text-sm transition-colors',
                       'bg-gradient-to-r from-brand-soft to-transparent font-bold text-ink-brand ring-1 ring-inset ring-brand-border' => $aktif,
                       'font-medium text-ink hover:bg-state-hover' => ! $aktif,
                       'md:justify-center xl:justify-start' => ! $drawer,
                   ])
                   @unless ($drawer) :class="collapsed ? 'xl:!justify-center' : ''" @endunless>
                    <span @class([
                        'grid size-[30px] shrink-0 place-items-center rounded-[10px]',
                        'bg-gradient-to-br from-brand-mint to-brand text-white shadow-[0_6px_14px_-6px_var(--brand-primary)]' => $aktif,
                        'bg-surface/60 text-ink-secondary ring-1 ring-inset ring-line-subtle' => ! $aktif,
                    ])>
                        <x-icon :name="$item['ikon']" class="size-[17px]" />
                    </span>
                    <span @class(['flex-1 truncate', 'md:hidden xl:inline' => ! $drawer])
                          @unless ($drawer) :class="collapsed ? 'xl:!hidden' : ''" @endunless>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    @endforeach
</nav>
