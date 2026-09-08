@extends('layouts.app-new')

@section('title', 'Nouvelle Demande')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-100 py-12 px-6">
    <div class="max-w-5xl mx-auto">
        
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-4 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Retour au portail
            </a>
            
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Créer une demande</h1>
                    <p class="text-gray-600">Remplissez le formulaire selon votre besoin. Le routage vers la bonne équipe est automatique.</p>
                </div>
                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                    Routage automatique
                </span>
            </div>
        </div>

        <!-- Error Messages -->
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h3 class="font-medium text-red-800 mb-1">Erreurs de validation</h3>
                        <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                            @foreach ($errors->all() as $erreur)
                                <li>{{ $erreur }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200" x-data="ticketForm()">
            <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="p-8 space-y-6">
                    
                    <!-- Section 1: Type et Service -->
                    <div class="space-y-6">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-sm font-bold">1</span>
                            Quel est votre besoin?
                        </h2>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nature de la demande *
                                </label>
                                <select id="type" name="type" required 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                    <option value="demande" @selected(old('type', 'demande') === 'demande')>Demande de service</option>
                                    <option value="incident" @selected(old('type') === 'incident')>Signaler un incident</option>
                                </select>
                            </div>

                            <div>
                                <label for="departement_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Service concerné *
                                </label>
                                <select id="departement_id" name="departement_id" required 
                                        @change="updateDepartement($event.target.value)"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                    <option value="">— Choisir un service —</option>
                                    @foreach ($departements as $departement)
                                        <option value="{{ $departement->id }}" @selected(old('departement_id', request('department')) == $departement->nom)>
                                            {{ $departement->nom }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Détails -->
                    <div class="space-y-6 pt-6 border-t border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-sm font-bold">2</span>
                            Décrivez votre demande
                        </h2>

                        <div>
                            <label for="titre" class="block text-sm font-medium text-gray-700 mb-2">
                                Titre de la demande *
                            </label>
                            <input type="text" id="titre" name="titre" value="{{ old('titre') }}" required 
                                   placeholder="Ex: Demande d'accès au système SAP"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Description détaillée *
                            </label>
                            <textarea id="description" name="description" required rows="6"
                                      placeholder="Décrivez votre demande en détail: contexte, besoin précis, informations importantes..."
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">{{ old('description') }}</textarea>
                            <p class="text-sm text-gray-500 mt-1">Plus vous donnez de détails, plus rapide sera le traitement</p>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label for="ticket_category_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Catégorie *
                                </label>
                                <select id="ticket_category_id" name="ticket_category_id" required 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                    <option value="">— Choisir une catégorie —</option>
                                    @foreach ($categories as $categorie)
                                        <option value="{{ $categorie->id }}" 
                                                data-departement="{{ $categorie->team?->departement_id }}" 
                                                @selected(old('ticket_category_id') == $categorie->id)>
                                            {{ $categorie->nom }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="site_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Site / Localisation
                                </label>
                                <select id="site_id" name="site_id" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                    <option value="">— Non spécifié —</option>
                                    @foreach ($sites as $site)
                                        <option value="{{ $site->id }}" @selected(old('site_id', auth()->user()->site_id) == $site->id)>
                                            {{ $site->nom }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Service Fields (HSE, Finance, etc.) -->
                    <div x-show="showHSEFields" x-transition class="space-y-6 pt-6 border-t border-gray-200">
                        <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            Informations HSE (Sécurité)
                        </h3>
                        
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date de l'événement</label>
                                <input type="date" name="service_data[date]" value="{{ old('service_data.date') }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Heure</label>
                                <input type="time" name="service_data[heure]" value="{{ old('service_data.heure') }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Lieu précis</label>
                                <input type="text" name="service_data[lieu]" value="{{ old('service_data.lieu') }}"
                                       placeholder="Zone, atelier, poste..."
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Gravité estimée</label>
                                <select name="service_data[gravite]" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                    <option value="">À évaluer</option>
                                    <option>Faible</option>
                                    <option>Moyenne</option>
                                    <option>Grave</option>
                                    <option>Critique</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Personnes impliquées</label>
                            <textarea name="service_data[personnes]" rows="3"
                                      placeholder="Noms, témoins, personnes blessées..."
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">{{ old('service_data.personnes') }}</textarea>
                        </div>
                    </div>

                    <div x-show="showServiceFields" x-transition class="space-y-6 pt-6 border-t border-gray-200">
                        <h3 class="font-semibold text-gray-900">Informations complémentaires</h3>
                        
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Équipement / Référence</label>
                                <input type="text" name="service_data[asset]" value="{{ old('service_data.asset') }}"
                                       placeholder="N° série, référence..."
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date souhaitée</label>
                                <input type="date" name="service_data[date_souhaitee]" value="{{ old('service_data.date_souhaitee') }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Priorité -->
                    <div class="space-y-6 pt-6 border-t border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-sm font-bold">3</span>
                            Évaluation de l'urgence
                        </h2>

                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                            <p class="text-sm text-blue-800">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                La priorité sera calculée automatiquement selon l'impact et l'urgence
                            </p>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label for="impact" class="block text-sm font-medium text-gray-700 mb-2">
                                    Impact sur vos activités *
                                </label>
                                <select id="impact" name="impact" required 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                    @foreach (['Faible', 'Moyen', 'Élevé', 'Critique'] as $niveau)
                                        <option value="{{ $niveau }}" @selected(old('impact', 'Moyen') == $niveau)>{{ $niveau }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Combien de personnes / processus sont affectés?</p>
                            </div>

                            <div>
                                <label for="urgence" class="block text-sm font-medium text-gray-700 mb-2">
                                    Urgence du traitement *
                                </label>
                                <select id="urgence" name="urgence" required 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                    @foreach (['Faible', 'Moyen', 'Élevé', 'Critique'] as $niveau)
                                        <option value="{{ $niveau }}" @selected(old('urgence', 'Moyen') == $niveau)>{{ $niveau }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Dans quel délai faut-il traiter?</p>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Pièces jointes -->
                    <div class="space-y-4 pt-6 border-t border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-sm font-bold">4</span>
                            Documents (optionnel)
                        </h2>

                        <div>
                            <label for="pieces_jointes" class="block text-sm font-medium text-gray-700 mb-2">
                                Joindre des fichiers
                            </label>
                            <input type="file" id="pieces_jointes" name="pieces_jointes[]" multiple 
                                   accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            <p class="text-sm text-gray-500 mt-1">Photos, captures d'écran, documents (max 10 Mo par fichier)</p>
                        </div>
                    </div>

                </div>

                <!-- Actions -->
                <div class="bg-gray-50 px-8 py-6 flex items-center justify-between border-t border-gray-200 rounded-b-xl">
                    <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-900 font-medium transition">
                        Annuler
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-8 py-3 rounded-lg font-semibold transition-all shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Envoyer la demande
                    </button>
                </div>

            </form>
        </div>

    </div>
</div>

@push('scripts')
<script>
function ticketForm() {
    return {
        selectedDepartement: '',
        showHSEFields: false,
        showServiceFields: false,

        init() {
            // Check if department is pre-selected
            const deptSelect = document.getElementById('departement_id');
            if (deptSelect && deptSelect.value) {
                this.updateDepartement(deptSelect.value);
            }

            // Filter categories based on department
            this.filterCategories();
            
            // Listen for department changes
            deptSelect?.addEventListener('change', () => {
                this.filterCategories();
            });
        },

        updateDepartement(deptId) {
            this.selectedDepartement = deptId;
            
            // Get department name
            const deptSelect = document.getElementById('departement_id');
            const deptName = deptSelect.options[deptSelect.selectedIndex]?.text || '';
            
            // Show/hide specific fields
            this.showHSEFields = deptName.includes('HSE');
            this.showServiceFields = ['Maintenance', 'IT', 'Informatique', 'Production'].some(d => deptName.includes(d));
            
            this.filterCategories();
        },

        filterCategories() {
            const deptId = document.getElementById('departement_id').value;
            const categorySelect = document.getElementById('ticket_category_id');
            const options = categorySelect.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value === '') {
                    option.style.display = '';
                    return;
                }
                
                const optionDept = option.getAttribute('data-departement');
                option.style.display = (!deptId || optionDept === deptId) ? '' : 'none';
            });
            
            // Reset category if not valid
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            if (selectedOption && selectedOption.style.display === 'none') {
                categorySelect.value = '';
            }
        }
    };
}
</script>
@endpush

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
