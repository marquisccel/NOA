@php $galatRecording = isset($errors) && $errors->has('recording'); @endphp

{{-- Pesan berhasil dan peringatan tampil sebagai notifikasi (toast) yang hilang sendiri; galat tetap tampil di halaman. --}}
@if (session('status') || session('peringatan'))
    <div class="pointer-events-none fixed inset-x-0 top-3 z-[65] flex flex-col items-center gap-2 px-3 print:hidden md:items-end md:pr-6" aria-live="polite">
        @if (session('status'))
            <div x-data="{ buka: true }" x-init="setTimeout(() => buka = false, 7000)" x-show="buka" x-transition.opacity.duration.300ms role="status"
                 class="pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-lg border border-success-border bg-success-soft p-3.5 text-[13px] leading-[19px] text-success-text shadow-lg">
                <x-icon name="circle-check" class="mt-px size-5" />
                <p class="min-w-0 flex-1 font-medium">{{ session('status') }}</p>
                <button type="button" @click="buka = false" class="grid size-7 shrink-0 place-items-center rounded-md hover:bg-surface/60" aria-label="Tutup pesan"><x-icon name="x" class="size-4" /></button>
            </div>
        @endif
        @if (session('peringatan'))
            <div x-data="{ buka: true }" x-show="buka" x-transition.opacity.duration.300ms role="alert"
                 class="pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-lg border border-warning-border bg-warning-soft p-3.5 text-[13px] leading-[19px] text-warning-text shadow-lg">
                <x-icon name="triangle-alert" class="mt-px size-5" />
                <p class="min-w-0 flex-1"><strong>Peringatan:</strong> {{ session('peringatan') }}</p>
                <button type="button" @click="buka = false" class="grid size-7 shrink-0 place-items-center rounded-md hover:bg-surface/60" aria-label="Tutup pesan"><x-icon name="x" class="size-4" /></button>
            </div>
        @endif
    </div>
@endif

@if ($galatRecording)
    <div class="mx-auto w-full max-w-7xl space-y-3 px-4 pt-4 sm:px-6 lg:px-8">
        <x-alert variant="error">{{ $errors->first('recording') }}</x-alert>
    </div>
@endif
