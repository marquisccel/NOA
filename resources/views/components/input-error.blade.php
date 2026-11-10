@props(['messages' => null])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-xs text-danger-text space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
