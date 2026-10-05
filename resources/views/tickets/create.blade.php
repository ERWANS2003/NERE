@extends('layouts.portal')

@section('titre', ! empty($prefill['template_id']) ? 'Nouvelle Demande (depuis un modèle)' : 'Nouvelle Demande')

@section('styles')
    <style>
        .form-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 1.5rem;
            animation: fadeInUp 0.6s ease-out;
        }

        .form-header {
            margin-bottom: 2rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: #ffd700;
        }

        .form-header-content {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1.5rem;
        }

        .form-header h1 {
            font: 700 2rem var(--font-sans);
            color: #fff;
            margin: 0 0 0.5rem;
            letter-spacing: -0.02em;
        }

        .form-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
        }

        .auto-routing-badge {
            padding: 0.5rem 1rem;
            background: rgba(102, 187, 106, 0.15);
            border: 1px solid rgba(102, 187, 106, 0.3);
            color: #66bb6a;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .form-widget {
            background: rgba(0, 0, 0, 0.24);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .form-section {
            padding: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .section-number {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background: rgba(255, 215, 0, 0.15);
            border: 1px solid rgba(255, 215, 0, 0.3);
            color: #ffd700;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .section-title {
            font: 600 1.1rem var(--font-sans);
            color: #fff;
            margin: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        @media (min-width: 768px) {
            .form-grid.cols-2 {
                grid-template-columns: 1fr 1fr;
            }
        }

        .form-field label {
            display: block;
            font: 600 0.75rem var(--font-sans);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0.5rem;
        }

        .form-field input,
        .form-field select,
        .form-field textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: #fff;
            font-family: var(--font-sans);
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-field input:focus,
        .form-field select:focus,
        .form-field textarea:focus {
            border-color: rgba(255, 215, 0, 0.4);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .form-field input::placeholder,
        .form-field textarea::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .form-field textarea {
            min-height: 140px;
            resize: vertical;
            line-height: 1.6;
        }

        .field-help {
            display: block;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 0.5rem;
        }

        .info-box {
            padding: 1rem;
            background: rgba(66, 165, 245, 0.1);
            border-left: 4px solid #42a5f5;
            border-radius: 6px;
            color: rgba(66, 165, 245, 0.9);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .info-box svg {
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
        }

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem 2rem;
            background: rgba(0, 0, 0, 0.3);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .cancel-link {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .cancel-link:hover {
            color: #fff;
        }

        @media (max-width: 640px) {
            .form-header-content {
                flex-direction: column;
            }

            .form-section {
                padding: 1.5rem;
            }

            .form-actions {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
        }
    </style>
@endsection

@section('contenu')
<div class="form-container">
    <div class="form-header">
        
        <a href="{{ route('dashboard') }}" class="back-link">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Retour au portail
        </a>
        
        <div class="form-header-content">
            <div>
                <h1>Créer une demande</h1>
                <p>Remplissez le formulaire selon votre besoin. Le routage vers la bonne équipe est automatique.</p>
            </div>
            <span class="auto-routing-badge">
                Routage automatique
            </span>
        </div>
    </div>

    <!-- Error Messages -->
    @if ($errors->any())
        <div class="alert-mining" style="background: rgba(211, 47, 47, 0.12); border-left-color: #d32f2f; color: #ff6b6b; margin-bottom: 1.5rem;">
            <svg style="width: 1.25rem; height: 1.25rem; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div style="flex: 1;">
                <strong style="display: block; margin-bottom: 0.5rem;">Erreurs de validation</strong>
                <ul style="list-style: disc; padding-left: 1.25rem; margin: 0;">
                    @foreach ($errors->all() as $erreur)
                        <li>{{ $erreur }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Main Form -->
    <div class="form-widget" x-data="ticketForm()">
        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
            @csrf

            <!-- Section 1: Type et Service -->
            <div class="form-section">
                <div class="section-header">
                    <span class="section-number">1</span>
                    <h2 class="section-title">Quel est votre besoin?</h2>
                </div>

                <div class="form-grid cols-2">
                    <div class="form-field">
                        <label for="type">Nature de la demande *</label>
                        <select id="type" name="type" required>
                            <option value="demande" @selected(old('type', 'demande') === 'demande')>Demande de service</option>
                            <option value="incident" @selected(old('type', request('type')) === 'incident')>Signaler un incident</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="departement_id">Service concerné *</label>
                        <select id="departement_id" name="departement_id" required @change="updateDepartement($event.target.value)">
                            <option value="">— Choisir un service —</option>
                            @foreach ($departements as $departement)
                                <option value="{{ $departement->id }}" @selected(old('departement_id', $selectedDepartment) == $departement->id)>
                                    {{ $departement->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2: Détails -->
            <div class="form-section">
                <div class="section-header">
                    <span class="section-number">2</span>
                    <h2 class="section-title">Décrivez votre demande</h2>
                </div>

                <div class="form-grid">
                    <div class="form-field">
                        <label for="titre">Titre de la demande *</label>
                        <input type="text" id="titre" name="titre" value="{{ old('titre', $prefill['titre'] ?? '') }}" required
                               placeholder="Ex: Demande d'accès au système SAP">
                    </div>

                    <div class="form-field">
                        <label for="description">Description détaillée *</label>
                        <textarea id="description" name="description" required 
                                  placeholder="Décrivez votre demande en détail: contexte, besoin précis, informations importantes...">{{ old('description', $prefill['description'] ?? '') }}</textarea>
                        <span class="field-help">Plus vous donnez de détails, plus rapide sera le traitement</span>
                    </div>
                </div>

                <div class="form-grid cols-2" style="margin-top: 1.5rem;">
                    <div class="form-field">
                        <label for="ticket_category_id">Catégorie *</label>
                        <select id="ticket_category_id" name="ticket_category_id" required>
                            <option value="">— Choisir une catégorie —</option>
                            @foreach ($categories as $categorie)
                                <option value="{{ $categorie->id }}" 
                                        data-departement="{{ $categorie->team?->departement_id }}" 
                                        @selected(old('ticket_category_id', $prefill['ticket_category_id'] ?? null) == $categorie->id)>
                                    {{ $categorie->nom }}
                                </option>
                            @endforeach
                        </select>
                        <span id="category-empty-hint" class="field-help hidden text-amber-500">
                            Aucune catégorie n'est rattachée à ce service. Contactez l'administrateur.
                        </span>
                    </div>

                    <div class="form-field">
                        <label for="site_id">Site / Localisation</label>
                        <select id="site_id" name="site_id">
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

            <!-- Section 3: Priorité -->
            <div class="form-section">
                <div class="section-header">
                    <span class="section-number">3</span>
                    <h2 class="section-title">Évaluation de l'urgence</h2>
                </div>

                <div class="info-box">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    La priorité sera calculée automatiquement selon l'impact et l'urgence
                </div>

                <div class="form-grid cols-2">
                    <div class="form-field">
                        <label for="impact">Impact sur vos activités *</label>
                        <select id="impact" name="impact" required>
                            @foreach (['Faible', 'Moyen', 'Élevé', 'Critique'] as $niveau)
                                <option value="{{ $niveau }}" @selected(old('impact', 'Moyen') == $niveau)>{{ $niveau }}</option>
                            @endforeach
                        </select>
                        <span class="field-help">Combien de personnes / processus sont affectés?</span>
                    </div>

                    <div class="form-field">
                        <label for="urgence">Urgence du traitement *</label>
                        <select id="urgence" name="urgence" required>
                            @foreach (['Faible', 'Moyen', 'Élevé', 'Critique'] as $niveau)
                                <option value="{{ $niveau }}" @selected(old('urgence', 'Moyen') == $niveau)>{{ $niveau }}</option>
                            @endforeach
                        </select>
                        <span class="field-help">Dans quel délai faut-il traiter?</span>
                    </div>
                </div>
            </div>

            <!-- Section 4: Pièces jointes -->
            <div class="form-section">
                <div class="section-header">
                    <span class="section-number">4</span>
                    <h2 class="section-title">Documents (optionnel)</h2>
                </div>

                <div class="form-field">
                    <label for="pieces_jointes">Joindre des fichiers</label>
                    <input type="file" id="pieces_jointes" name="pieces_jointes[]" multiple 
                           accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx"
                           style="cursor: pointer;">
                    <span class="field-help">Photos, captures d'écran, documents (max 10 Mo par fichier)</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <a href="{{ route('dashboard') }}" class="cancel-link">
                    Annuler
                </a>
                <button type="submit" class="btn-mining-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Envoyer la demande
                </button>
            </div>

        </form>
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

            // A service may have no category at all: surface that instead of
            // leaving the user with a hidden/empty choice.
            const visible = [...options].filter(o => o.value !== '' && o.style.display !== 'none');
            const hint = document.getElementById('category-empty-hint');
            if (hint) {
                hint.classList.toggle('hidden', !deptId || visible.length > 0);
            }
        }
    };
}
</script>
@endpush

@endsection
