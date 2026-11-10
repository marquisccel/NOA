@php
    use App\Support\Format as F;

    $r = $b['recording'];
    $deplDiAtas = $deplesiStd !== null && $b['deplesi_kum_persen'] > $deplesiStd;
    $fcrKet = $b['fcr_berjalan'] === null ? 'Perlu bobot aktual atau sampling' : ($fcrStd !== null ? 'std '.F::angka($fcrStd, 3) : null);
@endphp

<x-app-layout title="Recording tersimpan" :siklus="$siklus"
    :crumbs="[['Kandang & Siklus', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['Recording', route('recording.index', $siklus)], ['Tersimpan']]">
    <x-page lebar="max-w-xl">
        <div class="space-y-5 rounded-[16px] border border-line bg-surface p-5 sm:p-7">
            <div class="flex items-start gap-3.5">
                <span class="grid size-12 shrink-0 place-items-center rounded-full bg-success-soft text-success-text"><x-icon name="circle-check" class="size-6" /></span>
                <div class="min-w-0">
                    <h1 class="font-heading text-xl font-semibold">Recording hari ke-{{ $b['hari_ke'] }} tersimpan</h1>
                    <p class="mt-1 text-sm text-ink-secondary">
                        {{ $r->tanggal->translatedFormat('j M Y') }} · pakan {{ F::angka($r->pakan_pakai_sak) }} sak · ayam mati {{ $r->mati }} ekor · ayam afkir {{ $r->afkir }} ekor
                    </p>
                </div>
            </div>

            @if ($b['stok_sak'] < 0)
                <x-alert variant="warning">
                    <p class="font-semibold">Stok pakan {{ F::angka($b['stok_sak']) }} sak</p>
                    <p>Pakan terpakai melebihi stok gudang. Mungkin ada pengiriman pakan yang belum dicatat; isi pakan masuk di detail opsional recording.</p>
                </x-alert>
            @endif

            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-ink-muted">Angka berjalan terbaru</p>
                <dl class="mt-2 grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-line-subtle p-3.5">
                        <dt class="text-xs text-ink-secondary">Deplesi kumulatif</dt>
                        <dd class="mt-1 font-heading text-2xl font-semibold {{ $deplDiAtas ? 'text-warning-text' : '' }}">{{ F::angka($b['deplesi_kum_persen']) }}%</dd>
                        @if ($deplesiStd !== null)<dd class="text-xs text-ink-muted">std {{ F::angka($deplesiStd) }}% · {{ $deplDiAtas ? 'di atas' : 'di bawah atau sama' }}</dd>@endif
                    </div>
                    <div class="rounded-md border border-line-subtle p-3.5">
                        <dt class="text-xs text-ink-secondary">Populasi kini</dt>
                        <dd class="mt-1 font-heading text-2xl font-semibold">{{ F::angka($b['ayam_hidup'], 0) }} <span class="text-sm font-medium text-ink-secondary">ekor</span></dd>
                        <dd class="text-xs text-ink-muted">dari {{ F::angka($siklus->populasi, 0) }}</dd>
                    </div>
                    <div class="rounded-md border border-line-subtle p-3.5">
                        <dt class="text-xs text-ink-secondary">Stok pakan</dt>
                        <dd class="mt-1 font-heading text-2xl font-semibold {{ $b['stok_sak'] < 0 ? 'text-danger-text' : '' }}">{{ F::angka($b['stok_sak'], 1) }} <span class="text-sm font-medium text-ink-secondary">sak</span></dd>
                    </div>
                    <div class="rounded-md border border-line-subtle p-3.5">
                        <dt class="text-xs text-ink-secondary">FCR berjalan</dt>
                        <dd class="mt-1 font-heading text-2xl font-semibold">{{ $b['fcr_berjalan'] === null ? '–' : F::angka($b['fcr_berjalan'], 3) }}</dd>
                        @if ($fcrKet)<dd class="text-xs text-ink-muted">{{ $fcrKet }}</dd>@endif
                    </div>
                </dl>
            </div>

            @if ($terlewat && Gate::allows('create', [\App\Models\RecordingHarian::class, $siklus]))
                <x-alert variant="warning">
                    <p class="font-semibold">Recording hari ke-{{ $terlewat }} belum diisi</p>
                    <p>Angka di atas belum termasuk hari ke-{{ $terlewat }} ({{ $tanggalTerlewat->translatedFormat('j M Y') }}).</p>
                </x-alert>
            @endif

            <div class="flex flex-col gap-2.5 sm:flex-row sm:justify-end">
                @if ($terlewat && Gate::allows('create', [\App\Models\RecordingHarian::class, $siklus]))
                    <x-button variant="secondary" :href="route('recording.create', [$siklus, 'tanggal' => $tanggalTerlewat->toDateString()])">Isi hari ke-{{ $terlewat }}</x-button>
                @endif
                <x-button variant="secondary" :href="route('recording.index', $siklus)">Lihat riwayat</x-button>
                <x-button :href="auth()->user()->isPeternak() ? route('siklus.show', $siklus) : route('dashboard')">Selesai</x-button>
            </div>
        </div>
    </x-page>
</x-app-layout>
