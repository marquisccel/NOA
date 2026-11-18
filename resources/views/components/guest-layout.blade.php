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
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-page font-sans text-ink antialiased">
        <div class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(460px,540px)]">
            {{-- Sisi kiri (layar lebar): ilustrasi kandang ayam, matahari, dan ayam yang mematuk. Warna mengikuti tema. --}}
            <aside data-hero class="hero-kandang relative hidden h-screen flex-col overflow-hidden p-10 text-ink lg:sticky lg:top-0 lg:flex xl:px-14 xl:py-10">
                <a href="{{ route('login') }}" aria-label="NOA, ke halaman masuk" class="relative z-10 inline-flex self-start"><x-logo /></a>

                <div class="relative z-10 mt-[clamp(1.5rem,6vh,4rem)] max-w-[680px] space-y-4">
                    <p class="masuk-naik font-heading text-[clamp(2rem,min(3.4vw,6.4vh),3.3rem)] font-semibold leading-[1.08] tracking-[-0.03em]">
                        Catatan kandang yang <span class="text-ink-brand">jujur</span>.<br>Panen yang <span class="text-ink-brand">adil</span>.
                    </p>
                    <p class="masuk-naik max-w-[500px] text-base leading-[26px] text-ink-secondary" style="--d:.1s">
                        Catat kandang tiap hari, lihat hasilnya, lalu cocokkan dengan RHPP dari inti. Semua angka bisa Anda periksa sendiri.
                    </p>
                </div>

                <ol class="relative z-10 mt-6 grid max-w-[600px] grid-cols-3 gap-3 [@media(max-height:790px)]:hidden">
                    @foreach ([['Catat tiap hari', 'Pakan, ayam mati, ayam afkir. Kurang dari satu menit.'], ['Lihat hasilnya', 'FCR dan IP dihitung otomatis, rumusnya terlihat.'], ['Cocokkan RHPP', 'Bandingkan dengan laporan inti, baris demi baris.']] as $n => [$judul, $teks])
                        <li class="masuk-naik rounded-[18px] border border-line bg-surface/80 p-4 backdrop-blur-sm" style="--d:{{ 0.2 + $n * 0.08 }}s">
                            <span class="grid size-7 place-items-center rounded-full bg-brand text-[13px] font-bold text-ink-on-primary">{{ $n + 1 }}</span>
                            <p class="mt-2.5 text-[15px] font-bold">{{ $judul }}</p>
                            <p class="mt-0.5 text-[13px] leading-[19px] text-ink-secondary">{{ $teks }}</p>
                        </li>
                    @endforeach
                </ol>

                {{-- Ilustrasi: kandang panjang beratap, silo pakan, bukit, pagar, dan ayam. Dekoratif (aria-hidden). --}}
                <svg data-kandang class="pointer-events-none absolute inset-x-0 bottom-0 z-0 h-[46%] w-full" viewBox="0 0 800 400" preserveAspectRatio="xMidYMax slice" aria-hidden="true">
                    <defs>
                        <symbol id="ayam" viewBox="0 0 48 48">
                            <path d="M9 24Q1 14 8 9Q9 18 15 22Z" fill="#fff" stroke="#00000022" stroke-width=".8" />
                            <ellipse cx="22" cy="28" rx="15" ry="11" fill="#fff" stroke="#00000022" stroke-width=".8" />
                            <ellipse cx="21" cy="29" rx="8" ry="5" fill="#efe6d2" />
                            <circle cx="36" cy="17" r="7" fill="#fff" stroke="#00000022" stroke-width=".8" />
                            <path d="M32.5 11.5Q33.5 6 36 10Q38.5 6 40 11.5Z" fill="#d9392b" />
                            <ellipse cx="40" cy="23" rx="2.2" ry="3" fill="#d9392b" />
                            <path d="M42 16.2 48 18.2 42 20.2Z" fill="#f0a31a" />
                            <circle cx="38.2" cy="15.6" r="1.1" fill="#222" />
                            <path d="M19 38V46M26 38V46M16.5 46H21.5M23.5 46H28.5" stroke="#e09b2a" stroke-width="2" stroke-linecap="round" fill="none" />
                        </symbol>
                    </defs>

                    <circle cx="640" cy="120" r="64" fill="var(--matahari)" opacity=".22" />
                    <circle cx="640" cy="120" r="34" fill="var(--matahari)" />

                    <g class="awan-geser" fill="var(--awan)" opacity=".9">
                        <path d="M90 78a20 20 0 0 1 38-6 16 16 0 0 1 24 8 14 14 0 0 1-4 28H100a15 15 0 0 1-10-30Z" />
                        <path d="M380 46a16 16 0 0 1 30-5 12 12 0 0 1 19 6 11 11 0 0 1-3 22H388a12 12 0 0 1-8-23Z" opacity=".8" />
                    </g>

                    <path d="M0 250C120 205 240 215 360 245S600 215 800 240V400H0Z" fill="var(--bukit-1)" />
                    <path d="M0 300C150 265 300 285 430 300S680 270 800 295V400H0Z" fill="var(--bukit-2)" />
                    <path d="M0 338C200 326 600 326 800 338V400H0Z" fill="var(--tanah)" />

                    {{-- silo pakan --}}
                    <g>
                        <path d="M520 296H564" stroke="var(--silo)" stroke-width="5" />
                        <rect x="564" y="236" width="38" height="104" fill="var(--silo)" />
                        <path d="M559 236 583 204 607 236Z" fill="var(--silo)" />
                        <path d="M564 236V340M573 236V340" stroke="#0000001a" stroke-width="1.5" />
                        <path d="M568 340 564 352M598 340 602 352" stroke="var(--pagar)" stroke-width="3" />
                    </g>

                    {{-- kandang --}}
                    <g>
                        <rect x="190" y="268" width="330" height="72" fill="var(--dinding)" />
                        <path d="M172 270 216 232H494L538 270Z" fill="var(--atap)" />
                        <path d="M172 270H538" stroke="var(--atap-gelap)" stroke-width="5" />
                        <rect x="318" y="222" width="74" height="11" rx="3" fill="var(--atap-gelap)" />
                        <g fill="var(--tirai)">
                            <rect x="204" y="284" width="46" height="40" rx="3" />
                            <rect x="256" y="284" width="46" height="40" rx="3" />
                            <rect x="308" y="284" width="46" height="40" rx="3" />
                            <rect x="422" y="284" width="46" height="40" rx="3" />
                            <rect x="474" y="284" width="34" height="40" rx="3" />
                        </g>
                        <rect x="364" y="280" width="46" height="60" rx="3" fill="var(--pintu)" />
                        <circle cx="402" cy="312" r="2" fill="var(--dinding)" />
                    </g>

                    {{-- pagar --}}
                    <g stroke="var(--pagar)" stroke-width="3" stroke-linecap="round">
                        <path d="M20 334H170M20 346H170M628 334H780M628 346H780" />
                        <path d="M30 326V356M66 326V356M102 326V356M138 326V356M166 326V356M640 326V356M676 326V356M712 326V356M748 326V356M776 326V356" />
                    </g>

                    {{-- ayam --}}
                    <use href="#ayam" x="92" y="334" width="46" height="46" class="ayam-patuk" />
                    <use href="#ayam" x="300" y="348" width="54" height="54" class="ayam-patuk" style="animation-delay:-2s" />
                    <use href="#ayam" x="470" y="344" width="48" height="48" class="ayam-patuk" style="animation-delay:-3.3s" />
                    <use href="#ayam" x="676" y="340" width="44" height="44" class="ayam-patuk" style="animation-delay:-1s" />
                </svg>
            </aside>

            {{-- Panel formulir --}}
            <div class="auth panel-form relative flex min-h-screen flex-col px-5 py-5 sm:px-10">
                <div class="flex items-center justify-between">
                    <a href="{{ route('login') }}" aria-label="NOA, ke halaman masuk" class="lg:hidden"><x-logo /></a>
                    <button type="button" x-data="tema()" @click="alternatif()" :aria-label="gelap ? 'Ganti ke tema terang' : 'Ganti ke tema gelap'"
                        class="ml-auto grid size-11 place-items-center rounded-full border border-line bg-surface text-ink-secondary transition hover:bg-state-hover active:scale-95">
                        <x-icon name="moon" class="size-[18px]" x-show="! gelap" />
                        <x-icon name="sun" class="size-[18px]" x-show="gelap" x-cloak />
                    </button>
                </div>

                <main class="mx-auto flex w-full max-w-[400px] flex-1 flex-col justify-center gap-5 py-4 [@media(max-height:780px)]:gap-3.5 [@media(max-height:780px)]:py-1">
                    {{ $slot }}
                </main>

                <p class="text-center text-xs text-ink-muted [@media(max-height:780px)]:hidden">© {{ date('Y') }} NOA · Nitip, Openi, Adil</p>
            </div>
        </div>
    </body>
</html>
