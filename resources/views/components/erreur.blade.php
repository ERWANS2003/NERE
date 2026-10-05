@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'mt-2 space-y-1 text-sm text-red-700']) }}>
        @foreach ((array) $messages as $message)
            <li class="flex gap-1.5">
                <span aria-hidden="true">&bull;</span>
                <span>{{ $message }}</span>
            </li>
        @endforeach
    </ul>
@endif