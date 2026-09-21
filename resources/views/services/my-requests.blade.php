@extends('layouts.portal')

@section('titre', 'Mes demandes de service')
@section('sous-titre', 'Suivi des demandes soumises au catalogue')

@section('contenu')
<div class="p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-white">Mes demandes</h2>
            <p class="text-sm text-gray-400 mt-1">Retrouvez le statut, l'approbation et le ticket associé.</p>
        </div>
        <a href="{{ route('services.index') }}" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition">Nouveau service</a>
    </div>

    <div class="bg-dark-800 rounded-xl border border-dark-700 overflow-hidden">
        @forelse($requests as $serviceRequest)
            <div class="p-5 border-b border-dark-700 last:border-b-0 flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="font-semibold text-white">{{ $serviceRequest->service?->name ?? 'Service supprimé' }}</p>
                    <p class="text-xs text-gray-400 mt-1">Soumise {{ $serviceRequest->created_at->diffForHumans() }}</p>
                    @if($serviceRequest->approval_notes)
                        <p class="text-sm text-gray-300 mt-2">{{ $serviceRequest->approval_notes }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-700 text-gray-200">{{ ucfirst($serviceRequest->status) }}</span>
                    @if($serviceRequest->ticket)
                        <a href="{{ route('tickets.show', $serviceRequest->ticket) }}" class="text-sm text-primary-400 hover:text-primary-300">Voir le ticket</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-12 text-center text-gray-400">Aucune demande de service pour le moment.</div>
        @endforelse
    </div>

    {{ $requests->links() }}
</div>
@endsection
