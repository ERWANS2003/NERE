<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titre', 'Portail') · ITSM Néré Mining</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-nere-mining.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50">
    
    <!-- Simple Navbar -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo-nere-mining.png') }}" alt="Logo" class="h-10 w-10" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">ITSM Néré Mining</h1>
                        <p class="text-sm text-gray-500">Portail de Services</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    @auth
                        <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                        <a href="{{ route('tickets.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Mes tickets</a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-red-600 hover:text-red-700">Déconnexion</button>
                        </form>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <p class="text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <p class="text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main>
        @yield('contenu')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto px-6 py-6">
            <p class="text-center text-sm text-gray-500">
                © {{ date('Y') }} Néré Mining - ITSM Platform
            </p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
