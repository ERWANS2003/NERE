@extends('layouts.portal')

@section('titre', 'Analyses HSE')
@section('sous-titre', 'Répartition et tendance des incidents de sécurité')

@section('contenu')
<div class="p-4 sm:p-6 lg:p-8 space-y-6">

    <section class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="mb-2 text-[0.6875rem] font-bold uppercase tracking-[0.16em] text-primary-600">Pilotage</p>
            <h1 class="nm-page-title">Analyses HSE</h1>
            <p class="nm-page-subtitle">Où se concentrent les risques, et comment ils évoluent.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('safety.index') }}" class="nm-btn nm-btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" />
                Registre
            </a>
            @can('safety.manage')
                <a href="{{ route('safety.create') }}" class="nm-btn nm-btn-primary">
                    <x-icon name="plus" class="h-4 w-4" />
                    Signaler
                </a>
            @endcan
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        @php
            $indicateurs = [
                ['label' => 'Total signalements', 'value' => $total, 'tone' => 'text-strong'],
                ['label' => 'Non résolus', 'value' => $unresolved, 'tone' => 'text-warning-700'],
                ['label' => 'Critiques', 'value' => $critical, 'tone' => 'text-danger-700'],
                ['label' => '30 derniers jours', 'value' => $recent30, 'tone' => 'text-info-700'],
            ];
        @endphp
        @foreach ($indicateurs as $indicateur)
            <div class="nm-card px-4 py-4">
                <p class="text-[0.6875rem] font-semibold uppercase tracking-wide text-muted">{{ $indicateur['label'] }}</p>
                <p class="mt-2 text-2xl font-bold tabular-nums {{ $indicateur['tone'] }}">{{ number_format($indicateur['value']) }}</p>
            </div>
        @endforeach
    </section>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        <div class="nm-card p-5 sm:p-6">
            <h2 class="nm-section-title">Par sévérité</h2>
            @if ($bySeverity->isEmpty())
                <p class="nm-empty !py-8 text-sm">Aucune donnée de sévérité.</p>
            @else
                <ul class="mt-4 space-y-3">
                    @php $max = max(1, (int) $bySeverity->max()); @endphp
                    @foreach ($severites as $key => $libelle)
                        @php $valeur = (int) ($bySeverity[$key] ?? 0); @endphp
                        <li>
                            <div class="mb-1.5 flex items-center justify-between text-sm">
                                <span class="font-medium text-strong">{{ $libelle }}</span>
                                <span class="font-semibold tabular-nums text-muted">{{ $valeur }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-inset">
                                <div class="h-full rounded-full nm-brand-gradient"
                                     style="width: {{ round($valeur / $max * 100) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="nm-card p-5 sm:p-6">
            <h2 class="nm-section-title">Par statut</h2>
            @if ($byStatut->isEmpty())
                <p class="nm-empty !py-8 text-sm">Aucune donnée de statut.</p>
            @else
                <ul class="mt-4 space-y-3">
                    @php $maxStatut = max(1, (int) $byStatut->max()); @endphp
                    @foreach ($statuses as $key => $libelle)
                        @php $valeur = (int) ($byStatut[$key] ?? 0); @endphp
                        <li>
                            <div class="mb-1.5 flex items-center justify-between text-sm">
                                <span class="font-medium text-strong">{{ $libelle }}</span>
                                <span class="font-semibold tabular-nums text-muted">{{ $valeur }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-inset">
                                <div class="h-full rounded-full nm-spark-gradient"
                                     style="width: {{ round($valeur / $maxStatut * 100) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    <section class="nm-card p-5 sm:p-6">
        <h2 class="nm-section-title">Par zone opérationnelle</h2>
        @if ($byZone->isEmpty())
            <p class="nm-empty !py-8 text-sm">Aucun incident rattaché à une zone.</p>
        @else
            <div class="nm-table-wrap mt-4">
                <table class="nm-table">
                    <thead>
                        <tr>
                            <th>Zone</th>
                            <th class="!text-right">Incidents</th>
                            <th class="w-1/3">Part</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $maxZone = max(1, (int) $byZone->max()); @endphp
                        @foreach ($byZone->sortDesc() as $zone => $valeur)
                            <tr>
                                <td class="font-semibold text-strong">{{ $zone }}</td>
                                <td class="text-right tabular-nums text-body">{{ $valeur }}</td>
                                <td>
                                    <div class="h-2 overflow-hidden rounded-full bg-inset">
                                        <div class="h-full rounded-full nm-brand-gradient"
                                             style="width: {{ round($valeur / $maxZone * 100) }}%"></div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
