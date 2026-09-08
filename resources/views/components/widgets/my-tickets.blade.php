@props(['data' => null])

@php
$data = $data ?? [
    'tickets' => \App\Models\Ticket::where('assigned_to', auth()->id())
        ->with(['status', 'priority', 'category'])
        ->orderBy('created_at', 'desc')
        ->limit(8)
        ->get()
];
@endphp

<div class="widget-body">
    @if($data['tickets']->isEmpty())
    <div class="text-center py-8">
        <x-icon name="tickets" size="xl" class="mx-auto text-gray-300 mb-3" />
        <p class="text-gray-500">Aucun ticket assigné</p>
    </div>
    @else
    <div class="space-y-2">
        @foreach($data['tickets'] as $ticket)
        <a href="{{ route('tickets.show', $ticket) }}" 
           class="block bg-white hover:bg-gray-50 rounded-lg p-3 border border-gray-200 transition">
            <div class="flex items-start justify-between mb-2">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-mono text-gray-500">#{{ $ticket->id }}</span>
                        <x-status-badge :status="$ticket->status->slug ?? 'new'" type="status" />
                    </div>
                    <h4 class="font-semibold text-gray-900 text-sm line-clamp-2">
                        {{ $ticket->title }}
                    </h4>
                </div>
                @if($ticket->priority)
                <x-status-badge :status="$ticket->priority->slug ?? 'normal'" type="priority" />
                @endif
            </div>
            
            <div class="flex items-center justify-between text-xs text-gray-500">
                <div class="flex items-center gap-3">
                    @if($ticket->category)
                    <span class="flex items-center gap-1">
                        <x-icon name="tag" size="xs" />
                        {{ $ticket->category->name }}
                    </span>
                    @endif
                    <span class="flex items-center gap-1">
                        <x-icon name="clock" size="xs" />
                        {{ $ticket->created_at->diffForHumans() }}
                    </span>
                </div>
            </div>
        </a>
        @endforeach
    </div>

    @if($data['tickets']->count() >= 8)
    <div class="mt-4 text-center">
        <a href="{{ route('tickets.index') }}" class="text-sm text-accent-600 hover:text-accent-700 font-medium">
            Voir tous les tickets →
        </a>
    </div>
    @endif
    @endif
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
