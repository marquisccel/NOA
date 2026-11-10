@php use App\Support\Format as F; @endphp

{{-- Tabel di layar lebar, kartu di ponsel. Status akhir memakai kata, bukan hanya warna. --}}
<div class="hidden overflow-x-auto md:block">
    <table class="tabel">
        <caption class="sr-only">Riwayat siklus kandang {{ $kandang->nama_farm }}</caption>
        <thead>
            <tr>
                <th scope="col">Siklus</th>
                <th scope="col">DOC masuk</th>
                <th scope="col">Populasi awal</th>
                <th scope="col">Status akhir</th>
                <th scope="col">Panen terakhir</th>
                <th scope="col">Re-chick-in</th>
                <th scope="col"><span class="sr-only">Aksi</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($daftar as $s)
                <tr>
                    <th scope="row" class="font-bold">Siklus {{ $nomor[$s->id] ?? 1 }}</th>
                    <td class="whitespace-nowrap">{{ $s->tanggal_masuk->translatedFormat('j M Y') }}</td>
                    <td class="whitespace-nowrap">{{ F::angka($s->populasi, 0) }} ekor</td>
                    <td><x-status-siklus :siklus="$s" /></td>
                    <td class="whitespace-nowrap">{{ ($panenTerakhir[$s->id] ?? null)?->translatedFormat('j M Y') ?? '—' }}</td>
                    <td class="whitespace-nowrap">{{ $s->tanggal_rechick?->translatedFormat('j M Y') ?? '—' }}</td>
                    <td class="text-right"><a href="{{ route('siklus.show', $s) }}" class="inline-flex min-h-11 items-center whitespace-nowrap text-[13px] font-semibold text-ink-brand hover:underline">Lihat siklus</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<ul class="divide-y divide-line-subtle border-t border-line-subtle md:hidden">
    @foreach ($daftar as $s)
        <li>
            <a href="{{ route('siklus.show', $s) }}" class="flex flex-col gap-1.5 p-4 hover:bg-state-hover">
                <span class="flex items-center justify-between gap-2">
                    <span class="font-heading text-base font-semibold">Siklus {{ $nomor[$s->id] ?? 1 }}</span>
                    <x-status-siklus :siklus="$s" />
                </span>
                <span class="text-[13px] text-ink-secondary">DOC {{ $s->tanggal_masuk->translatedFormat('j M Y') }} · {{ F::angka($s->populasi, 0) }} ekor</span>
                <span class="text-[13px] text-ink-secondary">Panen terakhir {{ ($panenTerakhir[$s->id] ?? null)?->translatedFormat('j M Y') ?? '—' }} · Re-chick-in {{ $s->tanggal_rechick?->translatedFormat('j M Y') ?? '—' }}</span>
            </a>
        </li>
    @endforeach
</ul>
