@extends('layouts.portal')

@section('titre', 'HSE & conformité')
@section('sous-titre', 'Registre des incidents, risques et actions correctives')

@section('contenu')
<div class="p-4 sm:p-6 lg:p-8 space-y-6">

    <section class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="mb-2 text-[0.6875rem] font-bold uppercase tracking-[0.16em] text-primary-600">
                Prévention &amp; conformité
            </p>
            <h1 class="nm-page-title">Registre HSE</h1>
            <p class="nm-page-subtitle">Transformez chaque signalement en action suivie et documentée.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('safety.statistics') }}" class="nm-btn nm-btn-secondary">
                <x-icon name="chart-bar" class="h-4 w-4" />
                Analyses
            </a>
            @can('safety.manage')
                <a href="{{ route('safety.create') }}" class="nm-btn nm-btn-primary">
                    <x-icon name="plus" class="h-4 w-4" />
                    Signaler un incident
                </a>
            @endcan
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        @php
            $hseKpis = [
                ['label' => 'Signalements', 'value' => $incidents->total(), 'tone' => 'text-strong'],
                ['label' => 'Non résolus', 'value' => \App\Models\SafetyIncident::unresolved()->count(), 'tone' => 'text-warning-700'],
                ['label' => 'Critiques', 'value' => \App\Models\SafetyIncident::critical()->count(), 'tone' => 'text-danger-700'],
                ['label' => '30 derniers jours', 'value' => \App\Models\SafetyIncident::recent(30)->count(), 'tone' => 'text-info-700'],
            ];
        @endphp
        @foreach ($hseKpis as $kpi)
            <div class="nm-card px-4 py-4">
                <p class="text-[0.6875rem] font-semibold uppercase tracking-wide text-muted">{{ $kpi['label'] }}</p>
                <p class="mt-2 text-2xl font-bold tabular-nums {{ $kpi['tone'] }}">{{ number_format($kpi['value']) }}</p>
            </div>
        @endforeach
    </section>

    <section class="nm-card p-4 sm:p-5">
        <form method="GET" action="{{ route('safety.index') }}"
              class="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 xl:grid-cols-[2fr_1fr_1fr_1fr_auto]">
            <div>
                <label for="q" class="nm-label">Recherche</label>
                <input id="q" name="q" type="search" class="nm-search" value="{{ request('q') }}"
                       placeholder="Titre ou description">
            </div>

            <div>
                <label for="severity" class="nm-label">Sévérité</label>
                <select id="severity" name="severity" class="nm-select">
                    <option value="">Toutes</option>
                    @foreach ($severities as $key => $libelle)
                        <option value="{{ $key }}" @selected(request('severity') === $key)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="statut" class="nm-label">Statut</label>
                <select id="statut" name="statut" class="nm-select">
                    <option value="">Tous</option>
                    @foreach ($statuses as $key => $libelle)
                        <option value="{{ $key }}" @selected(request('statut') === $key)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="zone_id" class="nm-label">Zone</label>
                <select id="zone_id" name="zone_id" class="nm-select">
                    <option value="">Toutes</option>
                    @foreach ($zones as $zone)
                        <option value="{{ $zone->id }}" @selected(request('zone_id') == $zone->id)>{{ $zone->nom }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="nm-btn nm-btn-primary">
                    <x-icon name="filter" class="h-4 w-4" />
                    Filtrer
                </button>
                @if (request()->hasAny(['q', 'severity', 'statut', 'zone_id', 'unresolved', 'critical']))
                    <a href="{{ route('safety.index') }}" class="nm-btn nm-btn-ghost">Réinitialiser</a>
                @endif
            </div>
        </form>

        <div class="mt-4 flex flex-wrap gap-4 border-t border-line-subtle pt-4">
            @php
                $bascules = [
                    ['name' => 'unresolved', 'label' => 'Non résolus seulement'],
                    ['name' => 'critical', 'label' => 'Critiques seulement'],
                ];
            @endphp
            @foreach ($bascules as $bascule)
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-body">
                    <input type="checkbox" name="{{ $bascule['name'] }}" value="1"
                           class="h-4 w-4 rounded border-line text-primary-600 focus:ring-2 focus:ring-brand-ring"
                           @checked(request()->boolean($bascule['name']))>
                    {{ $bascule['label'] }}
                </label>
            @endforeach
        </div>
    </section>

    <section class="nm-card overflow-hidden">
        <header class="flex flex-wrap items-center justify-between gap-2 border-b border-line-subtle px-5 py-4 sm:px-6">
            <div>
                <h2 class="nm-section-title">Signalements</h2>
                <p class="mt-1 text-sm text-muted">Priorisez les risques et documentez les actions menées.</p>
            </div>
            <span class="nm-badge nm-badge-neutral">
                {{ $incidents->total() }} résultat{{ $incidents->total() > 1 ? 's' : '' }}
            </span>
        </header>

        @if ($incidents->isEmpty())
            <div class="nm-empty">
                <x-icon name="shield-check" class="h-8 w-8 text-subtle" />
                <p class="font-semibold text-strong">Aucun incident trouvé</p>
                <p class="text-sm">Les filtres actuels ne renvoient aucun résultat.</p>
            </div>
        @else
            <div class="nm-table-wrap !border-0 !rounded-none">
                <table class="nm-table">
                    <thead>
                        <tr>
                            <th>Signalement</th>
                            <th>Titre</th>
                            <th>Sévérité</th>
                            <th>Statut</th>
                            <th>Zone</th>
                            <th class="!text-right">Signalé le</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($incidents as $incident)
                            <tr>
                                <td>
                                    <a href="{{ route('safety.show', $incident) }}"
                                       class="font-mono text-xs font-semibold text-primary-600 hover:underline">
                                        #{{ $incident->id }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('safety.show', $incident) }}" class="block max-w-md">
                                        <span class="block truncate font-semibold text-strong">{{ $incident->titre }}</span>
                                        <span class="block truncate text-xs text-muted">{{ Str::limit($incident->description, 90) }}</span>
                                    </a>
                                </td>
                                <td>
                                    <span class="nm-badge {{ $incident->couleurSeverite() }}">{{ $incident->libelleSeverite() }}</span>
                                </td>
                                <td>
                                    <span class="nm-badge {{ $incident->couleurStatut() }}">{{ $incident->libelleStatut() }}</span>
                                </td>
                                <td class="text-sm text-muted">{{ $incident->operationalZone?->nom ?? '—' }}</td>
                                <td class="whitespace-nowrap text-right text-sm text-muted">
                                    {{ $incident->reported_at?->format('d/m/Y') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($incidents->hasPages())
                <div class="border-t border-line-subtle px-5 py-4 sm:px-6">
                    {{ $incidents->links() }}
                </div>
            @endif
        @endif
    </section>
</div>
@endsection
