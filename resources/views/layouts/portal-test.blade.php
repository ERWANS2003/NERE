<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-950 p-6">
            <h1>ITSM</h1>
            
            <!-- Test @auth directive -->
            @auth
                <p>Auth check passed</p>
            @endauth
            
            <!-- Test using if instead of @hasRole -->
            @if(auth()->user()->hasRole('admin'))
                <p>Has admin role (using if)</p>
            @endif
            
            <!-- Test accessing user properties -->
            <p>User: {{ auth()->user()->name ?? 'no name' }}</p>
        </div>
        
        <main class="flex-1 p-6">
            @yield('contenu')
        </main>
    </div>
</body>
</html>
