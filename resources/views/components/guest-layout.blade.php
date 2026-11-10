@props(['title' => 'Masuk'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }} · NOA</title>
        <link rel="icon" href="{{ asset('images/noa-mark.webp') }}" type="image/webp">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-page font-sans text-ink antialiased">
        <div class="flex min-h-screen gap-6 p-0 lg:p-6">
            <div class="flex min-w-0 flex-1 flex-col justify-between px-4 py-4 sm:px-8 sm:py-2">
                <a href="{{ route('login') }}" class="flex h-14 items-center self-start" aria-label="NOA, ke halaman masuk"><x-logo /></a>

                <main class="flex justify-center py-6">
                    <div class="w-full max-w-[440px] space-y-6 rounded-lg border border-line bg-surface p-6 sm:p-9">
                        {{ $slot }}
                    </div>
                </main>

                <p class="text-xs text-ink-muted">© {{ date('Y') }} NOA · Nitip, Openi, Adil</p>
            </div>

            {{-- Panel merek: hanya di layar lebar --}}
            <aside class="hidden w-[560px] shrink-0 flex-col justify-between rounded-xl bg-brand p-10 lg:flex" aria-hidden="true">
                <div class="grid h-[44%] min-h-[260px] place-items-center rounded-lg bg-[#FAF8F0] p-8">
                    <img src="{{ asset('images/noa-logo.webp') }}" alt="" width="520" height="237" class="h-auto w-full max-w-[420px]" loading="lazy">
                </div>
                <div class="space-y-3.5">
                    <p class="text-xs font-semibold uppercase tracking-[1.4px] text-brand-mint">Pantau siklus secara real-time</p>
                    <p class="font-heading text-4xl font-semibold leading-[41px] tracking-[-0.8px] text-white">Data kandang yang rapi, panen yang lebih pasti.</p>
                    <p class="text-[15px] leading-[23px] text-white/80">Catat mortalitas, bobot, pakan, dan OVK harian — lalu lihat FCR dan IP siklus langsung di dashboard.</p>
                </div>
            </aside>
        </div>
    </body>
</html>
