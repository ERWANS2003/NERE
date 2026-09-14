@extends('layouts.portal')

@section('titre', 'Gestion des Incidents Sécurité')
@section('sous-titre', 'Suivi et gestion des incidents de sécurité')

@section('contenu')
<div class="px-6 py-4 space-y-6">

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Total Incidents -->
        <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Total Incidents</p>
                    <p class="text-3xl font-bold text-white mt-2">{{ $incidents->total() }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-600/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Unresolved -->
        <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Non Résolus</p>
                    <p class="text-3xl font-bold text-yellow-400 mt-2">{{ \App\Models\SafetyIncident::unresolved()->count() }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-yellow-600/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Critical -->
        <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Critiques</p>
                    <p class="text-3xl font-bold text-red-400 mt-2">{{ \App\Models\SafetyIncident::critical()->count() }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-red-600/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2M6.228 6.228a9 9 0 1012.544 0M4.5 12a7.5 7.5 0 1115 0 7.5 7.5 0 01-15 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Recent 30 Days -->
        <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Derniers 30j</p>
                    <p class="text-3xl font-bold text-primary-400 mt-2">{{ \App\Models\SafetyIncident::recent(30)->count() }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-primary-600/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Filtrer & Rechercher</h3>
        <form method="GET" action="{{ route('safety.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Search -->
                <div>
                    <label for="q" class="block text-sm font-medium text-gray-300 mb-2">Recherche</label>
                    <input type="search" id="q" name="q" value="{{ request('q') }}"
                           placeholder="N° incident, titre..."
                           class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-primary-500 transition">
                </div>

                <!-- Severity -->
                <div>
                    <label for="severity" class="block text-sm font-medium text-gray-300 mb-2">Sévérité</label>
                    <select id="severity" name="severity" class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition">
                        <option value="">-- Tous --</option>
                        @foreach($severities as $sev)
                            <option value="{{ $sev }}" @selected(request('severity') == $sev)>
                                {{ ucfirst($sev) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-300 mb-2">Statut</label>
                    <select id="status" name="status" class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition">
                        <option value="">-- Tous --</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st }}" @selected(request('status') == $st)>
                                {{ ucfirst(str_replace('_', ' ', $st)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Type -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-300 mb-2">Type</label>
                    <select id="type" name="type" class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition">
                        <option value="">-- Tous --</option>
                        @foreach($types as $t)
                            <option value="{{ $t }}" @selected(request('type') == $t)>
                                {{ ucfirst(str_replace('_', ' ', $t)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Zone -->
                <div>
                    <label for="zone_id" class="block text-sm font-medium text-gray-300 mb-2">Zone</label>
                    <select id="zone_id" name="zone_id" class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition">
                        <option value="">-- Tous --</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" @selected(request('zone_id') == $zone->id)>
                                {{ $zone->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
                    Filtrer
                </button>
                <a href="{{ route('safety.index') }}" class="px-6 py-2 bg-dark-700 hover:bg-dark-600 text-white rounded-lg font-medium transition">
                    Réinitialiser
                </a>
                <a href="{{ route('safety.create') }}" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nouvel Incident
                </a>
            </div>
        </form>
    </div>

    <!-- Incidents List -->
    <div class="bg-dark-800 rounded-xl border border-dark-700 overflow-hidden">
        @if($incidents->isEmpty())
            <div class="p-12 text-center">
                <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-gray-400 text-lg">Aucun incident trouvé</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-dark-900/50 border-b border-dark-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-300">N° Incident</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-300">Titre</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-300">Sévérité</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-300">Statut</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-300">Signalé par</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-300">Date</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-700">
                        @foreach($incidents as $incident)
                        <tr class="hover:bg-dark-700/50 transition">
                            <td class="px-6 py-4">
                                <span class="font-mono text-sm font-semibold text-primary-400">{{ $incident->incident_number }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('safety.show', $incident) }}" class="text-white hover:text-primary-400 transition font-medium">
                                    {{ $incident->title }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $severityColors = [
                                        'low' => 'blue',
                                        'medium' => 'yellow',
                                        'high' => 'orange',
                                        'critical' => 'red',
                                    ];
                                    $color = $severityColors[$incident->severity] ?? 'gray';
                                @endphp
                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-{{ $color }}-600/20 text-{{ $color }}-300">
                                    {{ ucfirst($incident->severity) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'open' => 'blue',
                                        'under_investigation' => 'yellow',
                                        'resolved' => 'green',
                                        'closed' => 'gray',
                                    ];
                                    $color = $statusColors[$incident->status] ?? 'gray';
                                @endphp
                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-{{ $color }}-600/20 text-{{ $color }}-300">
                                    {{ ucfirst(str_replace('_', ' ', $incident->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-400">
                                {{ $incident->reporter?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-400">
                                {{ $incident->reported_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('safety.show', $incident) }}" 
                                       class="inline-flex items-center gap-1 px-3 py-1 bg-blue-600/20 hover:bg-blue-600/30 text-blue-300 rounded text-xs font-medium transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Voir
                                    </a>
                                    <a href="{{ route('safety.edit', $incident) }}" 
                                       class="inline-flex items-center gap-1 px-3 py-1 bg-primary-600/20 hover:bg-primary-600/30 text-primary-300 rounded text-xs font-medium transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Éditer
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($incidents->hasPages())
            <div class="px-6 py-4 border-t border-dark-700 bg-dark-900/50">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-gray-400">
                        Affichage de <span class="font-semibold">{{ $incidents->firstItem() }}</span> 
                        à <span class="font-semibold">{{ $incidents->lastItem() }}</span> 
                        sur <span class="font-semibold">{{ $incidents->total() }}</span> incidents
                    </p>
                    <div>
                        {{ $incidents->links('pagination::tailwind') }}
                    </div>
                </div>
            </div>
            @endif
        @endif
    </div>

</div>
@endsection
