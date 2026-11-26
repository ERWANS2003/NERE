@php
    $breadcrumbs = [['label' => 'Supervision réseau', 'url' => route('intranet.monitor.index')], ['label' => 'Découverte automatique']];
    $statusLabels = ['pending' => 'En attente', 'running' => 'En cours', 'done' => 'Terminée', 'failed' => 'Échec'];
@endphp

<x-intranet-layout title="Découverte automatique" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header title="Supervision réseau" description="Balaye un sous-réseau, reconnaît caméras, imprimantes, serveurs, antennes et équipements réseau, et écarte les ordinateurs du personnel. Rien n'est ajouté sans votre validation." />
    @include('intranet.monitor.partials.nav')

    @if($groups->isEmpty())
        <p class="carte p-5 text-sm text-graphite-700">Créez d’abord un groupe dans la vue d’ensemble : les équipements trouvés y seront rangés.</p>
    @else
        <form method="POST" action="{{ route('intranet.monitor.discovery.store') }}" class="carte mb-6 grid max-w-3xl gap-4 p-5 sm:grid-cols-3">
            @csrf
            <label class="block">
                <span class="etiquette">Sous-réseau</span>
                <input name="cidr" value="{{ old('cidr') }}" required maxlength="18" placeholder="192.168.10.0/24" class="champ">
                @error('cidr')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
            </label>
            <label class="block">
                <span class="etiquette">Ranger dans le groupe</span>
                <select name="group_id" class="champ">
                    @foreach($groups as $id => $path)
                        <option value="{{ $id }}" @selected((string) old('group_id') === (string) $id)>{{ $path }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end">
                <button class="bouton-primaire">Lancer la découverte</button>
            </div>
            <p class="text-xs text-graphite-500 sm:col-span-3">256 adresses au plus, réseaux internes uniquement. Chaque adresse qui répond au ping est interrogée sur quelques ports courants et en SNMP (communauté du groupe) pour deviner son type.</p>
        </form>
    @endif

    <section class="carte overflow-hidden">
        <h2 class="border-b border-graphite-100 px-5 py-3 font-semibold text-graphite-900">Dernières découvertes</h2>
        <ul class="divide-y divide-graphite-100 text-sm">
            @forelse($discoveries as $discovery)
                <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3">
                    <a href="{{ route('intranet.monitor.discovery.show', $discovery) }}" class="font-medium text-graphite-900 hover:text-nere-700">{{ $discovery->cidr }} → {{ $discovery->group?->name }}</a>
                    <span class="text-graphite-600">{{ $statusLabels[$discovery->status] ?? $discovery->status }}{{ $discovery->status === 'done' ? ' · '.count($discovery->results ?? []).' adresse(s) trouvée(s)' : '' }} · {{ $discovery->created_at->format('d/m/Y H:i') }}</span>
                </li>
            @empty
                <li class="px-5 py-3 text-graphite-600">Aucune découverte lancée.</li>
            @endforelse
        </ul>
    </section>
</x-intranet-layout>
