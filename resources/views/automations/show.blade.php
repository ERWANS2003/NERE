@extends('layouts.portal')

@section('titre', $automation->name)

@section('contenu')
<div class="max-w-7xl mx-auto">

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4 mb-8">
        <div>
            <nav class="mb-4">
                <ol class="flex items-center gap-2 text-sm text-gray-600">
                    <li><a href="{{ route('automations.index') }}" class="hover:text-accent-600">Automations</a></li>
                    <li><x-icon name="chevron-right" size="xs" /></li>
                    <li class="text-gray-900 font-medium truncate max-w-xs">{{ $automation->name }}</li>
                </ol>
            </nav>

            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-3xl font-bold text-gray-900">{{ $automation->name }}</h1>
                @if ($automation->is_active)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                        <x-icon name="check-circle" size="xs" /> Active
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                        <x-icon name="pause" size="xs" /> Inactive
                    </span>
                @endif
            </div>

            @if ($automation->description)
                <p class="text-gray-600 mt-2 max-w-2xl">{{ $automation->description }}</p>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('automations.toggle', $automation) }}">
                @csrf
                <button type="submit" class="btn-secondary px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition">
                    <x-icon name="{{ $automation->is_active ? 'pause' : 'check' }}" size="sm" />
                    {{ $automation->is_active ? 'Désactiver' : 'Activer' }}
                </button>
            </form>

            <a href="{{ route('automations.edit', $automation) }}" class="btn-primary px-4 py-2 rounded-lg hover:shadow-lg transition">
                <x-icon name="edit" size="sm" /> Modifier
            </a>

            <form method="POST" action="{{ route('automations.destroy', $automation) }}"
                  onsubmit="return confirm('Supprimer définitivement cette automation ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition">
                    <x-icon name="trash" size="sm" />
                    <span class="sr-only">Supprimer</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="text-sm text-gray-600">Exécutions</div>
            <div class="text-3xl font-bold text-gray-900 mt-1">{{ $automation->execution_count ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="text-sm text-gray-600">Dernière exécution</div>
            <div class="text-sm font-semibold text-gray-900 mt-2">
                {{ $automation->last_executed_at?->format('d/m/Y H:i') ?? 'Jamais' }}
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="text-sm text-gray-600">Créée le</div>
            <div class="text-sm font-semibold text-gray-900 mt-2">{{ $automation->created_at?->format('d/m/Y') ?? '—' }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="text-sm text-gray-600">Créateur</div>
            <div class="text-sm font-semibold text-gray-900 mt-2">{{ $automation->creator?->name ?? '—' }}</div>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-8">
        {{-- Trigger + conditions + actions --}}
        <div class="col-span-2 space-y-6">

            <div class="bg-white rounded-lg shadow-sm border-2 border-gray-200 p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Déclencheur</h2>
                <div class="flex items-center gap-3 p-4 bg-blue-50 border-2 border-blue-100 rounded-lg">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                        <x-icon name="lightning" size="md" class="text-blue-600" />
                    </div>
                    <div>
                        @php
                            $eventLabels = [
                                'ticket.created'         => 'Ticket créé',
                                'ticket.updated'         => 'Ticket modifié',
                                'ticket.assigned'        => 'Ticket assigné',
                                'ticket.status_changed'  => 'Statut du ticket modifié',
                                'sla.warning'            => 'SLA à risque',
                                'sla.breached'          => 'SLA dépassé',
                                'asset.maintenance_due'  => 'Maintenance prévue',
                                'safety.incident_reported' => 'Incident sécurité signalé',
                            ];
                            $triggerKey = $automation->trigger_event;
                        @endphp
                        <div class="font-semibold text-gray-900">{{ $eventLabels[$triggerKey] ?? $triggerKey }}</div>
                        <div class="text-xs text-gray-600 font-mono">{{ $triggerKey }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border-2 border-gray-200 p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-1">Conditions (IF)</h2>
                <p class="text-sm text-gray-600 mb-4">Toutes ces conditions doivent être remplies.</p>

                @php
                    $operatorLabels = [
                        '=' => 'est égal à', '!=' => 'est différent de', '>' => 'est supérieur à',
                        '<' => 'est inférieur à', '>=' => 'est supérieur ou égal à',
                        '<=' => 'est inférieur ou égal à', 'contains' => 'contient',
                        'starts_with' => 'commence par', 'ends_with' => 'finit par',
                        'in' => 'est dans la liste', 'not_in' => 'nest pas dans la liste',
                    ];
                    $fieldLabels = [
                        'priority' => 'Priorité', 'category' => 'Catégorie', 'status' => 'Statut',
                        'assigned_to' => 'Assigné à', 'title' => 'Titre', 'description' => 'Description',
                        'severity' => 'Sévérité', 'location' => 'Localisation',
                    ];
                @endphp

                @forelse ($automation->conditions ?? [] as $condition)
                    <div class="flex flex-wrap items-center gap-2 p-3 bg-gray-50 rounded-lg mb-2">
                        <span class="font-semibold text-gray-900">{{ $fieldLabels[$condition['field'] ?? ''] ?? ($condition['field'] ?? '—') }}</span>
                        <span class="text-gray-500 text-sm">{{ $operatorLabels[$condition['operator'] ?? '='] ?? ($condition['operator'] ?? '=') }}</span>
                        <span class="px-2 py-0.5 bg-white border border-gray-300 rounded font-mono text-xs text-gray-900">
                            {{ $condition['value'] ?? '—' }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 italic">Aucune condition définie.</p>
                @endforelse
            </div>

            <div class="bg-white rounded-lg shadow-sm border-2 border-gray-200 p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-1">Actions (THEN)</h2>
                <p class="text-sm text-gray-600 mb-4">Exécutées dans l'ordre lorsqu'un ticket correspond.</p>

                @php
                    $actionLabels = [
                        'assign_ticket'      => 'Assigner le ticket',
                        'change_status'      => 'Changer le statut',
                        'change_priority'    => 'Changer la priorité',
                        'send_notification'  => 'Envoyer une notification',
                        'create_ticket'      => 'Créer un ticket',
                        'add_comment'        => 'Ajouter un commentaire',
                        'escalate'           => 'Escalader le ticket',
                        'send_email'         => 'Envoyer un e-mail',
                    ];
                @endphp

                @forelse ($automation->actions ?? [] as $index => $action)
                    <div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-lg mb-2">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-6 w-6 flex-none items-center justify-center rounded-full bg-green-600 text-xs font-bold text-white">
                                {{ $index + 1 }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-gray-900">
                                    {{ $actionLabels[$action['type'] ?? ''] ?? ($action['type'] ?? 'Action inconnue') }}
                                </div>
                                @if (! empty($action['data']))
                                    <dl class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-xs">
                                        @foreach ($action['data'] as $dataKey => $dataValue)
                                            <div class="flex gap-1.5">
                                                <dt class="text-gray-500">{{ $dataKey }}:</dt>
                                                <dd class="font-mono text-gray-900 truncate">{{ is_scalar($dataValue) ? $dataValue : json_encode($dataValue) }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 italic">Aucune action définie.</p>
                @endforelse
            </div>
        </div>

        {{-- Execution log --}}
        <div class="col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Journal d'exécution</h2>

                @forelse ($automation->logs as $log)
                    <div class="flex items-start gap-3 py-3 border-b border-gray-100 last:border-0">
                        @if ($log->status === 'success')
                            <x-icon name="check-circle" size="sm" class="mt-0.5 text-green-600 flex-none" />
                        @elseif ($log->status === 'failed')
                            <x-icon name="alert" size="sm" class="mt-0.5 text-red-600 flex-none" />
                        @else
                            <x-icon name="info" size="sm" class="mt-0.5 text-gray-500 flex-none" />
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-gray-900 capitalize">{{ $log->status }}</div>
                            <div class="text-xs text-gray-500">{{ $log->executed_at?->format('d/m/Y H:i') ?? '—' }}</div>
                            @if ($log->error_message)
                                <div class="mt-1 text-xs text-red-600 break-words">{{ $log->error_message }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 italic">Aucune exécution enregistrée.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
