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
    <body class="min-h-screen bg-page font-sans text-ink antialiased print:bg-white" x-data="shell()" @keydown.escape.window="drawer = false"
          @if (request()->routeIs('dashboard') && $user->isPeternak()) data-tur="otomatis" @endif>
        <div id="bilah-kemajuan" class="pointer-events-none fixed inset-x-0 top-0 z-[60] h-[3px] origin-left bg-brand opacity-0 transition-opacity print:hidden" aria-hidden="true"></div>

        {{-- Tur panduan (disorot per langkah) --}}
        <div x-data="tur()" @mulai-tur.window="mulai()" @keydown.escape.window="aktif && selesai()" class="print:hidden">
            <div x-cloak x-show="aktif" class="fixed inset-0 z-[70]" role="dialog" aria-modal="true" aria-label="Tur panduan">
                <div class="pointer-events-none absolute rounded-lg ring-2 ring-brand transition-all duration-200"
                     :style="`top:${kotak.top}px;left:${kotak.left}px;width:${kotak.width}px;height:${kotak.height}px;box-shadow:0 0 0 9999px rgba(0,0,0,.5)`"></div>
                <div class="absolute w-[min(320px,calc(100vw-24px))] space-y-2.5 rounded-lg border border-line bg-surface p-4 shadow-xl"
                     :style="`top:${popover.top}px;left:${popover.left}px`">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-ink-brand">Tur panduan · <span x-text="(i + 1) + ' dari ' + langkah.length"></span></p>
                    <p class="font-heading text-base font-semibold" x-text="teks.judul"></p>
                    <p class="text-[13px] leading-[19px] text-ink-secondary" x-text="teks.isi"></p>
                    <div class="flex items-center justify-between gap-2 pt-1">
                        <button type="button" @click="selesai()" class="min-h-11 text-[13px] font-semibold text-ink-muted hover:underline">Lewati</button>
                        <div class="flex gap-2">
                            <button type="button" @click="kembali()" x-show="i > 0" class="min-h-11 rounded-md border border-line-control px-3 text-[13px] font-semibold hover:bg-state-hover">Kembali</button>
                            <button type="button" @click="berikut()" class="min-h-11 rounded-md bg-brand px-4 text-[13px] font-semibold text-white hover:bg-brand-hover" x-text="i >= langkah.length - 1 ? 'Selesai' : 'Berikut'"></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex min-h-screen flex-col">
            @if (config('noa.demo') && in_array($user->email, config('noa.akun_demo'), true))
                <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 bg-brand px-4 py-2 text-center text-xs font-medium text-white print:hidden" role="note" data-demo-banner>
                    <span><strong>Mode demo</strong> — data contoh untuk dicoba. Silakan tambah, ubah, atau hapus.</span>
                    <span class="flex items-center gap-3">
                        <button type="button" class="font-bold underline" @click="$dispatch('mulai-tur')">Tur panduan</button>
                        <form method="POST" action="{{ route('demo.reset') }}" onsubmit="return confirm('Kembalikan data demo ke keadaan awal? Perubahan Anda akan hilang.')">
                            @csrf
                            <button class="font-bold underline">Kembalikan data demo</button>
                        </form>
                    </span>
                </div>
            @endif
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
                    <aside data-tur="Menu utama|Semua fitur ada di sini: Dashboard, Aktivitas, Kandang & Siklus, Performa, Pengguna & Akses, dan Bantuan berisi arti istilah." data-tur-urut="1" class="sticky top-6 hidden h-[calc(100vh-68px-3rem)] shrink-0 flex-col justify-between self-start rounded-lg border border-line bg-surface p-3 md:flex md:w-[68px] xl:p-3.5 print:hidden"
                           :class="collapsed ? 'xl:w-[68px]' : 'xl:w-64'">
                        <x-shell-menu :grup="$grup" />
                        <div class="space-y-1 border-t border-line-subtle pt-2">
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
