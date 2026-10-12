/**
 * Module de recherche globale (Cmd+K)
 * Utilise Fuse.js pour la recherche fuzzy
 */

export function initGlobalSearch() {
    return {
        isOpen: false,
        query: '',
        results: [],
        selectedIndex: 0,
        
        // Configuration Fuse.js
        fuseOptions: {
            keys: [
                { name: 'title', weight: 0.4 },
                { name: 'description', weight: 0.3 },
                { name: 'tags', weight: 0.3 }
            ],
            threshold: 0.3,
            includeScore: true,
            includeMatches: true,
            ignoreDiacritics: true,
            minMatchCharLength: 2
        },
        
        // Données de recherche (à charger depuis l'API)
        searchData: [],
        fuse: null,
        
        init() {
            // Raccourci clavier Cmd+K / Ctrl+K
            document.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    this.toggle();
                }
                
                // Echap pour fermer
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });
            
            // Charger les données de recherche
            this.loadSearchData();
        },
        
        async loadSearchData() {
            try {
                const response = await fetch('/api/search/data');
                const data = await response.json();
                this.searchData = data;
                
                // Initialiser Fuse.js
                this.fuse = new window.Fuse(this.searchData, this.fuseOptions);
            } catch (error) {
                console.error('Erreur chargement données recherche:', error);
            }
        },
        
        toggle() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.$nextTick(() => {
                    this.$refs.searchInput?.focus();
                });
            }
        },
        
        open() {
            this.isOpen = true;
            this.$nextTick(() => {
                this.$refs.searchInput?.focus();
            });
        },
        
        close() {
            this.isOpen = false;
            this.query = '';
            this.results = [];
            this.selectedIndex = 0;
        },
        
        search() {
            if (!this.query || this.query.length < 2) {
                this.results = [];
                return;
            }
            
            if (!this.fuse) {
                console.warn('Fuse.js non initialisé');
                return;
            }
            
            // Recherche avec Fuse.js (20 résultats max pour garder le modal lisible)
            const fuseResults = this.fuse.search(this.query, { limit: 20 });
            this.results = fuseResults.map(result => ({
                ...result.item,
                matches: result.matches || [],
            }));
            this.selectedIndex = 0;
        },

        /**
         * Retourne le texte d'un champ avec les correspondances entourées de <mark>.
         * Le texte est échappé AVANT l'insertion des balises : le contenu venant de
         * la base (titres d'annonces, noms) ne peut donc pas injecter de HTML.
         */
        highlight(result, key) {
            const text = String(result[key] ?? '');
            const match = (result.matches || []).find(m => m.key === key);

            if (!match) {
                return this.escapeHtml(text);
            }

            let html = '';
            let cursor = 0;

            // Les indices Fuse sont inclusifs ; on les trie et on ignore les chevauchements.
            const plages = [...match.indices].sort((a, b) => a[0] - b[0]);

            for (const [debut, fin] of plages) {
                if (debut < cursor) continue;
                html += this.escapeHtml(text.slice(cursor, debut));
                html += '<mark class="bg-or-200 text-graphite-900 rounded px-0.5">'
                    + this.escapeHtml(text.slice(debut, fin + 1))
                    + '</mark>';
                cursor = fin + 1;
            }

            return html + this.escapeHtml(text.slice(cursor));
        },

        escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        },
        
        selectNext() {
            if (this.selectedIndex < this.results.length - 1) {
                this.selectedIndex++;
            }
        },
        
        selectPrevious() {
            if (this.selectedIndex > 0) {
                this.selectedIndex--;
            }
        },
        
        selectResult() {
            if (this.results[this.selectedIndex]) {
                this.navigateTo(this.results[this.selectedIndex]);
            }
        },
        
        navigateTo(result) {
            if (result.url) {
                window.location.href = result.url;
            }
        },
        
        getTypeLabel(type) {
            const labels = {
                'employee': 'Employé',
                'department': 'Département',
                'announcement': 'Annonce',
                'event': 'Événement',
                'document': 'Document',
                'form': 'Formulaire',
                'page': 'Page'
            };
            return labels[type] || type;
        },

        getResultIcon(type) {
            const icons = {
                'employee': '👤',
                'department': '🏢',
                'announcement': '📢',
                'event': '📅',
                'document': '📄',
                'form': '📝',
                'page': '📖'
            };
            return icons[type] || '🔍';
        }
    };
}
