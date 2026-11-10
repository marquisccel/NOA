@php
    use App\Support\Format as F;

    $nomor = \App\Models\Siklus::nomorMap([$siklus->kandang_id])[$siklus->id] ?? 1;
    $kunci = $siklus->isTerkunci();
    $rp = fn ($n, $satuan) => $n === null ? '–' : 'Rp '.F::angka($n, 0).' / '.$satuan;
    $bonus = ['fcr' => 'Bonus FCR', 'deplesi' => 'Bonus deplesi', 'ip' => 'Bonus IP', 'harga_pasar' => 'Bonus harga pasar'];
@endphp

<x-app-layout title="Pengaturan Siklus" :siklus="$siklus"
    :crumbs="[['Kandang & Siklus', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['Siklus '.$nomor], ['Pengaturan']]">
    <x-page>
        @if ($kunci)
            <x-alert variant="info">
                <strong>Data siklus terkunci.</strong> Siklus berstatus Ditutup, Ditutup tanpa RHPP, atau Dihentikan tidak dapat diubah.
                Data tetap dapat dilihat dan dicetak dari tab Laporan. Riwayat tersimpan di Detail Kandang.
            </x-alert>
        @endif

        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-4">
                @php
                    $kartu = fn ($judul, $ubah, $hanyaPeternak = false) => compact('judul', 'ubah', 'hanyaPeternak');
                @endphp

                {{-- Identitas & DOC --}}
                <section class="rounded-[16px] border border-line bg-surface p-5" aria-labelledby="s-identitas">
                    <div class="flex items-center justify-between gap-3">
                        <h2 id="s-identitas" class="text-base font-bold">Identitas &amp; DOC</h2>
                        @if ($kunci)<x-badge variant="neutral"><x-icon name="lock" class="size-3" /> Terkunci</x-badge>
                        @else<a href="{{ route('siklus.edit', $siklus) }}#identitas" class="inline-flex min-h-11 items-center gap-1.5 text-[13px] font-semibold text-ink-brand hover:underline"><x-icon name="pencil" class="size-4" /> Ubah</a>@endif
                    </div>
                    <dl class="mt-3 grid gap-x-6 gap-y-3 text-[13px] sm:grid-cols-2">
                        <div><dt class="text-ink-muted">DOC masuk</dt><dd class="mt-0.5 font-semibold">{{ $siklus->tanggal_masuk->translatedFormat('j M Y') }}</dd></div>
                        <div><dt class="text-ink-muted">Populasi awal</dt><dd class="mt-0.5 font-semibold">{{ F::angka($siklus->populasi, 0) }} ekor</dd></div>
                        <div><dt class="text-ink-muted">Strain · pakan</dt><dd class="mt-0.5 font-semibold">{{ $siklus->strain_doc ?: '–' }} · {{ $siklus->jenis_pakan ?: '–' }}</dd></div>
                        <div><dt class="text-ink-muted">PPL · inti</dt><dd class="mt-0.5 font-semibold">{{ $siklus->nama_ppl ?: '–' }} · {{ $siklus->nama_inti ?: '–' }}</dd></div>
                        <div><dt class="text-ink-muted">Bobot per sak</dt><dd class="mt-0.5 font-semibold">{{ rtrim(rtrim(number_format((float) $siklus->bobot_sak_kg, 2, ',', '.'), '0'), ',') }} kg</dd></div>
                    </dl>
                </section>

                {{-- Kontrak --}}
                <section class="rounded-[16px] border border-line bg-surface p-5" aria-labelledby="s-kontrak">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <h2 id="s-kontrak" class="text-base font-bold">Kontrak</h2>
                            <x-badge variant="brand">Hanya peternak</x-badge>
                        </div>
                        @if ($kunci)<x-badge variant="neutral"><x-icon name="lock" class="size-3" /> Terkunci</x-badge>
                        @else<a href="{{ route('siklus.edit', $siklus) }}#kontrak" class="inline-flex min-h-11 items-center gap-1.5 text-[13px] font-semibold text-ink-brand hover:underline"><x-icon name="pencil" class="size-4" /> Ubah</a>@endif
                    </div>
                    <dl class="mt-3 grid gap-x-6 gap-y-3 text-[13px] sm:grid-cols-2">
                        <div><dt class="text-ink-muted">Harga DOC</dt><dd class="mt-0.5 font-semibold">{{ $rp($kontrak?->harga_doc, 'ekor') }}</dd></div>
                        <div><dt class="text-ink-muted">Harga pakan</dt><dd class="mt-0.5 font-semibold">{{ $rp($kontrak?->harga_pakan_sak, 'sak') }}</dd></div>
                        <div><dt class="text-ink-muted">Harga OVK</dt><dd class="mt-0.5 font-semibold">{{ $kontrak?->harga_ovk === null ? '–' : 'Rp '.F::angka($kontrak->harga_ovk, 0) }}</dd></div>
                        <div><dt class="text-ink-muted">Kontrak ayam</dt><dd class="mt-0.5 font-semibold">{{ $rp($kontrak?->harga_kontrak_ayam, 'kg') }}</dd></div>
                    </dl>
                </section>

                {{-- Skema bonus --}}
                <section class="rounded-[16px] border border-line bg-surface p-5" aria-labelledby="s-bonus">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <h2 id="s-bonus" class="text-base font-bold">Skema bonus</h2>
                            <x-badge variant="brand">Hanya peternak</x-badge>
                        </div>
                        @if ($kunci)<x-badge variant="neutral"><x-icon name="lock" class="size-3" /> Terkunci</x-badge>
                        @else<a href="{{ route('siklus.edit', $siklus) }}#bonus" class="inline-flex min-h-11 items-center gap-1.5 text-[13px] font-semibold text-ink-brand hover:underline"><x-icon name="pencil" class="size-4" /> Ubah</a>@endif
                    </div>
                    <dl class="mt-3 grid gap-x-6 gap-y-3 text-[13px] sm:grid-cols-2">
                        @foreach ($bonus as $k => $label)
                            @php $aktif = (bool) $kontrak?->{'bonus_'.$k}; $nilai = $kontrak?->{'nilai_bonus_'.$k}; @endphp
                            <div><dt class="text-ink-muted">{{ $label }}</dt><dd class="mt-0.5 font-semibold">{{ $aktif ? 'Aktif'.($nilai !== null ? ' · Rp '.F::angka($nilai, 0).' / kg' : '') : 'Nonaktif' }}</dd></div>
                        @endforeach
                    </dl>
                </section>

                {{-- Standar harian --}}
                <section class="rounded-[16px] border border-line bg-surface p-5" aria-labelledby="s-standar">
                    <div class="flex items-center justify-between gap-3">
                        <h2 id="s-standar" class="text-base font-bold">Standar harian</h2>
                        @if ($kunci)<x-badge variant="neutral"><x-icon name="lock" class="size-3" /> Terkunci</x-badge>
                        @else<a href="{{ route('siklus.standar', $siklus) }}" class="inline-flex min-h-11 items-center gap-1.5 text-[13px] font-semibold text-ink-brand hover:underline"><x-icon name="upload" class="size-4" /> {{ $standar->isEmpty() ? 'Unggah' : 'Ubah' }}</a>@endif
                    </div>
                    <dl class="mt-3 grid gap-x-6 gap-y-3 text-[13px] sm:grid-cols-2">
                        @if ($standar->isEmpty())
                            <div class="sm:col-span-2"><dt class="text-ink-muted">Tabel standar</dt><dd class="mt-0.5 font-semibold">Belum diunggah — kolom standar tampil “–”</dd></div>
                        @else
                            <div><dt class="text-ink-muted">Isi</dt><dd class="mt-0.5 font-semibold">{{ $standar->count() }} baris · hari {{ $standar->min('hari_ke') }}–{{ $standar->max('hari_ke') }}</dd></div>
                            <div><dt class="text-ink-muted">Diunggah</dt><dd class="mt-0.5 font-semibold">{{ $standar->first()->created_at?->translatedFormat('j M Y') ?? '–' }}</dd></div>
                        @endif
                        <div class="sm:col-span-2 border-t border-line-subtle pt-3">
                            <dt class="text-ink-muted">Parameter umur · jadwal vaksin</dt>
                            <dd class="mt-0.5 flex flex-wrap items-center justify-between gap-2 font-semibold">
                                <span>{{ $parameter + $vaksin === 0 ? 'Belum diisi (opsional)' : $parameter.' rentang umur · '.$vaksin.' jadwal vaksin' }}</span>
                                @unless ($kunci)<a href="{{ route('siklus.acuan', $siklus) }}" class="inline-flex min-h-11 items-center text-[13px] font-semibold text-ink-brand hover:underline">Atur</a>@endunless
                            </dd>
                        </div>
                    </dl>
                </section>

                @if (! $kunci)
                    <section class="rounded-[16px] border border-line bg-surface p-5" aria-labelledby="s-tutup">
                        <h2 id="s-tutup" class="text-base font-bold">Akhiri siklus</h2>
                        @if ($siklus->status === \App\Models\Siklus::STATUS_BERJALAN)
                            <p class="mt-1 text-[13px] text-ink-secondary">Siklus normal berakhir lewat panen: tandai panen selesai, input RHPP, lalu tutup siklus. Bila terjadi kejadian luar biasa (misalnya wabah), siklus bisa dihentikan dini.</p>
                            <div class="mt-4 flex flex-wrap gap-2.5">
                                <x-button :href="route('panen.index', $siklus)">Ke Panen</x-button>
                                <x-button variant="secondary" :href="route('siklus.hentikan', $siklus)">Hentikan siklus dini</x-button>
                            </div>
                        @else
                            <p class="mt-1 text-[13px] text-ink-secondary">Siklus sedang {{ mb_strtolower($siklus->labelStatus()) }}. Lanjutkan ke RHPP dan penutupan.</p>
                            <div class="mt-4 flex flex-wrap gap-2.5">
                                <x-button :href="route('rhpp.show', $siklus)">Ke RHPP</x-button>
                                <x-button variant="secondary" :href="route('siklus.tutup', $siklus)">Tutup siklus</x-button>
                            </div>
                        @endif
                    </section>
                @endif
            </div>

            <aside class="space-y-4">
                <section class="rounded-[16px] border border-line bg-surface p-5">
                    <h2 class="text-sm font-bold">Tentang perubahan</h2>
                    <ul class="mt-3 space-y-3 text-[13px] leading-[19px] text-ink-secondary">
                        <li>Mengubah standar harian memperbarui kolom standar di semua layar siklus ini.</li>
                        <li>Bobot per sak dipakai untuk konversi sak ↔ kg; perubahan memengaruhi FCR dan stok pakan.</li>
                        <li>Kontrak &amp; skema bonus tidak terlihat oleh ABK dan Pengawas Inti.</li>
                        <li>Ubah tidak tersedia setelah siklus ditutup.</li>
                    </ul>
                </section>
            </aside>
        </div>
    </x-page>
</x-app-layout>
