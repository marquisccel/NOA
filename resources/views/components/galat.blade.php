@props(['kode', 'judul', 'teks', 'ikon' => 'info', 'warna' => 'info', 'tombol', 'tujuan' => null, 'muatUlang' => false, 'label' => null])

@php
    $warnaIkon = [
        'info' => 'bg-info-soft text-info-text',
        'warning' => 'bg-warning-soft text-warning-text',
        'danger' => 'bg-danger-soft text-danger-text',
    ][$warna] ?? 'bg-info-soft text-info-text';
@endphp

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $judul }} · NOA</title>
        <link rel="icon" href="{{ asset('images/noa-mark.webp') }}" type="image/webp">
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-page font-sans text-ink antialiased">
        {{-- Halaman galat berdiri sendiri: tidak bergantung pada sesi, basis data, atau navigasi agar tetap tampil saat sistem bermasalah. --}}
        <div class="flex min-h-screen flex-col items-center justify-center gap-6 px-4 py-10">
            <a href="{{ url('/') }}" class="flex h-14 items-center" aria-label="NOA"><x-logo /></a>
            <main class="w-full max-w-md space-y-3 rounded-[16px] border border-line bg-surface px-6 py-10 text-center sm:px-9" role="main">
                <span class="mx-auto grid size-12 place-items-center rounded-full {{ $warnaIkon }}"><x-icon :name="$ikon" class="size-6" /></span>
                <p class="text-[11px] font-bold uppercase tracking-[1.4px] text-ink-muted">{{ $label ?? 'Kode '.$kode }}</p>
                <h1 class="font-heading text-xl font-semibold">{{ $judul }}</h1>
                <p class="text-sm leading-[21px] text-ink-secondary">{{ $teks }}</p>
                <div class="pt-3">
                    @if ($muatUlang)
                        <x-button type="button" onclick="window.location.reload()">{{ $tombol }}</x-button>
                    @else
                        <x-button :href="$tujuan">{{ $tombol }}</x-button>
                    @endif
                </div>
            </main>
        </div>
    </body>
</html>
