@extends('layouts.portal')

@section('titre', 'Modifier l\'incident')
@section('sous-titre', 'Mise à jour du signalement de sécurité')

@section('contenu')
<div class="p-4 sm:p-6 lg:p-8">
    <div class="mx-auto max-w-3xl space-y-6">

        <a href="{{ route('safety.show', $incident) }}" class="nm-btn nm-btn-ghost -ml-3">
            <x-icon name="arrow-left" class="h-4 w-4" />
            Retour à la fiche
        </a>

        <div>
            <h1 class="nm-page-title">Modifier l'incident</h1>
            <p class="nm-page-subtitle">Signalé par {{ $incident->reporter?->name ?? '—' }}
                le {{ $incident->reported_at?->format('d/m/Y') ?? '—' }}.</p>
        </div>

        <form method="POST" action="{{ route('safety.update', $incident) }}" class="nm-card space-y-5 p-5 sm:p-6">
            @csrf
            @method('PUT')

            <div>
                <label for="titre" class="nm-label">
                    Titre <span class="text-danger-600">*</span>
                </label>
                <input id="titre" name="titre" type="text" required maxlength="255"
                       value="{{ old('titre', $incident->titre) }}" class="nm-input"
                       @error('titre') aria-invalid="true" @enderror>
                @error('titre')
                    <p class="nm-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="nm-label">
                    Description <span class="text-danger-600">*</span>
                </label>
                <textarea id="description" name="description" rows="6" required
                          class="nm-textarea" @error('description') aria-invalid="true" @enderror>{{ old('description', $incident->description) }}</textarea>
                @error('description')
                    <p class="nm-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="severity" class="nm-label">Sévérité <span class="text-danger-600">*</span></label>
                    <select id="severity" name="severity" required class="nm-select"
                            @error('severity') aria-invalid="true" @enderror>
                        @foreach ($severities as $key => $libelle)
                            <option value="{{ $key }}" @selected(old('severity', $incident->severity) === $key)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                    @error('severity')
                        <p class="nm-field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="statut" class="nm-label">Statut <span class="text-danger-600">*</span></label>
                    <select id="statut" name="statut" required class="nm-select"
                            @error('statut') aria-invalid="true" @enderror>
                        @foreach ($statuses as $key => $libelle)
                            <option value="{{ $key }}" @selected(old('statut', $incident->statut) === $key)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                    @error('statut')
                        <p class="nm-field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="operational_zone_id" class="nm-label">
                        Zone opérationnelle <span class="text-danger-600">*</span>
                    </label>
                    <select id="operational_zone_id" name="operational_zone_id" required class="nm-select"
                            @error('operational_zone_id') aria-invalid="true" @enderror>
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}" @selected(old('operational_zone_id', $incident->operational_zone_id) == $zone->id)>
                                {{ $zone->nom }}
                            </option>
                        @endforeach
                    </select>
                    @error('operational_zone_id')
                        <p class="nm-field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="investigated_by" class="nm-label">Investigateur</label>
                    <select id="investigated_by" name="investigated_by" class="nm-select">
                        <option value="">Non attribué</option>
                        @foreach ($investigateurs as $user)
                            <option value="{{ $user->id }}" @selected(old('investigated_by', $incident->investigated_by) == $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="investigation_notes" class="nm-label">Notes d'investigation</label>
                <textarea id="investigation_notes" name="investigation_notes" rows="4"
                          class="nm-textarea">{{ old('investigation_notes', $incident->investigation_notes) }}</textarea>
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-line-subtle pt-5">
                <a href="{{ route('safety.show', $incident) }}" class="nm-btn nm-btn-ghost">Annuler</a>
                <button type="submit" class="nm-btn nm-btn-primary">
                    <x-icon name="check" class="h-4 w-4" />
                    Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
