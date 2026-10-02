@extends('layouts.portal')

@section('titre', 'Gestion des Utilisateurs')
@section('sous-titre', 'Gérer les comptes utilisateurs et leurs rôles')

@section('contenu')
<div class="p-6 space-y-6">
    
    <!-- Actions Bar -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <!-- Search -->
            <form method="GET" class="flex gap-2">
                <div class="relative">
                    <input type="text" 
                           name="q" 
                           value="{{ request('q') }}"
                           placeholder="Rechercher un utilisateur..."
                           class="pl-10 pr-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white placeholder-gray-500 focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition w-80">
                    <svg class="w-5 h-5 text-gray-500 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                
                <!-- Filters -->
                <select name="role_id" 
                        onchange="this.form.submit()"
                        class="px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                    <option value="">Tous les rôles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->nom }}
                        </option>
                    @endforeach
                </select>

                <select name="actif" 
                        onchange="this.form.submit()"
                        class="px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                    <option value="">Tous les statuts</option>
                    <option value="1" {{ request('actif') === '1' ? 'selected' : '' }}>Actifs</option>
                    <option value="0" {{ request('actif') === '0' ? 'selected' : '' }}>Inactifs</option>
                </select>

                @if(request()->hasAny(['q', 'role_id', 'actif']))
                    <a href="{{ route('admin.users.index') }}" 
                       class="px-4 py-2 bg-dark-700 hover:bg-dark-600 border border-dark-600 text-gray-300 rounded-lg transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Réinitialiser
                    </a>
                @endif
            </form>
        </div>

        <button onclick="openCreateModal()" 
                class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium transition shadow-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nouvel utilisateur
        </button>
    </div>

    <!-- Stats Quick View -->
    <div class="grid grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="text-2xl font-bold text-white">{{ $users->total() }}</div>
            <div class="text-sm text-gray-400">Total utilisateurs</div>
        </div>
        <div class="stat-card">
            <div class="text-2xl font-bold text-green-400">{{ \App\Models\User::where('actif', true)->count() }}</div>
            <div class="text-sm text-gray-400">Actifs</div>
        </div>
        <div class="stat-card">
            <div class="text-2xl font-bold text-blue-400">{{ \App\Models\User::where('est_technicien', true)->count() }}</div>
            <div class="text-sm text-gray-400">Techniciens</div>
        </div>
        <div class="stat-card">
            <div class="text-2xl font-bold text-primary-400">{{ \App\Models\User::whereHas('role', fn($q) => $q->where('slug', 'admin'))->count() }}</div>
            <div class="text-sm text-gray-400">Administrateurs</div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-dark-800 rounded-xl border border-dark-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-dark-900 border-b border-dark-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Utilisateur</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Rôle</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Département</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-dark-700">
                    @forelse($users as $user)
                        <tr class="hover:bg-dark-700 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center flex-shrink-0">
                                        <span class="text-white font-semibold text-sm">{{ substr($user->name, 0, 2) }}</span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-white">{{ $user->name }}</div>
                                        <div class="text-sm text-gray-400">{{ $user->email }}</div>
                                        @if($user->matricule)
                                            <div class="text-xs text-gray-500">{{ $user->matricule }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($user->role)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-primary-600/20 border border-primary-600/30 text-primary-400 text-xs rounded-full">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                        </svg>
                                        {{ $user->role->nom }}
                                    </span>
                                @else
                                    <span class="text-gray-500 text-sm">Aucun rôle</span>
                                @endif
                                @if($user->est_technicien)
                                    <span class="ml-2 inline-flex items-center gap-1 px-2 py-1 bg-blue-600/20 border border-blue-600/30 text-blue-400 text-xs rounded-full">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        </svg>
                                        Technicien
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($user->departement)
                                    <div class="text-sm text-white">{{ $user->departement->nom }}</div>
                                    @if($user->site)
                                        <div class="text-xs text-gray-500">{{ $user->site->nom }}</div>
                                    @endif
                                @else
                                    <span class="text-gray-500 text-sm">Non assigné</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($user->actif)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-600/20 border border-green-600/30 text-green-400 text-xs rounded-full">
                                        <span class="w-1.5 h-1.5 bg-green-400 rounded-full"></span>
                                        Actif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-600/20 border border-gray-600/30 text-gray-400 text-xs rounded-full">
                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                                        Inactif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button onclick="editUser({{ $user->id }})" 
                                            class="p-2 text-blue-400 hover:bg-blue-600/20 rounded-lg transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    @if($user->actif)
                                        <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="p-2 text-orange-400 hover:bg-orange-600/20 rounded-lg transition" title="Désactiver">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="p-2 text-green-400 hover:bg-green-600/20 rounded-lg transition" title="Activer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                <p class="text-lg font-medium">Aucun utilisateur trouvé</p>
                                <p class="text-sm mt-1">Essayez de modifier vos filtres de recherche</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="px-6 py-4 border-t border-dark-700 bg-dark-900">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Create User Modal -->
<div id="createUserModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 overflow-y-auto" onclick="if(event.target === this) closeCreateModal()">
    <div class="bg-dark-800 rounded-xl p-6 max-w-3xl w-full mx-4 my-8 border border-dark-700">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-semibold text-white">Créer un utilisateur</h3>
            <button type="button" onclick="closeCreateModal()" class="text-gray-400 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        @if($errors->any())
            <div class="mb-4 bg-red-500/10 border border-red-500/30 rounded-lg p-3">
                <ul class="text-sm text-red-300 space-y-1">
                    @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_form" value="create">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="create_name" class="block text-sm font-medium text-gray-300 mb-1">Nom complet <span class="text-red-400">*</span></label>
                    <input type="text" id="create_name" name="name" value="{{ old('name') }}" required
                           class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                </div>
                <div>
                    <label for="create_email" class="block text-sm font-medium text-gray-300 mb-1">Email <span class="text-red-400">*</span></label>
                    <input type="email" id="create_email" name="email" value="{{ old('email') }}" required
                           class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                </div>
                <div>
                    <label for="create_password" class="block text-sm font-medium text-gray-300 mb-1">Mot de passe <span class="text-red-400">*</span></label>
                    <input type="password" id="create_password" name="password" required minlength="8"
                           class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                    <p class="text-xs text-gray-500 mt-1">8 caractères minimum.</p>
                </div>
                <div>
                    <label for="create_password_confirmation" class="block text-sm font-medium text-gray-300 mb-1">Confirmation <span class="text-red-400">*</span></label>
                    <input type="password" id="create_password_confirmation" name="password_confirmation" required minlength="8"
                           class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                </div>
                <div>
                    <label for="create_role_id" class="block text-sm font-medium text-gray-300 mb-1">Rôle <span class="text-red-400">*</span></label>
                    <select id="create_role_id" name="role_id" required
                            class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                        <option value="">— Sélectionner —</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ (int) old('role_id') === $role->id ? 'selected' : '' }}>
                                {{ $role->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="create_departement_id" class="block text-sm font-medium text-gray-300 mb-1">Département</label>
                    <select id="create_departement_id" name="departement_id"
                            class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                        <option value="">— Aucun —</option>
                        @foreach($departements as $departement)
                            <option value="{{ $departement->id }}" {{ (int) old('departement_id') === $departement->id ? 'selected' : '' }}>
                                {{ $departement->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="create_site_id" class="block text-sm font-medium text-gray-300 mb-1">Site</label>
                    <select id="create_site_id" name="site_id"
                            class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                        <option value="">— Aucun —</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" {{ (int) old('site_id') === $site->id ? 'selected' : '' }}>
                                {{ $site->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="create_matricule" class="block text-sm font-medium text-gray-300 mb-1">Matricule</label>
                    <input type="text" id="create_matricule" name="matricule" value="{{ old('matricule') }}"
                           class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                </div>
                <div>
                    <label for="create_poste" class="block text-sm font-medium text-gray-300 mb-1">Poste</label>
                    <input type="text" id="create_poste" name="poste" value="{{ old('poste') }}"
                           class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                </div>
                <div>
                    <label for="create_telephone" class="block text-sm font-medium text-gray-300 mb-1">Téléphone</label>
                    <input type="text" id="create_telephone" name="telephone" value="{{ old('telephone') }}"
                           class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                </div>
            </div>

            <div class="flex flex-wrap gap-6 pt-2">
                <label class="flex items-center gap-2 text-sm text-gray-300">
                    <input type="checkbox" name="est_technicien" value="1" @checked(old('est_technicien'))
                           class="rounded border-dark-600 bg-dark-900 text-primary-600 focus:ring-primary-600/40">
                    Est technicien
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-300">
                    <input type="checkbox" name="disponible" value="1" @checked(old('disponible', true))
                           class="rounded border-dark-600 bg-dark-900 text-primary-600 focus:ring-primary-600/40">
                    Disponible pour affectation
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-300">
                    <input type="checkbox" name="actif" value="1" @checked(old('actif', true))
                           class="rounded border-dark-600 bg-dark-900 text-primary-600 focus:ring-primary-600/40">
                    Compte actif
                </label>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-dark-700">
                <button type="button" onclick="closeCreateModal()"
                        class="px-4 py-2 bg-dark-700 hover:bg-dark-600 text-white rounded-lg transition">
                    Annuler
                </button>
                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition shadow-lg">
                    Créer l'utilisateur
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 overflow-y-auto" onclick="if(event.target === this) closeEditModal()">
    <div class="bg-dark-800 rounded-xl p-6 max-w-3xl w-full mx-4 my-8 border border-dark-700">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-semibold text-white">Modifier l'utilisateur</h3>
            <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div id="editUserError" class="hidden mb-4 bg-red-500/10 border border-red-500/30 rounded-lg p-3">
            <ul class="text-sm text-red-300 list-disc list-inside space-y-1"></ul>
        </div>

        <form id="editUserForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="edit">
            {{-- Requis pour rouvrir la bonne modale après une erreur de
                 validation : `old('id')` fait partie de l'input flashé. --}}
            <input type="hidden" name="id" value="{{ old('id') }}">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="edit_name" class="block text-sm font-medium text-gray-300 mb-1">Nom complet</label>
                    <input type="text" id="edit_name" name="name"
                           class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                </div>
                <div>
                    <label for="edit_email" class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                    <input type="email" id="edit_email" name="email"
                           class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                </div>
                <div>
                    <label for="edit_role_id" class="block text-sm font-medium text-gray-300 mb-1">Rôle</label>
                    <select id="edit_role_id" name="role_id"
                            class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="edit_departement_id" class="block text-sm font-medium text-gray-300 mb-1">Département</label>
                    <select id="edit_departement_id" name="departement_id"
                            class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                        <option value="">— Aucun —</option>
                        @foreach($departements as $departement)
                            <option value="{{ $departement->id }}">{{ $departement->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="edit_site_id" class="block text-sm font-medium text-gray-300 mb-1">Site</label>
                    <select id="edit_site_id" name="site_id"
                            class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                        <option value="">— Aucun —</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="edit_matricule" class="block text-sm font-medium text-gray-300 mb-1">Matricule</label>
                    <input type="text" id="edit_matricule" name="matricule"
                           class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                </div>
                <div>
                    <label for="edit_poste" class="block text-sm font-medium text-gray-300 mb-1">Poste</label>
                    <input type="text" id="edit_poste" name="poste"
                           class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                </div>
                <div>
                    <label for="edit_telephone" class="block text-sm font-medium text-gray-300 mb-1">Téléphone</label>
                    <input type="text" id="edit_telephone" name="telephone"
                           class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                </div>
            </div>

            <div class="flex flex-wrap gap-6 pt-2">
                <label class="flex items-center gap-2 text-sm text-gray-300">
                    <input type="checkbox" id="edit_est_technicien" name="est_technicien" value="1"
                           class="rounded border-dark-600 bg-dark-900 text-primary-600 focus:ring-primary-600/40">
                    Est technicien
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-300">
                    <input type="checkbox" id="edit_disponible" name="disponible" value="1"
                           class="rounded border-dark-600 bg-dark-900 text-primary-600 focus:ring-primary-600/40">
                    Disponible
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-300">
                    <input type="checkbox" id="edit_actif" name="actif" value="1"
                           class="rounded border-dark-600 bg-dark-900 text-primary-600 focus:ring-primary-600/40">
                    Compte actif
                </label>
            </div>

            <div class="pt-4 border-t border-dark-700">
                <p class="text-sm font-medium text-gray-300 mb-2">Réinitialiser le mot de passe <span class="text-gray-500">(optionnel)</span></p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="password" name="password" placeholder="Nouveau mot de passe (8 caractères min.)"
                           class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white placeholder-gray-500 focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                    <input type="password" name="password_confirmation" placeholder="Confirmer le mot de passe"
                           class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white placeholder-gray-500 focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-dark-700">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-dark-700 hover:bg-dark-600 text-white rounded-lg transition">
                    Annuler
                </button>
                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition shadow-lg">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const USERS_JSON = {{ Js::from($usersJson) }};
    // Le préfixe vient de la route nommée : un chemin en dur dans le JS
    // cassait silencieusement l'édition si le préfixe d'URL changeait.
    const USERS_UPDATE_BASE = @json(route('admin.users.update', ['user' => 'ID_PLACEHOLDER']));

    function showModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function hideModal(id) {
        const modal = document.getElementById(id);
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openCreateModal() {
        showModal('createUserModal');
    }

    function closeCreateModal() {
        hideModal('createUserModal');
    }

    function editUser(id) {
        const user = USERS_JSON.find(u => Number(u.id) === Number(id));
        if (!user) return;

        const form = document.getElementById('editUserForm');
        form.action = USERS_UPDATE_BASE.replace('ID_PLACEHOLDER', user.id);

        document.getElementById('edit_name').value = user.name ?? '';
        document.getElementById('edit_email').value = user.email ?? '';
        document.getElementById('edit_matricule').value = user.matricule ?? '';
        document.getElementById('edit_poste').value = user.poste ?? '';
        document.getElementById('edit_telephone').value = user.telephone ?? '';

        document.getElementById('edit_role_id').value = user.role_id ?? '';
        document.getElementById('edit_departement_id').value = user.departement_id ?? '';
        document.getElementById('edit_site_id').value = user.site_id ?? '';

        document.getElementById('edit_est_technicien').checked = !!user.est_technicien;
        document.getElementById('edit_disponible').checked = !!user.disponible;
        document.getElementById('edit_actif').checked = !!user.actif;

        form.querySelector('input[name="password"]').value = '';
        form.querySelector('input[name="password_confirmation"]').value = '';

        const errorBox = document.getElementById('editUserError');
        errorBox.classList.add('hidden');

        showModal('editUserModal');
    }

    function closeEditModal() {
        hideModal('editUserModal');
    }

    // Rouvre la modale concernée si la validation a échoué, en restaurant
    // la saisie plutôt que les valeurs de la base.
    @if($errors->any())
        @if(old('_form') === 'create')
            openCreateModal();
        @elseif(old('_form') === 'edit' && old('id'))
            editUser(@json((int) old('id')));
            (function () {
                const messages = @json($errors->all());
                const box = document.getElementById('editUserError');
                box.querySelector('ul').replaceChildren(...messages.map((m) => {
                    const li = document.createElement('li');
                    li.textContent = m;
                    return li;
                }));
                box.classList.remove('hidden');

                const set = (field, value) => {
                    const el = document.getElementById('edit_' + field);
                    if (!el) return;
                    if (el.type === 'checkbox') {
                        el.checked = value === '1' || value === 1;
                    } else {
                        el.value = value ?? '';
                    }
                };
                @foreach(['name', 'email', 'role_id', 'departement_id', 'site_id', 'matricule', 'poste', 'telephone', 'est_technicien', 'disponible', 'actif'] as $champ)
                    set(@json($champ), @json(old($champ)));
                @endforeach
            })();
        @endif
    @endif
</script>
@endpush

@push('styles')
<style>
/* Plain CSS on purpose: Blade does not run Tailwind, so an `@apply` inside a
   blade <style> block is shipped verbatim and the browser drops the rule. */
.stat-card {
    padding: 1rem;
    background-color: var(--surface-card);
    border: 1px solid var(--line-default);
    border-radius: 0.75rem;
}
</style>
@endpush
@endsection
