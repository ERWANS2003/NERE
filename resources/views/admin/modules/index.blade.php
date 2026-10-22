@extends('admin.layout')

@section('title', 'Modules de l’intranet')

@section('content')
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Activation des modules</h3>
        <p class="mt-1 text-sm text-gray-600">
            Un module désactivé n’est plus accessible aux employés. Ses données sont conservées et le module peut être réactivé ici.
        </p>
    </div>

    <div class="space-y-4">
        @foreach($modules as $key => $module)
            <section class="flex flex-col gap-4 rounded-lg bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h4 class="font-semibold text-gray-900">{{ $module['label'] }}</h4>
                    <p class="mt-1 text-sm {{ $module['enabled'] ? 'text-green-700' : 'text-gray-500' }}">
                        {{ $module['enabled'] ? 'Activé' : 'Désactivé' }}
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.modules.update', $key) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="enabled" value="{{ $module['enabled'] ? '0' : '1' }}">
                    <button type="submit" class="bouton-primaire">
                        {{ $module['enabled'] ? 'Désactiver' : 'Réactiver' }}
                    </button>
                </form>
            </section>
        @endforeach
    </div>

    <section class="mt-8">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">Gestion du contenu</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <a href="{{ route('admin.time-off.index') }}" class="rounded-lg bg-white p-5 shadow-sm hover:shadow">
                <h4 class="font-semibold text-gray-900">Congés</h4>
                <p class="mt-1 text-sm text-gray-600">Politiques, soldes et demandes d’approbation.</p>
            </a>
            <a href="{{ route('admin.resources.index') }}" class="rounded-lg bg-white p-5 shadow-sm hover:shadow">
                <h4 class="font-semibold text-gray-900">Ressources</h4>
                <p class="mt-1 text-sm text-gray-600">Catégories, publication et visibilité des ressources.</p>
            </a>
            @if($modules['knowledge_base']['enabled'])
                <a href="{{ route('intranet.knowledge-base.index') }}" class="rounded-lg bg-white p-5 shadow-sm hover:shadow">
                    <h4 class="font-semibold text-gray-900">Base de connaissances</h4>
                    <p class="mt-1 text-sm text-gray-600">Articles, publication et retours utilisateurs.</p>
                </a>
            @else
                <div class="rounded-lg bg-gray-100 p-5 text-gray-500">
                    <h4 class="font-semibold">Base de connaissances désactivée</h4>
                    <p class="mt-1 text-sm">Réactivez le module ci-dessus pour gérer les articles.</p>
                </div>
            @endif
            @if($modules['forms']['enabled'])
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <h4 class="font-semibold text-gray-900">Formulaires et services</h4>
                    <p class="mt-1 text-sm text-gray-600">Formulaires et soumissions, avec gestion des services par département.</p>
                    <div class="mt-3 flex flex-wrap gap-4 text-sm font-medium text-nere-700">
                        <a href="{{ route('intranet.forms.index') }}" class="hover:underline">Gérer les formulaires</a>
                        <a href="{{ route('intranet.departments.manage.index') }}" class="hover:underline">Gérer les services</a>
                    </div>
                </div>
            @else
                <div class="rounded-lg bg-gray-100 p-5 text-gray-500">
                    <h4 class="font-semibold">Formulaires et services désactivés</h4>
                    <p class="mt-1 text-sm">Réactivez le module ci-dessus pour gérer les formulaires.</p>
                </div>
            @endif
            <a href="{{ route('admin.tickets.index') }}" class="rounded-lg bg-white p-5 shadow-sm hover:shadow">
                <h4 class="font-semibold text-gray-900">Tickets support</h4>
                <p class="mt-1 text-sm text-gray-600">Vue globale avec accès aux fiches et au workflow existant.</p>
            </a>
        </div>
    </section>
@endsection
