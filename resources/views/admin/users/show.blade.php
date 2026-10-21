@extends('admin.layout')

@section('title', 'Détails utilisateur')

@section('content')
<div class="max-w-4xl">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
        </a>
        <div class="space-x-2">
            <a href="{{ route('admin.users.edit', $user) }}" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">
                <i class="fas fa-edit mr-2"></i>Modifier
            </a>
            @if($user->actif)
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600" 
                        onclick="return confirm('Désactiver cet utilisateur?')">
                    <i class="fas fa-ban mr-2"></i>Désactiver
                </button>
            </form>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center">
                <div class="w-16 h-16 bg-nere-100 rounded-full flex items-center justify-center text-nere-600 text-2xl font-bold mr-4">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">{{ $user->name }}</h3>
                    <p class="text-gray-600">{{ $user->email }}</p>
                    @if($user->is_super_admin)
                    <span class="inline-block mt-1 px-2 py-1 text-xs bg-nere-100 text-nere-700 rounded">
                        <i class="fas fa-shield-alt"></i> Administrateur système global
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Informations -->
        <div class="p-6">
            <h4 class="font-semibold text-gray-900 mb-4">Informations</h4>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Matricule</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->matricule ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Statut</dt>
                    <dd class="mt-1">
                        @if($user->actif)
                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Actif</span>
                        @else
                        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">Inactif</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Téléphone</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->telephone ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Poste</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->poste ?: '-' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Départements -->
        <div class="p-6 border-t border-gray-200">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h4 class="font-semibold text-gray-900">Départements</h4>
                    <p class="mt-1 text-sm text-gray-600">Affectez cet employé à un ou plusieurs départements.</p>
                </div>
                @if($availableDepartments->isNotEmpty())
                    <a href="#assign-department" class="text-sm font-semibold text-nere-700 hover:text-nere-900">
                        + Affecter à un département
                    </a>
                @endif
            </div>
            @if($user->departments->count() > 0)
            <div class="mb-5 flex flex-wrap gap-2">
                @foreach($user->departments as $dept)
                <span class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800">
                    {{ $dept->name }}
                    <span class="text-xs text-blue-700">{{ $departmentRoles[$dept->pivot->role] ?? 'Utilisateur' }}</span>
                </span>
                @endforeach
            </div>
            @else
            <p class="text-gray-500 text-sm">Aucun département assigné</p>
            @endif

            @if($availableDepartments->isNotEmpty())
                <form id="assign-department" method="POST" action="{{ route('admin.users.departments.assign', $user) }}"
                      class="grid gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end">
                    @csrf
                    <div>
                        <label for="department_id" class="mb-1 block text-sm font-medium text-gray-700">Département</label>
                        <select id="department_id" name="department_id" required
                                class="w-full rounded border border-gray-300 px-3 py-2 focus:border-nere-500 focus:ring-nere-500">
                            <option value="">Choisir un département</option>
                            @foreach($availableDepartments as $department)
                                <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="department_role" class="mb-1 block text-sm font-medium text-gray-700">Rôle dans le département</label>
                        <select id="department_role" name="role" required
                                class="w-full rounded border border-gray-300 px-3 py-2 focus:border-nere-500 focus:ring-nere-500">
                            @foreach(\App\Enums\DepartmentRole::options() as $value => $label)
                                <option value="{{ $value }}" @selected(old('role', 'user') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('role')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="rounded bg-nere-600 px-4 py-2 text-sm font-semibold text-white hover:bg-nere-700">
                        Affecter
                    </button>
                </form>
            @else
                <p class="text-sm text-gray-600">Cet employé est déjà affecté à tous les départements actifs.</p>
            @endif
        </div>

        <!-- Rôles système -->
        <div class="p-6 border-t border-gray-200">
            <h4 class="font-semibold text-gray-900 mb-4">Rôles système</h4>
            @if($user->roles->count() > 0)
            <div class="flex flex-wrap gap-2">
                @foreach($user->roles as $role)
                <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">
                    {{ $role->nom }}
                </span>
                @endforeach
            </div>
            @else
            <p class="text-gray-500 text-sm">Aucun rôle assigné</p>
            @endif
        </div>
    </div>
</div>
@endsection
