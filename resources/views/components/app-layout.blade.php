@props(['title' => null, 'crumbs' => null, 'header' => null, 'siklus' => null])

@php
    use App\Support\Navigasi;
    use Illuminate\Support\Str;

    $user = auth()->user();
    $grup = Navigasi::grup($user);
    $bawah = Navigasi::bawah($user);

    // Halaman lama masih memberi judul lewat slot header; ambil teks h2-nya untuk topbar sampai halamannya diperbarui.
    $judul = $title;
    if (! $judul && $header && preg_match('/<h2[^>]*>(.*?)<\/h2>/s', (string) $header, $m)) {
        $judul = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($m[1]))), 80);
    }
    $remah = $crumbs ?? [['Beranda', route('dashboard')], [$judul ?? 'Halaman']];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $judul ? $judul.' · NOA' : 'NOA' }}</title>
        <link rel="icon" href="{{ asset('images/noa-mark.webp') }}" type="image/webp">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-page font-sans text-ink antialiased print:bg-white" x-data="shell()" @keydown.escape.window="drawer = false">
        <div class="flex min-h-screen flex-col">
            {{-- Topbar desktop dan tablet --}}
            <header class="hidden h-[68px] shrink-0 items-center justify-between border-b border-line bg-surface pl-[26px] pr-6 md:flex print:hidden">
                <div class="flex min-w-0 items-center gap-6">
                    <a href="{{ route('dashboard') }}" aria-label="NOA, ke dashboard"><x-logo /></a>
                    <span class="h-7 w-px bg-line" aria-hidden="true"></span>
                    <div class="min-w-0">
                        <p class="truncate font-heading text-xl font-semibold leading-snug text-ink">{{ $judul ?? 'NOA' }}</p>
                    </div>
                </div>

                {{-- Jam dan tanggal (langsung menempel di topbar, tanpa kartu) --}}
                <div x-data="jam(@js(config('app.timezone')))" class="shrink-0 text-center leading-tight" role="timer" aria-label="Waktu sekarang">
                    <p class="font-heading text-lg font-bold tabular-nums text-ink" x-text="waktu">{{ now()->format('H.i.s') }}</p>
                    <p class="text-xs text-ink-secondary" x-text="tanggal">{{ now()->translatedFormat('D, j M') }}</p>
                </div>
            </header>

            {{-- Topbar ponsel --}}
            <header class="flex h-[60px] shrink-0 items-center gap-2.5 border-b border-line bg-surface px-3 md:hidden print:hidden">
                <button type="button" @click="drawer = true" aria-label="Buka menu" aria-controls="drawer-menu"
                    class="grid size-11 shrink-0 place-items-center rounded-md hover:bg-state-hover">
                    <x-icon name="menu" />
                </button>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-heading text-lg font-semibold leading-snug">{{ $judul ?? 'NOA' }}</p>
                </div>
                <div x-data="jam(@js(config('app.timezone')))" class="shrink-0 pr-1 text-center leading-tight" role="timer" aria-label="Waktu sekarang">
                    <p class="font-heading text-sm font-bold tabular-nums" x-text="waktu">{{ now()->format('H.i.s') }}</p>
                    <p class="text-[11px] text-ink-secondary" x-text="tanggal">{{ now()->translatedFormat('D, j M') }}</p>
                </div>
            </header>

            <div class="flex min-h-0 flex-1 gap-5 md:p-5 lg:gap-6 lg:p-6 print:p-0">
                {{-- Sidebar: penuh di desktop, ikon saja di tablet atau bila diciutkan --}}
                @if ($grup)
                    <aside class="sticky top-6 hidden h-[calc(100vh-68px-3rem)] shrink-0 flex-col justify-between self-start rounded-lg border border-line bg-surface p-3 md:flex md:w-[68px] xl:p-3.5 print:hidden"
                           :class="collapsed ? 'xl:w-[68px]' : 'xl:w-64'">
                        <x-shell-menu :grup="$grup" />
                        <div class="space-y-1">
                            <div class="hidden items-center justify-between gap-2 border-t border-line-subtle px-3 pt-3 xl:flex" :class="collapsed ? '!hidden' : ''">
                                <div class="min-w-0">
                                    <p class="truncate text-[13px] font-semibold text-ink">{{ $user->name }}</p>
                                    <p class="text-xs text-ink-secondary">{{ $user->labelPeran() }}</p>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" aria-label="Keluar" class="flex h-11 w-full items-center justify-center gap-2.5 rounded-md px-3 text-[13px] font-medium text-danger-text hover:bg-state-hover xl:justify-start" :class="collapsed ? 'xl:!justify-center' : ''">
                                    <x-icon name="log-out" class="size-[18px]" />
                                    <span class="hidden xl:inline" :class="collapsed ? '!hidden' : ''">Keluar</span>
                                </button>
                            </form>
                        <button type="button" @click="alternatif()" :aria-label="collapsed ? 'Perluas sidebar' : 'Ciutkan sidebar'"
                            class="hidden h-11 items-center gap-2.5 rounded-md border-t border-line-subtle px-3 text-[13px] font-medium text-ink-muted hover:bg-state-hover xl:flex"
                            :class="collapsed ? 'justify-center' : ''">
                            <x-icon name="panel-left-close" class="size-[18px]" x-show="! collapsed" />
                            <x-icon name="panel-left-open" class="size-[18px]" x-show="collapsed" x-cloak />
                            <span :class="collapsed ? 'xl:hidden' : ''">Ciutkan sidebar</span>
                        </button>
                        </div>
                    </aside>
                @endif

                <main id="isi" class="min-w-0 flex-1 pb-24 md:pb-0">
                    <x-flash />

                    @if ($siklus)
                        <div class="mx-auto w-full max-w-7xl px-4 pt-4 md:mb-5 md:px-0 md:pt-0"><x-siklus-header :siklus="$siklus" /></div>
                    @endif

                    @if ($header)
                        <div class="mx-auto w-full max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">{{ $header }}</div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>

        {{-- Navigasi bawah ponsel --}}
        @if ($bawah)
            <nav aria-label="Navigasi bawah" class="fixed inset-x-0 bottom-0 z-30 flex border-t border-line bg-surface px-2 pb-2.5 pt-1.5 md:hidden print:hidden">
                @foreach ($bawah as $item)
                    @php $aktif = Navigasi::aktif($item); @endphp
                    <a href="{{ route($item['rute']) }}" @if ($aktif) aria-current="page" @endif
                       class="flex min-h-11 flex-1 flex-col items-center justify-center gap-1 text-xs {{ $aktif ? 'font-bold text-ink-brand' : 'font-medium text-ink-secondary' }}">
                        <span class="grid h-7 w-[52px] place-items-center rounded-[14px] {{ $aktif ? 'bg-state-selected' : '' }}"><x-icon :name="$item['ikon']" class="size-5" /></span>
                        {{ $item['label'] }}
                    </a>
                @endforeach
                <button type="button" @click="drawer = true" aria-controls="drawer-menu"
                    class="flex min-h-11 flex-1 flex-col items-center justify-center gap-1 text-xs font-medium text-ink-secondary">
                    <span class="grid h-7 w-[52px] place-items-center rounded-[14px]"><x-icon name="ellipsis" class="size-5" /></span>
                    Lainnya
                </button>
            </nav>
        @endif

        {{-- Drawer menu ponsel --}}
        <div id="drawer-menu" x-cloak :class="drawer ? 'block' : 'hidden'" class="fixed inset-0 z-50 md:!hidden print:hidden" role="dialog" aria-modal="true" aria-label="Menu">
            <div class="absolute inset-0 bg-black/40" @click="drawer = false"></div>
            <div class="absolute inset-y-0 left-0 flex w-72 max-w-[85%] flex-col gap-5 overflow-y-auto bg-surface p-4 shadow-xl">
                <div class="flex items-center justify-between">
                    <x-logo />
                    <button type="button" @click="drawer = false" aria-label="Tutup menu" class="grid size-11 place-items-center rounded-md hover:bg-state-hover"><x-icon name="x" /></button>
                </div>
                <div class="flex items-center gap-2.5 rounded-md bg-page p-3">
                    <span class="min-w-0">
                        <span class="block truncate text-[13px] font-semibold">{{ $user->name }}</span>
                        <x-badge variant="brand" class="mt-1">{{ $user->labelPeran() }}</x-badge>
                    </span>
                </div>
                <x-shell-menu :grup="$grup" :drawer="true" />
                <form method="POST" action="{{ route('logout') }}" class="mt-auto border-t border-line-subtle pt-3">
                    @csrf
                    <button type="submit" class="flex h-11 w-full items-center gap-2.5 rounded-md px-2.5 text-sm font-medium text-danger-text hover:bg-state-hover">
                        <x-icon name="log-out" class="size-[18px]" /> Keluar
                    </button>
                </form>
            </div>
        </div>
    </body>
</html>
