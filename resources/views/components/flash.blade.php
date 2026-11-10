@if (session('status') || session('peringatan') || $errors->has('recording'))
    <div class="mx-auto w-full max-w-7xl space-y-3 px-4 pt-4 sm:px-6 lg:px-8">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif
        @if (session('peringatan'))
            <x-alert variant="warning"><strong>Peringatan:</strong> {{ session('peringatan') }}</x-alert>
        @endif
        @if ($errors->has('recording'))
            <x-alert variant="error">{{ $errors->first('recording') }}</x-alert>
        @endif
    </div>
@endif
