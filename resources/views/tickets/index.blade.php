@extends('layouts.portal')

@section('titre', 'Tickets')
@section('sous-titre', auth()->user()->hasRole('demandeur') ? 'Vos demandes et leur avancement' : 'Centre de service et de support')

@section('contenu')
<div class="p-6 lg:p-8 space-y-6">
    <section class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.16em] text-primary-700 mb-2">Centre de service</p>
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">{{ auth()->user()->hasRole('demandeur') ? 'Mes demandes' : 'File de tickets' }}</h1>
            <p class="text-gray-500 mt-2">Recherchez, priorisez et suivez les demandes de vos équipes.</p>
        </div>
        <a href="{{ route('tickets.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 text-white text-sm font-semibold hover:bg-primary-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nouveau ticket
        </a>
    </section>

    <section class="grid grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4">
        @php($ticketKpis = [['label' => 'Total', 'value' => $ticketStats['total'], 'tone' => 'text-gray-900'], ['label' => 'Ouverts', 'value' => $ticketStats['ouverts'], 'tone' => 'text-blue-700'], ['label' => 'En cours', 'value' => $ticketStats['en_cours'], 'tone' => 'text-amber-700'], ['label' => 'Urgents', 'value' => $ticketStats['urgents'], 'tone' => 'text-red-700']])
        @foreach($ticketKpis as $kpi)
            <div class="bg-white border border-gray-200 rounded-xl px-4 py-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $kpi['label'] }}</p><p class="text-2xl font-bold {{ $kpi['tone'] }} mt-2">{{ number_format($kpi['value']) }}</p></div>
        @endforeach
    </section>

    <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 sm:p-5">
        <form method="GET" action="{{ route('tickets.index') }}" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-[2fr_1fr_1fr_1fr_auto] gap-3 items-end">
            <div><label for="q" class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-2">Recherche</label><div class="relative"><svg class="absolute left-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0a7 7 0 0114 0z"></path></svg><input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Référence, titre ou description" class="w-full pl-9 pr-3 py-2.5 rounded-lg border text-sm outline-none focus:ring-2 focus:ring-primary-600/20"></div></div>
            <div><label for="statut" class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-2">Statut</label><select id="statut" name="statut" class="w-full px-3 py-2.5 rounded-lg border text-sm outline-none focus:ring-2 focus:ring-primary-600/20"><option value="">Tous</option>@foreach($statuts as $statut)<option value="{{ $statut->id }}" @selected(request('statut') == $statut->id)>{{ $statut->nom }}</option>@endforeach</select></div>
            <div><label for="priorite" class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-2">Priorité</label><select id="priorite" name="priorite" class="w-full px-3 py-2.5 rounded-lg border text-sm outline-none focus:ring-2 focus:ring-primary-600/20"><option value="">Toutes</option>@foreach($priorites as $priorite)<option value="{{ $priorite->id }}" @selected(request('priorite') == $priorite->id)>{{ $priorite->nom }}</option>@endforeach</select></div>
            <div><label for="site" class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-2">Site</label><select id="site" name="site" class="w-full px-3 py-2.5 rounded-lg border text-sm outline-none focus:ring-2 focus:ring-primary-600/20"><option value="">Tous les sites</option>@foreach($sites as $site)<option value="{{ $site->id }}" @selected(request('site') == $site->id)>{{ $site->nom }}</option>@endforeach</select></div>
            <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-gray-900 text-white text-sm font-semibold hover:bg-gray-700 transition">Filtrer</button>
        </form>
        @if(request()->hasAny(['q', 'statut', 'priorite', 'categorie', 'site', 'departement']))<a href="{{ route('tickets.index') }}" class="inline-block mt-3 text-sm font-semibold text-primary-700 hover:text-primary-900">Réinitialiser les filtres</a>@endif
    </section>

    <section class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 sm:px-6 py-5 border-b border-gray-200"><div><h2 class="font-bold text-gray-900">Demandes enregistrées</h2><p class="text-sm text-gray-500 mt-1">{{ $tickets->total() }} résultat{{ $tickets->total() > 1 ? 's' : '' }} · triés du plus récent au plus ancien</p></div><a href="{{ route('search.index') }}" class="text-sm font-semibold text-primary-700">Recherche avancée</a></div>
        @if($tickets->isEmpty())
            <div class="px-6 py-16 text-center"><div class="w-14 h-14 mx-auto rounded-2xl bg-gray-100 flex items-center justify-center text-gray-400"><svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6a1 1 0 01.7.3l5.4 5.4a1 1 0 01.3.7V19a2 2 0 01-2 2z"></path></svg></div><h3 class="mt-4 font-bold text-gray-900">Aucun ticket trouvé</h3><p class="text-sm text-gray-500 mt-1">Modifiez vos filtres ou créez une nouvelle demande.</p></div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($tickets as $ticket)
                    <a href="{{ route('tickets.show', $ticket) }}" class="block px-5 sm:px-6 py-4 hover:bg-gray-50 transition {{ $ticket->trashed() ? 'opacity-60' : '' }}">
                        <div class="flex flex-col lg:flex-row lg:items-center gap-3 lg:gap-6">
                            <div class="lg:w-32 shrink-0"><span class="font-mono text-xs font-bold text-primary-700">{{ $ticket->reference }}</span><p class="text-xs text-gray-400 mt-1">{{ $ticket->created_at->format('d/m/Y H:i') }}</p></div>
                            <div class="min-w-0 flex-1"><p class="font-semibold text-gray-900 truncate">{{ $ticket->titre }}</p><p class="text-sm text-gray-500 mt-1 truncate">{{ Str::limit($ticket->description, 100) }}</p></div>
                            <div class="flex flex-wrap items-center gap-2 lg:w-56">@if($ticket->statut)<span class="px-2.5 py-1 rounded-full text-xs font-semibold" style="background:{{ $ticket->statut->couleur }}18;color:{{ $ticket->statut->couleur }}">{{ $ticket->statut->nom }}</span>@endif @if($ticket->priorite)<span class="px-2.5 py-1 rounded-full text-xs font-semibold" style="background:{{ $ticket->priorite->couleur }}18;color:{{ $ticket->priorite->couleur }}">{{ $ticket->priorite->nom }}</span>@endif</div>
                            <div class="lg:w-40 text-sm text-gray-500">{{ $ticket->departement?->nom ?? 'Périmètre général' }}</div>
                            <svg class="w-5 h-5 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </div>
                    </a>
                @endforeach
            </div>
            @if($tickets->hasPages())<div class="px-5 sm:px-6 py-4 border-t border-gray-200">{{ $tickets->links() }}</div>@endif
        @endif
    </section>
</div>
@endsection
