@php
    use App\Support\Format as F;

    $kelasInput = 'block min-h-11 w-full rounded-md border-line-control bg-surface px-2.5 text-base focus:border-brand focus:ring-1 focus:ring-brand';
@endphp

<x-app-layout title="Isi banyak hari" :siklus="$siklus">
    <x-page>
        <x-page-header title="Isi banyak hari sekaligus" subtitle="Cocok untuk mengejar catatan di buku. Hari yang dikosongkan akan dilewati.">
            <x-slot name="actions"><x-button variant="secondary" :href="route('recording.index', $siklus)">Batal</x-button></x-slot>
        </x-page-header>

        @if ($errors->has('baris'))<x-alert variant="error">{{ $errors->first('baris') }}</x-alert>@endif
        @if ($sisa > 0)<x-alert variant="info">Menampilkan 14 hari terlama yang belum diisi. Setelah disimpan, {{ $sisa }} hari lainnya bisa diisi di putaran berikutnya.</x-alert>@endif

        <form method="POST" action="{{ route('recording.massal.simpan', $siklus) }}" novalidate x-data="{ ada: 0 }"
              x-init="ada = $el.querySelectorAll('input[data-pakan]').length" class="space-y-4">
            @csrf
            <div class="overflow-x-auto rounded-[16px] border border-line bg-surface">
                <table class="tabel min-w-[760px]">
                    <caption class="sr-only">Isian beberapa hari sekaligus</caption>
                    <thead>
                        <tr>
                            <th scope="col">Hari</th><th scope="col">Pakan terpakai (sak)</th><th scope="col">Pakan masuk (sak)</th><th scope="col">Ayam mati</th><th scope="col">Ayam afkir</th><th scope="col">Sebab afkir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($hari as $h)
                            @php $tanggal = $siklus->tanggal_masuk->copy()->addDays($h - 1); @endphp
                            <tr x-data="{ afkir: @js((int) old('baris.'.$h.'.afkir', 0)) }">
                                <th scope="row" class="whitespace-nowrap">
                                    Hari ke-{{ $h }}<span class="block text-xs font-normal text-ink-secondary">{{ $tanggal->translatedFormat('D, j M') }}@if (isset($stdHari[$h])) · std {{ F::angka($stdHari[$h], 1) }} sak @endif</span>
                                </th>
                                <td class="min-w-32">
                                    <input type="number" step="0.01" min="0" inputmode="decimal" data-pakan name="baris[{{ $h }}][pakan_pakai_sak]" value="{{ old('baris.'.$h.'.pakan_pakai_sak') }}" aria-label="Pakan terpakai hari ke-{{ $h }}" class="{{ $kelasInput }}">
                                    <x-input-error class="mt-1" :messages="$errors->get('baris.'.$h.'.pakan_pakai_sak')" />
                                </td>
                                <td class="min-w-28"><input type="number" step="0.01" min="0" inputmode="decimal" name="baris[{{ $h }}][pakan_masuk_sak]" value="{{ old('baris.'.$h.'.pakan_masuk_sak') }}" aria-label="Pakan masuk hari ke-{{ $h }}" class="{{ $kelasInput }}"></td>
                                <td class="min-w-24"><input type="number" step="1" min="0" inputmode="numeric" name="baris[{{ $h }}][mati]" value="{{ old('baris.'.$h.'.mati') }}" aria-label="Ayam mati hari ke-{{ $h }}" class="{{ $kelasInput }}"></td>
                                <td class="min-w-24"><input type="number" step="1" min="0" inputmode="numeric" name="baris[{{ $h }}][afkir]" x-model.number="afkir" aria-label="Ayam afkir hari ke-{{ $h }}" class="{{ $kelasInput }}"></td>
                                <td class="min-w-32">
                                    <select name="baris[{{ $h }}][sebab_afkir]" x-bind:disabled="!(afkir > 0)" aria-label="Sebab afkir hari ke-{{ $h }}" class="{{ $kelasInput }} disabled:opacity-40">
                                        <option value="">Pilih</option>
                                        @foreach (\App\Models\RecordingHarian::SEBAB_AFKIR as $sebab)<option value="{{ $sebab }}" @selected(old('baris.'.$h.'.sebab_afkir') === $sebab)>{{ ucfirst($sebab) }}</option>@endforeach
                                    </select>
                                    <x-input-error class="mt-1" :messages="$errors->get('baris.'.$h.'.sebab_afkir')" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-ink-secondary">Pakan terpakai wajib bila satu baris diisi. Ayam mati dan afkir yang kosong dianggap 0.</p>
                <x-button>Simpan semua yang terisi</x-button>
            </div>
        </form>
    </x-page>
</x-app-layout>
