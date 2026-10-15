@extends('admin.layout')

@section('title', 'Supervision globale')

@section('content')
<div class="mb-6">
    <h3 class="text-lg font-semibold text-gray-900">Supervision globale de l'intranet</h3>
    <p class="text-sm text-gray-600">Vue transversale des utilisateurs, rôles, départements et files opérationnelles.</p>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label' => 'Utilisateurs actifs', 'value' => $metrics['usersActive'], 'class' => 'bg-green-50 text-green-800'],
        ['label' => 'Utilisateurs AD', 'value' => $metrics['usersAd'], 'class' => 'bg-blue-50 text-blue-800'],
        ['label' => 'Demandes en attente', 'value' => $metrics['pendingSubmissions'], 'class' => 'bg-yellow-50 text-yellow-800'],
        ['label' => 'Congés en attente', 'value' => $metrics['pendingTimeOff'], 'class' => 'bg-purple-50 text-purple-800'],
    ] as $metric)
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-2xl font-bold text-gray-900">{{ $metric['value'] }}</div>
            <div class="mt-1 text-xs {{ $metric['class'] }} inline-block rounded px-2 py-1">{{ $metric['label'] }}</div>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-sm">
        <h4 class="font-semibold text-gray-900 mb-4">Population</h4>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">Total</dt><dd class="font-semibold">{{ $metrics['usersTotal'] }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Actifs</dt><dd class="font-semibold text-green-700">{{ $metrics['usersActive'] }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Inactifs AD</dt><dd class="font-semibold text-gray-600">{{ $metrics['usersInactive'] }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Comptes locaux</dt><dd class="font-semibold">{{ $metrics['usersLocal'] }}</dd></div>
        </dl>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-sm">
        <h4 class="font-semibold text-gray-900 mb-4">Contenu intranet</h4>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">Départements actifs</dt><dd class="font-semibold">{{ $metrics['departments'] }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Ressources</dt><dd class="font-semibold">{{ $metrics['resources'] }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Annonces actives</dt><dd class="font-semibold">{{ $metrics['announcements'] }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Événements à venir</dt><dd class="font-semibold">{{ $metrics['upcomingEvents'] }}</dd></div>
        </dl>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-sm">
        <h4 class="font-semibold text-gray-900 mb-4">Droits attribués</h4>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">Affectations départements</dt><dd class="font-semibold">{{ $metrics['departmentAssignments'] }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Rôles système</dt><dd class="font-semibold">{{ $metrics['roleAssignments'] }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Rôles configurés</dt><dd class="font-semibold">{{ $roles->count() }}</dd></div>
        </dl>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <section class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-gray-200 flex items-center justify-between">
            <h4 class="font-semibold text-gray-900">Effectifs par département</h4>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-nere-600 hover:text-nere-700">Gérer</a>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($departments as $department)
                <div class="px-5 py-3 flex items-center justify-between text-sm">
                    <span class="text-gray-700">{{ $department->name }}</span>
                    <span class="font-semibold text-gray-900">{{ $department->users_count }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-gray-200 flex items-center justify-between">
            <h4 class="font-semibold text-gray-900">Rôles système</h4>
            <a href="{{ route('admin.roles.index') }}" class="text-sm text-nere-600 hover:text-nere-700">Gérer</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($roles as $role)
                <div class="px-5 py-3 flex items-center justify-between text-sm">
                    <span class="text-gray-700">{{ $role->nom }}</span>
                    <span class="font-semibold text-gray-900">{{ $role->users_count }} utilisateur(s)</span>
                </div>
            @empty
                <p class="p-5 text-sm text-gray-500">Aucun rôle système attribué.</p>
            @endforelse
        </div>
    </section>

    <section class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-gray-200">
            <h4 class="font-semibold text-gray-900">Dernières synchronisations AD</h4>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($recentlySynced as $user)
                <a href="{{ route('admin.users.show', $user) }}" class="px-5 py-3 flex items-center justify-between text-sm hover:bg-gray-50">
                    <span><span class="font-medium text-gray-800">{{ $user->name }}</span><span class="block text-xs text-gray-500">{{ $user->email }}</span></span>
                    <span class="text-xs {{ $user->actif ? 'text-green-700' : 'text-gray-500' }}">{{ $user->actif ? 'Actif' : 'Inactif' }}</span>
                </a>
            @empty
                <p class="p-5 text-sm text-gray-500">Aucune synchronisation AD enregistrée.</p>
            @endforelse
        </div>
    </section>

    <section class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-gray-200 flex items-center justify-between">
            <h4 class="font-semibold text-gray-900">Files à surveiller</h4>
            <a href="{{ route('intranet.analytics') }}" class="text-sm text-nere-600 hover:text-nere-700">Analytics</a>
        </div>
        <div class="p-5 space-y-3 text-sm">
            <a href="{{ route('intranet.submissions.index') }}" class="flex justify-between rounded bg-yellow-50 px-3 py-2 hover:bg-yellow-100"><span>Soumissions en attente</span><strong>{{ $metrics['pendingSubmissions'] }}</strong></a>
            <a href="{{ route('intranet.time-off.approvals') }}" class="flex justify-between rounded bg-purple-50 px-3 py-2 hover:bg-purple-100"><span>Congés en attente</span><strong>{{ $metrics['pendingTimeOff'] }}</strong></a>
            <a href="{{ route('admin.ldap.sync') }}" class="flex justify-between rounded bg-blue-50 px-3 py-2 hover:bg-blue-100"><span>Synchronisation AD</span><span>Ouvrir</span></a>
        </div>
    </section>
</div>

<section class="mt-6 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-gray-200 flex items-center justify-between">
        <div>
            <h4 class="font-semibold text-gray-900">Journal d'audit</h4>
            <p class="text-xs text-gray-500 mt-1">Changements de rôles, permissions et actions d'administration.</p>
        </div>
        <span class="text-xs text-gray-500">20 dernières actions</span>
    </div>
    <div class="divide-y divide-gray-100">
        @forelse($recentAudit as $event)
            <div class="px-5 py-3 flex items-start justify-between gap-4 text-sm">
                <div>
                    <p class="font-medium text-gray-800">{{ $event->action }}</p>
                    <p class="text-xs text-gray-500">
                        Par {{ $event->user?->name ?? 'Système' }}
                        @if($event->model_id) · cible #{{ $event->model_id }} @endif
                    </p>
                </div>
                <time class="shrink-0 text-xs text-gray-500" datetime="{{ $event->created_at?->toIso8601String() }}">
                    {{ $event->created_at?->diffForHumans() }}
                </time>
            </div>
        @empty
            <p class="p-5 text-sm text-gray-500">Aucune action auditée pour le moment.</p>
        @endforelse
    </div>
</section>
@endsection
