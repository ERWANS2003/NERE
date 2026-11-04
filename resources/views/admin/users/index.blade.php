@extends('admin.layout')

@section('title', 'Gestion des utilisateurs')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div><h2 class="text-2xl font-bold text-gray-900">Utilisateurs</h2><p class="mt-1 text-sm text-gray-600">Recherchez, segmentez et gérez les comptes locaux et Active Directory.</p></div>
    <a href="{{ route('admin.users.create') }}" class="bouton-primaire"><x-icone nom="plus" />Nouvel utilisateur</a>
</div>

@if($savedFilters->isNotEmpty())
<div class="mb-4 flex flex-wrap items-center gap-2">
    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Vues enregistrées</span>
    @foreach($savedFilters as $filter)
        <span class="inline-flex items-center rounded-full border border-gray-200 bg-white">
            <a href="{{ route('admin.users.index', ['filter' => $filter->id]) }}" class="px-3 py-1.5 text-xs font-medium {{ (string) request('filter') === (string) $filter->id ? 'text-nere-700' : 'text-gray-700' }}">{{ $filter->name }}</a>
            <form method="POST" action="{{ route('admin.users.filters.destroy', $filter) }}">@csrf @method('DELETE')<button class="pr-2 text-gray-400 hover:text-red-700" aria-label="Supprimer la vue {{ $filter->name }}">×</button></form>
        </span>
    @endforeach
</div>
@endif

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-4">
    <form method="GET" action="{{ route('admin.users.index') }}" data-live-search class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
        <label class="xl:col-span-2"><span class="mb-1 block text-xs font-semibold">Recherche</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Nom, e-mail ou matricule…" class="w-full"></label>
        <label><span class="mb-1 block text-xs font-semibold">Statut</span><select name="status" class="w-full"><option value="">Tous</option><option value="active" @selected(request('status') === 'active')>Actifs</option><option value="inactive" @selected(request('status') === 'inactive')>Inactifs</option></select></label>
        <label><span class="mb-1 block text-xs font-semibold">Département</span><select name="department" class="w-full"><option value="">Tous</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((string) request('department') === (string) $department->id)>{{ $department->name }}</option>@endforeach</select></label>
        <label><span class="mb-1 block text-xs font-semibold">Rôle</span><select name="role" class="w-full"><option value="">Tous</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected((string) request('role') === (string) $role->id)>{{ $role->nom }}</option>@endforeach</select></label>
        <label><span class="mb-1 block text-xs font-semibold">Origine</span><select name="source" class="w-full"><option value="">Toutes</option><option value="local" @selected(request('source') === 'local')>Locale</option><option value="ldap" @selected(request('source') === 'ldap')>Active Directory</option></select></label>
        <label><span class="mb-1 block text-xs font-semibold">Dernière connexion</span><select name="last_login" class="w-full"><option value="">Toutes</option><option value="30d" @selected(request('last_login') === '30d')>Moins de 30 jours</option><option value="90d" @selected(request('last_login') === '90d')>Plus de 90 jours</option><option value="never" @selected(request('last_login') === 'never')>Jamais</option></select></label>
        <label><span class="mb-1 block text-xs font-semibold">Accès menu</span><select name="menu" class="w-full"><option value="">Tous</option><option value="yes" @selected(request('menu') === 'yes')>Autorisé</option><option value="no" @selected(request('menu') === 'no')>Non autorisé</option></select></label>
        <div class="flex items-end gap-2 xl:col-span-4"><button class="bouton-primaire"><x-icone nom="search" />Filtrer</button><a href="{{ route('admin.users.index') }}" class="bouton-secondaire"><x-icone nom="x" />Réinitialiser</a></div>
    </form>

    @if(request()->hasAny(['search','status','department','role','source','last_login','menu']))
    <form method="POST" action="{{ route('admin.users.filters.store') }}" class="mt-4 flex flex-col gap-2 border-t border-gray-100 pt-4 sm:flex-row sm:items-end">
        @csrf
        @foreach(['search','status','department','role','source','last_login','menu'] as $criterion)<input type="hidden" name="criteria[{{ $criterion }}]" value="{{ request($criterion) }}">@endforeach
        <label class="flex-1"><span class="mb-1 block text-xs font-semibold">Nom de cette vue</span><input name="name" maxlength="80" required placeholder="Ex. Comptes AD inactifs" class="w-full"></label>
        <button class="bouton-secondaire"><x-icone nom="save" />Enregistrer la vue</button>
    </form>
    @endif
</div>

<form method="POST" action="{{ route('admin.users.bulk') }}" data-bulk-users>
    @csrf
    <div class="mb-3 flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-3 xl:flex-row xl:items-end">
        <label><span class="mb-1 block text-xs font-semibold">Action groupée</span><select name="action" required data-bulk-action><option value="">Choisir…</option><option value="activate">Activer</option><option value="deactivate">Désactiver</option><option value="assign_department">Rattacher à un département</option><option value="add_role">Ajouter un rôle</option><option value="remove_role">Retirer un rôle</option><option value="menu_yes">Autoriser le menu</option><option value="menu_no">Retirer l’accès menu</option><option value="export">Exporter en CSV</option></select></label>
        <label data-bulk-department hidden><span class="mb-1 block text-xs font-semibold">Département</span><select name="department_id"><option value="">Choisir…</option>@foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></label>
        <label data-bulk-role hidden><span class="mb-1 block text-xs font-semibold">Rôle</span><select name="role_id"><option value="">Choisir…</option>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->nom }}</option>@endforeach</select></label>
        <button class="bouton-primaire" data-bulk-submit disabled>Appliquer à la sélection</button>
        <span class="text-xs text-gray-500" data-bulk-count>0 sélectionné</span>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="w-full">
            <thead><tr><th class="w-10 px-4 py-3"><input type="checkbox" data-select-all aria-label="Tout sélectionner"></th><th class="px-4 py-3 text-left">Utilisateur</th><th class="px-4 py-3 text-left">Départements et rôles</th><th class="px-4 py-3 text-left">Origine</th><th class="px-4 py-3 text-left">Dernière connexion</th><th class="px-4 py-3 text-left">Statut</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
            @forelse($users as $user)
                <tr>
                    <td class="px-4 py-3"><input type="checkbox" name="user_ids[]" value="{{ $user->id }}" data-user-checkbox aria-label="Sélectionner {{ $user->name }}"></td>
                    <td class="px-4 py-3"><div class="flex items-center gap-3"><span class="grid h-9 w-9 flex-none place-items-center rounded-full bg-nere-100 font-semibold text-nere-700">{{ $user->initials }}</span><span><strong class="block text-sm">{{ $user->name }}</strong><span class="block text-xs text-gray-500">{{ $user->email }} · {{ $user->matricule ?: 'Sans matricule' }}</span>@if($user->is_super_admin)<span class="text-xs font-semibold text-nere-700">Administrateur système</span>@endif</span></div></td>
                    <td class="px-4 py-3 text-xs text-gray-600"><span class="block">{{ $user->departments->pluck('name')->join(', ') ?: 'Aucun département' }}</span><span class="mt-1 block text-gray-500">{{ $user->roles->pluck('nom')->join(', ') ?: 'Aucun rôle' }}</span></td>
                    <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs font-medium {{ $user->guid ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700' }}">{{ $user->guid ? 'Active Directory' : 'Local' }}</span></td>
                    <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-600">{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Jamais' }}</td>
                    <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs font-medium {{ $user->actif ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">{{ $user->actif ? 'Actif' : 'Inactif' }}</span></td>
                    <td class="px-4 py-3 text-right"><a href="{{ route('admin.users.show', $user) }}" class="mr-3 text-blue-700" aria-label="Voir {{ $user->name }}"><x-icone nom="eye" /></a><a href="{{ route('admin.users.edit', $user) }}" class="text-amber-700" aria-label="Modifier {{ $user->name }}"><x-icone nom="pencil" /></a></td>
                </tr>
            @empty<tr><td colspan="7" class="px-4 py-12 text-center text-gray-500">Aucun utilisateur ne correspond aux critères.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</form>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
