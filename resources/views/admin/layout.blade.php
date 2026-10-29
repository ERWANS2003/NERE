<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon-32.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#b91c1c">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa-icon-192.png') }}">
    <title>@yield('title') - {{ $siteName ?? config('app.name') }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-shell bg-gray-50">
    @php($isSystemAdmin = auth()->user()->is_super_admin)
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 text-white">
            <div class="p-4 border-b border-gray-800">
                <h1 class="text-xl font-bold text-nere-500">
                    <x-icone :nom="$isSystemAdmin ? 'shield' : 'building-2'" class="mr-2" />{{ $isSystemAdmin ? 'Administration système' : 'Gestion des départements' }}
                </h1>
                <p class="text-xs text-gray-400 mt-1">{{ $siteName ?? config('app.name') }} · {{ $isSystemAdmin ? 'Système' : 'Organisation' }}</p>
            </div>
            
            <nav class="p-4">
                <a href="{{ route('intranet.dashboard') }}" class="block px-4 py-2 mb-2 rounded text-gray-300 hover:bg-gray-800">
                    <x-icone nom="arrow-left" class="mr-2" /> Retour à l'intranet
                </a>
                
                <div class="my-4 border-t border-gray-800"></div>

                @if($isSystemAdmin)
                <a href="{{ route('admin.dashboard') }}"
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('admin.dashboard') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <x-icone nom="gauge" class="mr-2" />Vue d'ensemble
                </a>
                
                <a href="{{ route('admin.users.index') }}" 
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('admin.users.*') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <x-icone nom="users" class="mr-2" /> Utilisateurs
                </a>
                
                <a href="{{ route('admin.roles.index') }}" 
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('admin.roles.*') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <x-icone nom="user-cog" class="mr-2" /> Rôles
                </a>

                <a href="{{ route('admin.modules.index') }}"
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('admin.modules.*') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <x-icone nom="puzzle" class="mr-2" /> Modules de l’intranet
                </a>

                <a href="{{ route('admin.time-off.index') }}"
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('admin.time-off.*') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <x-icone nom="calendar-check" class="mr-2" /> Congés
                </a>

                <a href="{{ route('admin.resources.index') }}"
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('admin.resources.*') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <x-icone nom="folder-open" class="mr-2" /> Ressources
                </a>

                @if(app(\App\Support\IntranetModuleRegistry::class)->enabled('knowledge_base'))
                    <a href="{{ route('intranet.knowledge-base.index') }}"
                       class="block px-4 py-2 mb-2 rounded text-gray-300 hover:bg-gray-800">
                        <x-icone nom="book-open" class="mr-2" /> Base de connaissances
                    </a>
                @endif

                @if(app(\App\Support\IntranetModuleRegistry::class)->enabled('forms'))
                    <a href="{{ route('intranet.forms.index') }}"
                       class="block px-4 py-2 mb-2 rounded text-gray-300 hover:bg-gray-800">
                        <x-icone nom="file-text" class="mr-2" /> Formulaires et services
                    </a>
                @endif

                <a href="{{ route('admin.tickets.index') }}"
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('admin.tickets.*') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <x-icone nom="ticket" class="mr-2" /> Tickets support
                </a>
                @endif

                <a href="{{ route('intranet.departments.manage.index') }}"
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('intranet.departments.manage.*') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <x-icone nom="building-2" class="mr-2" /> Départements
                </a>

                @if($isSystemAdmin)
                <a href="{{ route('admin.announcements.index') }}"
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('admin.announcements.*') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <x-icone nom="megaphone" class="mr-2" /> Annonces
                </a>

                <a href="{{ route('admin.calendar.index') }}"
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('admin.calendar.*') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <x-icone nom="calendar-days" class="mr-2" /> Calendrier
                </a>

                <a href="{{ route('admin.settings') }}"
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('admin.settings*', 'admin.ldap.*') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <x-icone nom="settings" class="mr-2" /> Paramètres
                </a>

                <a href="{{ route('admin.health') }}"
                   class="block px-4 py-2 mb-2 rounded {{ request()->routeIs('admin.health*') ? 'bg-nere-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <x-icone nom="gauge" class="mr-2" /> Santé du serveur
                </a>
                @endif
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
                                <x-icone nom="log-out" />
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                    <x-icone nom="circle-check" class="mr-2" />{{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                    <x-icone nom="circle-alert" class="mr-2" />{{ session('error') }}
                </div>
                @endif

                @if($errors->any())
                <div role="alert" class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                    <ul class="list-inside list-disc">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
