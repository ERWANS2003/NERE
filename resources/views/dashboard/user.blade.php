@extends('layouts.portal')

@section('titre', 'Portail de services')
@section('sous-titre', 'Créez et suivez vos demandes')

@section('contenu')
<div class="p-6 space-y-6">

    <!-- Actions rapides -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="{{ route('tickets.create', ['type' => 'demande']) }}"
           class="flex items-center gap-4 bg-dark-800 hover:bg-dark-700 border border-dark-700 hover:border-primary-600 rounded-xl p-5 transition group">
            <div class="w-12 h-12 rounded-xl bg-primary-600/20 border border-primary-600/30 flex items-center justify-center group-hover:bg-primary-600/30">
                <svg class="w-6 h-6 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-white">Créer une demande</p>
                <p class="text-sm text-gray-400">Demande de service, accès, matériel…</p>
            </div>
        </a>

        <a href="{{ route('tickets.create', ['type' => 'incident']) }}"
           class="flex items-center gap-4 bg-dark-800 hover:bg-dark-700 border border-dark-700 hover:border-red-600 rounded-xl p-5 transition group">
            <div class="w-12 h-12 rounded-xl bg-red-600/20 border border-red-600/30 flex items-center justify-center group-hover:bg-red-600/30">
                <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-white">Signaler un incident</p>
                <p class="text-sm text-gray-400">Panne, problème urgent, dysfonctionnement…</p>
            </div>
        </a>
    </div>

    <!-- Mes tickets récents -->
    <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-white">Mes demandes récentes</h3>
            <a href="{{ route('tickets.index') }}" class="text-sm text-primary-400 hover:text-primary-300">Voir tout →</a>
        </div>
        <div class="space-y-3">
            @forelse($myTickets as $ticket)
                <a href="{{ route('tickets.show', $ticket) }}"
                   class="flex items-center justify-between p-3 rounded-lg bg-dark-900 hover:bg-dark-700 border border-dark-700 hover:border-primary-600 transition">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ $ticket->titre }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $ticket->reference }} · {{ $ticket->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="ml-3 text-xs px-2 py-1 rounded-full flex-shrink-0"
                          style="background: {{ $ticket->statut?->couleur ?? '#374151' }}33; color: {{ $ticket->statut?->couleur ?? '#9ca3af' }}">
                        {{ $ticket->statut?->nom ?? 'En cours' }}
                    </span>
                </a>
            @empty
                <p class="text-center text-gray-500 py-6">Aucune demande pour l'instant.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection
