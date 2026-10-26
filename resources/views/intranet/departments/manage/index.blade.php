@extends('admin.layout')

@section('title', 'Départements')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Organisation de l’entreprise</h3>
            <p class="text-sm text-gray-600">Consultez les départements, leurs responsables et leurs membres.</p>
        </div>
        @can('create', App\Models\Department::class)
        <div>
            <a href="{{ route('intranet.departments.manage.create') }}" 
               class="inline-flex items-center rounded bg-nere-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-nere-700 focus:outline-none focus:ring-2 focus:ring-nere-500 focus:ring-offset-2">
                <x-icone nom="plus" class="mr-2" />
                Nouveau département
            </a>
        </div>
        @endcan
    </div>

    <!-- Filters -->
    <div class="rounded-lg bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('intranet.departments.manage.index') }}" data-live-search class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
                <input type="text" 
                       name="search" 
                       id="search"
                       value="{{ request('search') }}"
                       placeholder="Nom du département..."
                       class="block w-full rounded border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-nere-500 focus:ring-nere-500">
            </div>
            
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select name="status" id="status" class="block w-full rounded border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-nere-500 focus:ring-nere-500">
                    <option value="">Tous les statuts</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                </select>
            </div>

            <div>
                <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Trier par</label>
                <select name="sort" id="sort" class="block w-full rounded border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-nere-500 focus:ring-nere-500">
                    <option value="name" {{ request('sort', 'name') === 'name' ? 'selected' : '' }}>Nom</option>
                    <option value="created_at" {{ request('sort') === 'created_at' ? 'selected' : '' }}>Date de création</option>
                    <option value="users_count" {{ request('sort') === 'users_count' ? 'selected' : '' }}>Nombre d'utilisateurs</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" 
                        class="w-full inline-flex items-center justify-center rounded bg-gray-800 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                    <x-icone nom="search" class="mr-2" />
                    Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-lg bg-white p-5 shadow-sm transition hover:shadow">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-nere-50 text-nere-600">
                            <x-icone nom="network" class="text-sm" />
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Total départements
                            </dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                {{ $departments->total() }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-lg bg-white p-5 shadow-sm transition hover:shadow">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50 text-green-600">
                            <x-icone nom="circle-check" class="text-sm" />
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Départements actifs
                            </dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                {{ $activeDepartments ?? 0 }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-lg bg-white p-5 shadow-sm transition hover:shadow">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                            <x-icone nom="users" class="text-sm" />
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Total utilisateurs
                            </dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                {{ $totalUsers ?? 0 }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Departments List -->
    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        @if($departments->count() > 0)
            <ul class="divide-y divide-gray-200">
                @foreach($departments as $department)
                <li>
                    <div class="px-4 py-4 flex items-center justify-between hover:bg-gray-50">
                        <div class="flex items-center flex-1">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full {{ $department->is_active ? 'bg-green-100' : 'bg-gray-100' }} flex items-center justify-center">
                                    <x-icone nom="network" class="text-{{ $department->is_active ? 'green' : 'gray' }}-600" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900">
                                            {{ $department->name }}
                                        </h3>
                                        @if($department->description)
                                        <p class="text-sm text-gray-500 mt-1">
                                            {{ Str::limit($department->description, 150) }}
                                        </p>
                                        @endif
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $department->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $department->is_active ? 'Actif' : 'Inactif' }}
                                        </span>
                                        <div class="text-sm text-gray-500">
                                            <div class="flex items-center">
                                                <x-icone nom="users" class="mr-1" />
                                                {{ $department->users_count ?? 0 }} utilisateur(s)
                                            </div>
                                            <div class="flex items-center mt-1">
                                                <x-icone nom="calendar" class="mr-1" />
                                                {{ $department->created_at->format('d/m/Y') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 ml-4">
                            <a href="{{ route('intranet.departments.manage.show', $department) }}" 
                               class="text-blue-600 hover:text-blue-800">
                                <x-icone nom="eye" />
                                <span class="sr-only">Voir</span>
                            </a>
                            @can('update', $department)
                            <a href="{{ route('intranet.departments.manage.edit', $department) }}" 
                               class="text-yellow-600 hover:text-yellow-800">
                                <x-icone nom="pencil" />
                                <span class="sr-only">Modifier</span>
                            </a>
                            @endcan
                            @can('delete', $department)
                            <button type="button" 
                                    onclick="deleteDepartment({{ $department->id }})"
                                    class="text-red-600 hover:text-red-800">
                                <x-icone nom="trash-2" />
                                <span class="sr-only">Supprimer</span>
                            </button>
                            @endcan
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>

            <!-- Pagination -->
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $departments->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <x-icone nom="network" class="text-4xl text-gray-400 mb-4" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun département trouvé</h3>
                <p class="text-gray-500 mb-6">
                    @if(request()->hasAny(['search', 'status']))
                        Aucun département ne correspond à vos critères de recherche.
                    @else
                        Commencez par créer votre premier département.
                    @endif
                </p>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('intranet.departments.manage.index') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <x-icone nom="x" class="mr-2" />
                        Effacer les filtres
                    </a>
                @elseif(auth()->user()->can('create', App\Models\Department::class))
                    <a href="{{ route('intranet.departments.manage.create') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <x-icone nom="plus" class="mr-2" />
                        Créer le premier département
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>

<!-- Delete Modal -->
@can('create', App\Models\Department::class)
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <x-icone nom="triangle-alert" class="text-red-600" />
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Supprimer le département</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Êtes-vous sûr de vouloir supprimer ce département ? Cette action est irréversible.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">
                        Supprimer
                    </button>
                </form>
                <button onclick="closeDeleteModal()" 
                        class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Annuler
                </button>
            </div>
        </div>
    </div>
</div>
@endcan

@endsection

@can('create', App\Models\Department::class)
@push('scripts')
<script>
function deleteDepartment(departmentId) {
    const modal = document.getElementById('deleteModal');
    const form = document.getElementById('deleteForm');
    
    form.action = @json(route('intranet.departments.manage.destroy', ['department' => '__DEPARTMENT__'])).replace('__DEPARTMENT__', departmentId);
    modal.classList.remove('hidden');
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>
@endpush
@endcan