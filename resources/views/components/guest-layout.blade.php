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
    <body class="min-h-[100dvh] bg-page font-sans text-ink antialiased">
        {{-- Latar aurora: gradasi lembut berwarna hijau, emas, dan persik yang hanyut pelan. --}}
        <div class="latar" aria-hidden="true"><i></i><i></i><i></i><i></i></div>

        <div class="relative z-10 flex min-h-[100dvh] items-center justify-center p-4 lg:p-6">
            {{-- Satu kartu kaca besar: panel gambar (kiri, layar lebar) dan formulir (kanan) menyatu seperti referensi. --}}
            <div class="kaca-form naik grid w-full max-w-[1180px] lg:min-h-[min(680px,calc(100dvh-3rem))] lg:grid-cols-[minmax(0,1fr)_440px] lg:rounded-[36px] lg:p-3" style="--d:.05s">
                {{-- Panel gambar: ayam 3D di tengah, kartu keunggulan melayang, dan sapaan --}}
                <section data-hero class="panel-gambar relative hidden overflow-hidden rounded-[26px] lg:block">
                    <span class="halo"></span><span class="halo"></span><span class="halo"></span>

                    <div class="absolute left-8 top-8 z-10 max-w-[440px] space-y-3 xl:left-10 xl:top-10">
                        <p class="font-heading text-[clamp(1.9rem,min(3vw,5.6vh),2.9rem)] font-semibold leading-[1.06] tracking-[-0.035em]">
                            Catatan kandang yang <span class="text-ink-brand">jujur</span>.<br>Panen yang <span class="text-ink-brand">adil</span>.
                        </p>
                        <p class="max-w-[330px] text-[15px] leading-[24px] text-ink-secondary">Catat kandang tiap hari, lihat hasilnya, lalu cocokkan dengan RHPP dari inti.</p>
                    </div>

                    @php
                        $ayam = fn (string $f) => asset('images/ayam/'.$f).'?v='.@filemtime(public_path('images/ayam/'.$f));
                    @endphp
                    {{-- Maskot ayam: badan utuh, ekor dan jengger yang bergoyang, dan mata asli yang bergerak di dalam wajah. Matanya mengikuti kursor. --}}
                    <div data-ayam class="absolute inset-x-0 bottom-[3%] top-[29%] [@media(max-height:600px)]:top-[34%]" style="opacity:0;transition:opacity .8s">
                        <button type="button" data-colek aria-label="Colek ayam" class="ayam-tombol">
                            <span class="ayam-lompat" data-lompat>
                                <span class="ayam-bayang"></span>
                                <span class="ayam-adegan" data-adegan2>
                                    <span class="ayam-napas">
                                        <span class="ayam-ekor"><img class="ayam-lapis" src="{{ $ayam('ekor.webp') }}" alt="" draggable="false"></span>
                                        <span class="ayam-jengger"><img class="ayam-lapis" src="{{ $ayam('jengger.webp') }}" alt="" draggable="false"></span>
                                        <img class="ayam-lapis" src="{{ $ayam('badan.webp') }}" alt="" draggable="false">
                                        <span class="ayam-mata" data-kepala style="-webkit-mask-image:url('{{ $ayam('kepala.webp') }}');mask-image:url('{{ $ayam('kepala.webp') }}')">
                                            <span class="ayam-oko" style="left:63.25%;top:19.37%;width:10.13%;height:7.17%">
                                                <img src="{{ $ayam('mata1.webp') }}" alt="" draggable="false">
                                                <span class="ayam-kulit" style="background-image:url('{{ $ayam('badan.webp') }}');background-size:987.1% 1394.7%;background-position:70.38% 20.87%;-webkit-mask-image:url('{{ $ayam('mata1.webp') }}');mask-image:url('{{ $ayam('mata1.webp') }}')"></span>
                                                <i class="ayam-garis"></i>
                                            </span>
                                        </span>
                                        <span class="ayam-mata ayam-mata-jauh">
                                            <span class="ayam-oko" style="left:82.33%;top:20.06%;width:5.17%;height:5.49%">
                                                <img src="{{ $ayam('mata2.webp') }}" alt="" draggable="false">
                                                <span class="ayam-kulit" style="background-image:url('{{ $ayam('badan.webp') }}');background-size:1934% 1821%;background-position:86.82% 21.22%;-webkit-mask-image:url('{{ $ayam('mata2.webp') }}');mask-image:url('{{ $ayam('mata2.webp') }}')"></span>
                                                <i class="ayam-garis ayam-garis-jauh"></i>
                                            </span>
                                        </span>
                                    </span>
                                </span>
                            </span>
                        </button>
                    </div>

                    {{-- Tiga kartu keunggulan sistem: sedikit membesar dan naik saat disorot --}}
                    <div class="statistik z-10" style="left:5%;top:36%;--k:10">
                        <div class="melayang kaca-chip flex items-center gap-3 rounded-2xl py-2.5 pl-3 pr-5" style="animation-delay:-1s">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-soft text-ink-brand"><x-icon name="clipboard-list" class="size-5" /></span>
                            <span><span class="block text-sm font-bold">Recording harian</span><span class="block text-[11px] leading-4 text-ink-muted">Pakan, ayam mati, afkir.<br>Kurang dari satu menit.</span></span>
                        </div>
                    </div>

                    <div class="statistik z-10" style="right:4%;top:57%;--k:8">
                        <div class="melayang kaca-chip flex items-center gap-3 rounded-2xl py-2.5 pl-3 pr-5" style="animation-delay:-5s">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-gold-soft text-ink-gold"><x-icon name="bell" class="size-5" /></span>
                            <span><span class="block text-sm font-bold">Pantau kandang</span><span class="block text-[11px] leading-4 text-ink-muted">Stok pakan, hari terlewat,<br>dan paraf PPL.</span></span>
                        </div>
                    </div>

                    <div class="statistik z-10" style="left:5%;bottom:15%;--k:9">
                        <div class="melayang kaca-chip flex items-center gap-3 rounded-2xl py-2.5 pl-3 pr-5" style="animation-delay:-2.2s">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-info-soft text-info-text"><x-icon name="columns-2" class="size-5" /></span>
                            <span><span class="block text-sm font-bold">Cocokkan RHPP</span><span class="block text-[11px] leading-4 text-ink-muted">Banding dengan laporan inti,<br>baris demi baris.</span></span>
                        </div>
                    </div>
                </section>

                {{-- Formulir (logo dan tombol tema ada di bagian atas) --}}
                <main class="auth flex flex-col gap-5 p-6 sm:p-9 lg:justify-center lg:gap-5 lg:px-10 lg:py-7 [@media(max-height:780px)]:lg:py-4 [@media(max-height:780px)]:gap-3 [@media(max-height:780px)]:sm:p-7">
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
    </body>
</html>
