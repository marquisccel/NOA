@php use App\Support\Format as F; @endphp

<x-app-layout title="Siklus Dibagikan" :crumbs="[['Beranda', route('dashboard')], ['Siklus Dibagikan']]">
    <x-page>
        <x-page-header title="Siklus Dibagikan" subtitle="Baca saja: recording, sampling, OVK, dan panen pada satu siklus yang dibagikan peternak. Akses dapat dicabut peternak." />

        @if ($daftar->isEmpty())
            <x-empty-state icon="shield-check" title="Belum ada siklus yang dibagikan"
                text="Peternak belum membagikan siklus ke akun Anda, atau aksesnya sudah dicabut. Hubungi peternak untuk meminta akses." />
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($daftar as $d)
                    @php $s = $d['siklus']; @endphp
                    {{-- Hanya konteks siklus; tanpa kontrak, biaya, atau rekonsiliasi (R13). --}}
                    <article class="space-y-4 rounded-[16px] border border-line bg-surface p-5">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h2 class="font-heading text-lg font-semibold">Siklus {{ $d['nomor'] }}</h2>
                                <p class="mt-1 flex items-center gap-1.5 text-[13px] text-ink-secondary"><x-icon name="warehouse" class="size-3.5" /> {{ $s->kandang->nama_farm }}</p>
                            </div>
                            <x-status-siklus :siklus="$s" />
                        </div>
                        <dl class="space-y-2.5 rounded-md bg-page p-3.5 text-[13px]">
                            <div class="flex justify-between gap-3"><dt class="text-ink-secondary">Dibagikan oleh</dt><dd class="font-bold">{{ $s->kandang->user->name }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-ink-secondary">DOC masuk</dt><dd class="font-bold">{{ $s->tanggal_masuk->translatedFormat('j M Y') }}</dd></div>
                            @if ($s->isTerbuka())<div class="flex justify-between gap-3"><dt class="text-ink-secondary">Umur ayam</dt><dd class="font-bold">Hari ke-{{ $s->umurHari() }}</dd></div>@endif
                        </dl>
                        <x-button :href="route('recording.index', $s)" class="w-full">Buka siklus <x-icon name="arrow-right" class="size-4" /></x-button>
                    </article>
                @endforeach
            </div>
        @endif
    </x-page>
</x-app-layout>
