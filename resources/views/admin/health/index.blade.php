@extends('admin.layout')

@section('title', 'Santé du serveur')

@php
    $badge = fn (bool $ok, string $okText = 'OK', string $koText = 'À vérifier') => $ok
        ? '<span class="rounded bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">'.e($okText).'</span>'
        : '<span class="rounded bg-red-100 px-2 py-1 text-xs font-semibold text-red-800">'.e($koText).'</span>';
@endphp

@section('content')
    <p class="mb-6 text-sm text-gray-600">État technique de l’intranet, vérifié à l’ouverture de la page.</p>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <section class="rounded-lg border border-gray-200 bg-white p-5" id="sante-taches">
            <div class="mb-2 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900">Tâches planifiées</h2>
                {!! $badge($scheduler['ok'], 'Actives', 'Arrêtées') !!}
            </div>
            <p class="text-sm text-gray-600">
                Dernière exécution :
                {{ $scheduler['last_run'] ? $scheduler['last_run']->format('d/m/Y H:i:s').' ('.$scheduler['last_run']->diffForHumans().')' : 'jamais' }}
            </p>
            <p class="mt-1 text-xs text-gray-500">Alertes SLA, rappels de congés et de menus, synchronisation AD. Tourne chaque minute.</p>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5" id="sante-mail">
            <div class="mb-2 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900">E-mail (SMTP)</h2>
                {!! $badge($mail['ok'], 'Configuré', 'Non configuré') !!}
            </div>
            <p class="text-sm text-gray-600">{{ $mail['mailer'] }} · {{ $mail['host'] }}:{{ $mail['port'] }} · expéditeur {{ $mail['from'] }}</p>
            @unless($mail['ok'])
                <p class="mt-1 text-xs text-red-700">Valeurs d’exemple dans le .env du serveur : aucun e-mail ne part.</p>
            @endunless
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5" id="sante-sauvegarde">
            <div class="mb-2 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900">Sauvegarde de la base</h2>
                {!! $badge($backup['ok'], 'À jour', $backup['status'] ? 'En échec ou ancienne' : 'Aucune') !!}
            </div>
            @if($backup['status'])
                <p class="text-sm text-gray-600">
                    Dernière : {{ \Illuminate\Support\Carbon::parse($backup['status']['date'])->format('d/m/Y H:i') }}
                    @isset($backup['status']['taille_mo']) · {{ $backup['status']['taille_mo'] }} Mo @endisset
                    @isset($backup['status']['nombre']) · {{ $backup['status']['nombre'] }} copie(s) conservée(s) @endisset
                </p>
                <p class="mt-1 text-xs {{ empty($backup['status']['copie']) ? 'text-amber-700' : 'text-gray-500' }}">
                    {{ empty($backup['status']['copie']) ? 'Aucune copie hors du serveur : une panne du disque ferait tout perdre.' : 'Copie : '.$backup['status']['copie'] }}
                </p>
                @if(! empty($backup['status']['erreur']))
                    <p class="mt-1 text-xs text-red-700">{{ $backup['status']['erreur'] }}</p>
                @endif
            @else
                <p class="text-sm text-gray-600">Aucune sauvegarde enregistrée par le script nocturne.</p>
            @endif
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5" id="sante-ldap">
            <div class="mb-2 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900">Synchronisation Active Directory</h2>
                {!! $ldap['enabled'] ? $badge(empty($ldap['last_error']), 'Activée', 'Erreur') : $badge(true, 'Désactivée') !!}
            </div>
            <p class="text-sm text-gray-600">
                Fréquence : {{ ['manual' => 'manuelle', 'hourly' => 'toutes les heures', 'daily' => 'quotidienne', 'weekly' => 'hebdomadaire'][$ldap['frequency']] ?? $ldap['frequency'] }}
                · dernière : {{ $ldap['last_sync'] ? \Illuminate\Support\Carbon::parse($ldap['last_sync'])->format('d/m/Y H:i') : 'jamais' }}
            </p>
            @if($ldap['last_error'])
                <p class="mt-1 text-xs text-red-700">{{ \Illuminate\Support\Str::limit($ldap['last_error'], 200) }}</p>
            @endif
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5" id="sante-disque">
            <div class="mb-2 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900">Espace disque</h2>
                {!! $badge($disk['ok'], 'Suffisant', 'Presque plein') !!}
            </div>
            <p class="text-sm text-gray-600">
                @if($disk['free'] && $disk['total'])
                    {{ number_format($disk['free'] / 1073741824, 1, ',', ' ') }} Go libres sur {{ number_format($disk['total'] / 1073741824, 1, ',', ' ') }} Go
                @else
                    Inconnu
                @endif
            </p>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5" id="sante-file">
            <div class="mb-2 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900">File d’attente</h2>
                {!! $badge($queue['ok'], 'OK', 'À vérifier') !!}
            </div>
            <p class="text-sm text-gray-600">
                {{ $queue['pending'] }} en attente
                @if($queue['oldest']) (le plus ancien {{ $queue['oldest']->diffForHumans() }}) @endif
                · {{ $queue['failed_total'] }} en échec
            </p>
        </section>
    </div>

    <section class="mt-6 rounded-lg border border-gray-200 bg-white" id="sante-echecs">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
            <h2 class="font-semibold text-gray-900">Jobs en échec</h2>
            @if($queue['failed_total'] > 0)
                <form method="POST" action="{{ route('admin.health.retry-all') }}" onsubmit="return confirm('Relancer tous les jobs en échec ?')">
                    @csrf
                    <button class="rounded bg-nere-700 px-3 py-1.5 text-sm font-semibold text-white hover:bg-nere-800">Tout relancer</button>
                </form>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-2">Date</th>
                        <th class="px-4 py-2">Job</th>
                        <th class="px-4 py-2">Erreur</th>
                        <th class="px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($queue['failed'] as $job)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-2">{{ $job['failed_at']->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2">{{ class_basename($job['name']) }}</td>
                            <td class="px-4 py-2 text-xs text-gray-600">{{ $job['error'] }}</td>
                            <td class="whitespace-nowrap px-4 py-2">
                                <div class="flex gap-3">
                                    <form method="POST" action="{{ route('admin.health.retry', $job['uuid']) }}">
                                        @csrf
                                        <button class="font-medium text-nere-700 hover:underline">Relancer</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.health.forget', $job['uuid']) }}" onsubmit="return confirm('Supprimer ce job en échec ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="font-medium text-red-700 hover:underline">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Aucun job en échec.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
