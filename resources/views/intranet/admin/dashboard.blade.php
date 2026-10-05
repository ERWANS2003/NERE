@extends('intranet.layouts.app')

@section('titre', 'Administration')

@section('breadcrumb')
    <span class="mx-1 opacity-40">/</span>
    <span>Administration</span>
@endsection

@section('content')

<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-[10px] font-extrabold uppercase tracking-widest mb-1" style="color: var(--spark);">Super Admin</p>
        <h1 class="text-xl font-bold" style="color: var(--text-strong);">Tableau de bord Intranet</h1>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('intranet.admin.departments.index') }}" class="nm-btn nm-btn-secondary nm-btn-sm">
            🏢 Départements
        </a>
    </div>
</div>

{{-- ── KPIs globaux ─────────────────────────────────────────── --}}
<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-6 mb-8">
    @php $kpis = [
        ['label' => 'Total demandes',   'value' => $stats['total'],    'color' => '#6b7280'],
        ['label' => 'Ouvertes',         'value' => $stats['open'],     'color' => '#2563eb'],
        ['label' => 'En retard',        'value' => $stats['overdue'],  'color' => '#dc2626'],
        ['label' => 'Résolues',         'value' => $stats['resolved'], 'color' => '#16a34a'],
        ['label' => 'Clôturées',        'value' => $stats['closed'],   'color' => '#9333ea'],
        ['label' => 'Utilisateurs actifs', 'value' => $stats['users'],'color' => '#d97706'],
    ]; @endphp

    @foreach ($kpis as $kpi)
        <div class="rounded-2xl border p-4" style="background: var(--surface-card); border-color: var(--line-subtle);">
            <p class="text-[10px] font-bold uppercase tracking-wide mb-1" style="color: var(--text-muted);">
                {{ $kpi['label'] }}
            </p>
            <p class="text-2xl font-bold" style="color: {{ $kpi['color'] }};">
                {{ number_format($kpi['value']) }}
            </p>
        </div>
    @endforeach
</div>

{{-- ── Par département ──────────────────────────────────────── --}}
<div class="grid grid-cols-1 gap-5 lg:grid-cols-2 mb-8">
    <div class="rounded-2xl border p-5" style="background: var(--surface-card); border-color: var(--line-subtle);">
        <h2 class="mb-4 text-sm font-bold uppercase tracking-wide" style="color: var(--text-muted);">
            Activité par département
        </h2>
        <div class="space-y-3">
            @foreach ($byDepartment as $dept)
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium" style="color: var(--text-body);">{{ $dept['name'] }}</span>
                        <span class="text-xs font-bold" style="color: {{ $dept['color'] }};">
                            {{ $dept['open'] }} ouvert{{ $dept['open'] > 1 ? 's' : '' }}
                            @if ($dept['overdue'] > 0)
                                <span class="text-red-500 ml-1">· {{ $dept['overdue'] }} en retard</span>
                            @endif
                        </span>
                    </div>
                    @if ($dept['total'] > 0)
                        <div class="h-1.5 w-full rounded-full overflow-hidden" style="background: var(--surface-sunken);">
                            <div class="h-full rounded-full transition-all"
                                 style="width: {{ min(($dept['open'] / max($dept['total'], 1)) * 100, 100) }}%; background: {{ $dept['color'] }};"></div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Temps moyen --}}
    <div class="rounded-2xl border p-5" style="background: var(--surface-card); border-color: var(--line-subtle);">
        <h2 class="mb-4 text-sm font-bold uppercase tracking-wide" style="color: var(--text-muted);">
            Indicateurs de performance
        </h2>
        <div class="space-y-4">
            <div class="rounded-xl p-4" style="background: var(--surface-inset);">
                <p class="text-xs font-semibold mb-1" style="color: var(--text-muted);">Délai moyen de résolution</p>
                <p class="text-2xl font-bold" style="color: var(--text-strong);">
                    {{ $avgResolutionHours ? number_format($avgResolutionHours, 1) . ' h' : '—' }}
                </p>
            </div>
            <div class="rounded-xl p-4" style="background: var(--surface-inset);">
                <p class="text-xs font-semibold mb-1" style="color: var(--text-muted);">Taux de résolution</p>
                @php $total = max($stats['total'], 1); @endphp
                <p class="text-2xl font-bold" style="color: #16a34a;">
                    {{ number_format((($stats['resolved'] + $stats['closed']) / $total) * 100, 1) }}%
                </p>
            </div>
        </div>
    </div>
</div>

{{-- ── Dernières soumissions ────────────────────────────────── --}}
<div class="rounded-2xl border" style="background: var(--surface-card); border-color: var(--line-subtle);">
    <div class="px-5 py-4 border-b" style="border-color: var(--line-subtle);">
        <h2 class="text-sm font-bold uppercase tracking-wide" style="color: var(--text-muted);">
            10 dernières demandes
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr style="background: var(--surface-inset);">
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Référence</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Formulaire</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Demandeur</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Département</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recent as $sub)
                    @php
                        $dc = $sub->form->service->department->color ?? '#6b7280';
                        $statusLabels = ['brouillon'=>'Brouillon','soumise'=>'Soumise','en_validation'=>'En validation','assignee'=>'Assignée','en_cours'=>'En cours','en_attente'=>'En attente','escaladee'=>'Escaladée','resolue'=>'Résolue','cloturee'=>'Clôturée','rejetee'=>'Rejetée','annulee'=>'Annulée'];
                    @endphp
                    <tr class="border-b" style="border-color: var(--line-subtle);">
                        <td class="px-4 py-3 font-mono text-xs font-bold" style="color: {{ $dc }};">{{ $sub->reference }}</td>
                        <td class="px-4 py-3 font-medium" style="color: var(--text-body);">{{ $sub->form->name }}</td>
                        <td class="px-4 py-3 text-xs" style="color: var(--text-muted);">{{ $sub->requester->name }}</td>
                        <td class="px-4 py-3 text-xs" style="color: var(--text-muted);">{{ $sub->form->service->department->name }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                  style="background: var(--surface-inset); color: var(--text-body);">
                                {{ $statusLabels[$sub->status] ?? $sub->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs" style="color: var(--text-muted);">{{ $sub->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('intranet.submissions.show', $sub) }}" class="text-xs font-semibold" style="color: var(--brand);">Voir →</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
