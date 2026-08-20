@extends('layouts.app')

@section('titre', 'Nouveau ticket')

@section('styles')
    @include('tickets.partials.styles')
@endsection

@section('contenu')

    <div class="page-actions">
        <a href="{{ route('tickets.index') }}" class="btn btn-secondaire btn-sm">← Retour à la liste</a>
    </div>

    <div class="carte" style="max-width:780px;">
        <h2 class="section-titre">Signaler un incident ou une demande</h2>

        @if ($errors->any())
            <div class="alerte-form">
                <ul>
                    @foreach ($errors->all() as $erreur)
                        <li>{{ $erreur }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="champ" style="margin-bottom:1rem;">
                <label for="titre">Titre *</label>
                <input type="text" id="titre" name="titre" value="{{ old('titre') }}" required placeholder="Ex. : Panne réseau bureau production">
            </div>

            <div class="champ" style="margin-bottom:1rem;">
                <label for="description">Description *</label>
                <textarea id="description" name="description" required placeholder="Décrivez le problème en détail…">{{ old('description') }}</textarea>
            </div>

            <div id="suggestions-kb" class="suggestions">
                <h4>Articles pertinents — peut-être une solution existe déjà ?</h4>
                <ul id="suggestions-liste"></ul>
            </div>

            <div class="form-grille" style="margin-bottom:1rem;">
                <div class="champ">
                    <label for="ticket_category_id">Catégorie *</label>
                    <select id="ticket_category_id" name="ticket_category_id" required>
                        <option value="">— Choisir —</option>
                        @foreach ($categories as $categorie)
                            <option value="{{ $categorie->id }}" @selected(old('ticket_category_id') == $categorie->id)>{{ $categorie->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="champ">
                    <label for="site_id">Site</label>
                    <select id="site_id" name="site_id">
                        <option value="">— Choisir —</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}" @selected(old('site_id', auth()->user()->site_id) == $site->id)>{{ $site->nom }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-grille" style="margin-bottom:1rem;">
                <div class="champ">
                    <label for="impact">Impact *</label>
                    <select id="impact" name="impact" required>
                        @foreach (['Faible', 'Moyen', 'Élevé', 'Critique'] as $niveau)
                            <option value="{{ $niveau }}" @selected(old('impact') == $niveau)>{{ $niveau }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="champ">
                    <label for="urgence">Urgence *</label>
                    <select id="urgence" name="urgence" required>
                        @foreach (['Faible', 'Moyen', 'Élevé', 'Critique'] as $niveau)
                            <option value="{{ $niveau }}" @selected(old('urgence') == $niveau)>{{ $niveau }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <p style="font-size:0.78rem; color:var(--texte-att); margin:0 0 1rem;">
                La priorité sera calculée automatiquement à partir de l'impact et de l'urgence.
            </p>

            <div class="champ" style="margin-bottom:1rem;">
                <label for="pieces_jointes">Pièces jointes</label>
                <input type="file" id="pieces_jointes" name="pieces_jointes[]" multiple accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx">
                <small style="color:var(--texte-att); font-size:0.75rem;">Max. 10 Mo par fichier</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primaire">Créer le ticket</button>
                <a href="{{ route('tickets.index') }}" class="btn btn-secondaire">Annuler</a>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
    <script>
        const titreInput = document.getElementById('titre');
        const descInput = document.getElementById('description');
        const categorieSelect = document.getElementById('ticket_category_id');
        const suggestionsBox = document.getElementById('suggestions-kb');
        const suggestionsListe = document.getElementById('suggestions-liste');
        let debounceTimer;

        function chercherSuggestions() {
            const terme = (titreInput.value + ' ' + descInput.value).trim();
            if (terme.length < 3) {
                suggestionsBox.classList.remove('visible');
                return;
            }

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const params = new URLSearchParams({ terme });
                if (categorieSelect.value) params.set('categorie_id', categorieSelect.value);

                fetch(`{{ route('knowledge.suggest') }}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(r => r.json())
                    .then(articles => {
                        suggestionsListe.innerHTML = '';
                        if (!articles.length) {
                            suggestionsBox.classList.remove('visible');
                            return;
                        }
                        articles.forEach(a => {
                            const li = document.createElement('li');
                            const link = document.createElement('a');
                            link.href = `/base-connaissances/${a.id}`;
                            link.target = '_blank';
                            link.textContent = a.titre;
                            li.appendChild(link);
                            suggestionsListe.appendChild(li);
                        });
                        suggestionsBox.classList.add('visible');
                    })
                    .catch(() => suggestionsBox.classList.remove('visible'));
            }, 400);
        }

        titreInput.addEventListener('input', chercherSuggestions);
        descInput.addEventListener('input', chercherSuggestions);
        categorieSelect.addEventListener('change', chercherSuggestions);
    </script>
@endpush
