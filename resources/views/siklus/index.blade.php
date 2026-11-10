<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Siklus</h2>
            <a href="{{ route('siklus.create') }}" class="inline-flex items-center min-h-11 px-4 py-2 bg-gray-800 rounded-md font-semibold text-sm text-white hover:bg-gray-700">Buka siklus</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @include('siklus.partials.daftar', ['siklus' => $siklus, 'showKandang' => true])
            </div>
        </div>
    </div>
</x-app-layout>
