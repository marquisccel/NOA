@props(['title' => 'Masuk'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }} · NOA</title>
        <link rel="icon" href="{{ asset('images/noa-mark.webp') }}" type="image/webp">
        <script>(function(){try{var t=localStorage.getItem('noa.tema');if(!t&&window.matchMedia('(prefers-color-scheme: dark)').matches){t='dark'}if(t){document.documentElement.dataset.theme=t}}catch(e){}})()</script>
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/masuk.js'])
    </head>
    <body class="min-h-screen bg-page font-sans text-ink antialiased">
        {{-- Latar aurora: gradasi lembut berwarna hijau, emas, dan persik yang hanyut pelan. --}}
        <div class="latar" aria-hidden="true"><i></i><i></i><i></i><i></i></div>

        <div class="relative z-10 flex min-h-screen items-center justify-center p-4 lg:p-8">
            <div class="grid w-full max-w-[1260px] gap-5 lg:h-[min(740px,calc(100vh-4rem))] lg:grid-cols-[minmax(0,1fr)_470px]">
                {{-- Panel gambar (layar lebar): ayam 3D di tengah, kartu statistik, dan sapaan --}}
                <section data-hero class="panel-gambar naik relative hidden overflow-hidden rounded-[32px] lg:block" style="--d:.05s">
                    <span class="halo"></span><span class="halo"></span><span class="halo"></span>

                    <div class="absolute left-8 top-7 z-10 max-w-[460px] space-y-3 xl:left-10 xl:top-9">
                        <span class="kaca-chip inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-xs font-bold tracking-wide text-ink-brand">
                            <span class="size-1.5 rounded-full bg-brand"></span> Untuk peternak plasma
                        </span>
                        <p class="font-heading text-[clamp(1.9rem,min(3vw,5.6vh),2.9rem)] font-semibold leading-[1.06] tracking-[-0.035em]">
                            Catatan kandang yang <span class="text-ink-brand">jujur</span>.<br>Panen yang <span class="text-ink-brand">adil</span>.
                        </p>
                        <p class="max-w-[400px] text-[15px] leading-[24px] text-ink-secondary">Catat kandang tiap hari, lihat hasilnya, lalu cocokkan dengan RHPP dari inti.</p>
                        <ol class="sr-only">
                            <li>Catat tiap hari</li><li>Lihat hasilnya</li><li>Cocokkan RHPP</li>
                        </ol>
                    </div>

                    {{-- Ayam 3D (three.js, dimuat malas hanya di layar lebar). Bila gagal dimuat, area ini kosong. --}}
                    <div data-ayam3d class="absolute inset-x-0 bottom-0 top-[18%] [@media(max-height:600px)]:top-[24%]" style="opacity:0;transition:opacity 1.2s">
                        <canvas class="size-full"></canvas>
                        <button type="button" data-colek tabindex="-1" aria-label="Colek ayam" class="absolute rounded-full" style="cursor:pointer"></button>
                    </div>

                    {{-- Kartu statistik pemanis: gambaran isi aplikasi dengan bahasa peternak --}}
                    <div class="statistik z-10" style="left:5%;top:52%;--k:10;--gx:34px;--gt:60px">
                        <div class="melayang kaca-chip flex items-center gap-3 rounded-2xl py-2.5 pl-3 pr-5" style="animation-delay:-1s">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-soft text-ink-brand"><x-icon name="clipboard-list" class="size-5" /></span>
                            <span><span class="block text-[11px] text-ink-muted">Kandang A · Hari ke-23</span><span class="block text-sm font-bold">Catatan hari ini terisi</span></span>
                        </div>
                    </div>

                    <div class="statistik z-10" style="right:4%;top:9%;--k:14;--gx:46px;--gt:84px">
                        <div class="melayang kaca-chip rounded-2xl px-4 py-3" style="animation-delay:-3.2s">
                            <span class="flex items-center gap-2 text-[11px] text-ink-muted"><x-icon name="trending-down" class="size-3.5 text-ink-brand" /> FCR sementara</span>
                            <span class="mt-0.5 flex items-baseline gap-2"><span class="font-heading text-2xl font-semibold tabular-nums">1,63</span><span class="text-[11px] font-semibold text-ink-brand">lebih baik dari standar 1,70</span></span>
                            <span class="mt-1.5 flex h-6 items-end gap-[3px]" aria-hidden="true">
                                @foreach ([30, 42, 38, 55, 50, 64, 58, 72, 68, 84] as $t)
                                    <span class="w-[5px] rounded-sm bg-brand/70" style="height:{{ $t }}%"></span>
                                @endforeach
                            </span>
                        </div>
                    </div>

                    <div class="statistik z-10" style="right:9%;bottom:9%;--k:8;--gx:30px;--gt:56px;--gy:-56px;--gb:to top">
                        <div class="melayang kaca-chip flex items-center gap-3 rounded-2xl py-2.5 pl-3 pr-5" style="animation-delay:-5s">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-gold-soft text-ink-gold"><x-icon name="feather" class="size-5" /></span>
                            <span><span class="block text-[11px] text-ink-muted">Ayam hidup</span><span class="block text-sm font-bold">7.796 ekor · deplesi 2,1%</span></span>
                        </div>
                    </div>

                    <p class="absolute inset-x-0 bottom-4 z-10 text-center text-xs font-medium text-ink-muted [@media(max-height:700px)]:hidden">Gerakkan kursor, ayamnya ikut melihat. Colek untuk mengepak.</p>
                </section>

                {{-- Kartu kaca berisi formulir (logo dan tombol tema ada di dalam kartu) --}}
                <div class="auth flex items-center justify-center">
                    <main class="kaca-form naik flex w-full max-w-[470px] flex-col gap-5 p-7 sm:p-9 lg:h-full lg:justify-center [@media(max-height:780px)]:gap-3.5 [@media(max-height:780px)]:p-6 [@media(max-height:780px)]:sm:p-7" style="--d:.2s">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('login') }}" aria-label="NOA, ke halaman masuk" class="inline-flex"><x-logo /></a>
                            <button type="button" x-data="tema()" @click="alternatif()" :aria-label="gelap ? 'Ganti ke tema terang' : 'Ganti ke tema gelap'"
                                class="kaca-chip grid size-10 place-items-center rounded-full text-ink-secondary transition hover:text-ink active:scale-95">
                                <x-icon name="moon" class="size-[18px]" x-show="! gelap" />
                                <x-icon name="sun" class="size-[18px]" x-show="gelap" x-cloak />
                            </button>
                        </div>

                        {{ $slot }}

                        <p class="border-t border-line-subtle pt-3 text-center text-xs text-ink-muted [@media(max-height:780px)]:hidden">© {{ date('Y') }} NOA · Nitip, Openi, Adil</p>
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
