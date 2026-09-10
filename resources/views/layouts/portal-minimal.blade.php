<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titre', 'Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-950 border-r border-gray-800 p-6">
            <h1 class="text-2xl font-bold mb-8">ITSM</h1>
            <nav class="space-y-2">
                <a href="#" class="block px-4 py-2 bg-gray-800 rounded">Dashboard</a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1">
            <div class="bg-gray-950 border-b border-gray-800 p-6">
                <h2 class="text-2xl font-bold">@yield('titre', 'Dashboard')</h2>
            </div>
            
            <main class="p-6">
                @yield('contenu')
            </main>
        </div>
    </div>
</body>
</html>
