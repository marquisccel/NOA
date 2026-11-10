@php
    $peta = fn (\App\Models\Siklus $s) => $s->kandang->nama_farm.' · Siklus '.($nomor[$s->id] ?? 1);
    $aktif = $akses->filter->masihAktif();
@endphp

<x-app-layout title="Pengguna & Akses" :crumbs="[['Beranda', route('dashboard')], ['Pengguna & Akses']]">
    <x-page>
        <x-page-header title="Pengguna & Akses" subtitle="Kelola akun ABK dan akses hanya-baca Pengawas Inti" />
        <x-tab-pengguna aktif="akses" />

        @if (session('sandi_pengawas'))
            <x-alert variant="success">
                <strong>Akun Pengawas dibuat untuk {{ session('sandi_pengawas.email') }}. Kata sandi awal:</strong>
                <code class="mx-1 rounded bg-surface px-2 py-0.5 text-sm font-bold text-ink">{{ session('sandi_pengawas.sandi') }}</code>
                Bagikan langsung ke Pengawas. Kata sandi ini hanya ditampilkan sekali; Pengawas bisa menggantinya di Pengaturan.
            </x-alert>
        @endif

        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-4">
                @if ($akses->isEmpty())
                    <x-empty-state icon="shield-check" title="Belum ada akses Pengawas"
                        text="Bagikan satu siklus ke Pengawas Inti agar mereka dapat melihat recording, sampling, OVK, dan panen tanpa bisa mengubahnya." />
                @else
                    <div class="overflow-x-auto rounded-[16px] border border-line bg-surface">
                        <table class="tabel min-w-[640px]">
                            <caption class="sr-only">Akses Pengawas Inti per siklus</caption>
                            <thead>
                                <tr><th scope="col">Pengawas</th><th scope="col">Siklus dibagikan</th><th scope="col">Diberikan</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Aksi</span></th></tr>
                            </thead>
                            <tbody>
                                @foreach ($akses as $a)
                                    <tr>
                                        <th scope="row"><span class="font-bold">{{ $a->pengawas->name }}</span>@if ($a->siklus->nama_inti)<span class="font-normal text-ink-secondary"> · {{ $a->siklus->nama_inti }}</span>@endif<span class="block text-xs font-normal text-ink-muted">{{ $a->pengawas->email }}</span></th>
                                        <td>{{ $peta($a->siklus) }}</td>
                                        <td class="whitespace-nowrap">{{ $a->created_at->translatedFormat('j M Y') }}</td>
                                        <td>
                                            @if ($a->masihAktif())<x-badge variant="success" :dot="true">Aktif</x-badge>
                                            @else<x-badge variant="neutral">Dicabut {{ $a->dicabut_pada->translatedFormat('j M Y') }}</x-badge>@endif
                                        </td>
                                        <td class="whitespace-nowrap text-right">
                                            @if ($a->masihAktif())
                                                <button type="button" x-data @click="$dispatch('open-modal', 'cabut-{{ $a->id }}')" class="inline-flex min-h-11 items-center px-2 text-[13px] font-semibold text-danger-text hover:underline">Cabut akses</button>
                                            @else
                                                <span class="text-ink-muted">–</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <x-alert variant="info">
                    <p>Pengawas hanya melihat recording, sampling, OVK, dan panen pada <strong>satu</strong> siklus. Kontrak, biaya, bonus, RHPP, dan rekonsiliasi tidak terlihat. Pengawas tidak memberi paraf.</p>
                </x-alert>
            </div>

            <section class="space-y-4 rounded-[16px] border border-line bg-surface p-5" aria-labelledby="beri-akses">
                <h2 id="beri-akses" class="text-base font-bold">Beri akses ke satu siklus</h2>
                @if ($siklus->isEmpty())
                    <p class="text-[13px] text-ink-secondary">Belum ada siklus. Buka siklus di Kandang &amp; Siklus lebih dulu.</p>
                @else
                    <form method="POST" action="{{ route('akses.store') }}" class="space-y-4" novalidate>
                        @csrf
                        <x-field name="email" label="Email Pengawas *" type="email" placeholder="nama@inti.co.id" inputmode="email" autocomplete="off" />
                        <div>
                            <x-input-label for="siklus_id" value="Siklus *" />
                            <select id="siklus_id" name="siklus_id" class="mt-2 block min-h-[46px] w-full rounded-md border-line-control bg-surface px-3.5 text-base focus:border-brand focus:ring-1 focus:ring-brand">
                                @foreach ($siklus as $s)
                                    <option value="{{ $s->id }}" @selected((int) old('siklus_id') === $s->id)>{{ $peta($s) }} ({{ $s->labelStatus() }})</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-1.5" :messages="$errors->get('siklus_id')" />
                            <p class="mt-1.5 text-xs text-ink-muted">Satu akses = satu siklus. Akses berakhir otomatis bila dicabut.</p>
                        </div>
                        <p class="text-xs text-ink-muted">Bila email belum punya akun, akun Pengawas dibuat dengan kata sandi awal yang tampil sekali.</p>
                        <x-button class="w-full">Beri akses</x-button>
                    </form>
                @endif
            </section>
        </div>

        @foreach ($aktif as $a)
            <x-modal :name="'cabut-'.$a->id">
                <form method="POST" action="{{ route('akses.cabut', $a) }}" class="space-y-4">
                    @csrf @method('DELETE')
                    <h2 id="cabut-{{ $a->id }}-judul" class="font-heading text-lg font-semibold">Cabut akses {{ $a->pengawas->name }}?</h2>
                    <p class="text-sm leading-[21px] text-ink-secondary">{{ $a->pengawas->name }} tidak bisa lagi melihat {{ $peta($a->siklus) }}. Data siklus tidak berubah. Akses dapat diberikan kembali kapan saja.</p>
                    <div class="flex justify-end gap-2.5">
                        <x-button type="button" variant="secondary" @click="$dispatch('close-modal', 'cabut-{{ $a->id }}')">Batal</x-button>
                        <x-button variant="danger">Cabut akses</x-button>
                    </div>
                </form>
            </x-modal>
        @endforeach
    </x-page>
</x-app-layout>
