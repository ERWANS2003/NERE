{{-- Panneau adaptatif selon le rôle effectif dans l'intranet. --}}
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-xl font-bold text-graphite-900">{{ $roleContext['label'] }}</h2>
            <p class="text-sm text-graphite-500">Votre espace de travail personnalisé</p>
        </div>
        @if($roleContext['isAdmin'])
            <a href="{{ route('admin.monitoring') }}" class="inline-flex items-center gap-2 rounded-lg bg-nere-600 px-3 py-2 text-sm font-semibold text-white hover:bg-nere-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-6m3 6v-4m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v11z"/>
                </svg>
                Supervision globale
            </a>
        @endif
    </div>

    @if($roleContext['isAdmin'])
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'Utilisateurs actifs', 'value' => $adminMonitoring['usersActive'], 'class' => 'bg-nere-50 text-nere-700'],
                ['label' => 'Comptes AD', 'value' => $adminMonitoring['usersAd'], 'class' => 'bg-blue-50 text-blue-700'],
                ['label' => 'Soumissions en attente', 'value' => $adminMonitoring['pendingSubmissions'], 'class' => 'bg-or-50 text-or-800'],
                ['label' => 'Demandes de congé en attente', 'value' => $adminMonitoring['pendingTimeOff'], 'class' => 'bg-purple-50 text-purple-700'],
            ] as $metric)
                <div class="rounded-xl border border-graphite-200 bg-white p-4">
                    <div class="text-2xl font-bold text-graphite-900">{{ $metric['value'] }}</div>
                    <div class="mt-1 text-xs text-graphite-500">{{ $metric['label'] }}</div>
                </div>
            @endforeach
        </div>
    @elseif($roleContext['isDirector'])
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-xl border border-nere-200 bg-nere-50 p-4"><div class="text-2xl font-bold text-nere-800">{{ $roleContext['managedDepartmentCount'] }}</div><div class="mt-1 text-xs text-nere-700">Départements dirigés</div></div>
            <div class="rounded-xl border border-graphite-200 bg-white p-4"><div class="text-2xl font-bold text-graphite-900">{{ $roleContext['teamCount'] }}</div><div class="mt-1 text-xs text-graphite-500">Membres d'équipe</div></div>
            <div class="rounded-xl border border-or-200 bg-or-50 p-4"><div class="text-2xl font-bold text-or-800">{{ $roleContext['pendingSubmissions'] }}</div><div class="mt-1 text-xs text-or-800">Demandes à valider</div></div>
            <div class="rounded-xl border border-purple-200 bg-purple-50 p-4"><div class="text-2xl font-bold text-purple-800">{{ $roleContext['pendingTimeOff'] }}</div><div class="mt-1 text-xs text-purple-700">Congés à traiter</div></div>
        </div>
    @elseif($roleContext['isTechnician'])
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4"><div class="text-2xl font-bold text-blue-800">{{ $roleContext['departmentCount'] }}</div><div class="mt-1 text-xs text-blue-700">Départements opérationnels</div></div>
            <div class="rounded-xl border border-graphite-200 bg-white p-4"><div class="text-2xl font-bold text-graphite-900">{{ $roleContext['teamCount'] }}</div><div class="mt-1 text-xs text-graphite-500">Collaborateurs visibles</div></div>
            <div class="rounded-xl border border-or-200 bg-or-50 p-4"><div class="text-2xl font-bold text-or-800">{{ $roleContext['pendingSubmissions'] }}</div><div class="mt-1 text-xs text-or-800">Demandes opérationnelles</div></div>
        </div>
    @else
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="rounded-xl border border-nere-200 bg-nere-50 p-4"><div class="text-2xl font-bold text-nere-800">{{ $roleContext['departmentCount'] }}</div><div class="mt-1 text-xs text-nere-700">Mes départements</div></div>
            <div class="rounded-xl border border-graphite-200 bg-white p-4"><div class="text-2xl font-bold text-graphite-900">{{ $stats['submissions'] }}</div><div class="mt-1 text-xs text-graphite-500">Mes demandes</div></div>
            <div class="rounded-xl border border-graphite-200 bg-white p-4"><div class="text-2xl font-bold text-graphite-900">{{ $stats['events'] }}</div><div class="mt-1 text-xs text-graphite-500">Événements à venir</div></div>
        </div>
    @endif
</div>
