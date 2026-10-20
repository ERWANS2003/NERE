@extends('admin.layout')

@section('title', 'Paramètres')

@section('content')
<div class="mb-6">
    <h3 class="text-lg font-semibold text-gray-900">Paramètres de l’application</h3>
    <p class="text-sm text-gray-600">Gérez l’identité du site et la synchronisation Active Directory.</p>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <section class="rounded-lg bg-white p-6 shadow-sm xl:col-span-2">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <h4 class="font-semibold text-gray-900">Identité du site</h4>
                <p class="mt-1 text-sm text-gray-500">Le nom est utilisé dans les titres des pages. Les paramètres techniques restent dans la configuration sécurisée du serveur.</p>
            </div>

            <div>
                <label for="site_name" class="mb-1 block text-sm font-medium text-gray-700">Nom du site</label>
                <input id="site_name" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required maxlength="120"
                       class="w-full rounded border border-gray-300 px-3 py-2 focus:border-nere-500 focus:ring-nere-500">
                @error('site_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="support_email" class="mb-1 block text-sm font-medium text-gray-700">Adresse de support</label>
                <input id="support_email" type="email" name="support_email" value="{{ old('support_email', $settings['support_email']) }}"
                       class="w-full rounded border border-gray-300 px-3 py-2 focus:border-nere-500 focus:ring-nere-500">
                @error('support_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="border-t border-gray-200 pt-6">
                <h4 class="font-semibold text-gray-900">Synchronisation Active Directory</h4>
                <p class="mt-1 text-sm text-gray-500">
                    Connexion AD :
                    <span class="{{ $ldapEnabled ? 'text-green-700' : 'text-red-700' }} font-medium">{{ $ldapEnabled ? 'activée' : 'désactivée côté serveur' }}</span>.
                    Les hôtes et identifiants LDAP ne sont jamais modifiables depuis cette page.
                </p>
            </div>

            <div>
                <label for="ldap_sync_frequency" class="mb-1 block text-sm font-medium text-gray-700">Fréquence</label>
                <select id="ldap_sync_frequency" name="ldap_sync_frequency" class="w-full rounded border border-gray-300 px-3 py-2 focus:border-nere-500 focus:ring-nere-500">
                    @foreach(['manual' => 'Manuelle uniquement', 'hourly' => 'Toutes les heures', 'daily' => 'Chaque nuit', 'weekly' => 'Chaque semaine'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('ldap_sync_frequency', $settings['ldap_sync_frequency']) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('ldap_sync_frequency')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="ldap_sync_time" class="mb-1 block text-sm font-medium text-gray-700">Heure (nuit/semaine)</label>
                    <input id="ldap_sync_time" type="time" name="ldap_sync_time" value="{{ old('ldap_sync_time', $settings['ldap_sync_time']) }}"
                           class="w-full rounded border border-gray-300 px-3 py-2 focus:border-nere-500 focus:ring-nere-500">
                    @error('ldap_sync_time')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="ldap_sync_day" class="mb-1 block text-sm font-medium text-gray-700">Jour (fréquence hebdomadaire)</label>
                    <select id="ldap_sync_day" name="ldap_sync_day" class="w-full rounded border border-gray-300 px-3 py-2 focus:border-nere-500 focus:ring-nere-500">
                        @foreach([1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'] as $value => $label)
                            <option value="{{ $value }}" @selected((int) old('ldap_sync_day', $settings['ldap_sync_day']) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('ldap_sync_day')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded bg-nere-600 px-4 py-2 font-medium text-white hover:bg-nere-700">
                    <i class="fas fa-save mr-2"></i>Enregistrer les paramètres
                </button>
            </div>
        </form>
    </section>

    <aside class="space-y-6">
        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h4 class="font-semibold text-gray-900">Synchronisation manuelle</h4>
            <p class="mt-2 text-sm text-gray-600">Importe et met à jour les comptes AD dans l’intranet. Cette opération peut prendre quelques minutes.</p>
            <form method="POST" action="{{ route('admin.settings.ldap.sync') }}" class="mt-4">
                @csrf
                <button type="submit" @disabled(!$ldapEnabled)
                        class="w-full rounded bg-nere-600 px-4 py-2 font-medium text-white hover:bg-nere-700 disabled:cursor-not-allowed disabled:bg-gray-400">
                    <i class="fas fa-sync mr-2"></i>Lancer la synchronisation
                </button>
            </form>
            @if($lastSyncError)
                <div class="mt-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                    <p class="font-semibold">Détail de la dernière erreur</p>
                    <p class="mt-1 break-words">{{ $lastSyncError }}</p>
                </div>
            @endif
            @if($lastSyncAttemptAt)
                <p class="mt-3 text-xs text-gray-500">Dernière tentative : {{ \Illuminate\Support\Carbon::parse($lastSyncAttemptAt)->format('d/m/Y à H:i') }}</p>
            @endif
            @if($lastSyncAt)
                <p class="mt-4 text-xs text-gray-500">Dernière réussite : {{ \Illuminate\Support\Carbon::parse($lastSyncAt)->format('d/m/Y à H:i') }}</p>
                @if($lastSyncSummary)
                    <ul class="mt-2 space-y-1 text-xs text-gray-600">
                        <li>Importés : {{ $lastSyncSummary['imported'] ?? 0 }}</li>
                        <li>Mis à jour : {{ $lastSyncSummary['updated'] ?? 0 }}</li>
                        <li>Ignorés : {{ $lastSyncSummary['ignored'] ?? 0 }}</li>
                        <li>Désactivés : {{ $lastSyncSummary['disabled'] ?? 0 }}</li>
                    </ul>
                @endif
            @else
                <p class="mt-4 text-xs text-gray-500">Aucune synchronisation réussie enregistrée.</p>
            @endif
        </section>

    </aside>
</div>
@endsection
