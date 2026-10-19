@extends('admin.layout')

@section('title', 'Administration système')

@section('content')
<div class="mb-6">
    <h3 class="text-lg font-semibold text-gray-900">Contrôle de l’application</h3>
    <p class="text-sm text-gray-600">Ces privilèges sont liés au compte administrateur système, pas au département Administration.</p>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @foreach([
        ['Utilisateurs', $totalUsers, 'fa-users', route('admin.users.index')],
        ['Comptes actifs', $activeUsers, 'fa-user-check', route('admin.users.index', ['status' => 'active'])],
        ['Comptes inactifs', $inactiveUsers, 'fa-user-slash', route('admin.users.index', ['status' => 'inactive'])],
        ['Administrateurs système', $administrators, 'fa-shield-alt', route('admin.users.index')],
        ['Départements', $departments, 'fa-building', route('intranet.departments.manage.index')],
        ['Rôles', $roles, 'fa-user-tag', route('admin.roles.index')],
    ] as [$label, $count, $icon, $url])
        <a href="{{ $url }}" class="rounded-lg bg-white p-5 shadow-sm transition hover:shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $count }}</p>
                </div>
                <i class="fas {{ $icon }} text-xl text-nere-600"></i>
            </div>
        </a>
    @endforeach
</div>

<div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-5 text-sm text-blue-900">
    <h4 class="font-semibold"><i class="fas fa-info-circle mr-2"></i>Portée des droits</h4>
    <p class="mt-1">Les comptes administrateurs système peuvent gérer les comptes, rôles, départements et paramètres de l’application. Le département « Administration » reste un département métier ordinaire.</p>
</div>
@endsection
