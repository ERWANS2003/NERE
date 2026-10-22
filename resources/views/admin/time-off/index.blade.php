@extends('admin.layout')

@section('title', 'Gestion des congés')

@section('content')
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Politiques et soldes</h3>
        <p class="mt-1 text-sm text-gray-600">Les ajustements ne modifient jamais les jours déjà consommés ni l’historique des demandes.</p>
        @if($moduleEnabled)
            <a href="{{ route('intranet.time-off.approvals') }}" class="mt-3 inline-block text-sm font-medium text-nere-700 hover:underline">Traiter les demandes de congés</a>
        @else
            <a href="{{ route('admin.modules.index') }}" class="mt-3 inline-block text-sm font-medium text-nere-700 hover:underline">Réactivez le module pour traiter les demandes</a>
        @endif
    </div>

    <section class="mb-6 rounded-lg bg-white p-5 shadow-sm">
        <h4 class="mb-4 font-semibold text-gray-900">Créer une politique</h4>
        <form method="POST" action="{{ route('admin.time-off.policies.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            @csrf
            <input name="name" required maxlength="255" placeholder="Nom" class="rounded border-gray-300">
            <input name="type" required maxlength="100" pattern="[A-Za-z0-9_-]+" placeholder="Type unique (ex. vacation)" class="rounded border-gray-300">
            <input name="annual_allocation" type="number" min="0" max="366" required placeholder="Jours annuels" class="rounded border-gray-300">
            <input name="carry_over_limit" type="number" min="0" max="366" value="0" required placeholder="Report maximal" class="rounded border-gray-300">
            <input name="advance_notice_days" type="number" min="0" max="366" value="0" required placeholder="Préavis (jours)" class="rounded border-gray-300">
            <input name="description" maxlength="2000" placeholder="Description" class="rounded border-gray-300 md:col-span-2">
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 text-sm"><input type="hidden" name="requires_approval" value="0"><input type="checkbox" name="requires_approval" value="1" checked> Approbation</label>
                <input type="hidden" name="is_active" value="1">
                <button class="bouton-primaire">Créer</button>
            </div>
        </form>
    </section>

    <section class="mb-6 rounded-lg bg-white p-5 shadow-sm">
        <h4 class="mb-4 font-semibold text-gray-900">Politiques existantes</h4>
        <div class="space-y-3">
            @foreach($policies as $policy)
                <div class="rounded border border-gray-200 p-4">
                    <form method="POST" action="{{ route('admin.time-off.policies.update', $policy) }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        @csrf
                        @method('PUT')
                        <input name="name" value="{{ $policy->name }}" required maxlength="255" class="rounded border-gray-300">
                        <input name="type" value="{{ $policy->type }}" required maxlength="100" pattern="[A-Za-z0-9_-]+" class="rounded border-gray-300">
                        <input name="annual_allocation" type="number" min="0" max="366" value="{{ $policy->annual_allocation }}" required aria-label="Jours annuels" class="rounded border-gray-300">
                        <input name="carry_over_limit" type="number" min="0" max="366" value="{{ $policy->carry_over_limit }}" required aria-label="Report maximal" class="rounded border-gray-300">
                        <input name="advance_notice_days" type="number" min="0" max="366" value="{{ $policy->advance_notice_days }}" required aria-label="Préavis en jours" class="rounded border-gray-300">
                        <input name="description" value="{{ $policy->description }}" maxlength="2000" placeholder="Description" class="rounded border-gray-300 md:col-span-2">
                        <div class="flex flex-wrap items-center gap-3">
                            <label class="flex items-center gap-2 text-sm"><input type="hidden" name="requires_approval" value="0"><input type="checkbox" name="requires_approval" value="1" @checked($policy->requires_approval)> Approbation</label>
                            <label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($policy->is_active)> Active</label>
                            <button class="rounded bg-gray-800 px-3 py-2 text-sm text-white">Enregistrer</button>
                        </div>
                    </form>
                    @if($policy->is_active)
                        <form method="POST" action="{{ route('admin.time-off.policies.destroy', $policy) }}" class="mt-2" onsubmit="return confirm('Désactiver cette politique ? Les données existantes seront conservées.')">
                            @csrf
                            @method('DELETE')
                            <button class="text-xs text-red-700 hover:underline">Désactiver la politique</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-lg bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h4 class="font-semibold text-gray-900">Soldes employés</h4>
                <p class="mt-1 text-xs text-gray-500">Les jours consommés sont calculés par le workflow d’approbation et ne sont pas modifiables ici.</p>
            </div>
            <form method="GET" class="flex flex-wrap gap-2">
                <input name="year" type="number" min="2000" max="2100" value="{{ $year }}" class="w-28 rounded border-gray-300" aria-label="Année">
                <input name="search" value="{{ request('search') }}" placeholder="Nom ou courriel" class="rounded border-gray-300">
                <button class="rounded bg-gray-800 px-3 py-2 text-sm text-white">Filtrer</button>
            </form>
        </div>

        <form method="POST" action="{{ route('admin.time-off.balances.store') }}" class="mb-5 grid grid-cols-1 gap-3 rounded border border-blue-200 bg-blue-50 p-4 md:grid-cols-5">
            @csrf
            <select name="user_id" required class="rounded border-gray-300">
                <option value="">Choisir un employé</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} · {{ $user->email }}</option>
                @endforeach
            </select>
            <select name="policy_id" required class="rounded border-gray-300">
                <option value="">Choisir une politique</option>
                @foreach($policies as $policy)
                    <option value="{{ $policy->id }}">{{ $policy->name }}</option>
                @endforeach
            </select>
            <input name="year" type="number" min="2000" max="2100" value="{{ $year }}" required class="rounded border-gray-300" aria-label="Année">
            <input name="total_days" type="number" step="0.5" min="0" max="366" required placeholder="Jours annuels" class="rounded border-gray-300">
            <div class="flex gap-2">
                <input name="carry_over_days" type="number" step="0.5" min="0" max="366" value="0" required placeholder="Report" class="min-w-0 flex-1 rounded border-gray-300">
                <button class="bouton-primaire">Créer / ajuster</button>
            </div>
        </form>

        <div class="space-y-3">
            @forelse($balances as $balance)
                <form method="POST" action="{{ route('admin.time-off.balances.update', $balance) }}" class="grid grid-cols-1 items-center gap-3 rounded border border-gray-200 p-3 md:grid-cols-6">
                    @csrf
                    @method('PUT')
                    <div class="md:col-span-2">
                        <p class="font-medium text-gray-900">{{ $balance->user?->name ?? 'Compte supprimé' }}</p>
                        <p class="text-xs text-gray-500">{{ $balance->policy?->name }} · {{ $balance->year }}</p>
                    </div>
                    <label class="text-xs text-gray-600">Annuel
                        <input name="total_days" type="number" step="0.5" min="0" max="366" value="{{ $balance->total_days }}" required class="mt-1 w-full rounded border-gray-300">
                    </label>
                    <label class="text-xs text-gray-600">Report
                        <input name="carry_over_days" type="number" step="0.5" min="0" max="366" value="{{ $balance->carry_over_days }}" required class="mt-1 w-full rounded border-gray-300">
                    </label>
                    <div class="text-sm text-gray-600">Consommés : {{ $balance->used_days }}<br>Restants : {{ $balance->remaining_days }}</div>
                    <button class="bouton-primaire">Ajuster</button>
                </form>
            @empty
                <p class="py-6 text-center text-sm text-gray-500">Aucun solde trouvé pour cette année.</p>
            @endforelse
        </div>
        <div class="mt-5">{{ $balances->links() }}</div>
    </section>
@endsection
