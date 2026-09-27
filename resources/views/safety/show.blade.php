@extends('layouts.portal')

@section('titre', $incident->titre)
@section('sous-titre', 'Incident de sécurité #' . $incident->id)

@section('contenu')
<div class="p-4 sm:p-6 lg:p-8 space-y-6">

    <a href="{{ route('safety.index') }}" class="nm-btn nm-btn-ghost -ml-3">
        <x-icon name="arrow-left" class="h-4 w-4" />
        Retour au registre
    </a>

    <section class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="min-w-0">
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <span class="nm-badge {{ $incident->couleurSeverite() }}">{{ $incident->libelleSeverite() }}</span>
                <span class="nm-badge {{ $incident->couleurStatut() }}">{{ $incident->libelleStatut() }}</span>
                <span class="nm-badge nm-badge-neutral">
                    <x-icon name="map-pin" class="h-3 w-3" />
                    {{ $incident->operationalZone?->nom ?? 'Zone non précisée' }}
                </span>
            </div>
            <h1 class="nm-page-title">{{ $incident->titre }}</h1>
            <p class="nm-page-subtitle">
                Signalé par {{ $incident->reporter?->name ?? '—' }}
                le {{ $incident->reported_at?->format('d/m/Y à H:i') ?? '—' }}
                @if ($incident->incident_at)
                    · Survenu le {{ $incident->incident_at->format('d/m/Y à H:i') }}
                @endif
            </p>
        </div>

        @can('safety.manage')
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('safety.edit', $incident) }}" class="nm-btn nm-btn-secondary">
                    <x-icon name="pencil" class="h-4 w-4" />
                    Modifier
                </a>
                @if (in_array($incident->statut, ['reported', 'investigating'], true))
                    <button type="button" class="nm-btn nm-btn-primary" data-open-resolve>
                        <x-icon name="check-circle" class="h-4 w-4" />
                        Clôturer l'investigation
                    </button>
                @endif
            </div>
        @endcan
    </section>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
            $tuiles = [
                ['label' => 'Sévérité', 'valeur' => $incident->libelleSeverite()],
                ['label' => 'Statut', 'valeur' => $incident->libelleStatut()],
                ['label' => 'Investigateur', 'valeur' => $incident->investigator?->name ?? 'Non attribué'],
                ['label' => 'Résolu le', 'valeur' => $incident->resolved_at?->format('d/m/Y') ?? '—'],
            ];
        @endphp
        @foreach ($tuiles as $tuile)
            <div class="nm-card px-4 py-4">
                <p class="text-[0.6875rem] font-semibold uppercase tracking-wide text-muted">{{ $tuile['label'] }}</p>
                <p class="mt-1.5 truncate text-sm font-bold text-strong">{{ $tuile['valeur'] }}</p>
            </div>
        @endforeach
    </div>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="nm-card space-y-5 p-5 sm:p-6 lg:col-span-2">
            <h2 class="nm-section-title">Description</h2>
            <p class="whitespace-pre-line text-sm leading-relaxed text-body">{{ $incident->description }}</p>

            @if ($incident->investigation_notes)
                <div class="nm-divider"></div>
                <div>
                    <h3 class="mb-2 text-sm font-bold text-strong">Notes d'investigation</h3>
                    <p class="whitespace-pre-line text-sm leading-relaxed text-body">{{ $incident->investigation_notes }}</p>
                </div>
            @endif

            @if (! empty($incident->corrective_actions))
                <div class="nm-divider"></div>
                <div>
                    <h3 class="mb-2 text-sm font-bold text-strong">Actions correctives</h3>
                    <ul class="space-y-1.5">
                        @foreach ((array) $incident->corrective_actions as $action)
                            <li class="flex items-start gap-2 text-sm text-body">
                                <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-success-600" />
                                <span>{{ is_array($action) ? ($action['libelle'] ?? json_encode($action)) : $action }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <aside class="space-y-6">
            @can('safety.manage')
                @if ($incident->statut === 'reported')
                    <div class="nm-card p-5">
                        <h2 class="mb-3 nm-section-title">Assigner l'investigation</h2>
                        <form method="POST" action="{{ route('safety.assignInvestigation', $incident) }}" class="space-y-3">
                            @csrf
                            <label for="investigated_by" class="nm-label">Investigateur</label>
                            <select id="investigated_by" name="investigated_by" required class="nm-select">
                                <option value="">-- Sélectionner --</option>
                                @foreach ($investigateurs as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="nm-btn nm-btn-secondary w-full">Assigner</button>
                        </form>
                    </div>
                @endif
            @endcan

            <div class="nm-card p-5">
                <h2 class="mb-3 nm-section-title">Fiche</h2>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Identifiant</dt>
                        <dd class="font-mono font-semibold text-strong">#{{ $incident->id }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Zone</dt>
                        <dd class="text-right font-medium text-strong">{{ $incident->operationalZone?->nom ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Signalé par</dt>
                        <dd class="text-right font-medium text-strong">{{ $incident->reporter?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Créé le</dt>
                        <dd class="text-right font-medium text-strong">{{ $incident->created_at?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </aside>
    </section>

    @can('safety.manage')
        @if (in_array($incident->statut, ['reported', 'investigating'], true))
            <div id="resolve-panel" hidden class="nm-card p-5 sm:p-6">
                <h2 class="mb-4 nm-section-title">Clôturer l'investigation</h2>
                <form method="POST" action="{{ route('safety.resolve', $incident) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="investigation_notes_resolve" class="nm-label">
                            Conclusion de l'investigation <span class="text-danger-600">*</span>
                        </label>
                        <textarea id="investigation_notes_resolve" name="investigation_notes" rows="4" required
                                  class="nm-textarea" @error('investigation_notes') aria-invalid="true" @enderror>{{ old('investigation_notes') }}</textarea>
                        @error('investigation_notes')
                            <p class="nm-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="corrective_actions" class="nm-label">Actions correctives</label>
                        <textarea id="corrective_actions" name="corrective_actions" rows="3"
                                  placeholder="Une action par ligne" class="nm-textarea"></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="nm-btn nm-btn-ghost" data-close-resolve>Annuler</button>
                        <button type="submit" class="nm-btn nm-btn-primary">Marquer comme résolu</button>
                    </div>
                </form>
            </div>
        @endif
    @endcan
</div>

@push('scripts')
<script>
    // The corrective-actions textarea is a newline-separated list; the
    // controller validates it as an array of strings.
    document.addEventListener('DOMContentLoaded', function () {
        const panel = document.getElementById('resolve-panel');
        const form = panel && panel.querySelector('form');
        const textarea = document.getElementById('corrective_actions');

        document.querySelector('[data-open-resolve]')?.addEventListener('click', function () {
            panel.hidden = false;
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        document.querySelector('[data-close-resolve]')?.addEventListener('click', function () {
            panel.hidden = true;
        });

        form?.addEventListener('submit', function (event) {
            if (!textarea) return;
            const actions = textarea.value.split('\n').map((l) => l.trim()).filter(Boolean);
            textarea.removeAttribute('name');
            for (const [i, action] of actions.entries()) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'corrective_actions[]';
                input.value = action;
                form.appendChild(input);
            }
        });
    });
</script>
@endpush
@endsection
