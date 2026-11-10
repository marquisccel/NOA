<x-guest-layout title="Kata sandi diperbarui">
    <div class="space-y-2">
        <span class="grid size-11 place-items-center rounded-md bg-success-soft text-success-text"><x-icon name="circle-check" /></span>
        <h1 class="pt-2 font-heading text-[26px] font-semibold tracking-[-0.4px]">Kata sandi diperbarui</h1>
        <p class="text-sm leading-[21px] text-ink-secondary">Kata sandi baru berhasil disimpan. Silakan masuk kembali menggunakan kata sandi baru Anda.</p>
    </div>

    <x-button :href="route('login')" class="w-full">Masuk ke NOA</x-button>
</x-guest-layout>
