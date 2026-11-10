@php use App\Support\Format as F; @endphp

<x-app-layout title="Pengguna & Akses" :crumbs="[['Beranda', route('dashboard')], ['Pengguna & Akses']]">
    <x-page>
        <x-page-header title="Pengguna & Akses" subtitle="Kelola akun ABK dan akses hanya-baca Pengawas Inti">
            <x-slot name="actions">
                <x-button :href="route('abk.create')"><x-icon name="user-plus" class="size-4" /> Tambah ABK</x-button>
            </x-slot>
        </x-page-header>
        <x-tab-pengguna aktif="abk" />

        @if ($total === 0)
            <x-empty-state icon="users" title="Belum ada akun ABK"
                text="Buat akun untuk anak buah kandang agar mereka bisa mencatat pakan, ayam mati, dan ayam afkir pada siklus yang ditugaskan.">
                <x-button :href="route('abk.create')"><x-icon name="user-plus" class="size-4" /> Tambah ABK pertama</x-button>
            </x-empty-state>
        @else
            <section class="rounded-[16px] border border-line bg-surface" aria-label="Daftar akun ABK">
                <form method="GET" action="{{ route('abk.index') }}" class="flex flex-wrap items-center gap-2.5 p-3 sm:p-4">
                    <div class="relative min-w-0 flex-1 sm:max-w-xs">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-muted" />
                        <input type="search" name="q" value="{{ $q }}" placeholder="Cari nama atau email" aria-label="Cari nama atau email"
                               class="block min-h-11 w-full rounded-md border-line-control bg-surface pl-9 text-base placeholder:text-ink-muted focus:border-brand focus:ring-1 focus:ring-brand">
                    </div>
                    <x-button variant="secondary" class="min-h-11">Cari</x-button>
                    <p class="ms-auto text-[13px] text-ink-secondary" role="status">{{ $total }} akun ABK · {{ $aktif }} aktif</p>
                </form>

                @if ($abk->isEmpty())
                    <p class="border-t border-line-subtle px-5 py-10 text-center text-sm text-ink-secondary">Tidak ada akun ABK dengan “{{ $q }}”.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="tabel">
                            <caption class="sr-only">Daftar akun ABK, penugasan, dan statusnya</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Nama ABK</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Kandang · Siklus ditugaskan</th>
                                    <th scope="col">Status</th>
                                    <th scope="col"><span class="sr-only">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($abk as $a)
                                    @php $s = $a->siklusDitugaskan; @endphp
                                    <tr>
                                        <th scope="row">
                                            <span class="flex items-center gap-2.5">
                                                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-tint text-xs font-bold text-ink-brand" aria-hidden="true">{{ $a->inisial() }}</span>
                                                <span class="font-bold">{{ $a->name }}</span>
                                            </span>
                                        </th>
                                        <td class="text-ink-secondary">{{ $a->email }}</td>
                                        <td>{{ $s ? $s->kandang->nama_farm.' · Siklus '.($nomor[$s->id] ?? 1) : '— (belum ditugaskan)' }}</td>
                                        <td><x-badge :variant="$a->aktif ? 'success' : 'neutral'" :dot="true">{{ $a->aktif ? 'Aktif' : 'Nonaktif' }}</x-badge></td>
                                        <td class="whitespace-nowrap text-right">
                                            <a href="{{ route('abk.edit', $a) }}" class="inline-flex min-h-11 items-center px-2 text-[13px] font-semibold text-ink-brand hover:underline">Ubah</a>
                                            @if ($a->aktif)
                                                <button type="button" x-data x-on:click="$dispatch('open-modal', 'nonaktif-{{ $a->id }}')" class="inline-flex min-h-11 items-center px-2 text-[13px] font-semibold text-danger-text hover:underline">Nonaktifkan</button>
                                            @else
                                                <form method="POST" action="{{ route('abk.status', $a) }}" class="inline">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="aktif" value="1">
                                                    <button class="inline-flex min-h-11 items-center px-2 text-[13px] font-semibold text-ink-brand hover:underline">Aktifkan</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <p class="border-t border-line-subtle p-4 text-xs leading-[18px] text-ink-muted sm:px-5">
                    ABK hanya dapat melihat konteks siklus yang ditugaskan dan mencatat pakan, ayam mati, dan ayam afkir. Kontrak, biaya, panen, dan rekonsiliasi tidak terlihat oleh ABK.
                </p>
            </section>

            @foreach ($abk->where('aktif', true) as $a)
                @include('abk.partials.dialog-nonaktif', ['a' => $a])
            @endforeach
        @endif
    </x-page>
</x-app-layout>
