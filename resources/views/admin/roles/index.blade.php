@extends('layouts.portal')

@section('titre', 'Gestion des Rôles')
@section('sous-titre', 'Gérer les rôles et permissions du système')

@section('contenu')
<div class="p-6 space-y-6">
    
    <!-- Header Actions -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white">Rôles du système</h2>
            <p class="text-gray-400 mt-1">{{ $roles->count() }} rôle(s) configuré(s)</p>
        </div>

        <a href="{{ route('admin.roles.create') }}" 
           class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium transition shadow-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Créer un rôle
        </a>
    </div>

    <!-- Roles Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($roles as $role)
            <div class="bg-dark-800 rounded-xl border border-dark-700 overflow-hidden hover:border-primary-600/50 transition group">
                <!-- Role Header -->
                <div class="p-6 border-b border-dark-700 bg-gradient-to-br from-dark-800 to-dark-900">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white group-hover:text-primary-400 transition">{{ $role->nom }}</h3>
                                <span class="inline-block px-2 py-0.5 bg-dark-700 text-gray-400 text-xs rounded mt-1 font-mono">{{ $role->slug }}</span>
                            </div>
                        </div>

                        @if(in_array($role->slug, ['admin', 'dsi']))
                            <span class="px-2 py-1 bg-amber-600/20 border border-amber-600/30 text-amber-400 text-xs rounded-full flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                Système
                            </span>
                        @endif
                    </div>

                    @if($role->description)
                        <p class="text-sm text-gray-400 leading-relaxed">{{ $role->description }}</p>
                    @else
                        <p class="text-sm text-gray-500 italic">Aucune description</p>
                    @endif
                </div>

                <!-- Role Stats -->
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span class="text-sm">Utilisateurs</span>
                        </div>
                        <span class="text-xl font-bold text-white">{{ $role->users_count }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                            <span class="text-sm">Permissions</span>
                        </div>
                        <span class="text-xl font-bold text-primary-400">{{ $role->permissions->count() }}</span>
                    </div>

                    <!-- Permissions Preview -->
                    @if($role->permissions->count() > 0)
                        <div class="pt-4 border-t border-dark-700">
                            <div class="text-xs font-semibold text-gray-400 uppercase mb-2">Permissions</div>
                            <div class="flex flex-wrap gap-1">
                                @foreach($role->permissions->take(6) as $permission)
                                    <span class="px-2 py-0.5 bg-dark-700 text-gray-300 text-xs rounded border border-dark-600">
                                        {{ $permission->nom }}
                                    </span>
                                @endforeach
                                @if($role->permissions->count() > 6)
                                    <span class="px-2 py-0.5 bg-primary-600/20 text-primary-400 text-xs rounded border border-primary-600/30">
                                        +{{ $role->permissions->count() - 6 }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Actions -->
                <div class="px-6 py-4 bg-dark-900 border-t border-dark-700 flex items-center justify-end gap-2">
                    <a href="{{ route('admin.roles.edit', $role) }}" 
                       class="inline-flex items-center gap-2 px-4 py-2 bg-dark-700 hover:bg-dark-600 text-white rounded-lg transition text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Modifier
                    </a>

                    @if(!in_array($role->slug, ['admin', 'dsi']))
                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" 
                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce rôle ?')"
                              class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-600/20 hover:bg-red-600/30 text-red-400 border border-red-600/30 rounded-lg transition text-sm"
                                    @if($role->users_count > 0) disabled title="Impossible de supprimer un rôle avec des utilisateurs" @endif>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Supprimer
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if($roles->isEmpty())
        <div class="bg-dark-800 rounded-xl border border-dark-700 p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
            <h3 class="text-lg font-semibold text-white mb-2">Aucun rôle configuré</h3>
            <p class="text-gray-400 mb-4">Créez votre premier rôle pour commencer à organiser les permissions.</p>
            <a href="{{ route('admin.roles.create') }}" 
               class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Créer un rôle
            </a>
        </div>
    @endif

</div>
@endsection
