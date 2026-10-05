@extends('intranet.layouts.app')

@section('titre', 'Mes demandes')

@section('breadcrumb')
    <span class="mx-1 opacity-40">/</span>
    <span>Mes demandes</span>
@endsection

@section('content')

<div class="mb-6 flex items-end justify-between gap-4">
    <div>
        <h1 class="text-xl font-bold" style="color: var(--text-strong);">Mes demandes</h1>
        <p class="text-sm mt-0.5" style="color: var(--text-muted);">
            Toutes vos demandes, tous départements confondus.
        </p>
    </div>
    <a href="{{ route('intranet.home') }}" class="nm-btn nm-btn-secondary nm-btn-sm">
        ← Accueil
    </a>
</div>

@if ($submissions->isEmpty())
    <div class="rounded-2xl border py-16 text-center" style="border-color: var(--line-subtle);">
        <p class="text-sm" style="color: var(--text-muted);">Vous n'avez soumis aucune demande pour l'instant.</p>
    </div>
@else
    <div class="overflow-x-auto rounded-2xl border" style="border-color: var(--line-subtle);">
        <table class="w-full text-sm">
            <thead>
                <tr style="background-color: var(--surface-inset); border-bottom: 1px solid var(--line-subtle);">
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Référence</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Formulaire</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Département</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($submissions as $sub)
                    @php
                        $deptColor = $sub->form->service->department->color ?? '#e0a52f';
                        $statusLabels = ['brouillon'=>'Brouillon','soumise'=>'Soumise','en_validation'=>'En validation','assignee'=>'Assignée','en_cours'=>'En cours','en_attente'=>'En attente','escaladee'=>'Escaladée','resolue'=>'Résolue','cloturee'=>'Clôturée','rejetee'=>'Rejetée','annulee'=>'Annulée'];
                    @endphp
                    <tr class="border-b" style="border-color: var(--line-subtle);">
                        <td class="px-4 py-3 font-mono text-xs font-bold" style="color: {{ $deptColor }};">
                            {{ $sub->reference }}
                        </td>
                        <td class="px-4 py-3 font-medium" style="color: var(--text-body);">
                            {{ $sub->form->name }}
                        </td>
                        <td class="px-4 py-3 text-xs" style="color: var(--text-muted);">
                            {{ $sub->form->service->department->name }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                  style="background-color: var(--surface-inset); color: var(--text-body);">
                                {{ $statusLabels[$sub->status] ?? $sub->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs" style="color: var(--text-muted);">
                            {{ $sub->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('intranet.submissions.show', $sub) }}"
                               class="text-xs font-semibold" style="color: var(--brand);">
                                Voir →
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $submissions->links() }}
    </div>
@endif

@endsection
