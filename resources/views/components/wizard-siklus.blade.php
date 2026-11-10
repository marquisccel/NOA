@props(['kandang', 'langkah', 'konteks'])

@php
    $langkahSemua = \App\Http\Controllers\SiklusBaruController::LANGKAH;
    $terakhir = $konteks['terakhir'];
@endphp

<x-app-layout title="Buka Siklus Baru" :crumbs="[['Kandang & Siklus', route('kandang.index')], [$kandang->nama_farm, route('kandang.show', $kandang)], ['Buka siklus baru']]">
    <x-page>
        <div class="space-y-3">
            <a href="{{ route('kandang.show', $kandang) }}" class="inline-flex min-h-11 items-center gap-1.5 text-[13px] font-semibold text-ink-brand hover:underline">
                <x-icon name="arrow-left" class="size-4" /> Kembali ke detail {{ $kandang->nama_farm }}
            </a>
            <x-page-header :title="'Buka Siklus Baru · '.$kandang->nama_farm" />
        </div>

        {{-- Stepper 5 langkah --}}
        <nav aria-label="Langkah buka siklus" class="overflow-x-auto">
            <ol class="flex min-w-max gap-2 sm:min-w-0 sm:grid sm:grid-cols-5">
                @foreach ($langkahSemua as $no => $nama)
                    @php $selesai = $no < $langkah; $aktif = $no === $langkah; @endphp
                    <li class="flex-1">
                        @if ($selesai)
                            <a href="{{ route('siklus.baru.langkah', [$kandang, $no]) }}" class="flex min-h-11 items-center gap-2.5 rounded-md border border-brand-border bg-tint px-3 py-2">
                        @else
                            <div @if ($aktif) aria-current="step" @endif class="flex min-h-11 items-center gap-2.5 rounded-md border px-3 py-2 {{ $aktif ? 'border-brand bg-surface' : 'border-line-subtle bg-surface' }}">
                        @endif
                            <span class="grid size-6 shrink-0 place-items-center rounded-full text-xs font-bold {{ $selesai ? 'bg-brand text-white' : ($aktif ? 'bg-brand text-white' : 'bg-neutral-soft text-ink-secondary') }}">
                                @if ($selesai)<x-icon name="check" class="size-3.5" />@else{{ $no }}@endif
                            </span>
                            <span class="leading-tight">
                                <span class="block text-[10px] font-bold uppercase tracking-[1px] text-ink-muted">Langkah {{ $no }}</span>
                                <span class="block text-[13px] font-semibold {{ $aktif ? 'text-ink-brand' : 'text-ink' }}">{{ $nama }}</span>
                            </span>
                        @if ($selesai)</a>@else</div>@endif
                    </li>
                @endforeach
            </ol>
        </nav>

        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div class="rounded-[16px] border border-line bg-surface p-5 sm:p-6">
                {{ $slot }}
            </div>

            <aside class="space-y-3 rounded-[16px] border border-line bg-surface p-5" aria-label="Konteks kandang">
                <p class="text-[11px] font-bold uppercase tracking-[1.2px] text-ink-muted">Siklus untuk</p>
                <dl class="space-y-3 text-[13px]">
                    <div><dt class="text-ink-muted">Kandang</dt><dd class="mt-0.5 font-semibold">{{ $kandang->nama_farm }}</dd></div>
                    <div><dt class="text-ink-muted">Tipe</dt><dd class="mt-0.5 font-semibold">{{ $kandang->labelTipe() ?? '–' }}</dd></div>
                    <div><dt class="text-ink-muted">Alamat</dt><dd class="mt-0.5 font-semibold">{{ $kandang->alamat ?: '–' }}</dd></div>
                    <div><dt class="text-ink-muted">Kapasitas</dt><dd class="mt-0.5 font-semibold">{{ number_format($kandang->kapasitas, 0, ',', '.') }} ekor</dd></div>
                    <div><dt class="text-ink-muted">Siklus berikutnya</dt><dd class="mt-0.5 font-semibold">Siklus {{ $konteks['berikutnya'] }}</dd></div>
                    <div><dt class="text-ink-muted">Siklus terakhir</dt>
                        <dd class="mt-0.5 font-semibold">
                            @if ($terakhir) Siklus {{ $konteks['nomor_terakhir'] }} · {{ $terakhir->labelStatus() }}@if ($terakhir->ditutup_pada) {{ $terakhir->ditutup_pada->translatedFormat('j M Y') }}@endif
                            @else Belum ada @endif
                        </dd>
                    </div>
                </dl>
                <p class="border-t border-line-subtle pt-3 text-xs leading-[18px] text-ink-muted">Isian tersimpan sebagai draf. Siklus baru dibuka setelah Langkah 5.</p>
            </aside>
        </div>
    </x-page>
</x-app-layout>
