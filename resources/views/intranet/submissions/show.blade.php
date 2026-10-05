@extends('intranet.layouts.app')

@section('titre', $submission->reference)

@section('breadcrumb')
    <span class="mx-1 opacity-40">/</span>
    <a href="{{ route('intranet.departments.show', $dept) }}" class="hover:underline" style="color: var(--brand);">
        {{ $dept->name }}
    </a>
    <span class="mx-1 opacity-40">/</span>
    <span>{{ $submission->reference }}</span>
@endsection

@section('content')

@php
    $isStaff = $pivot && in_array($pivot->role, ['technician', 'director']);
    $isDirector = $pivot && $pivot->role === 'director';
    $isOwner = auth()->id() === $submission->requester_id;
    $statusLabels = [
        'brouillon' => 'Brouillon', 'soumise' => 'Soumise',
        'en_validation' => 'En validation', 'assignee' => 'Assignée',
        'en_cours' => 'En cours', 'en_attente' => 'En attente',
        'escaladee' => 'Escaladée', 'resolue' => 'Résolue',
        'cloturee' => 'Clôturée', 'rejetee' => 'Rejetée', 'annulee' => 'Annulée',
    ];
    $priColors = ['basse' => '#6b7280', 'normale' => '#2563eb', 'haute' => '#d97706', 'critique' => '#dc2626'];
    $pc = $priColors[$submission->priority] ?? '#6b7280';
