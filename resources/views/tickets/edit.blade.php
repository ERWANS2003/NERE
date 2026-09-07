@extends('layouts.app')

@section('titre', 'Modifier ' . $ticket->reference)

@section('styles')
    @include('tickets.partials.styles')
@endsection

@section('contenu')

    @php
        $peutGererTicket = auth()->user()->hasRole('admin');
    @endphp

    <div class="page-actions">
        <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-secondaire btn-sm">← Retour au ticket</a>
    </div>

    <div class="carte" style="max-width:780px;">
        <h2 class="section-titre">Modifier le ticket {{ $ticket->reference }}</h2>

        @if ($errors->any())
            <div class="alerte-form">
                <ul>
                    @foreach ($errors->all() as $erreur)
                        <li>{{ $erreur }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tickets.update', $ticket) }}">
            @csrf
            @method('PUT')

            <div class="champ" style="margin-bottom:1rem;">
                <label for="titre">Titre</label>
                <input type="text" id="titre" name="titre" value="{{ old('titre', $ticket->titre) }}" required>
            </div>

            <div class="champ" style="margin-bottom:1rem;">
                <label for="description">Description</label>
                <textarea id="description" name="description" required>{{ old('description', $ticket->description) }}</textarea>
            </div>

            <div class="form-grille" style="margin-bottom:1rem;">
                <div class="champ">
                    <label for="impact">Impact</label>
                    <select id="impact" name="impact">
                        @foreach (['Faible', 'Moyen', 'Élevé', 'Critique'] as $niveau)
                            <option value="{{ $niveau }}" @selected(old('impact', $ticket->impact) == $niveau)>{{ $niveau }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="champ">
                    <label for="urgence">Urgence</label>
                    <select id="urgence" name="urgence">
                        @foreach (['Faible', 'Moyen', 'Élevé', 'Critique'] as $niveau)
                            <option value="{{ $niveau }}" @selected(old('urgence', $ticket->urgence) == $niveau)>{{ $niveau }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($peutGererTicket)
                <div class="form-grille" style="margin-bottom:1rem;">
                    <div class="champ">
                        <label for="assigned_to">Technicien assigné</label>
                        <select id="assigned_to" name="assigned_to">
                            <option value="">— Non assigné —</option>
                            @foreach ($techniciens as $technicien)
                                <option value="{{ $technicien->id }}" @selected(old('assigned_to', $ticket->assigned_to) == $technicien->id)>
                                    {{ $technicien->name }}{{ $technicien->disponible ? '' : ' (indisponible)' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            <div class="form-actions">
                <button type="submit" class="btn btn-primaire">Enregistrer</button>
                <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-secondaire">Annuler</a>
            </div>
        </form>
    </div>

@endsection
