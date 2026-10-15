@extends('admin.layout')

@section('title', 'Gérer les droits utilisateur')

@section('content')
<div class="max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('admin.users.show', $user) }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-2"></i>Retour aux détails
        </a>
    </div>

    @if($isAdUser)
        <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
            <div class="flex items-start gap-3">
                <i class="fas fa-info-circle mt-0.5"></i>
                <div>
                    <p class="font-semibold">Utilisateur Active Directory</p>
                    <p class="mt-1">
                        L'identité, l'email, le poste, le statut et le mot de passe sont gérés par l'AD.
                        Cette page modifie uniquement les rôles et permissions de l'intranet.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Gérer les droits de {{ $user->name }}</h3>

        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')

            <!-- Identité -->
            <div class="space-y-4 mb-6 pb-6 border-b border-gray-200">
                <h4 class="font-semibold text-gray-900">Identité</h4>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           @if($isAdUser) readonly @endif
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @if($isAdUser) bg-gray-100 @endif">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           @if($isAdUser) readonly @endif
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @if($isAdUser) bg-gray-100 @endif">
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Matricule</label>
                        <input type="text" name="matricule" value="{{ old('matricule', $user->matricule) }}"
                               @if($isAdUser) readonly @endif
                               class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @if($isAdUser) bg-gray-100 @endif">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                        <input type="text" name="telephone" value="{{ old('telephone', $user->telephone) }}"
                               @if($isAdUser) readonly @endif
                               class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @if($isAdUser) bg-gray-100 @endif">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Poste</label>
                        <input type="text" name="poste" value="{{ old('poste', $user->poste) }}"
                               @if($isAdUser) readonly @endif
                               class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @if($isAdUser) bg-gray-100 @endif">
                    </div>
                </div>
            </div>

            @unless($isAdUser)
                <!-- Mot de passe local -->
                <div class="space-y-4 mb-6 pb-6 border-b border-gray-200">
                    <p class="text-sm text-gray-600 mb-3">
                        <i class="fas fa-info-circle mr-1"></i>Laissez vide pour conserver le mot de passe actuel.
                    </p>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
                        <input type="password" name="password"
                               class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @error('password') border-red-500 @enderror">
                        @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
                        <input type="password" name="password_confirmation"
                               class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500">
                    </div>
                </div>
            @endunless

            <!-- Localisation repas (donnée locale, jamais écrite dans l'AD) -->
            <div class="mb-6 pb-6 border-b border-gray-200">
                <h4 class="font-semibold text-gray-900 mb-1">Localisation restauration</h4>
                <p class="text-sm text-gray-500 mb-3">Détermine si l'employé reçoit le menu hebdomadaire. Donnée interne à l'intranet.</p>
                @php $mealLocationId = old('meal_location_id', $user->meal_location_id); @endphp
                <select name="meal_location_id" class="w-full md:w-1/2 px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500">
                    <option value="">— Aucune (pas de menu) —</option>
                    @foreach($mealLocations as $location)
                        <option value="{{ $location->id }}" @selected((string) $mealLocationId === (string) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
                @error('meal_location_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Rôles par département -->
            <div class="mb-6 pb-6 border-b border-gray-200">
                <div class="mb-3">
                    <h4 class="font-semibold text-gray-900">Rôles par département</h4>
                    <p class="text-sm text-gray-500">Un utilisateur peut avoir un rôle différent dans chaque département.</p>
                </div>
                <div class="space-y-3">
                    @foreach($departments as $dept)
                        @php
                            $membership = $user->departments->firstWhere('id', $dept->id);
                            $rolePath = "department_assignments.{$dept->id}.role";
                            $levelPath = "department_assignments.{$dept->id}.tech_level";
                            $role = old($rolePath, $membership?->pivot?->role ?? '');
                            $techLevel = old($levelPath, $membership?->pivot?->tech_level ?? '');
                        @endphp
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-center rounded-lg border border-gray-200 p-3">
                            <div class="font-medium text-sm text-gray-800 md:col-span-1">{{ $dept->name }}</div>
                            <select name="department_assignments[{{ $dept->id }}][role]" class="px-3 py-2 border border-gray-300 rounded text-sm focus:ring-nere-500 focus:border-nere-500">
                                <option value="">Aucun accès</option>
                                <option value="user" @selected($role === 'user')>Utilisateur</option>
                                <option value="technician" @selected($role === 'technician')>Technicien</option>
                                <option value="director" @selected($role === 'director')>Directeur</option>
                            </select>
                            <select name="department_assignments[{{ $dept->id }}][tech_level]" class="px-3 py-2 border border-gray-300 rounded text-sm focus:ring-nere-500 focus:border-nere-500">
                                <option value="">Niveau technique —</option>
                                <option value="1" @selected((string) $techLevel === '1')>N1 — Support</option>
                                <option value="2" @selected((string) $techLevel === '2')>N2 — Confirmé</option>
                                <option value="3" @selected((string) $techLevel === '3')>N3 — Expert</option>
                            </select>
                        </div>
                    @endforeach
                </div>
                @error('department_assignments')<p class="text-red-500 text-xs mt-2">{{ $message }}</p>@enderror
            </div>

            <!-- Rôles système -->
            <div class="mb-6 pb-6 border-b border-gray-200">
                <h4 class="font-semibold text-gray-900 mb-2">Rôles système</h4>
                <p class="text-sm text-gray-500 mb-3">Ces rôles et permissions sont propres à l'intranet. Ils ne modifient pas l'AD.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    @foreach($roles as $role)
                        <label class="flex items-start rounded border border-gray-200 p-3 hover:bg-gray-50">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                   @checked(in_array($role->id, old('roles', $user->roles->pluck('id')->toArray())))
                                   class="mt-1 rounded border-gray-300 text-nere-600 focus:ring-nere-500">
                            <span class="ml-2 text-sm text-gray-700">
                                <span class="font-medium">{{ $role->nom }}</span>
                                @if($role->description)<span class="block text-xs text-gray-500">{{ $role->description }}</span>@endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Options de sécurité -->
            <div class="space-y-3 mb-6">
                @unless($isAdUser)
                    <label class="flex items-center">
                        <input type="checkbox" name="actif" value="1"
                               @checked(old('actif', $user->actif))
                               class="rounded border-gray-300 text-nere-600 focus:ring-nere-500">
                        <span class="ml-2 text-sm text-gray-700">Compte local actif</span>
                    </label>
                @else
                    <p class="text-sm text-gray-600"><strong>Statut AD :</strong> {{ $user->actif ? 'actif' : 'désactivé' }}. Il est contrôlé par la synchronisation AD.</p>
                @endunless

                <label class="flex items-center">
                    <input type="checkbox" name="is_super_admin" value="1"
                           @checked(old('is_super_admin', $user->is_super_admin))
                           class="rounded border-gray-300 text-nere-600 focus:ring-nere-500">
                    <span class="ml-2 text-sm text-gray-700">
                        <i class="fas fa-shield-alt text-nere-600"></i> Super administrateur de l'intranet
                    </span>
                </label>
                @error('is_super_admin')<p class="text-red-500 text-xs">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('admin.users.show', $user) }}" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">Annuler</a>
                <button type="submit" class="px-4 py-2 bg-nere-600 text-white rounded hover:bg-nere-700">
                    <i class="fas fa-save mr-2"></i>Enregistrer les droits
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
