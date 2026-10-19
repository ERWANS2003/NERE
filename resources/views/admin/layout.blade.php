<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - {{ $siteName ?? config('app.name') }}</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 text-white">
            <div class="p-4 border-b border-gray-800">
                <h1 class="text-xl font-bold text-nere-500">
                    <i class="fas fa-shield-alt mr-2"></i>Administration système
                </h1>
                <p class="text-xs text-gray-400 mt-1">{{ $siteName ?? config('app.name') }} · Système</p>
            </div>
            
            <nav class="p-4">
                <a href="{{ route('intranet.dashboard') }}" class="block px-4 py-2 mb-2 rounded text-gray-300 hover:bg-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i> Retour à l'intranet
                </a>
                
                <div class="my-4 border-t border-gray-800"></div>

                <a href="{{ route('admin.dashboard') }}"
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('admin.dashboard') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <i class="fas fa-gauge-high mr-2"></i>Vue d'ensemble
                </a>
                
                <a href="{{ route('admin.users.index') }}" 
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('admin.users.*') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <i class="fas fa-users mr-2"></i> Utilisateurs
                </a>
                
                <a href="{{ route('admin.roles.index') }}" 
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('admin.roles.*') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <i class="fas fa-user-tag mr-2"></i> Rôles
                </a>
                
                <a href="{{ route('intranet.departments.manage.index') }}"
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('intranet.departments.manage.*') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <i class="fas fa-building mr-2"></i> Départements
                </a>

                <a href="{{ route('admin.announcements.index') }}"
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('admin.announcements.*') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <i class="fas fa-bullhorn mr-2"></i> Annonces
                </a>

                <a href="{{ route('admin.calendar.index') }}"
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('admin.calendar.*') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <i class="fas fa-calendar-days mr-2"></i> Calendrier
                </a>

                <a href="{{ route('admin.settings') }}"
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('admin.settings*', 'admin.ldap.*') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <i class="fas fa-cog mr-2"></i> Paramètres
                </a>
            </nav>
        </aside>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top bar -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between px-6 py-4">
                    <h2 class="text-xl font-semibold text-gray-800">@yield('title')</h2>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">
                                <i class="fas fa-sign-out-alt"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
