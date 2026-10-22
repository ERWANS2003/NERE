<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Menu du personnel') · NÉRÉ Mining</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 text-gray-900">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
            <a href="{{ route('menu.portal') }}" class="font-semibold tracking-wide text-red-700">MENU DU PERSONNEL</a>
            <div class="flex items-center gap-4 text-sm">
                @if(auth()->user()->hasPermission('menu.manage'))
                    <a href="{{ route('menu.manager.campaigns') }}" class="text-gray-600 hover:text-gray-900">Gestion</a>
                @endif
                <span>{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-gray-600 hover:text-gray-900">Déconnexion</button>
                </form>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-5xl px-4 py-8">
        @if(session('success'))
            <div role="status" class="mb-5 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div role="alert" class="mb-5 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                <ul class="list-inside list-disc">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
