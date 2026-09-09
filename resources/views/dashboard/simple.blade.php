@extends('layouts.portal')

@section('titre', $title ?? 'Tableau de Bord')
@section('sous-titre', 'Vue simplifiée')

@section('contenu')
<div class="p-6">
    <div class="bg-dark-800 rounded-xl border border-dark-700 p-8">
        <div class="text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <h1 class="text-2xl font-bold text-white mb-2">{{ $title ?? 'Tableau de Bord' }}</h1>
            <p class="text-gray-300 mb-6">{{ $message ?? 'Le tableau de bord est temporairement indisponible.' }}</p>
            
            <div class="space-y-3">
                <a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-2 px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Voir mes tickets
                </a>
                
                <a href="{{ route('tickets.create') }}" class="inline-flex items-center gap-2 px-6 py-2 bg-dark-700 hover:bg-dark-600 text-white rounded-lg transition ml-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Créer une demande
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
