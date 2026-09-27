@extends('layouts.portal')

@section('titre', 'Signaler un incident')
@section('sous-titre', 'Créer un nouvel incident de sécurité')

@section('contenu')
<div class="p-4 sm:p-6 lg:p-8">
    <div class="mx-auto max-w-3xl space-y-6">

        <a href="{{ route('safety.index') }}" class="nm-btn nm-btn-ghost -ml-3">
            <x-icon name="arrow-left" class="h-4 w-4" />
            Retour au registre
        </a>

        <div>
            <h1 class="nm-page-title">Signaler un incident de sécurité</h1>
            <p class="nm-page-subtitle">
                Documentez les faits le plus précisément possible : cet enregistrement
                alimente le registre HSE et le suivi des actions correctives.
            </p>
        </div>

        <form method="POST" action="{{ route('safety.store') }}" class="nm-card space-y-5 p-5 sm:p-6">
            @csrf

            <div>
                <label for="titre" class="nm-label">
                    Titre <span class="text-danger-600">*</span>
                </label>
                <input id="titre" name="titre" type="text" required maxlength="255"
                       value="{{ old('titre') }}"
                       placeholder="Résumé factuel de l'incident"
                       class="nm-input" @error('titre') aria-invalid="true" @enderror>
                @error('titre')
                    <p class="nm-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="nm-label">
                    Description <span class="text-danger-600">*</span>
                </label>
                <textarea id="description" name="description" rows="6" required
                          placeholder="Circonstances, personnes concernées, impacts constatés, mesures immédiates prises…"
                          class="nm-textarea" @error('description') aria-invalid="true" @enderror>{{ old('description') }}</textarea>
                @error('description')
                    <p class="nm-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="severity" class="nm-label">
                        Sévérité <span class="text-danger-600">*</span>
                    </label>
                    <select id="severity" name="severity" required class="nm-select"
                            @error('severity') aria-invalid="true" @enderror>
                        <option value="">-- Sélectionner --</option>
                        @foreach ($severities as $key => $libelle)
                            <option value="{{ $key }}" @selected(old('severity') === $key)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                    @error('severity')
                        <p class="nm-field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="operational_zone_id" class="nm-label">
                        Zone opérationnelle <span class="text-danger-600">*</span>
                    </label>
                    <select id="operational_zone_id" name="operational_zone_id" required class="nm-select"
                            @error('operational_zone_id') aria-invalid="true" @enderror>
                        <option value="">-- Sélectionner --</option>
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}" @selected(old('operational_zone_id') == $zone->id)>
                                {{ $zone->nom }}
                            </option>
                        @endforeach
                    </select>
                    @error('operational_zone_id')
                        <p class="nm-field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="incident_at" class="nm-label">
                    Date et heure de l'incident <span class="text-danger-600">*</span>
                </label>
                <input id="incident_at" name="incident_at" type="datetime-local" required
                       value="{{ old('incident_at', now()->format('Y-m-d\TH:i')) }}"
                       class="nm-input" @error('incident_at') aria-invalid="true" @enderror>
                @error('incident_at')
                    <p class="nm-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-line-subtle pt-5">
                <a href="{{ route('safety.index') }}" class="nm-btn nm-btn-ghost">Annuler</a>
                <button type="submit" class="nm-btn nm-btn-primary">
                    <x-icon name="shield-alert" class="h-4 w-4" />
                    Enregistrer le signalement
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
