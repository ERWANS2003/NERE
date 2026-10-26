/**
 * Tutoriel interactif de l'intranet.
 *
 * Le projecteur est un calque fixe posé au-dessus de toute la page et percé à
 * l'emplacement de la cible (grande ombre portée autour d'un rectangle
 * transparent). Il ne dépend donc ni du z-index ni des contextes d'empilement
 * de l'élément présenté : l'en-tête et la barre latérale en verre dépoli en
 * créent chacun un, ce qui rendait l'ancienne mise en valeur invisible.
 */
const MARGE = 8;
const LARGEUR_BULLE = 360;
const ECART = 14;

function attendre(ms) {
    return new Promise((resolve) => window.setTimeout(resolve, ms));
}

function lireProgression(cle) {
    try {
        const valeur = window.localStorage.getItem(cle);
        return valeur === null ? null : Number.parseInt(valeur, 10);
    } catch {
        return null;
    }
}

function ecrireProgression(cle, valeur) {
    try {
        if (valeur === null) {
            window.localStorage.removeItem(cle);
        } else {
            window.localStorage.setItem(cle, String(valeur));
        }
    } catch {
        // Stockage indisponible (navigation privée) : la reprise est simplement perdue.
    }
}

window.intranetTutorial = function intranetTutorial(config) {
    return {
        open: false,
        index: 0,
        etapes: [],
        envoi: false,
        jeton: 0,
        mobile: false,
        reduireAnimations: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        projecteur: { top: '50%', left: '50%', width: '0px', height: '0px' },
        projecteurVisible: false,
        bulle: { top: 'auto', bottom: '12px', left: '50%', transform: 'translateX(-50%)' },
        fleche: { side: null, offset: '50%' },

        get etape() {
            return this.etapes[this.index] ?? null;
        },

        get progression() {
            return this.etapes.length > 1 ? Math.round((this.index / (this.etapes.length - 1)) * 100) : 100;
        },

        init() {
            const relancer = () => window.requestAnimationFrame(() => this.mesurer());
            window.addEventListener('resize', relancer, { passive: true });
            document.addEventListener('scroll', relancer, { passive: true, capture: true });
            // La fin d'une animation (tiroir mobile, menu) peut déplacer la cible.
            document.addEventListener('transitionend', (event) => {
                if (this.open && !event.target.closest?.('.intranet-tour-card, .intranet-tour-spotlight')) relancer();
            }, true);
            window.addEventListener('open-intranet-tutorial', () => this.demarrer(0));

            if (config.autoOpen) {
                // Laisse la page finir son rendu (polices, images) avant de mesurer.
                window.setTimeout(() => this.demarrer(lireProgression(config.storageKey) ?? 0), 400);
            }
        },

        demarrer(depart) {
            // Une étape dont la cible n'existe pas sur cette page est ignorée.
            this.etapes = config.steps.filter((etape) => !etape.target || document.querySelector(etape.target));
            this.open = true;
            this.aller(Math.min(Math.max(depart, 0), this.etapes.length - 1));
        },

        async aller(nouvelIndex) {
            // Un clic rapide sur « Suivant » invalide l'étape encore en préparation.
            const jeton = ++this.jeton;
            this.index = nouvelIndex;
            ecrireProgression(config.storageKey, nouvelIndex);
            this.mobile = window.innerWidth < 640;

            const etape = this.etape;
            const dansSidebar = etape?.zone === 'sidebar';
            window.dispatchEvent(new CustomEvent(dansSidebar ? 'intranet-tour-open-sidebar' : 'intranet-tour-close-sidebar'));
            window.dispatchEvent(new CustomEvent(etape?.opens === 'profile' ? 'open-intranet-profile' : 'close-intranet-profile'));

            // La barre latérale mobile et le menu du profil s'animent avant d'être mesurables.
            await attendre(this.reduireAnimations ? 20 : 260);
            if (jeton !== this.jeton) return;

            // Un menu qui s'ouvre avec une transition peut mettre plus longtemps à prendre sa taille.
            let cible = this.cible();
            for (let essai = 0; !cible && etape?.target && essai < 8; essai++) {
                await attendre(100);
                if (jeton !== this.jeton) return;
                cible = this.cible();
            }

            if (cible) {
                cible.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: this.reduireAnimations ? 'auto' : 'smooth' });
                await attendre(this.reduireAnimations ? 20 : 220);
                if (jeton !== this.jeton) return;
            }

            this.mesurer();
            this.$nextTick(() => this.$refs.titre?.focus({ preventScroll: true }));

            // Rattrape les décalages tardifs (images, polices, fin d'animation).
            for (const delai of [250, 700]) {
                window.setTimeout(() => jeton === this.jeton && this.mesurer(), delai);
            }
        },

        cible() {
            const selecteur = this.etape?.target;
            const element = selecteur ? document.querySelector(selecteur) : null;
            if (!element) return null;

            // Une cible encore hors écran (barre latérale en train de glisser) n'est pas prête.
            const rect = element.getBoundingClientRect();
            const visible = rect.width > 0 && rect.height > 0
                && rect.right > 0 && rect.left < window.innerWidth
                && rect.bottom > 0 && rect.top < window.innerHeight + rect.height;

            return visible ? element : null;
        },

        mesurer() {
            if (!this.open) return;

            this.mobile = window.innerWidth < 640;
            const cible = this.cible();

            if (!cible) {
                this.projecteurVisible = false;
                this.projecteur = { top: '50%', left: '50%', width: '0px', height: '0px' };
                this.fleche = { side: null, offset: '50%' };
                this.bulle = this.mobile
                    ? { top: 'auto', bottom: '12px', left: '12px', transform: 'none' }
                    : { top: '50%', bottom: 'auto', left: '50%', transform: 'translate(-50%, -50%)' };
                return;
            }

            const r = cible.getBoundingClientRect();
            const zone = {
                top: Math.max(4, r.top - MARGE),
                left: Math.max(4, r.left - MARGE),
                right: Math.min(window.innerWidth - 4, r.right + MARGE),
                bottom: Math.min(window.innerHeight - 4, r.bottom + MARGE),
            };

            this.projecteurVisible = true;
            this.projecteur = {
                top: `${zone.top}px`,
                left: `${zone.left}px`,
                width: `${zone.right - zone.left}px`,
                height: `${zone.bottom - zone.top}px`,
            };

            if (this.mobile) {
                // Sur téléphone, la bulle devient un panneau en bas ou en haut, à l'opposé de la cible.
                const cibleEnBas = zone.top > window.innerHeight / 2;
                this.bulle = cibleEnBas
                    ? { top: '12px', bottom: 'auto', left: '12px', transform: 'none' }
                    : { top: 'auto', bottom: '12px', left: '12px', transform: 'none' };
                this.fleche = { side: null, offset: '50%' };
                return;
            }

            const largeur = Math.min(LARGEUR_BULLE, window.innerWidth - 24);
            const hauteur = this.$refs.bulle?.offsetHeight || 260;
            const place = {
                droite: window.innerWidth - zone.right,
                bas: window.innerHeight - zone.bottom,
                haut: zone.top,
            };
            const centreX = (zone.left + zone.right) / 2;
            const centreY = (zone.top + zone.bottom) / 2;
            const borner = (valeur, min, max) => Math.min(Math.max(valeur, min), max);

            if (place.droite >= largeur + ECART + 12 && (this.etape?.zone === 'sidebar' || place.bas < hauteur + ECART)) {
                const top = borner(centreY - hauteur / 2, 12, window.innerHeight - hauteur - 12);
                this.bulle = { top: `${top}px`, bottom: 'auto', left: `${zone.right + ECART}px`, transform: 'none' };
                this.fleche = { side: 'left', offset: `${borner(centreY - top, 20, hauteur - 20)}px` };
            } else if (place.bas >= hauteur + ECART || place.bas >= place.haut) {
                const left = borner(centreX - largeur / 2, 12, window.innerWidth - largeur - 12);
                this.bulle = { top: `${zone.bottom + ECART}px`, bottom: 'auto', left: `${left}px`, transform: 'none' };
                this.fleche = { side: 'top', offset: `${borner(centreX - left, 20, largeur - 20)}px` };
            } else {
                const left = borner(centreX - largeur / 2, 12, window.innerWidth - largeur - 12);
                this.bulle = { top: `${Math.max(12, zone.top - ECART - hauteur)}px`, bottom: 'auto', left: `${left}px`, transform: 'none' };
                this.fleche = { side: 'bottom', offset: `${borner(centreX - left, 20, largeur - 20)}px` };
            }
        },

        suivant() {
            if (this.index < this.etapes.length - 1) this.aller(this.index + 1);
        },

        precedent() {
            if (this.index > 0) this.aller(this.index - 1);
        },

        // Ferme en gardant la progression : le guide reprendra à cette étape.
        plusTard() {
            this.open = false;
            window.dispatchEvent(new CustomEvent('intranet-tour-close-sidebar'));
            window.dispatchEvent(new CustomEvent('close-intranet-profile'));
        },

        async terminer() {
            if (this.envoi) return;
            this.envoi = true;

            try {
                const reponse = await fetch(config.completeUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': config.csrf,
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!reponse.ok) throw new Error(String(reponse.status));

                ecrireProgression(config.storageKey, null);
                config.autoOpen = false;
                this.plusTard();
                window.toastr?.success('Guide terminé. Retrouvez-le à tout moment dans le menu de votre profil.');
            } catch {
                // Repli sans JavaScript réseau : soumission classique du formulaire.
                this.$refs.formulaireFin?.submit();
            } finally {
                this.envoi = false;
            }
        },
    };
};
