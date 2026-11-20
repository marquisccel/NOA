@props(['siklus'])

@php
    $daftar = app(\App\Services\PenjelasRumus::class)->untukSiklus($siklus);
@endphp

@if ($daftar)
    {{-- Setiap angka dijelaskan dengan rumus dan angka periode ini sendiri: transparan dan bisa diperiksa. --}}
    <details class="group kartu" data-tur="Kenapa angkanya begini?|Buka ini untuk melihat rumus tiap angka lengkap dengan angka periode Anda sendiri. Tidak ada angka ajaib." data-tur-urut="6">
        <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-3 px-5 py-3">
            <span class="flex items-center gap-2.5">
                <span class="grid size-9 place-items-center rounded-md bg-tint text-ink-brand"><x-icon name="calculator" class="size-5" /></span>
                <span>
                    <span class="block text-sm font-bold">Kenapa angkanya begini?</span>
                    <span class="block text-xs text-ink-secondary">Lihat cara hitung tiap angka dengan angka periode ini</span>
                </span>
            </span>
            <x-icon name="chevron-down" class="size-5 text-ink-muted transition group-open:rotate-180" />
        </summary>
        <div class="space-y-3 border-t border-line-subtle p-5">
            @foreach ($daftar as $d)
                <article id="rumus-{{ $d['kunci'] }}" class="rounded-md border border-line-subtle p-4">
                    <h3 class="text-sm font-bold">{{ $d['judul'] }}</h3>
                    <p class="mt-1 text-[13px] text-ink-secondary">Rumus: {{ $d['rumus'] }}</p>
                    <p class="mt-2 rounded-md bg-page px-3 py-2 text-[13px]"><span class="text-ink-secondary">Angka Anda:</span> {{ $d['angka'] }} <span class="font-bold text-ink-brand">= {{ $d['hasil'] }}</span></p>
                    @if ($d['catatan'])<p class="mt-2 text-xs text-ink-muted">{{ $d['catatan'] }}</p>@endif
                </article>
            @endforeach
        </div>
    </details>
@endif
