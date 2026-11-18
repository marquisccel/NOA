<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-ink leading-tight">Periode</h2>
            <a href="{{ route('siklus.create') }}" class="inline-flex items-center min-h-11 px-4 py-2 bg-brand rounded-md font-semibold text-sm text-white hover:bg-brand-hover">Buka periode</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-surface shadow-sm sm:rounded-lg p-6">
                @include('siklus.partials.daftar', ['siklus' => $siklus, 'showKandang' => true])
            </div>
        </div>
    </div>
</x-app-layout>
