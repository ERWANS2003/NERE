/**
 * Utilitaires pour l'intranet
 */

/**
 * Formater une date avec Day.js
 */
export function formatDate(date, format = 'DD/MM/YYYY') {
    return window.dayjs(date).format(format);
}

/**
 * Formater une date relative (il y a X jours)
 */
export function formatRelativeDate(date) {
    return window.dayjs(date).fromNow();
}

/**
 * Afficher une notification de succès
 */
export function showSuccessNotification(message, title = 'Succès') {
    window.toastr.success(message, title);
}

/**
 * Afficher une notification d'erreur
 */
export function showErrorNotification(message, title = 'Erreur') {
    window.toastr.error(message, title);
}

/**
 * Afficher une notification d'info
 */
export function showInfoNotification(message, title = 'Information') {
    window.toastr.info(message, title);
}

/**
 * Afficher une notification d'avertissement
 */
export function showWarningNotification(message, title = 'Attention') {
    window.toastr.warning(message, title);
}

/**
 * Initialiser un élément Sortable
 */
export function initSortable(elementId, options = {}) {
    const el = document.getElementById(elementId);
    if (!el) {
        console.error(`Élément #${elementId} introuvable`);
        return null;
    }
    
    const defaultOptions = {
        animation: 150,
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        handle: '.sortable-handle',
        ...options
    };
    
    return window.Sortable.create(el, defaultOptions);
}

/**
 * Composant Alpine.js pour Quick Links réorganisables
 */
export function quickLinksComponent() {
    return {
        sortable: null,
        
        init() {
            this.$nextTick(() => {
                this.sortable = initSortable('quick-links-list', {
                    onEnd: (evt) => this.handleReorder(evt)
                });
            });
        },
        
        async handleReorder(evt) {
            const items = Array.from(evt.to.children).map((el, index) => ({
                id: el.dataset.id,
                order: index
            }));
            
            try {
                const response = await fetch('/api/quick-links/reorder', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ items })
                });
                
                if (!response.ok) {
                    throw new Error('Erreur réorganisation');
                }
                
                showSuccessNotification('Liens réorganisés');
            } catch (error) {
                console.error('Erreur réorganisation:', error);
                showErrorNotification('Erreur lors de la réorganisation');
                // Recharger pour revenir à l'ordre original
                location.reload();
            }
        }
    };
}

/**
 * Composant Alpine.js pour les Kudos
 */
export function kudosComponent() {
    return {
        showModal: false,
        loading: false,
        form: {
            to_user_id: '',
            message: '',
            type: 'appreciation'
        },
        
        openModal(userId = null) {
            this.form.to_user_id = userId || '';
            this.showModal = true;
        },
        
        closeModal() {
            this.showModal = false;
            this.resetForm();
        },
        
        resetForm() {
            this.form = {
                to_user_id: '',
                message: '',
                type: 'appreciation'
            };
        },
        
        async submit() {
            if (!this.form.to_user_id || !this.form.message) {
                showWarningNotification('Veuillez remplir tous les champs');
                return;
            }
            
            this.loading = true;
            
            try {
                const response = await fetch('/api/kudos', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });
                
                if (!response.ok) {
                    throw new Error('Erreur envoi kudos');
                }
                
                showSuccessNotification('Kudos envoyé avec succès! 🎉');
                this.closeModal();
                
                // Recharger la liste des kudos
                window.Livewire.dispatch('kudos-sent');
            } catch (error) {
                console.error('Erreur envoi kudos:', error);
                showErrorNotification('Erreur lors de l\'envoi du kudos');
            } finally {
                this.loading = false;
            }
        }
    };
}

/**
 * Débounce pour les champs de recherche
 */
export function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Copier du texte dans le presse-papiers
 */
export async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showSuccessNotification('Copié dans le presse-papiers');
        return true;
    } catch (error) {
        console.error('Erreur copie presse-papiers:', error);
        showErrorNotification('Impossible de copier');
        return false;
    }
}

/**
 * Télécharger un fichier
 */
export function downloadFile(url, filename) {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
