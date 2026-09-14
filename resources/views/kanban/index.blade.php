@extends('layouts.portal')

@section('titre', 'Tableau Kanban')
@section('sous-titre', 'Visualisez et gérez les tickets par statut')

@section('contenu')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Tableau Kanban</h1>
            <p class="text-sm text-gray-400 mt-1">Glissez-déposez les tickets pour changer leur statut</p>
        </div>
        <a href="{{ route('tickets.index') }}" class="px-4 py-2 border border-dark-700 text-white rounded-lg hover:bg-dark-800 transition">
            Vue Liste
        </a>
    </div>

    <!-- Kanban Board -->
    <div class="overflow-x-auto pb-4">
        <div class="flex gap-6 min-w-max">
            @foreach($statuses as $status)
                <div class="flex-shrink-0 w-96 bg-dark-800 border border-dark-700 rounded-xl overflow-hidden flex flex-col" 
                     data-status-id="{{ $status->id }}">
                    
                    <!-- Column Header -->
                    <div class="px-6 py-4 border-b border-dark-700 bg-dark-700/30">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-white">{{ $status->nom }}</h3>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ $tickets->get($status->id, collect())->count() }} ticket{{ $tickets->get($status->id, collect())->count() !== 1 ? 's' : '' }}
                                </p>
                            </div>
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-dark-600 text-xs font-semibold text-white">
                                {{ $tickets->get($status->id, collect())->count() }}
                            </span>
                        </div>
                    </div>

                    <!-- Tickets Column -->
                    <div class="flex-1 p-4 space-y-3 overflow-y-auto min-h-96 kanban-column" 
                         data-status-id="{{ $status->id }}">
                        @forelse($tickets->get($status->id, collect()) as $ticket)
                            <div class="kanban-card p-4 bg-dark-700 border border-dark-600 rounded-lg cursor-move hover:border-primary-500 transition group"
                                 draggable="true" 
                                 data-ticket-id="{{ $ticket->id }}"
                                 data-status-id="{{ $status->id }}">
                                
                                <!-- Reference Badge -->
                                <div class="flex items-center justify-between mb-2">
                                    <span class="inline-block px-2 py-1 bg-primary-900/30 text-primary-400 text-xs font-mono rounded">
                                        {{ $ticket->reference }}
                                    </span>
                                    <span class="inline-block px-2 py-1 text-xs font-medium rounded
                                        @if($ticket->priorite?->niveau >= 3) bg-red-900/30 text-red-400
                                        @elseif($ticket->priorite?->niveau >= 2) bg-yellow-900/30 text-yellow-400
                                        @else bg-green-900/30 text-green-400 @endif">
                                        {{ $ticket->priorite?->nom ?? 'N/A' }}
                                    </span>
                                </div>

                                <!-- Title -->
                                <a href="{{ route('tickets.show', $ticket) }}" class="block text-sm font-medium text-white hover:text-primary-400 transition line-clamp-2">
                                    {{ $ticket->titre }}
                                </a>

                                <!-- Description -->
                                <p class="text-xs text-gray-400 mt-2 line-clamp-2">
                                    {{ Str::limit($ticket->description, 80) }}
                                </p>

                                <!-- Footer Info -->
                                <div class="flex items-center justify-between mt-3 pt-3 border-t border-dark-600">
                                    <div class="flex items-center gap-2">
                                        @if($ticket->assignee)
                                            <div class="w-6 h-6 rounded-full bg-primary-600/30 flex items-center justify-center text-xs font-semibold text-white"
                                                 title="{{ $ticket->assignee->name }}">
                                                {{ substr($ticket->assignee->name, 0, 1) }}
                                            </div>
                                        @else
                                            <div class="w-6 h-6 rounded-full border border-dashed border-gray-600 flex items-center justify-center text-xs text-gray-500">
                                                —
                                            </div>
                                        @endif
                                    </div>
                                    <span class="text-xs text-gray-500">
                                        {{ $ticket->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                <!-- Drag Handle (visual cue) -->
                                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                    </svg>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12 text-gray-500">
                                <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <p class="text-sm">Aucun ticket</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Add Ticket Button -->
                    <div class="px-4 py-3 border-t border-dark-700 bg-dark-700/20">
                        <a href="{{ route('tickets.create', ['status_id' => $status->id]) }}" 
                           class="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm text-gray-400 hover:text-white hover:bg-dark-600 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Nouveau ticket
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<style>
    .kanban-card {
        position: relative;
        transition: all 0.2s;
    }

    .kanban-card.dragging {
        opacity: 0.5;
        transform: scale(0.95);
    }

    .kanban-card.drag-over {
        border-color: #e18a07 !important;
        background-color: rgba(225, 138, 7, 0.1) !important;
    }

    .kanban-column {
        background: linear-gradient(to bottom, rgba(33, 37, 41, 0.5), rgba(13, 17, 23, 0.3));
    }

    .kanban-column.drag-over-column {
        background: linear-gradient(to bottom, rgba(33, 37, 41, 0.8), rgba(13, 17, 23, 0.6)) !important;
        border-color: #e18a07 !important;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let draggedCard = null;
    let sourceStatusId = null;

    // Handle drag start
    document.querySelectorAll('.kanban-card').forEach(card => {
        card.addEventListener('dragstart', (e) => {
            draggedCard = card;
            sourceStatusId = card.dataset.statusId;
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', card.dataset.ticketId);
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
            document.querySelectorAll('.kanban-card.drag-over').forEach(c => c.classList.remove('drag-over'));
        });
    });

    // Handle drop zones (columns)
    document.querySelectorAll('.kanban-column').forEach(column => {
        column.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            column.classList.add('drag-over-column');
        });

        column.addEventListener('dragleave', () => {
            column.classList.remove('drag-over-column');
        });

        column.addEventListener('drop', (e) => {
            e.preventDefault();
            column.classList.remove('drag-over-column');

            if (!draggedCard) return;

            const ticketId = draggedCard.dataset.ticketId;
            const newStatusId = column.dataset.statusId;
            const oldStatusId = draggedCard.dataset.statusId;

            if (oldStatusId === newStatusId) {
                draggedCard = null;
                return;
            }

            // Move card visually
            draggedCard.dataset.statusId = newStatusId;
            column.appendChild(draggedCard);

            // Send AJAX request to update backend
            fetch(`{{ route('kanban.move', ['ticket' => 'ID']) }}`.replace('ID', ticketId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    status_id: newStatusId,
                    position: Array.from(column.querySelectorAll('.kanban-card')).indexOf(draggedCard),
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update counter
                    updateColumnCounts();
                } else {
                    alert('Erreur: ' + (data.message || 'Impossible de déplacer le ticket'));
                    // Revert the move
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erreur réseau');
                location.reload();
            });

            draggedCard = null;
        });
    });

    // Update column ticket counts
    function updateColumnCounts() {
        document.querySelectorAll('[data-status-id]').forEach(column => {
            if (!column.classList.contains('kanban-column')) return;
            const count = column.querySelectorAll('.kanban-card').length;
            const header = column.closest('.flex').querySelector('p');
            if (header) {
                header.textContent = `${count} ticket${count !== 1 ? 's' : ''}`;
            }
        });
    }
});
</script>
@endsection
