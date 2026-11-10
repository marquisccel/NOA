@php
    $s = $a->siklusDitugaskan;
    $idModal = $idModal ?? 'nonaktif-'.$a->id;
@endphp

<x-modal :name="$idModal">
    <h2 id="{{ $idModal }}-judul" class="font-heading text-lg font-semibold">Nonaktifkan akun {{ $a->name }}?</h2>
    <p class="text-sm leading-[21px] text-ink-secondary">
        {{ $a->name }} tidak akan bisa masuk ke NOA{{ $s ? ' dan kehilangan akses ke '.$s->kandang->nama_farm : '' }}. Data recording yang sudah dicatat tetap tersimpan dan bisa diaktifkan kembali kapan saja.
    </p>
    <form method="POST" action="{{ route('abk.status', $a) }}" class="flex justify-end gap-2.5 pt-2">
        @csrf @method('PATCH')
        <input type="hidden" name="aktif" value="0">
        <x-button type="button" variant="secondary" x-on:click="$dispatch('close-modal', '{{ $idModal }}')">Batal</x-button>
        <x-button variant="danger">Nonaktifkan</x-button>
    </form>
</x-modal>
