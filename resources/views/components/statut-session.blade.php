@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-md border border-or-300 bg-or-50 px-3 py-2 text-sm text-nere-950', 'role' => 'status']) }}>
        {{ $status }}
    </div>
@endif