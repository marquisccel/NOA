<x-guest-layout title="Verifikasi email">
    <div class="space-y-2">
        <h1 class="font-heading text-[26px] font-semibold tracking-[-0.4px]">Verifikasi email Anda</h1>
        <p class="text-sm leading-[21px] text-ink-secondary">
            Terima kasih sudah mendaftar. Sebelum mulai, klik tautan verifikasi yang baru kami kirim ke email Anda. Belum menerimanya? Kami bisa mengirim ulang.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <x-alert variant="success">Tautan verifikasi baru telah dikirim ke email yang Anda daftarkan.</x-alert>
    @endif

    <div class="space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-button class="w-full">Kirim ulang email verifikasi</x-button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-button variant="ghost" class="w-full">Keluar</x-button>
        </form>
    </div>
</x-guest-layout>
