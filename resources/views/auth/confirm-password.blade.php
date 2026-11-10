<x-guest-layout title="Konfirmasi kata sandi">
    <div class="space-y-2">
        <h1 class="font-heading text-[26px] font-semibold tracking-[-0.4px]">Konfirmasi kata sandi</h1>
        <p class="text-sm leading-[21px] text-ink-secondary">Ini area yang dilindungi. Masukkan kata sandi Anda sebelum melanjutkan.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-6">
        @csrf
        <x-field name="password" label="Kata sandi" type="password" icon="lock" required autofocus autocomplete="current-password" />
        <x-button class="w-full">Konfirmasi</x-button>
    </form>
</x-guest-layout>
