@extends('layouts.portal')

@section('titre', 'Approbations de services')
@section('sous-titre', 'Demandes en attente de validation')

@section('contenu')
<div class="p-6 space-y-6">
    <div>
        <h2 class="text-xl font-semibold text-white">Demandes à approuver</h2>
        <p class="text-sm text-gray-400 mt-1">Validez ou refusez les demandes nécessitant un contrôle managérial.</p>
    </div>

    <div class="bg-dark-800 rounded-xl border border-dark-700 overflow-hidden">
        @forelse($requests as $serviceRequest)
            <div class="p-5 border-b border-dark-700 last:border-b-0 space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="font-semibold text-white">{{ $serviceRequest->service?->name ?? 'Service supprimé' }}</p>
                        <p class="text-sm text-gray-400 mt-1">Demandée par {{ $serviceRequest->requester?->name ?? 'Utilisateur supprimé' }} · {{ $serviceRequest->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-600/20 text-yellow-300">En attente</span>
                </div>
                @if($serviceRequest->form_data)
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                        @foreach($serviceRequest->form_data as $key => $value)
                            <div class="flex gap-2"><dt class="text-gray-500">{{ $key }}:</dt><dd class="text-gray-200">{{ is_scalar($value) ? $value : json_encode($value) }}</dd></div>
                        @endforeach
                    </dl>
                @endif
                <div class="flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('services.approve', $serviceRequest) }}" class="flex gap-2">
                        @csrf
                        <input name="notes" maxlength="500" placeholder="Note facultative" class="px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-sm text-white">
                        <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium">Approuver</button>
                    </form>
                    <form method="POST" action="{{ route('services.reject', $serviceRequest) }}" class="flex gap-2">
                        @csrf
                        <input name="notes" required maxlength="500" placeholder="Motif du refus" class="px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-sm text-white">
                        <button class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium">Refuser</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="p-12 text-center text-gray-400">Aucune demande en attente.</div>
        @endforelse
    </div>

    {{ $requests->links() }}
</div>
@endsection
