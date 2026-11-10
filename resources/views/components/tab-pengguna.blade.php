@props(['aktif' => 'abk'])

@php
    $tab = [['abk', 'Akun ABK', route('abk.index')], ['akses', 'Akses Pengawas', route('akses.index')]];
@endphp

<nav aria-label="Pengguna dan akses" class="flex gap-1.5">
    @foreach ($tab as [$kunci, $label, $url])
        <a href="{{ $url }}" @if ($aktif === $kunci) aria-current="page" @endif
           class="inline-flex min-h-11 items-center rounded-[10px] border px-4 text-sm {{ $aktif === $kunci ? 'border-line bg-surface font-bold text-ink-brand' : 'border-line-subtle font-medium text-ink-secondary hover:bg-state-hover' }}">{{ $label }}</a>
    @endforeach
</nav>
