@extends('admin.layout')

@section('title', 'Modifier un utilisateur')

@section('content')
<div class="max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('admin.users.show', $user) }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-2"></i>Retour aux détails
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Modifier l'utilisateur</h3>

        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')

            <!-- Informations de base -->
            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom complet *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @error('name') border-red-500 @enderror">
                    @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @error('email') border-red-500 @enderror">
                    @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Matricule</label>
                    <input type="text" name="matricule" value="{{ old('matricule', $user->matricule) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @error('matricule') border-red-500 @enderror">
                    @error('matricule')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                        <input type="text" name="telephone" value="{{ old('telephone', $user->telephone) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Poste</label>
                        <input type="text" name="poste" value="{{ old('poste', $user->poste) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500">
                    </div>
                </div>
            </div>

            <!-- Mot de passe -->
            <div class="space-y-4 mb-6 pb-6 border-b border-gray-200">
                <p class="text-sm text-gray-600 mb-3">
                    <i class="fas fa-info-circle mr-1"></i>Laissez vide pour conserver le mot de passe actuel
                </p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
                    <input type="password" name="password"
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @error('password') border-red-500 @enderror">
                    @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirmation"
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500">
                </div>
            </div>

            <!-- Départements -->
            <div class="mb-6 pb-6 border-b border-gray-200">
                <div class="mb-3">
                    <h4 class="block text-sm font-medium text-gray-700">Départements</h4>
                    <p class="mt-1 text-xs text-gray-500">Sélectionnez les départements de l’employé, puis définissez son rôle et son niveau dans chacun.</p>
                </div>
                <input type="hidden" name="departments_present" value="1">
                <div class="space-y-3">
                    @foreach($departments as $dept)
                        @php
                            $membership = $user->departments->firstWhere('id', $dept->id);
                            $departmentRole = old('department_roles.'.$dept->id, $membership?->pivot->role ?? \App\Enums\DepartmentRole::User->value);
                            $departmentLevel = old('department_levels.'.$dept->id, $membership?->pivot->tech_level);
                        @endphp
                        <div class="grid gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,0.75fr)] sm:items-center">
                            <label class="flex items-center">
                                <input type="checkbox" name="departments[]" value="{{ $dept->id }}"
                                       {{ in_array($dept->id, old('departments', $user->departments->pluck('id')->toArray())) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-nere-600 focus:ring-nere-500">
                                <span class="ml-2 text-sm font-medium text-gray-700">
                                    {{ $dept->name }}
                                    @unless($dept->is_active)
                                        <span class="text-xs text-amber-700">(inactif)</span>
                                    @endunless
                                </span>
                            </label>
                            <div>
                                <label for="department_role_{{ $dept->id }}" class="mb-1 block text-xs font-medium text-gray-600">Rôle dans le département</label>
                                <select id="department_role_{{ $dept->id }}" name="department_roles[{{ $dept->id }}]"
                                        class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-nere-500 focus:ring-nere-500">
                                    @foreach(\App\Enums\DepartmentRole::options() as $value => $label)
                                        <option value="{{ $value }}" @selected($departmentRole === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('department_roles.'.$dept->id)
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="department_level_{{ $dept->id }}" class="mb-1 block text-xs font-medium text-gray-600">Niveau technicien</label>
                                <select id="department_level_{{ $dept->id }}" name="department_levels[{{ $dept->id }}]"
                                        class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-nere-500 focus:ring-nere-500">
                                    <option value="">Non applicable</option>
                                    @foreach([1, 2, 3] as $level)
                                        <option value="{{ $level }}" @selected((string) $departmentLevel === (string) $level)>Niveau {{ $level }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Obligatoire pour un technicien.</p>
                                @error('department_levels.'.$dept->id)
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Rôles -->
            <div class="mb-6 pb-6 border-b border-gray-200">
                <label class="block text-sm font-medium text-gray-700 mb-2">Rôles système</label>
                <input type="hidden" name="roles_present" value="1">
                <div class="grid grid-cols-2 gap-2">
                    @foreach($roles as $role)
                    <label class="flex items-center">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" 
                               {{ in_array($role->id, old('roles', $user->roles->pluck('id')->toArray())) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-nere-600 focus:ring-nere-500">
                        <span class="ml-2 text-sm text-gray-700">
                            <span class="font-medium">{{ $role->nom }}</span>
                            <span class="block text-xs text-gray-500">
                                {{ $role->description ?: 'Rôle personnalisé' }} · {{ $role->permissions_count }} permission(s)
                            </span>
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- Options -->
            <div class="space-y-3 mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="actif" value="1" 
                           {{ old('actif', $user->actif) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-nere-600 focus:ring-nere-500">
                    <span class="ml-2 text-sm text-gray-700">Compte actif</span>
                </label>

                <label class="flex items-center">
                    <input type="checkbox" name="is_super_admin" value="1"
                           {{ old('is_super_admin', $user->is_super_admin) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-nere-600 focus:ring-nere-500">
                    <span class="ml-2 text-sm text-gray-700">
                        <i class="fas fa-shield-alt text-nere-600"></i> Administrateur système global
                    </span>
                </label>
                <p class="ml-6 text-xs text-gray-500">Droit transversal, indépendant de tout département métier.</p>
            </div>

            <!-- Boutons -->
            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('admin.users.show', $user) }}" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                    Annuler
                </a>
                <button type="submit" class="px-4 py-2 bg-nere-600 text-white rounded hover:bg-nere-700">
                    <i class="fas fa-save mr-2"></i>Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
