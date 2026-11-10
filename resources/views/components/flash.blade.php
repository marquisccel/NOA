@if (session('status'))
    <div role="status" class="max-w-7xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
        <div class="rounded-md bg-green-50 border border-green-300 p-4 text-base text-green-900">{{ session('status') }}</div>
    </div>
@endif
