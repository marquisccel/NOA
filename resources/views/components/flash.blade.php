@if (session('status'))
    <div role="status" class="max-w-7xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
        <div class="rounded-md bg-green-50 border border-green-300 p-4 text-base text-green-900">{{ session('status') }}</div>
    </div>
@endif
@if (session('peringatan'))
    <div role="alert" class="max-w-7xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
        <div class="rounded-md bg-amber-50 border border-amber-400 p-4 text-base text-amber-900"><strong>Peringatan:</strong> {{ session('peringatan') }}</div>
    </div>
@endif
@if ($errors->has('recording'))
    <div role="alert" class="max-w-7xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
        <div class="rounded-md bg-red-50 border border-red-300 p-4 text-base text-red-900">{{ $errors->first('recording') }}</div>
    </div>
@endif
