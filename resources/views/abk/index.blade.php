<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Akun ABK</h2>
        <p class="mt-1 text-base text-gray-700">Anak kandang (ABK) hanya bisa mengisi recording harian. Mereka tidak melihat kontrak, biaya, panen, atau rekonsiliasi RHPP.</p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <section>
                <h3 class="mb-3 text-lg font-semibold text-gray-900">Tambah akun ABK</h3>
                <form method="POST" action="{{ route('abk.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                    @csrf
                    <x-field name="name" label="Nama" required autocomplete="off" />
                    <x-field name="email" label="Email (untuk masuk)" type="email" required autocomplete="off" />
                    <div class="grid gap-6 sm:grid-cols-2">
                        <x-field name="password" label="Kata sandi" type="password" required autocomplete="new-password" hint="Minimal 8 karakter." />
                        <x-field name="password_confirmation" label="Ulangi kata sandi" type="password" required autocomplete="new-password" />
                    </div>
                    <x-primary-button class="min-h-11 text-sm">Buat akun ABK</x-primary-button>
                </form>
            </section>

            <section class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">ABK Anda</h3>
                @if ($abk->isEmpty())
                    <p class="mt-2 text-base text-gray-600">Belum ada akun ABK.</p>
                @else
                    <ul class="mt-4 divide-y divide-gray-200">
                        @foreach ($abk as $a)
                            <li class="py-3 flex flex-wrap items-center justify-between gap-2 text-base">
                                <span>{{ $a->name }} <span class="text-gray-600">· {{ $a->email }}</span></span>
                                <form method="POST" action="{{ route('abk.destroy', $a) }}" onsubmit="return confirm('Cabut akun ABK ini? Ia tidak bisa masuk lagi.')">
                                    @csrf @method('DELETE')
                                    <button class="min-h-11 text-base text-red-700 underline">Cabut akses</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
