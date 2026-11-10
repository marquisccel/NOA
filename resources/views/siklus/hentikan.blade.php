@php
    use App\Support\Format as F;

    $nomor = \App\Models\Siklus::nomorMap([$siklus->kandang_id])[$siklus->id] ?? 1;
@endphp

<x-app-layout title="Hentikan siklus dini" :siklus="$siklus"
    :crumbs="[['Kandang & Siklus', route('kandang.index')], [$siklus->kandang->nama_farm, route('kandang.show', $siklus->kandang)], ['Siklus '.$nomor, route('siklus.show', $siklus)], ['Hentikan dini']]">
    <x-page>
        <x-page-header title="Hentikan siklus dini" subtitle="Untuk kejadian luar biasa seperti wabah. Data siklus akan dikunci." />

        @if ($errors->has('siklus'))<x-alert variant="error">{{ $errors->first('siklus') }}</x-alert>@endif

        <form method="POST" action="{{ route('siklus.hentikan.simpan', $siklus) }}" class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)]"
              onsubmit="return confirm('Hentikan siklus ini? Data akan dikunci dan angka ditandai tidak sebanding.')">
            @csrf
            <div class="space-y-5 rounded-[16px] border border-line bg-surface p-5 sm:p-6">
                <h2 class="text-base font-bold">Data penghentian</h2>
                <div>
                    <x-input-label for="alasan_penutupan" value="Alasan *" />
                    <select id="alasan_penutupan" name="alasan_penutupan" required class="mt-2 block min-h-[50px] w-full rounded-md border-line-control bg-surface px-3.5 text-base focus:border-brand focus:ring-1 focus:ring-brand">
                        @foreach ($alasan as $a)<option value="{{ $a }}" @selected(old('alasan_penutupan', $alasan[0]) === $a)>{{ $a }}</option>@endforeach
                    </select>
                    <x-input-error class="mt-1.5" :messages="$errors->get('alasan_penutupan')" />
                </div>
                <div>
                    <x-input-label for="keterangan_penutupan" value="Keterangan *" />
                    <textarea id="keterangan_penutupan" name="keterangan_penutupan" rows="3" maxlength="1000" required class="mt-2 block w-full rounded-md border-line-control bg-surface px-3.5 py-2.5 text-base focus:border-brand focus:ring-1 focus:ring-brand">{{ old('keterangan_penutupan') }}</textarea>
                    <x-input-error class="mt-1.5" :messages="$errors->get('keterangan_penutupan')" />
                </div>
                <x-field name="tanggal_dihentikan" label="Tanggal dihentikan *" type="date" :value="now()->toDateString()" min="{{ $siklus->tanggal_masuk->toDateString() }}" max="{{ now()->toDateString() }}" required />
                <div>
                    <x-input-label value="Populasi tersisa" />
                    <div class="mt-2 flex min-h-[50px] items-center rounded-md border border-line bg-page px-3.5 text-lg font-semibold">{{ F::angka($tersisa, 0) }}<span class="ml-2 text-base font-normal text-ink-secondary">ekor</span></div>
                    <p class="mt-1.5 text-xs text-ink-muted">Sesuai recording terakhir{{ $hari_terakhir ? ' (hari ke-'.$hari_terakhir.')' : '' }}</p>
                </div>
                <x-alert variant="warning"><p class="font-semibold">FCR dan IP ditandai “tidak sebanding”</p><p>Angka tetap dihitung dari data yang ada, tetapi tidak dibandingkan dengan siklus normal atau standar.</p></x-alert>
                <div class="flex flex-wrap items-center justify-end gap-2.5 border-t border-line-subtle pt-4">
                    <x-button variant="secondary" :href="route('siklus.pengaturan', $siklus)">Batal</x-button>
                    <x-button variant="danger">Hentikan siklus</x-button>
                </div>
            </div>

            <aside class="space-y-3 rounded-[16px] border border-line bg-surface p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-ink-muted">Data saat ini</p>
                <dl class="space-y-2.5 text-sm">
                    @foreach ([
                        ['Populasi awal', F::angka($siklus->populasi, 0).' ekor'],
                        ['Deplesi'.($hari_terakhir ? ' s.d. hari ke-'.$hari_terakhir : ''), F::angka($deplesi, 0).' ekor · '.F::angka($deplesi_persen).'%'],
                        ['Populasi tersisa', F::angka($tersisa, 0).' ekor'],
                        ['Pakan terpakai', F::angka($pakan_sak).' sak'],
                        ['ABW terakhir', $abw_gram === null ? '–' : F::angka($abw_gram, 0).' g (hari ke-'.$abw_hari.')'],
                    ] as [$k, $v])
                        <div class="flex justify-between gap-3"><dt class="text-ink-secondary">{{ $k }}</dt><dd class="font-semibold">{{ $v }}</dd></div>
                    @endforeach
                    <div class="flex justify-between gap-3 border-t border-line-subtle pt-2.5"><dt class="text-ink-secondary">Status setelahnya</dt><dd class="font-semibold">Dihentikan · terkunci</dd></div>
                </dl>
            </aside>
        </form>
    </x-page>
</x-app-layout>
