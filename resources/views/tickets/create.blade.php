@extends('layouts.app')

@section('titre', 'Nouveau ticket')

@section('styles')
    @include('tickets.partials.styles')
@endsection

@section('contenu')

    <div class="page-actions">
        <a href="{{ route('tickets.index') }}" class="btn btn-secondaire btn-sm">← Retour à la liste</a>
    </div>

    <div class="carte" style="max-width:900px;">
        <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <p class="sur-titre">PORTAIL DES SERVICES</p>
                <h2 class="section-titre" style="margin-bottom:.35rem;">Créer une demande</h2>
                <p style="color:var(--texte-att);margin:0;font-size:.88rem;">Choisissez le service concerné : le bon groupe sera prévenu automatiquement.</p>
            </div>
            <span class="badge" style="background:rgba(63,166,107,.12);color:#7fd4a0;border:1px solid rgba(63,166,107,.25);">Routage automatique</span>
        </div>

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
                <label for="type">Nature de la demande *</label>
                <select id="type" name="type" required>
                    <option value="demande" @selected(old('type', 'demande') === 'demande')>Demande de service</option>
                    <option value="incident" @selected(old('type') === 'incident')>Incident à signaler</option>
                </select>
            </div>

            <div class="champ" style="margin-bottom:1rem;">
                <label for="titre">Titre *</label>
                <input type="text" id="titre" name="titre" value="{{ old('titre') }}" required placeholder="Ex. : Panne réseau bureau production">
            </div>

            <fieldset id="hse-fields" class="service-fields" hidden>
                <legend>Informations HSE</legend>
                <div class="form-grille">
                    <div class="champ"><label for="service_date">Date de l'événement</label><input id="service_date" name="service_data[date]" type="date" value="{{ old('service_data.date') }}"></div>
                    <div class="champ"><label for="service_heure">Heure</label><input id="service_heure" name="service_data[heure]" type="time" value="{{ old('service_data.heure') }}"></div>
                    <div class="champ"><label for="service_lieu">Lieu</label><input id="service_lieu" name="service_data[lieu]" value="{{ old('service_data.lieu') }}" placeholder="Zone, atelier, site..."></div>
                    <div class="champ"><label for="service_gravite">Gravité</label><select id="service_gravite" name="service_data[gravite]"><option value="">À évaluer</option><option>Faible</option><option>Moyenne</option><option>Grave</option><option>Critique</option></select></div>
                </div>
                <div class="champ"><label for="service_personnes">Personnes impliquées</label><textarea id="service_personnes" name="service_data[personnes]" rows="3">{{ old('service_data.personnes') }}</textarea></div>
            </fieldset>

            <fieldset id="service-fields" class="service-fields" hidden>
                <legend id="service-fields-title">Informations complémentaires</legend>
                <div class="form-grille">
                    <div class="champ"><label id="service-label-asset" for="service_asset">Équipement ou référence</label><input id="service_asset" name="service_data[asset]" value="{{ old('service_data.asset') }}"></div>
                    <div class="champ"><label id="service-label-date" for="service_date_service">Date souhaitée</label><input id="service_date_service" name="service_data[date_souhaitee]" type="date" value="{{ old('service_data.date_souhaitee') }}"></div>
                    <div class="champ"><label id="service-label-reference" for="service_reference">Référence interne</label><input id="service_reference" name="service_data[reference]" value="{{ old('service_data.reference') }}"></div>
                    <div class="champ"><label id="service-label-montant" for="service_montant">Montant estimé</label><input id="service_montant" name="service_data[montant]" type="number" min="0" step="0.01" value="{{ old('service_data.montant') }}"></div>
                </div>
                <div class="champ"><label id="service-label-details" for="service_details">Détails de la demande</label><textarea id="service_details" name="service_data[details]" rows="3">{{ old('service_data.details') }}</textarea></div>
            </fieldset>

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
                    <label for="departement_id">Service concerné *</label>
                    <select id="departement_id" name="departement_id" required>
                        <option value="">— Choisir un service —</option>
                        @foreach ($departements as $departement)
                            <option value="{{ $departement->id }}" @selected(old('departement_id', request('departement', auth()->user()->departement_id)) == $departement->id)>{{ $departement->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="champ">
                    <label for="ticket_category_id">Catégorie *</label>
                    <select id="ticket_category_id" name="ticket_category_id" required>
                        <option value="">— Choisir —</option>
                        @foreach ($categories as $categorie)
                            <option value="{{ $categorie->id }}" data-departement="{{ $categorie->team?->departement_id }}" @selected(old('ticket_category_id') == $categorie->id)>{{ $categorie->nom }}</option>
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
        const departementSelect = document.getElementById('departement_id');
        const typeSelect = document.getElementById('type');
        const hseFields = document.getElementById('hse-fields');
        const suggestionsBox = document.getElementById('suggestions-kb');
        const suggestionsListe = document.getElementById('suggestions-liste');
        let debounceTimer;

        function filtrerCategories() {
            const departementId = departementSelect.value;
            Array.from(categorieSelect.options).forEach(option => {
                option.hidden = option.value !== '' && option.dataset.departement !== departementId;
            });
            if (categorieSelect.selectedOptions[0]?.hidden) categorieSelect.value = '';
        }

        function afficherChampsService() {
            const service = departementSelect.options[departementSelect.selectedIndex]?.text.trim().toLowerCase();
            hseFields.hidden = service !== 'hse';
            const serviceFields = document.getElementById('service-fields');
            serviceFields.hidden = !service || service === 'hse';
            const titles = {
                it: 'Informations du support IT', maintenance: 'Informations de l’équipement', finance: 'Informations financières',
                logistique: 'Informations logistiques', production: 'Informations de production', géologie: 'Informations géologiques'
            };
            document.getElementById('service-fields-title').textContent = titles[service] || 'Informations complémentaires';
            document.getElementById('service-label-asset').textContent = ['maintenance', 'production'].includes(service) ? 'Équipement concerné' : 'Équipement ou référence';
            document.getElementById('service-label-date').textContent = service === 'logistique' ? 'Date du transport' : 'Date souhaitée';
            document.getElementById('service-label-montant').textContent = service === 'finance' ? 'Montant concerné' : 'Montant estimé';
        }

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
        departementSelect.addEventListener('change', filtrerCategories);
        departementSelect.addEventListener('change', afficherChampsService);
        typeSelect.addEventListener('change', afficherChampsService);
        filtrerCategories();
        afficherChampsService();
    </script>
@endpush
