@extends('intranet.layouts.app')

@section('title', 'Modifier ' . $department->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-4">
                <li>
                    <a href="{{ route('intranet.dashboard') }}" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-home"></i>
                        <span class="sr-only">Tableau de bord</span>
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mr-4"></i>
                        <a href="{{ route('intranet.departments.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                            Départements
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mr-4"></i>
                        <a href="{{ route('intranet.departments.show', $department) }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                            {{ $department->name }}
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mr-4"></i>
                        <span class="text-sm font-medium text-gray-500">Modifier</span>
                    </div>
                </li>
            </ol>
        </nav>
        <h1 class="mt-4 text-3xl font-bold text-gray-900">Modifier {{ $department->name }}</h1>
        <p class="mt-2 text-gray-600">Modifiez les informations du département</p>
    </div>

    <!-- Form -->
    <div class="bg-white shadow rounded-lg">
        <form action="{{ route('intranet.departments.update', $department) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 gap-6">
                    <!-- Basic Information -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informations de base</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">
                                    Nom du département <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       name="name" 
                                       id="name" 
                                       value="{{ old('name', $department->name) }}"
                                       required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('name') border-red-300 @enderror">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Code -->
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700">
                                    Code département
                                </label>
                                <input type="text" 
                                       name="code" 
                                       id="code" 
                                       value="{{ old('code', $department->code) }}"
                                       placeholder="Ex: IT, HR, FIN"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('code') border-red-300 @enderror">
                                <p class="mt-1 text-xs text-gray-500">Code court pour identifier le département</p>
                                @error('code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mt-6">
                            <label for="description" class="block text-sm font-medium text-gray-700">
                                Description
                            </label>
                            <textarea name="description" 
                                      id="description" 
                                      rows="4"
                                      placeholder="Décrivez les responsabilités et missions de ce département..."
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('description') border-red-300 @enderror">{{ old('description', $department->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Manager Information -->
                    <div class="pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Responsable du département</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Manager -->
                            <div>
                                <label for="manager_id" class="block text-sm font-medium text-gray-700">
                                    Responsable
                                </label>
                                <select name="manager_id" 
                                        id="manager_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('manager_id') border-red-300 @enderror">
                                    <option value="">Sélectionner un responsable</option>
                                    @foreach($managers as $manager)
                                        <option value="{{ $manager->id }}" {{ old('manager_id', $department->manager_id) == $manager->id ? 'selected' : '' }}>
                                            {{ $manager->first_name }} {{ $manager->last_name }} ({{ $manager->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('manager_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Parent Department -->
                            <div>
                                <label for="parent_id" class="block text-sm font-medium text-gray-700">
                                    Département parent
                                </label>
                                <select name="parent_id" 
                                        id="parent_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('parent_id') border-red-300 @enderror">
                                    <option value="">Aucun département parent</option>
                                    @foreach($departments as $dept)
                                        @if($dept->id !== $department->id)
                                            <option value="{{ $dept->id }}" {{ old('parent_id', $department->parent_id) == $dept->id ? 'selected' : '' }}>
                                                {{ $dept->name }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Sélectionnez si ce département dépend d'un autre</p>
                                @error('parent_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informations de contact</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">
                                    Email du département
                                </label>
                                <input type="email" 
                                       name="email" 
                                       id="email" 
                                       value="{{ old('email', $department->email) }}"
                                       placeholder="department@company.com"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('email') border-red-300 @enderror">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">
                                    Téléphone
                                </label>
                                <input type="text" 
                                       name="phone" 
                                       id="phone" 
                                       value="{{ old('phone', $department->phone) }}"
                                       placeholder="+33 1 23 45 67 89"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('phone') border-red-300 @enderror">
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="mt-6">
                            <label for="location" class="block text-sm font-medium text-gray-700">
                                Localisation
                            </label>
                            <input type="text" 
                                   name="location" 
                                   id="location" 
                                   value="{{ old('location', $department->location) }}"
                                   placeholder="Bâtiment, étage, bureau..."
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('location') border-red-300 @enderror">
                            @error('location')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Settings -->
                    <div class="pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Paramètres</h3>
                        
                        <div class="space-y-6">
                            <!-- Budget -->
                            <div>
                                <label for="budget" class="block text-sm font-medium text-gray-700">
                                    Budget annuel (€)
                                </label>
                                <input type="number" 
                                       name="budget" 
                                       id="budget" 
                                       value="{{ old('budget', $department->budget) }}"
                                       min="0"
                                       step="0.01"
                                       placeholder="100000"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('budget') border-red-300 @enderror">
                                @error('budget')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" 
                                           name="is_active" 
                                           id="is_active" 
                                           value="1"
                                           {{ old('is_active', $department->is_active) ? 'checked' : '' }}
                                           class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="is_active" class="font-medium text-gray-700">
                                        Département actif
                                    </label>
                                    <p class="text-gray-500">
                                        Un département inactif ne peut pas recevoir de nouveaux tickets
                                    </p>
                                </div>
                            </div>

                            <!-- Allow Ticket Creation -->
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="hidden" name="allow_ticket_creation" value="0">
                                    <input type="checkbox" 
                                           name="allow_ticket_creation" 
                                           id="allow_ticket_creation" 
                                           value="1"
                                           {{ old('allow_ticket_creation', $department->allow_ticket_creation) ? 'checked' : '' }}
                                           class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="allow_ticket_creation" class="font-medium text-gray-700">
                                        Autoriser la création de tickets
                                    </label>
                                    <p class="text-gray-500">
                                        Les utilisateurs peuvent créer des tickets pour ce département
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    @can('delete_departments')
                    <div class="pt-6 border-t border-red-200">
                        <h3 class="text-lg font-medium text-red-900 mb-4">Zone de danger</h3>
                        <div class="bg-red-50 border border-red-200 rounded-md p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-red-400"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">
                                        Supprimer le département
                                    </h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <p>Une fois supprimé, ce département et toutes ses données associées seront définitivement perdus.</p>
                                    </div>
                                    <div class="mt-4">
                                        <button type="button" 
                                                onclick="confirmDelete()"
                                                class="bg-red-600 border border-transparent rounded-md py-2 px-4 inline-flex justify-center text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                            Supprimer le département
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endcan
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
                <a href="{{ route('intranet.departments.show', $department) }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-times mr-2"></i>
                    Annuler
                </a>
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-save mr-2"></i>
                    Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
@can('delete_departments')
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Confirmer la suppression</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Êtes-vous absolument sûr de vouloir supprimer le département "{{ $department->name }}" ?
                </p>
                <p class="text-sm text-red-600 mt-2 font-medium">
                    Cette action est irréversible et supprimera toutes les données associées.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <form method="POST" action="{{ route('intranet.departments.destroy', $department) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-32 mr-2 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">
                        Oui, supprimer
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

@push('scripts')
<script>
// Auto-generate code from name if code is empty
document.getElementById('name').addEventListener('input', function() {
    const codeField = document.getElementById('code');
    if (!codeField.value.trim()) {
        const name = this.value;
        const code = name.toUpperCase()
                        .replace(/[^A-Z0-9\s]/g, '')
                        .split(' ')
                        .map(word => word.substring(0, 3))
                        .join('');
        codeField.value = code.substring(0, 10);
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    
    if (!name) {
        e.preventDefault();
        alert('Le nom du département est obligatoire.');
        document.getElementById('name').focus();
        return;
    }
    
    if (name.length < 2) {
        e.preventDefault();
        alert('Le nom du département doit contenir au moins 2 caractères.');
        document.getElementById('name').focus();
        return;
    }
});

@can('delete_departments')
function confirmDelete() {
    const modal = document.getElementById('deleteModal');
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
@endcan
</script>
@endpush