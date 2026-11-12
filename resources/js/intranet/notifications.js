// Suppression animée des notifications (une à une ou toutes d'un coup).
const reduire = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

async function requete(url, csrf) {
    const reponse = await fetch(url, {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!reponse.ok) throw new Error(String(reponse.status));

    return reponse.json();
}

// La ligne glisse vers la droite en s'estompant, puis la liste se referme.
async function sortir(ligne, delai = 0) {
    if (reduire()) {
        ligne.remove();
        return;
    }
    const hauteur = ligne.offsetHeight;
    ligne.style.overflow = 'hidden';
    await ligne.animate([
        { transform: 'translateX(0) rotate(0deg)', opacity: 1, filter: 'blur(0)' },
        { transform: 'translateX(-12px) rotate(0deg)', opacity: 1, offset: 0.15 },
        { transform: 'translateX(110%) rotate(2deg)', opacity: 0, filter: 'blur(2px)' },
    ], { duration: 420, delay: delai, easing: 'cubic-bezier(0.55, 0, 0.75, 0.3)', fill: 'forwards' }).finished;
    await ligne.animate([
        { height: `${hauteur}px` },
        { height: '0px', paddingTop: 0, paddingBottom: 0, borderWidth: 0 },
    ], { duration: 220, easing: 'ease-in-out', fill: 'forwards' }).finished;
    ligne.remove();
}

window.notificationsList = function notificationsList(config) {
    return {
        total: config.total,
        confirmation: false,
        enCours: false,
        vide: config.total === 0,
        erreur: '',

        get lignes() {
            return [...this.$refs.liste.querySelectorAll('[data-notification]')];
        },

        annoncer(resultat) {
            this.total = resultat.total;
            window.dispatchEvent(new CustomEvent('intranet-notifications-count', { detail: { unread: resultat.unread } }));
        },

        async supprimer(id) {
            const ligne = this.$refs.liste.querySelector(`[data-notification="${id}"]`);
            if (!ligne || ligne.dataset.sortie) return;
            ligne.dataset.sortie = '1';
            this.erreur = '';
            try {
                const resultat = await requete(config.destroyUrl.replace('__ID__', encodeURIComponent(id)), config.csrf);
                await sortir(ligne);
                this.annoncer(resultat);
                if (this.lignes.length === 0) {
                    // Plus rien sur cette page : la page suivante (s'il y en a) prend le relais.
                    resultat.total > 0 ? window.location.assign(config.indexUrl) : this.afficherVide();
                }
            } catch {
                delete ligne.dataset.sortie;
                this.erreur = 'La notification n’a pas pu être supprimée. Réessayez.';
            }
        },

        async toutSupprimer() {
            if (this.enCours) return;
            this.enCours = true;
            this.erreur = '';
            try {
                const resultat = await requete(config.destroyAllUrl, config.csrf);
                this.confirmation = false;
                // Cascade : chaque ligne part un peu après la précédente.
                const lignes = this.lignes;
                await Promise.all(lignes.map((ligne, i) => sortir(ligne, Math.min(i, 14) * 55)));
                this.annoncer(resultat);
                this.afficherVide();
            } catch (erreur) {
                console.error(erreur);
                this.erreur = 'Les notifications n’ont pas pu être supprimées. Réessayez.';
            } finally {
                this.enCours = false;
            }
        },

        afficherVide() {
            this.vide = true;
            this.$nextTick(() => {
                const cloche = this.$refs.cloche;
                if (cloche && !reduire()) {
                    cloche.animate([
                        { transform: 'scale(0.6) rotate(0deg)', opacity: 0 },
                        { transform: 'scale(1.1) rotate(-14deg)', opacity: 1, offset: 0.35 },
                        { transform: 'scale(1) rotate(10deg)', offset: 0.55 },
                        { transform: 'rotate(-6deg)', offset: 0.75 },
                        { transform: 'rotate(0deg)' },
                    ], { duration: 900, easing: 'ease-out' });
                }
            });
        },
    };
};
