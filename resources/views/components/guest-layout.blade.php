@props(['title' => 'Masuk'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }} · NOA</title>
        <link rel="icon" href="{{ asset('images/noa-mark.svg') }}" type="image/svg+xml">
        <script>(function(){try{var t=localStorage.getItem('noa.tema');if(!t&&window.matchMedia('(prefers-color-scheme: dark)').matches){t='dark'}if(t){document.documentElement.dataset.theme=t}}catch(e){}})()</script>
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/masuk.js'])
    </head>
    <body class="min-h-screen bg-page font-sans text-ink antialiased">
        <div class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(460px,540px)]">
            {{-- Hero interaktif (kanvas): hanya di layar lebar. Selalu gelap agar kontras tetap baik di kedua tema. --}}
            <aside data-hero class="relative hidden h-screen flex-col justify-between gap-4 overflow-hidden bg-[#05120d] p-10 text-white lg:sticky lg:top-0 lg:flex xl:px-14 xl:py-10">
                <canvas data-medan class="absolute inset-0 size-full" aria-hidden="true"></canvas>
                <div class="hero-cahaya pointer-events-none absolute inset-0" aria-hidden="true"></div>

                <div class="relative z-10 flex items-center justify-between">
                    <a href="{{ route('login') }}" aria-label="NOA, ke halaman masuk" class="masuk-naik" style="--d:.05s"><x-logo :animasi="true" :ukuran="40" /></a>
                    <span class="masuk-naik inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3.5 py-1.5 text-xs font-semibold text-white/80 backdrop-blur-md" style="--d:.15s">
                        <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-[#7ed6b0] opacity-60"></span><span class="relative inline-flex size-2 rounded-full bg-[#7ed6b0]"></span></span>
                        Pantau setiap periode pemeliharaan
                    </span>
                </div>

                <div class="relative z-10 max-w-[640px] space-y-4">
                    <p class="font-heading text-[clamp(2rem,min(4.2vw,7.2vh),3.9rem)] font-semibold leading-[1.04] tracking-[-0.035em]">
                        @foreach (explode(' ', 'Catatan kandang yang jujur. Panen yang adil.') as $i => $kata)
                            <span class="kata {{ in_array($kata, ['jujur.', 'adil.'], true) ? 'kata-emas' : '' }}" style="--i:{{ $i }}">{{ $kata }}</span>
                        @endforeach
                    </p>
                    <p class="masuk-naik max-w-[520px] text-[15px] leading-[24px] text-white/70" style="--d:.9s">
                        NOA membantu peternak plasma mencatat harian, menghitung performa, dan mencocokkannya dengan RHPP dari inti. Semuanya dengan rumus yang bisa dilihat.
                    </p>
                </div>

                {{-- Kartu produk melayang (contoh data) --}}
                <div class="relative z-10 masuk-naik [@media(max-height:600px)]:hidden" style="--d:1.1s">
                    <div data-miring="1" class="kaca ayun max-w-[560px] rounded-[22px] p-5 will-change-transform" data-sorot>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[1.2px] text-white/50">Contoh tampilan · Periode berjalan</p>
                                <p class="mt-0.5 text-sm font-semibold">Kandang A · Hari ke-23</p>
                            </div>
                            <span class="rounded-full bg-[#7ed6b0]/15 px-2.5 py-1 text-[11px] font-bold text-[#7ed6b0]">Berjalan</span>
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-4">
                            <div><p class="text-[11px] text-white/50">Bobot rata-rata</p><p class="font-heading text-2xl font-semibold tabular-nums"><span data-hitung="1.42" data-desimal="2">1,42</span><span class="ml-1 text-xs font-medium text-white/50">kg</span></p></div>
                            <div><p class="text-[11px] text-white/50">FCR sementara</p><p class="font-heading text-2xl font-semibold tabular-nums" data-hitung="1.63" data-desimal="2">1,63</p></div>
                            <div><p class="text-[11px] text-white/50">Deplesi</p><p class="font-heading text-2xl font-semibold tabular-nums"><span data-hitung="2.1" data-desimal="1">2,1</span><span class="ml-0.5 text-xs font-medium text-white/50">%</span></p></div>
                        </div>
                        <canvas data-grafik class="mt-3 h-[96px] w-full" aria-hidden="true"></canvas>
                        <div class="mt-1 flex items-center gap-4 text-[11px] text-white/50">
                            <span class="inline-flex items-center gap-1.5"><span class="h-0.5 w-4 rounded bg-[#7ed6b0]"></span> Catatan Anda</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-0 w-4 border-t-2 border-dashed border-[#e7c16b]"></span> Standar inti</span>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 grid max-w-[640px] grid-cols-3 gap-3 [@media(max-height:780px)]:hidden">
                    @foreach ([['clipboard-list', 'Catat', 'Kurang dari satu menit per hari'], ['calculator', 'Hitung', 'FCR dan IP otomatis, rumus terlihat'], ['columns-2', 'Cocokkan', 'Banding dengan RHPP baris demi baris']] as $n => [$ikon, $judul, $teks])
                        <div data-sorot class="kaca kartu-sorot masuk-naik rounded-[18px] p-4" style="--d:{{ 1.3 + $n * 0.1 }}s">
                            <x-icon :name="$ikon" class="size-[18px] text-[#7ed6b0]" />
                            <p class="mt-2.5 text-sm font-semibold">{{ $judul }}</p>
                            <p class="mt-0.5 text-xs leading-[17px] text-white/55">{{ $teks }}</p>
                        </div>
                    @endforeach
                </div>
            </aside>

            {{-- Panel formulir --}}
            <div class="auth panel-form relative flex min-h-screen flex-col px-5 py-5 sm:px-10">
                <div class="flex items-center justify-between">
                    <a href="{{ route('login') }}" aria-label="NOA, ke halaman masuk" class="lg:hidden"><x-logo :ukuran="34" /></a>
                    <button type="button" x-data="tema()" @click="alternatif()" :aria-label="gelap ? 'Ganti ke tema terang' : 'Ganti ke tema gelap'"
                        class="ml-auto grid size-11 place-items-center rounded-full border border-line bg-surface/70 text-ink-secondary backdrop-blur transition hover:bg-state-hover active:scale-95">
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
