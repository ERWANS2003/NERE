@extends('layouts.portal')

@section('titre', 'Centre de pilotage')
@section('sous-titre', 'Vue opérationnelle de la mine et des services internes')

@section('contenu')
<div class="p-6 lg:p-8 space-y-7">
    <section class="flex flex-col lg:flex-row lg:items-end justify-between gap-5">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-primary-700 mb-2">Néré Mining / Control room</p>
            <h1 class="text-3xl lg:text-4xl font-bold tracking-tight text-gray-900">Bonjour, {{ auth()->user()->name }}</h1>
            <p class="text-gray-500 mt-2 max-w-2xl">Suivez les engagements de service, les risques HSE et la charge des départements depuis un seul espace.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:border-primary-600 hover:text-primary-700 transition">Rapports</a>
            <a href="{{ route('tickets.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 text-white text-sm font-semibold hover:bg-primary-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nouvelle demande
            </a>
        </div>
    </section>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        @php($kpis = [
            ['label' => 'Tickets ouverts', 'value' => $stats['tickets_ouverts'] ?? 0, 'detail' => 'à prendre en charge', 'tone' => 'text-blue-700', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0a9 9 0 0118 0z'],
            ['label' => 'Hors SLA', 'value' => $stats['sla_depasse'] ?? 0, 'detail' => 'engagements dépassés', 'tone' => 'text-red-700', 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0a9 9 0 0118 0z'],
            ['label' => 'Incidents HSE', 'value' => $securite['incidents_ouverts'] ?? 0, 'detail' => 'dossiers non résolus', 'tone' => 'text-orange-700', 'icon' => 'M12 9v2m0 4v.01M5.07 19h13.86a2 2 0 001.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16a2 2 0 001.73 3z'],
            ['label' => 'Techniciens actifs', 'value' => $stats['techniciens_disponibles'] ?? 0, 'detail' => 'disponibles maintenant', 'tone' => 'text-emerald-700', 'icon' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m9-10a4 4 0 10-8 0a4 4 0 008 0zm5 0a3 3 0 11-2.83-4M22 21v-2a4 4 0 00-3-3.87'],
        ])
        @foreach($kpis as $kpi)
            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div><p class="text-sm font-medium text-gray-500">{{ $kpi['label'] }}</p><p class="text-3xl font-bold {{ $kpi['tone'] }} mt-3">{{ number_format($kpi['value']) }}</p></div>
                    <span class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center {{ $kpi['tone'] }}"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="{{ $kpi['icon'] }}"></path></svg></span>
                </div>
                <p class="text-xs text-gray-400 mt-3">{{ $kpi['detail'] }}</p>
            </div>
        @endforeach
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-[1.4fr_1fr] gap-5">
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200"><div><h2 class="font-bold text-gray-900">Activité récente</h2><p class="text-sm text-gray-500 mt-1">Dernières demandes enregistrées</p></div><a href="{{ route('tickets.index') }}" class="text-sm font-semibold text-primary-700 hover:text-primary-800">Voir tout</a></div>
            <div class="divide-y divide-gray-100">
                @forelse($recentTickets ?? [] as $ticket)
                    <a href="{{ route('tickets.show', $ticket) }}" class="flex items-center justify-between gap-4 px-6 py-4 hover:bg-gray-50 transition"><div class="min-w-0"><p class="font-semibold text-gray-800 truncate">{{ $ticket->titre }}</p><p class="text-xs text-gray-500 mt-1">{{ $ticket->reference }} · {{ $ticket->demandeur?->name ?? 'Demandeur inconnu' }}</p></div><div class="text-right shrink-0"><span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold" style="background:{{ $ticket->priorite?->couleur ?? '#73817b' }}18;color:{{ $ticket->priorite?->couleur ?? '#53615c' }}">{{ $ticket->priorite?->nom ?? 'Standard' }}</span><p class="text-xs text-gray-400 mt-1">{{ $ticket->created_at->diffForHumans() }}</p></div></a>
                @empty
                    <p class="px-6 py-12 text-center text-gray-500">Aucun ticket récent.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200"><h2 class="font-bold text-gray-900">Santé HSE</h2><p class="text-sm text-gray-500 mt-1">Signalements à surveiller</p></div>
            <div class="p-6 space-y-4">
                <a href="{{ route('safety.index', ['unresolved' => 1]) }}" class="flex items-center justify-between p-4 rounded-lg bg-orange-50 border border-orange-100 hover:border-orange-300 transition"><span><span class="block text-sm font-semibold text-orange-900">Incidents ouverts</span><span class="block text-xs text-orange-700 mt-1">Investigation ou action requise</span></span><strong class="text-2xl text-orange-700">{{ $securite['incidents_ouverts'] ?? 0 }}</strong></a>
                <a href="{{ route('safety.index', ['critical' => 1, 'unresolved' => 1]) }}" class="flex items-center justify-between p-4 rounded-lg bg-red-50 border border-red-100 hover:border-red-300 transition"><span><span class="block text-sm font-semibold text-red-900">Critiques non résolus</span><span class="block text-xs text-red-700 mt-1">Priorité immédiate</span></span><strong class="text-2xl text-red-700">{{ $securite['incidents_critiques'] ?? 0 }}</strong></a>
                <a href="{{ route('tickets.index', ['sla_depasse' => 1]) }}" class="flex items-center justify-between p-4 rounded-lg bg-blue-50 border border-blue-100 hover:border-blue-300 transition"><span><span class="block text-sm font-semibold text-blue-900">Tickets hors SLA</span><span class="block text-xs text-blue-700 mt-1">Revue des engagements</span></span><strong class="text-2xl text-blue-700">{{ $stats['sla_depasse'] ?? 0 }}</strong></a>
            </div>
        </div>
    </section>

    <section class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200"><h2 class="font-bold text-gray-900">Charge par département</h2><p class="text-sm text-gray-500 mt-1">Tickets enregistrés par périmètre opérationnel</p></div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-5">
            @forelse($ticketsParDepartement as $dept => $count)
                @php($maxTickets = max((int) $ticketsParDepartement->max(), 1))
                <div><div class="flex items-center justify-between text-sm mb-2"><span class="font-medium text-gray-700">{{ $dept }}</span><span class="font-bold text-gray-900">{{ $count }}</span></div><div class="h-2 rounded-full bg-gray-100 overflow-hidden"><div class="h-full rounded-full bg-primary-600" style="width: {{ min(100, ($count / $maxTickets) * 100) }}%"></div></div></div>
            @empty
                <p class="text-gray-500">Aucune donnée disponible.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
