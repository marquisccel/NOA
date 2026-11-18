@props(['title' => 'Masuk'])

@php
    // Hamparan acak yang tetap sama di setiap muat (benih tetap): bintang, serbuk cahaya, dan batang gandum.
    mt_srand(11);
    $bintang = collect(range(1, 46))->map(fn () => ['x' => mt_rand(40, 1560), 'y' => mt_rand(24, 400), 'r' => mt_rand(8, 18) / 10, 'd' => mt_rand(0, 40) / 10]);
    $serbuk = collect(range(1, 16))->map(fn () => ['x' => mt_rand(2, 96), 'u' => mt_rand(3, 7), 'w' => -mt_rand(0, 220) / 10, 't' => mt_rand(150, 260) / 10, 'dx' => mt_rand(-60, 60), 'o' => mt_rand(40, 85) / 100]);
    $gandum = collect(range(0, 47))->map(function ($i) {
        $x = -30 + $i * 35 + mt_rand(-10, 10);
        $tengah = $x > 250 && $x < 760;
        $h = $tengah ? mt_rand(80, 120) : mt_rand(120, 210);

        return ['x' => $x, 'h' => $h, 'd' => -mt_rand(0, 50) / 10, 's' => mt_rand(40, 70) / 10];
    });
@endphp

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
        {{-- Adegan peternakan berlapis (dekoratif): pagi keemasan di tema terang, malam berbintang dengan jendela menyala di tema gelap. --}}
        <div class="adegan" data-adegan aria-hidden="true">
            <div class="adegan-isi">
                {{-- Lapisan 1: matahari/bulan, bintang, awan --}}
                <svg class="lapisan" style="--z:4" viewBox="0 0 1600 900" preserveAspectRatio="xMidYMax slice">
                    <defs>
                        <linearGradient id="g-matahari" x1="0" y1="0" x2="0" y2="1"><stop offset="0" style="stop-color:var(--matahari-a)" /><stop offset="1" style="stop-color:var(--matahari-b)" /></linearGradient>
                        <linearGradient id="g-kabut" x1="0" y1="0" x2="0" y2="1"><stop offset="0" style="stop-color:var(--kabut);stop-opacity:0" /><stop offset="1" style="stop-color:var(--kabut);stop-opacity:var(--kabut-opa)" /></linearGradient>
                        <linearGradient id="g-tanah" x1="0" y1="0" x2="0" y2="1"><stop offset="0" style="stop-color:var(--ladang)" /><stop offset="1" style="stop-color:var(--bukit-3)" /></linearGradient>
                        <symbol id="gandum" viewBox="0 0 20 140">
                            <path d="M10 140C10 100 8 62 10 22" fill="none" style="stroke:var(--gandum-batang)" stroke-width="2" stroke-linecap="round" />
                            <g style="fill:var(--gandum)">
                                <ellipse cx="10" cy="10" rx="2.8" ry="7" />
                                <ellipse cx="6.5" cy="22" rx="3" ry="6" transform="rotate(-28 6.5 22)" /><ellipse cx="13.5" cy="22" rx="3" ry="6" transform="rotate(28 13.5 22)" />
                                <ellipse cx="6.5" cy="33" rx="3" ry="6" transform="rotate(-28 6.5 33)" /><ellipse cx="13.5" cy="33" rx="3" ry="6" transform="rotate(28 13.5 33)" />
                                <ellipse cx="7" cy="44" rx="2.8" ry="5.6" transform="rotate(-28 7 44)" /><ellipse cx="13" cy="44" rx="2.8" ry="5.6" transform="rotate(28 13 44)" />
                            </g>
                        </symbol>
                        <symbol id="ayam-putih" viewBox="0 0 48 48">
                            <path d="M9 24Q1 14 8 9Q9 18 15 22Z" fill="#fff" stroke="#0000001f" stroke-width=".8" />
                            <ellipse cx="22" cy="28" rx="15" ry="11" fill="#fff" stroke="#0000001f" stroke-width=".8" />
                            <ellipse cx="21" cy="29" rx="8" ry="5" fill="#eee5d0" />
                            <circle cx="36" cy="17" r="7" fill="#fff" stroke="#0000001f" stroke-width=".8" />
                            <path d="M32.5 11.5Q33.5 6 36 10Q38.5 6 40 11.5Z" fill="#d9392b" />
                            <ellipse cx="40" cy="23" rx="2.2" ry="3" fill="#d9392b" />
                            <path d="M42 16.2 48 18.2 42 20.2Z" fill="#f0a31a" />
                            <circle cx="38.2" cy="15.6" r="1.1" fill="#222" />
                            <path d="M19 38V46M26 38V46M16.5 46H21.5M23.5 46H28.5" stroke="#e09b2a" stroke-width="2" stroke-linecap="round" fill="none" />
                        </symbol>
                        <symbol id="ayam-coklat" viewBox="0 0 48 48">
                            <path d="M9 24Q1 14 8 9Q9 18 15 22Z" fill="#8f4f26" />
                            <ellipse cx="22" cy="28" rx="15" ry="11" fill="#c47a3d" />
                            <ellipse cx="21" cy="29" rx="8" ry="5" fill="#a6622d" />
                            <circle cx="36" cy="17" r="7" fill="#c47a3d" />
                            <path d="M32.5 11.5Q33.5 6 36 10Q38.5 6 40 11.5Z" fill="#d9392b" />
                            <ellipse cx="40" cy="23" rx="2.2" ry="3" fill="#d9392b" />
                            <path d="M42 16.2 48 18.2 42 20.2Z" fill="#f0a31a" />
                            <circle cx="38.2" cy="15.6" r="1.1" fill="#222" />
                            <path d="M19 38V46M26 38V46M16.5 46H21.5M23.5 46H28.5" stroke="#d99a2a" stroke-width="2" stroke-linecap="round" fill="none" />
                        </symbol>
                    </defs>

                    <g class="bintang">
                        @foreach ($bintang as $b)
                            <circle cx="{{ $b['x'] }}" cy="{{ $b['y'] }}" r="{{ $b['r'] }}" fill="#fff" style="animation-delay:-{{ $b['d'] }}s" />
                        @endforeach
                    </g>

                    <circle cx="930" cy="440" r="340" style="fill:var(--matahari-b)" opacity=".10" />
                    <circle cx="930" cy="440" r="220" style="fill:var(--matahari-b)" opacity=".16" />
                    <circle cx="930" cy="440" r="130" style="fill:var(--matahari-b)" opacity=".24" />
                    <circle cx="930" cy="440" r="66" fill="url(#g-matahari)" />

                    <g class="awan-geser" style="fill:var(--awan)" opacity=".75">
                        <ellipse cx="260" cy="250" rx="110" ry="20" /><ellipse cx="215" cy="236" rx="60" ry="22" /><ellipse cx="310" cy="238" rx="66" ry="20" />
                    </g>
                    <g class="awan-geser" style="fill:var(--awan)" opacity=".6">
                        <ellipse cx="1180" cy="330" rx="130" ry="22" /><ellipse cx="1130" cy="314" rx="70" ry="24" /><ellipse cx="1240" cy="316" rx="72" ry="22" />
                    </g>
                </svg>

                {{-- Lapisan 2: bukit jauh dan kabut --}}
                <svg class="lapisan" style="--z:8" viewBox="0 0 1600 900" preserveAspectRatio="xMidYMax slice">
                    <path d="M-60 600C150 540 330 560 520 585S900 540 1150 570S1500 560 1660 580V900H-60Z" style="fill:var(--bukit-1)" />
                    <rect x="-60" y="540" width="1720" height="140" fill="url(#g-kabut)" />
                    <path d="M-60 650C200 606 420 630 700 654S1150 612 1400 642S1600 644 1660 654V900H-60Z" style="fill:var(--bukit-2)" />
                </svg>

                {{-- Lapisan 3: kandang, silo pakan, pohon --}}
                <svg class="lapisan" style="--z:16" viewBox="0 0 1600 900" preserveAspectRatio="xMidYMax slice" data-kandang>
                    <path d="M-60 706C200 668 420 696 700 706S1100 676 1660 706V900H-60Z" style="fill:var(--bukit-3)" />

                    <g transform="translate(-26 0)">
                        <rect x="104" y="648" width="9" height="62" style="fill:var(--batang)" />
                        <ellipse cx="108" cy="626" rx="48" ry="56" style="fill:var(--pohon)" /><ellipse cx="82" cy="648" rx="30" ry="34" style="fill:var(--pohon)" /><ellipse cx="136" cy="648" rx="32" ry="36" style="fill:var(--pohon)" />
                    </g>
                    <g transform="translate(1010 40) scale(.8)">
                        <rect x="104" y="648" width="9" height="62" style="fill:var(--batang)" />
                        <ellipse cx="108" cy="626" rx="48" ry="56" style="fill:var(--pohon)" /><ellipse cx="82" cy="648" rx="30" ry="34" style="fill:var(--pohon)" /><ellipse cx="136" cy="648" rx="32" ry="36" style="fill:var(--pohon)" />
                    </g>

                    <g transform="translate(150 0)">
                        <path d="M395 654H340" stroke-width="7" stroke-linecap="round" style="stroke:var(--silo)" />
                        <rect x="395" y="566" width="44" height="140" style="fill:var(--silo)" />
                        <rect x="425" y="566" width="14" height="140" fill="#00000018" />
                        <path d="M389 566 417 520 445 566Z" style="fill:var(--silo)" />
                        <rect x="395" y="600" width="44" height="3" fill="#00000014" /><rect x="395" y="640" width="44" height="3" fill="#00000014" />

                        <rect x="-18" y="634" width="376" height="6" style="fill:var(--atap-2)" />
                        <rect x="0" y="636" width="340" height="68" style="fill:var(--dinding)" />
                        <rect x="0" y="694" width="340" height="10" style="fill:var(--dinding-bayang)" />
                        <path d="M-18 636 32 586H308L358 636Z" style="fill:var(--atap)" />
                        <rect x="130" y="576" width="80" height="11" rx="3" style="fill:var(--atap-2)" />
                        <g class="jendela" style="fill:var(--jendela)">
                            <rect x="20" y="650" width="44" height="36" rx="3" /><rect x="72" y="650" width="44" height="36" rx="3" /><rect x="124" y="650" width="44" height="36" rx="3" />
                            <rect x="238" y="650" width="44" height="36" rx="3" /><rect x="290" y="650" width="34" height="36" rx="3" />
                        </g>
                        <rect x="178" y="644" width="40" height="60" rx="3" style="fill:var(--pintu)" />
                        <circle cx="210" cy="676" r="2.2" style="fill:var(--dinding)" />
                    </g>
                </svg>

                {{-- Lapisan 4: ladang depan dengan barisan tanaman, dan ayam --}}
                <svg class="lapisan" style="--z:26" viewBox="0 0 1600 900" preserveAspectRatio="xMidYMax slice">
                    <path d="M-60 762C250 732 600 760 900 752S1400 738 1660 762V900H-60Z" fill="url(#g-tanah)" />
                    <g style="stroke:var(--baris)" stroke-width="2" opacity=".3" fill="none">
                        @foreach (range(-6, 14) as $k)
                            <path d="M{{ 800 + ($k * 200 - 800) * 0.22 }} 768L{{ $k * 200 }} 910" />
                        @endforeach
                    </g>
                    <use href="#ayam-putih" x="330" y="792" width="80" height="80" class="ayam-patuk" />
                    <use href="#ayam-coklat" x="470" y="812" width="66" height="66" class="ayam-patuk" style="animation-delay:-2.4s" />
                    <use href="#ayam-putih" x="600" y="798" width="72" height="72" class="ayam-patuk" style="animation-delay:-4s" />
                </svg>

                {{-- Lapisan 5: gandum depan yang bergoyang --}}
                <svg class="lapisan" style="--z:42" viewBox="0 0 1600 900" preserveAspectRatio="xMidYMax slice">
                    @foreach ($gandum as $g)
                        <use href="#gandum" x="{{ $g['x'] }}" y="{{ 912 - $g['h'] }}" width="{{ round($g['h'] / 7, 1) }}" height="{{ $g['h'] }}" class="goyang" style="animation-delay:{{ $g['d'] }}s;animation-duration:{{ $g['s'] }}s" />
                    @endforeach
                </svg>

                @foreach ($serbuk as $s)
                    <span class="serbuk" style="left:{{ $s['x'] }}%;width:{{ $s['u'] }}px;height:{{ $s['u'] }}px;--w:{{ $s['w'] }}s;--t:{{ $s['t'] }}s;--dx:{{ $s['dx'] }}px;--o:{{ $s['o'] }}"></span>
                @endforeach
            </div>
            <div class="adegan-vinyet"></div>
        </div>

        <div class="relative z-10 grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(480px,540px)]">
            {{-- Sisi kiri (layar lebar): sambutan --}}
            <section class="relative hidden flex-col px-10 py-8 lg:flex xl:px-16" data-hero>
                <div class="naik flex items-center justify-between" style="--d:.05s">
                    <a href="{{ route('login') }}" aria-label="NOA, ke halaman masuk" class="inline-flex"><x-logo /></a>
                    <button type="button" x-data="tema()" @click="alternatif()" :aria-label="gelap ? 'Ganti ke tema terang' : 'Ganti ke tema gelap'"
                        class="kaca-chip grid size-11 place-items-center rounded-full text-ink-secondary transition hover:text-ink active:scale-95">
                        <x-icon name="moon" class="size-[18px]" x-show="! gelap" />
                        <x-icon name="sun" class="size-[18px]" x-show="gelap" x-cloak />
                    </button>
                </div>

                <div class="mt-[clamp(1rem,5vh,3.5rem)] max-w-[640px] space-y-5">
                    <span class="naik kaca-chip inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-xs font-bold tracking-wide text-ink-brand" style="--d:.15s">
                        <span class="size-1.5 rounded-full bg-brand"></span> Untuk peternak plasma
                    </span>
                    <p class="naik font-heading text-[clamp(2.25rem,min(4.1vw,7.4vh),3.9rem)] font-semibold leading-[1.04] tracking-[-0.04em]" style="--d:.25s">
                        Catatan kandang yang <span class="text-ink-brand">jujur</span>.<br>Panen yang <span class="text-ink-brand">adil</span>.
                    </p>
                    <p class="naik max-w-[500px] text-[17px] leading-[27px] text-ink-secondary" style="--d:.38s">
                        Catat kandang tiap hari, lihat hasilnya, lalu cocokkan dengan RHPP dari inti. Semua angka bisa Anda periksa sendiri.
                    </p>
                    <ol class="naik flex flex-wrap gap-2 pt-1 [@media(max-height:760px)]:hidden" style="--d:.5s">
                        @foreach (['Catat tiap hari', 'Lihat hasilnya', 'Cocokkan RHPP'] as $n => $langkah)
                            <li class="kaca-chip inline-flex items-center gap-2 rounded-full py-1.5 pl-1.5 pr-3.5 text-[13px] font-semibold">
                                <span class="grid size-6 place-items-center rounded-full bg-brand text-[11px] font-bold text-ink-on-primary">{{ $n + 1 }}</span>{{ $langkah }}
                            </li>
                        @endforeach
                    </ol>
                </div>

                {{-- Kartu info melayang di atas ladang: gambaran isi aplikasi dengan bahasa peternak --}}
                <div class="pointer-events-none absolute bottom-[13%] right-[3%] hidden w-[min(290px,36%)] space-y-3 xl:block [@media(max-height:700px)]:hidden" aria-hidden="true">
                    <div class="naik" style="--d:.7s"><div class="chip-melayang kaca-chip flex items-center gap-3 rounded-2xl py-2.5 pl-3 pr-5" style="animation-delay:-1s">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-brand-soft text-ink-brand"><x-icon name="clipboard-list" class="size-[18px]" /></span>
                        <span><span class="block text-[11px] text-ink-muted">Kandang A · Hari ke-23</span><span class="block text-sm font-bold">Catatan hari ini terisi</span></span>
                    </div></div>
                    <div class="naik ml-8" style="--d:.85s"><div class="chip-melayang kaca-chip flex items-center gap-3 rounded-2xl py-2.5 pl-3 pr-5" style="animation-delay:-3.5s">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-gold-soft text-ink-gold"><x-icon name="feather" class="size-[18px]" /></span>
                        <span><span class="block text-[11px] text-ink-muted">Ayam hidup</span><span class="block text-sm font-bold">7.796 ekor · deplesi 2,1%</span></span>
                    </div></div>
                </div>
            </section>

            {{-- Sisi kanan: kartu kaca berisi formulir --}}
            <div class="auth relative flex min-h-screen flex-col items-center justify-center px-4 pb-8 pt-[84px] sm:px-8 lg:py-6">
                <div class="absolute inset-x-4 top-5 flex items-center justify-between sm:inset-x-8 lg:hidden">
                    <a href="{{ route('login') }}" aria-label="NOA, ke halaman masuk"><x-logo /></a>
                    <button type="button" x-data="tema()" @click="alternatif()" :aria-label="gelap ? 'Ganti ke tema terang' : 'Ganti ke tema gelap'"
                        class="kaca-chip ml-auto grid size-11 place-items-center rounded-full text-ink-secondary transition hover:text-ink active:scale-95">
                        <x-icon name="moon" class="size-[18px]" x-show="! gelap" />
                        <x-icon name="sun" class="size-[18px]" x-show="gelap" x-cloak />
                    </button>
                </div>

                <main class="kaca-form naik flex w-full max-w-[440px] flex-col gap-5 p-7 sm:p-9 [@media(max-height:780px)]:gap-3.5 [@media(max-height:780px)]:p-6 [@media(max-height:780px)]:sm:p-7" style="--d:.2s">
                    {{ $slot }}
                    <p class="border-t border-line-subtle pt-3 text-center text-xs text-ink-muted [@media(max-height:780px)]:hidden">© {{ date('Y') }} NOA · Nitip, Openi, Adil</p>
                </main>
            </div>
        </div>
    </body>
</html>
