@php
    $breadcrumbs = [['label' => 'Supervision réseau', 'url' => route('intranet.monitor.index')], ['label' => 'Découverte automatique', 'url' => route('intranet.monitor.discovery')], ['label' => $discovery->cidr]];
    $results = collect($discovery->results ?? []);
@endphp

<x-intranet-layout title="Découverte {{ $discovery->cidr }}" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header title="Découverte de {{ $discovery->cidr }}" :description="'Groupe de destination : '.($discovery->group?->path() ?? '-')" />
    @include('intranet.monitor.partials.nav')

    @if(in_array($discovery->status, ['pending', 'running'], true))
        <div class="carte p-5 text-sm text-graphite-700" data-auto-refresh data-interval="10000">
            <p class="font-semibold text-graphite-900">{{ $discovery->status === 'pending' ? 'En attente de démarrage…' : 'Balayage en cours…' }}</p>
            <p class="mt-1">Cela prend de 1 à 5 minutes. La page se recharge toute seule.</p>
            <button type="button" class="hidden" data-auto-refresh-toggle aria-pressed="false"></button>
            <span class="sr-only" role="status" aria-live="polite" data-auto-refresh-status></span>
        </div>
    @elseif($discovery->status === 'failed')
        <p class="carte p-5 text-sm text-red-800">La découverte a échoué : {{ $discovery->error }}</p>
    @else
        <form method="POST" action="{{ route('intranet.monitor.discovery.import', $discovery) }}" class="carte overflow-hidden">
            @csrf
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-graphite-100 px-5 py-3">
                <h2 class="font-semibold text-graphite-900">{{ $results->count() }} adresse(s) ont répondu</h2>
                <button class="bouton-primaire">Ajouter les équipements cochés</button>
            </div>
            @error('hosts')<p class="px-5 pt-3 text-sm text-red-700">{{ $message }}</p>@enderror
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-graphite-50 text-left text-xs uppercase tracking-wide text-graphite-500">
                        <tr>
                            <th class="px-4 py-2"><span class="sr-only">Ajouter</span></th>
                            <th class="px-4 py-2">Adresse</th>
                            <th class="px-4 py-2">Ports ouverts</th>
                            <th class="px-4 py-2">Description SNMP</th>
                            <th class="px-4 py-2">Type</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-graphite-100">
                        @foreach($results as $host)
                            <tr class="{{ $host['workstation'] || $host['monitored'] ? 'text-graphite-500' : '' }}">
                                <td class="px-4 py-2">
                                    <input type="checkbox" name="hosts[]" value="{{ $host['ip'] }}" aria-label="Ajouter {{ $host['ip'] }}"
                                           @checked($host['type'] && ! $host['workstation'] && ! $host['monitored']) @disabled($host['monitored']) class="rounded border-graphite-300">
                                </td>
                                <td class="px-4 py-2">
                                    <span class="font-medium text-graphite-900">{{ $host['ip'] }}</span>
                                    @if($host['name'])<span class="block text-xs">{{ $host['name'] }}</span>@endif
                                    @if($host['monitored'])<span class="block text-xs text-green-700">Déjà supervisé</span>@endif
                                    @if($host['workstation'])<span class="block text-xs text-amber-700">Probablement un ordinateur du personnel : écarté</span>@endif
                                </td>
                                <td class="px-4 py-2">{{ $host['ports'] ? implode(', ', $host['ports']) : '-' }}</td>
                                <td class="max-w-xs px-4 py-2 text-xs">{{ $host['description'] ?: '-' }}</td>
                                <td class="px-4 py-2">
                                    <select name="types[{{ $host['ip'] }}]" class="champ" aria-label="Type de {{ $host['ip'] }}">
                                        @foreach($types as $value => $label)
                                            <option value="{{ $value }}" @selected(($host['type'] ?? 'server') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </form>
    @endif
</x-intranet-layout>
