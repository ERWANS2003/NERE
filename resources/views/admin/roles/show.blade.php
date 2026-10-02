@extends('layouts.portal')

@section('titre', 'Rôle : ' . $role->nom)
@section('sous-titre', $role->description ?: 'Détail du rôle et de ses permissions')

@section('contenu')
<div class="p-6 space-y-6">

    <!-- Breadcrumb / actions -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.roles.index') }}"
           class="inline-flex items-center gap-2 text-gray-400 hover:text-white transition text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Retour aux rôles
        </a>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.roles.edit', $role) }}"
               class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-5 py-2 rounded-lg font-medium transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Modifier
            </a>
        </div>
    </div>

    <!-- Header -->
    <div class="bg-dark-800 rounded-xl border border-dark-700 overflow-hidden">
        <div class="p-6 border-b border-dark-700 bg-gradient-to-br from-dark-800 to-dark-900">
            <div class="flex items-start gap-4">
                <div class="w-14 h-14 rounded-lg bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-2xl font-bold text-white">{{ $role->nom }}</h2>
                        <span class="px-2 py-0.5 bg-dark-700 text-gray-400 text-xs rounded font-mono">{{ $role->slug }}</span>

                        @if(in_array($role->slug, ['admin', 'dsi'], true))
                            <span class="px-2 py-1 bg-amber-600/20 border border-amber-600/30 text-amber-400 text-xs rounded-full flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                Rôle système
                            </span>
                        @endif
                    </div>

                    <p class="text-gray-400 mt-2 leading-relaxed">
                        {{ $role->description ?: 'Aucune description fournie.' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="p-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-dark-900 rounded-lg p-4 border border-dark-700">
                <div class="flex items-center gap-2 text-gray-400 mb-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span class="text-sm">Utilisateurs</span>
                </div>
                <span class="text-2xl font-bold text-white">{{ $users->count() }}</span>
            </div>

            <div class="bg-dark-900 rounded-lg p-4 border border-dark-700">
                <div class="flex items-center gap-2 text-gray-400 mb-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                    <span class="text-sm">Permissions</span>
                </div>
                <span class="text-2xl font-bold text-primary-400">{{ $permissions->count() }}</span>
            </div>

            <div class="bg-dark-900 rounded-lg p-4 border border-dark-700">
                <div class="flex items-center gap-2 text-gray-400 mb-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 112.25 2.25H18A2.25 2.25 0 0020.25 6V3.75A2.25 2.25 0 0018 1.5h-2.25a2.25 2.25 0 00-2.25 2.25V6zM13.5 15.75a2.25 2.25 0 112.25 2.25H18a2.25 2.25 0 002.25-2.25V13.5A2.25 2.25 0 0018 11.25h-2.25A2.25 2.25 0 0013.5 13.5v2.25z"></path>
                    </svg>
                    <span class="text-sm">Modules couverts</span>
                </div>
                <span class="text-2xl font-bold text-white">{{ $permissions->pluck('module')->filter()->unique()->count() }}</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Permissions par module -->
        <div class="bg-dark-800 rounded-xl border border-dark-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-dark-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">Permissions</h3>
                <span class="text-sm text-gray-400">{{ $permissions->count() }} permission(s)</span>
            </div>

            <div class="p-6">
                @if($permissions->isEmpty())
                    <p class="text-sm text-gray-500 italic text-center py-4">Aucune permission rattachée à ce rôle.</p>
                @else
                    @foreach($permissions->groupBy('module') as $module => $modulePermissions)
                        <div class="mb-4 last:mb-0">
                            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">
                                {{ $module ?: 'Général' }}
                            </div>
                            <div class="flex flex-wrap gap-1">
                                @foreach($modulePermissions as $permission)
                                    <span class="px-2 py-0.5 bg-dark-700 text-gray-300 text-xs rounded border border-dark-600">
                                        {{ $permission->nom }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <!-- Utilisateurs -->
        <div class="bg-dark-800 rounded-xl border border-dark-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-dark-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">Utilisateurs</h3>
                <span class="text-sm text-gray-400">{{ $users->count() }} utilisateur(s)</span>
            </div>

            <div class="divide-y divide-dark-700">
                @forelse($users as $user)
                    <div class="px-6 py-4 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-full bg-dark-700 flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-semibold text-gray-300">
                                    {{ Str::upper(Str::substr($user->name ?? '?', 0, 1)) }}
                                </span>
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-white truncate">{{ $user->name ?? '—' }}</div>
                                <div class="text-xs text-gray-500 truncate">{{ $user->email ?? '' }}</div>
                            </div>
                        </div>

                        @if($user->actif ?? true)
                            <span class="px-2 py-0.5 bg-green-600/20 border border-green-600/30 text-green-400 text-xs rounded-full flex-shrink-0">
                                Actif
                            </span>
                        @else
                            <span class="px-2 py-0.5 bg-dark-700 text-gray-500 text-xs rounded-full flex-shrink-0">
                                Inactif
                            </span>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500 italic text-center py-8">Aucun utilisateur rattaché à ce rôle.</p>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
