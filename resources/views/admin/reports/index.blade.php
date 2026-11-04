@extends('admin.layout')

@section('title', 'Rapports d’administration')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Indicateurs de gouvernance</h2>
    <p class="mt-1 text-sm text-gray-600">Vue synthétique des comptes, usages, rôles, demandes et incidents techniques.</p>
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <article class="carte"><p class="text-xs font-semibold uppercase text-gray-500">Comptes actifs</p><p class="mt-2 text-3xl font-bold">{{ number_format($userStatus['Actifs'], 0, ',', ' ') }}</p><p class="text-sm text-gray-500">{{ $userStatus['Inactifs'] }} inactif(s)</p></article>
    <article class="carte"><p class="text-xs font-semibold uppercase text-gray-500">Délai d’approbation</p><p class="mt-2 text-3xl font-bold">{{ $averageApprovalHours !== null ? $averageApprovalHours.' h' : '—' }}</p><p class="text-sm text-gray-500">Moyenne sur 6 mois</p></article>
    <article class="carte"><p class="text-xs font-semibold uppercase text-gray-500">Incidents techniques</p><p class="mt-2 text-3xl font-bold">{{ $failedJobsLast30Days }}</p><p class="text-sm text-gray-500">Jobs échoués sur 30 jours</p></article>
    <article class="carte"><p class="text-xs font-semibold uppercase text-gray-500">Rôles configurés</p><p class="mt-2 text-3xl font-bold">{{ $roleDistribution->count() }}</p><p class="text-sm text-gray-500">Profils d’autorisation</p></article>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <section class="carte"><h3 class="font-semibold">Évolution sur six mois</h3><div class="mt-4 space-y-3">@php($max = max(1, $months->max(fn($m) => max($m['users'], $m['submissions'])))) @foreach($months as $month)<div class="grid grid-cols-[5rem_1fr] items-center gap-3"><span class="text-xs text-gray-600">{{ $month['label'] }}</span><div><div class="h-2 rounded bg-nere-100"><div class="h-2 rounded bg-nere-600" style="width: {{ $month['submissions'] / $max * 100 }}%"></div></div><p class="mt-1 text-xs text-gray-500">{{ $month['submissions'] }} demandes · {{ $month['users'] }} comptes créés</p></div></div>@endforeach</div></section>
    <section class="carte"><h3 class="font-semibold">Utilisation des modules</h3><div class="mt-4 space-y-3">@php($moduleMax = max(1, max($moduleUsage))) @foreach($moduleUsage as $label => $count)<div><div class="flex justify-between text-sm"><span>{{ $label }}</span><strong>{{ $count }}</strong></div><div class="mt-1 h-2 rounded bg-gray-100"><div class="h-2 rounded bg-blue-600" style="width: {{ $count / $moduleMax * 100 }}%"></div></div></div>@endforeach</div></section>
    <section class="carte"><h3 class="font-semibold">Répartition des rôles</h3><div class="mt-4 divide-y divide-gray-100">@forelse($roleDistribution as $role)<div class="flex justify-between py-2 text-sm"><span>{{ $role->nom }}</span><strong>{{ $role->users_count }}</strong></div>@empty<p class="text-sm text-gray-500">Aucun rôle.</p>@endforelse</div></section>
    <section class="carte"><h3 class="font-semibold">Demandes par département</h3><div class="mt-4 divide-y divide-gray-100">@forelse($requestsByDepartment as $department)<div class="flex justify-between py-2 text-sm"><span>{{ $department->name }}</span><strong>{{ $department->requests_count }}</strong></div>@empty<p class="text-sm text-gray-500">Aucune demande.</p>@endforelse</div></section>
</div>
@endsection
