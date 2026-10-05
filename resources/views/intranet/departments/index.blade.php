@extends('intranet.layouts.app')

@section('title', 'Départements')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Départements</h1>
            <p class="mt-2 text-gray-600">Gérez l'organisation de votre entreprise</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('intranet.departments.create') }}" 
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-plus mr-2"></i>
                Nouveau département
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <form method="GET" action="{{ route('intranet.departments.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
                <input type="text" 
                       name="search" 
                       id="search"
                       value="{{ request('search') }}"
                       placeholder="Nom du département..."
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>
            
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select name="status" id="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="">Tous les statuts</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                </select>
            </div>

            <div>
                <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Trier par</label>
                <select name="sort" id="sort" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="name" {{ request('sort', 'name') === 'name' ? 'selected' : '' }}>Nom</option>
                    <option value="created_at" {{ request('sort') === 'created_at' ? 'selected' : '' }}>Date de création</option>
                    <option value="users_count" {{ request('sort') === 'users_count' ? 'selected' : '' }}>Nombre d'utilisateurs</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" 
                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    <i class="fas fa-search mr-2"></i>
                    Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-sitemap text-white text-sm"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Total départements
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                {{ $departments->total() }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-check-circle text-white text-sm"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Départements actifs
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                {{ $activeDepartments ?? 0 }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-users text-white text-sm"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Total utilisateurs
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                {{ $totalUsers ?? 0 }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Departments List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        @if($departments->count() > 0)
            <ul class="divide-y divide-gray-200">
                @foreach($departments as $department)
                <li>
                    <div class="px-4 py-4 flex items-center justify-between hover:bg-gray-50">
                        <div class="flex items-center flex-1">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full {{ $department->is_active ? 'bg-green-100' : 'bg-gray-100' }} flex items-center justify-center">
                                    <i class="fas fa-sitemap text-{{ $department->is_active ? 'green' : 'gray' }}-600"></i>
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
                                                <i class="fas fa-users mr-1"></i>
                                                {{ $department->users_count ?? 0 }} utilisateur(s)
                                            </div>
                                            <div class="flex items-center mt-1">
                                                <i class="fas fa-calendar mr-1"></i>
                                                {{ $department->created_at->format('d/m/Y') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 ml-4">
                            <a href="{{ route('intranet.departments.show', $department) }}" 
                               class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-eye"></i>
                                <span class="sr-only">Voir</span>
                            </a>
                            @can('manage_departments')
                            <a href="{{ route('intranet.departments.edit', $department) }}" 
                               class="text-yellow-600 hover:text-yellow-800">
                                <i class="fas fa-edit"></i>
                                <span class="sr-only">Modifier</span>
                            </a>
                            <button type="button" 
                                    onclick="deleteDepartment({{ $department->id }})"
                                    class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
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
                <i class="fas fa-sitemap text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun département trouvé</h3>
                <p class="text-gray-500 mb-6">
                    @if(request()->hasAny(['search', 'status']))
                        Aucun département ne correspond à vos critères de recherche.
                    @else
                        Commencez par créer votre premier département.
                    @endif
                </p>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('intranet.departments.index') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-times mr-2"></i>
                        Effacer les filtres
                    </a>
                @else
                    <a href="{{ route('intranet.departments.create') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>
                        Créer le premier département
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
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

@endsection

@push('scripts')
<script>
function deleteDepartment(departmentId) {
    const modal = document.getElementById('deleteModal');
    const form = document.getElementById('deleteForm');
    
    form.action = `/intranet/departments/${departmentId}`;
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