@endphp

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    {{-- ── Colonne principale (2/3) ─────────────────────────── --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Titre / référence --}}
        <div class="rounded-2xl border p-5"
             style="background-color: var(--surface-card); border-color: var(--line-subtle);">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-mono font-bold mb-1" style="color: {{ $dept->color }};">
                        {{ $submission->reference }}
                    </p>
                    <h1 class="text-lg font-bold" style="color: var(--text-strong);">
                        {{ $submission->form->name }}
                    </h1>
                    <p class="text-sm mt-0.5" style="color: var(--text-muted);">
                        Soumise par {{ $submission->requester->name }}
                        le {{ $submission->created_at->format('d/m/Y à H:i') }}
                    </p>
                </div>

                {{-- Badge statut --}}
                <span class="rounded-full px-3 py-1 text-xs font-bold"
                      style="background-color: var(--surface-inset); color: var(--text-body);">
                    {{ $statusLabels[$submission->status] ?? $submission->status }}
                </span>
            </div>

            {{-- Actions rapides --}}
            @if (! $submission->isTerminal())
                <div class="mt-4 flex flex-wrap gap-2 pt-4 border-t" style="border-color: var(--line-subtle);">

                    {{-- Prendre en charge --}}
                    @if ($isStaff && $submission->status === \App\Models\Intranet\Submission::STATUS_ASSIGNED)
                        <form method="POST" action="{{ route('intranet.submissions.update-status', $submission) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="en_cours">
                            <button type="submit" class="nm-btn nm-btn-sm" style="background-color: #059669; color: #fff;">
                                ▶ Prendre en charge
                            </button>
                        </form>
                    @endif

                    {{-- Résoudre --}}
                    @if ($isStaff && in_array($submission->status, ['en_cours', 'en_attente']))
                        <form method="POST" action="{{ route('intranet.submissions.update-status', $submission) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="resolue">
                            <button type="submit" class="nm-btn nm-btn-sm" style="background-color: #16a34a; color: #fff;">
                                ✓ Marquer résolu
                            </button>
                        </form>
                    @endif

                    {{-- Clôturer --}}
                    @if ($isDirector && $submission->status === \App\Models\Intranet\Submission::STATUS_RESOLVED)
                        <form method="POST" action="{{ route('intranet.submissions.update-status', $submission) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="cloturee">
                            <button type="submit" class="nm-btn nm-btn-sm nm-btn-secondary">
                                ✕ Clôturer
                            </button>
                        </form>
                    @endif

                    {{-- Rejeter --}}
                    @if ($isDirector && $submission->status === \App\Models\Intranet\Submission::STATUS_VALIDATING)
                        <form method="POST" action="{{ route('intranet.submissions.update-status', $submission) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="rejetee">
                            <button type="submit" class="nm-btn nm-btn-sm" style="background-color: #dc2626; color: #fff;"
                                    onclick="return confirm('Confirmer le rejet ?')">
                                ✕ Rejeter
                            </button>
                        </form>
                    @endif

                    {{-- Escalader --}}
                    @if ($isStaff && ! in_array($submission->status, \App\Models\Intranet\Submission::TERMINAL_STATUSES))
                        <form method="POST" action="{{ route('intranet.submissions.escalate', $submission) }}">
                            @csrf
                            <button type="submit" class="nm-btn nm-btn-sm" style="background-color: #9333ea; color: #fff;"
                                    onclick="return confirm('Escalader au niveau supérieur ?')">
                                ↑ Escalader
                            </button>
                        </form>
                    @endif

                    {{-- Annuler (demandeur) --}}
                    @if ($isOwner && in_array($submission->status, ['brouillon','soumise']))
                        <form method="POST" action="{{ route('intranet.submissions.update-status', $submission) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="annulee">
                            <button type="submit" class="nm-btn nm-btn-sm nm-btn-secondary"
                                    onclick="return confirm('Annuler cette demande ?')">
                                Annuler la demande
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        {{-- Réponses du formulaire --}}
        <div class="rounded-2xl border p-5"
             style="background-color: var(--surface-card); border-color: var(--line-subtle);">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide" style="color: var(--text-muted);">
                Contenu de la demande
            </h2>
            <dl class="space-y-3">
                @foreach ($submission->values as $val)
                    <div class="grid grid-cols-3 gap-2 text-sm py-2 border-b last:border-0"
                         style="border-color: var(--line-subtle);">
                        <dt class="font-semibold col-span-1" style="color: var(--text-muted);">
                            {{ $val->field->label }}
                        </dt>
                        <dd class="col-span-2" style="color: var(--text-body);">
                            {{ $val->value ?? '—' }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- Pièces jointes --}}
        @if ($submission->attachments->isNotEmpty())
            <div class="rounded-2xl border p-5"
                 style="background-color: var(--surface-card); border-color: var(--line-subtle);">
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wide" style="color: var(--text-muted);">
                    Pièces jointes
                </h2>
                <ul class="space-y-2">
                    @foreach ($submission->attachments as $att)
                        <li class="flex items-center justify-between text-sm rounded-lg px-3 py-2"
                            style="background-color: var(--surface-inset);">
                            <span class="truncate" style="color: var(--text-body);">
                                📎 {{ $att->original_name }}
                                <span class="ml-2 text-xs" style="color: var(--text-subtle);">{{ $att->humanSize() }}</span>
                            </span>
                            <a href="{{ route('intranet.attachments.download', $att) }}"
                               class="ml-3 shrink-0 text-xs font-semibold" style="color: var(--brand);">
                                Télécharger
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Commentaires --}}
        <div class="rounded-2xl border p-5"
             style="background-color: var(--surface-card); border-color: var(--line-subtle);">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide" style="color: var(--text-muted);">
                Commentaires
            </h2>

            @php $visibleComments = $isStaff
                ? $submission->comments
                : $submission->comments->where('is_internal', false); @endphp

            @forelse ($visibleComments as $c)
                <div class="mb-3 rounded-xl px-4 py-3 text-sm
                    {{ $c->is_internal ? 'border-l-2' : '' }}"
                     style="{{ $c->is_internal ? 'border-color: #d97706; background-color: #fffbeb;' : 'background-color: var(--surface-inset);' }}">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-semibold" style="color: var(--text-strong);">
                            {{ $c->author->name }}
                            @if ($c->is_internal)
                                <span class="ml-1 text-[10px] font-bold text-amber-700 uppercase">interne</span>
                            @endif
                        </span>
                        <span class="text-xs" style="color: var(--text-subtle);">
                            {{ $c->created_at->diffForHumans() }}
                        </span>
                    </div>
                    <p style="color: var(--text-body);">{{ $c->body }}</p>
                </div>
            @empty
                <p class="text-sm italic" style="color: var(--text-subtle);">Aucun commentaire.</p>
            @endforelse

            {{-- Ajouter un commentaire --}}
            @if (! $submission->isTerminal())
                <form method="POST" action="{{ route('intranet.submissions.comment', $submission) }}"
                      class="mt-4 pt-4 border-t space-y-2" style="border-color: var(--line-subtle);">
                    @csrf
                    <textarea name="body" rows="3" required placeholder="Votre commentaire…"
                              class="nm-textarea w-full text-sm"></textarea>
                    @if ($isStaff)
                        <label class="flex items-center gap-2 text-sm cursor-pointer" style="color: var(--text-muted);">
                            <input type="checkbox" name="is_internal" value="1"
                                   class="h-3.5 w-3.5 accent-amber-500">
                            Commentaire interne (non visible par le demandeur)
                        </label>
                    @endif
                    <div class="flex justify-end">
                        <button type="submit" class="nm-btn nm-btn-primary nm-btn-sm">
                            Envoyer
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- ── Colonne latérale (1/3) ───────────────────────────── --}}
    <div class="space-y-5">

        {{-- Méta --}}
        <div class="rounded-2xl border p-4 text-sm space-y-3"
             style="background-color: var(--surface-card); border-color: var(--line-subtle);">
            <h2 class="text-xs font-bold uppercase tracking-wide mb-2" style="color: var(--text-muted);">
                Informations
            </h2>
            @php $metas = [
                'Département'   => $dept->name,
                'Service'       => $submission->form->service->name,
                'Formulaire'    => $submission->form->name,
                'Priorité'      => ucfirst($submission->priority),
                'Assigné à'     => $submission->assignee?->name ?? '—',
                'Étape courante'=> $submission->currentStep?->name ?? '—',
                'Échéance'      => $submission->due_at?->format('d/m/Y H:i') ?? '—',
            ]; @endphp
            @foreach ($metas as $label => $val)
                <div class="flex justify-between gap-2">
                    <span class="font-medium" style="color: var(--text-muted);">{{ $label }}</span>
                    <span style="color: var(--text-body);">{{ $val }}</span>
                </div>
            @endforeach
        </div>

        {{-- Assigner (directeur) --}}
        @if ($isDirector && ! $submission->isTerminal())
            <div class="rounded-2xl border p-4"
                 style="background-color: var(--surface-card); border-color: var(--line-subtle);">
                <h2 class="mb-3 text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">
                    Assigner à
                </h2>
                <form method="POST" action="{{ route('intranet.submissions.assign', $submission) }}"
                      class="flex flex-col gap-2">
                    @csrf
                    <select name="assignee_id" required class="nm-input text-sm">
                        <option value="">— Choisir un technicien —</option>
                        @foreach ($technicians as $tech)
                            <option value="{{ $tech->id }}"
                                    {{ $submission->assignee_id === $tech->id ? 'selected' : '' }}>
                                {{ $tech->name }}
                                @if ($tech->pivot->tech_level)
                                    (N{{ $tech->pivot->tech_level }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="nm-btn nm-btn-primary nm-btn-sm">
                        Assigner
                    </button>
                </form>
            </div>
        @endif

        {{-- Historique --}}
        <div class="rounded-2xl border p-4"
             style="background-color: var(--surface-card); border-color: var(--line-subtle);">
            <h2 class="mb-3 text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">
                Historique
            </h2>
            <ol class="relative border-l" style="border-color: var(--line-subtle);">
                @foreach ($submission->events->take(20) as $evt)
                    @php
                        $evtLabels = [
                            'created' => '📝 Créée', 'submitted' => '📤 Soumise',
                            'assigned' => '👤 Assignée', 'status_changed' => '🔄 Statut changé',
                            'commented' => '💬 Commentaire', 'escalated' => '⬆ Escaladée',
                            'resolved' => '✅ Résolue', 'closed' => '🔒 Clôturée',
                            'rejected' => '❌ Rejetée',
                        ];
                    @endphp
                    <li class="mb-3 ml-4">
                        <div class="absolute -left-1.5 h-3 w-3 rounded-full border-2"
                             style="background-color: {{ $dept->color }}; border-color: var(--surface-card);"></div>
                        <p class="text-xs font-semibold" style="color: var(--text-body);">
                            {{ $evtLabels[$evt->action] ?? $evt->action }}
                        </p>
                        <p class="text-xs" style="color: var(--text-subtle);">
                            {{ $evt->actor?->name ?? 'Système' }}
                            · {{ $evt->created_at->diffForHumans() }}
                        </p>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</div>

@endsection
