@extends('admin.layout')

@section('title', 'Administration système')

@section('content')
<div class="mb-6">
    <h3 class="text-lg font-semibold text-gray-900">Contrôle de l’application</h3>
    <p class="text-sm text-gray-600">Ces privilèges sont liés au compte administrateur système, pas au département Administration.</p>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @foreach([
        ['Utilisateurs', $totalUsers, 'users', route('admin.users.index')],
        ['Comptes actifs', $activeUsers, 'user-check', route('admin.users.index', ['status' => 'active'])],
        ['Comptes inactifs', $inactiveUsers, 'user-x', route('admin.users.index', ['status' => 'inactive'])],
        ['Administrateurs système', $administrators, 'shield', route('admin.users.index')],
        ['Départements', $departments, 'building-2', route('intranet.departments.manage.index')],
        ['Rôles', $roles, 'user-cog', route('admin.roles.index')],
    ] as [$label, $count, $icon, $url])
        <a href="{{ $url }}" class="rounded-lg bg-white p-5 shadow-sm transition hover:shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $count }}</p>
                </div>
                <x-icone :nom="$icon" class="text-xl text-nere-600" />
            </div>
        </a>
    @endforeach
</div>

<div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-5 text-sm text-blue-900">
    <h4 class="font-semibold"><x-icone nom="info" class="mr-2" />Portée des droits</h4>
    <p class="mt-1">Les comptes administrateurs système peuvent gérer les comptes, rôles, départements et paramètres de l’application. Le département « Administration » reste un département métier ordinaire.</p>
</div>
@endsection
