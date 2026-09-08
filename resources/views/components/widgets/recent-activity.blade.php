@props(['data' => null])

@php
$data = $data ?? [
    'activities' => \App\Models\AuditLog::with('user')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get()
];
@endphp

<div class="widget-body">
    @if($data['activities']->isEmpty())
    <div class="text-center py-8">
        <x-icon name="activity" size="xl" class="mx-auto text-gray-300 mb-3" />
        <p class="text-gray-500">Aucune activité récente</p>
    </div>
    @else
    <div class="space-y-3">
        @foreach($data['activities'] as $activity)
        <div class="flex items-start gap-3 pb-3 border-b border-gray-100 last:border-0 last:pb-0">
            <!-- Icon selon l'action -->
            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 {{ 
                match($activity->action) {
                    'created' => 'bg-green-100 text-green-600',
                    'updated' => 'bg-blue-100 text-blue-600',
                    'deleted' => 'bg-red-100 text-red-600',
                    'restored' => 'bg-amber-100 text-amber-600',
                    default => 'bg-gray-100 text-gray-600'
                }
            }}">
                <x-icon name="{{ 
                    match($activity->action) {
                        'created' => 'plus',
                        'updated' => 'edit',
                        'deleted' => 'trash',
                        'restored' => 'refresh',
                        default => 'activity'
                    }
                }}" size="sm" />
            </div>

            <!-- Contenu -->
            <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-900">
                    <span class="font-semibold">{{ $activity->user->name ?? 'Système' }}</span>
                    <span class="text-gray-600">
                        {{ 
                            match($activity->action) {
                                'created' => 'a créé',
                                'updated' => 'a modifié',
                                'deleted' => 'a supprimé',
                                'restored' => 'a restauré',
                                default => 'a effectué une action sur'
                            }
                        }}
                    </span>
                    <span class="font-medium text-gray-900">
                        {{ class_basename($activity->auditable_type ?? 'élément') }}
                    </span>
                </p>
                
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs text-gray-500">
                        {{ $activity->created_at->diffForHumans() }}
                    </span>
                    @if($activity->ip_address)
                    <span class="text-xs text-gray-400">•</span>
                    <span class="text-xs text-gray-500 font-mono">
                        {{ $activity->ip_address }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Lien voir tout -->
    <div class="mt-4 text-center pt-3 border-t border-gray-200">
        <a href="#" class="text-sm text-accent-600 hover:text-accent-700 font-medium">
            Voir l'historique complet →
        </a>
    </div>
    @endif
</div>
