/**
 * Guides interactifs de l'intranet.
 *
 * Un guide est une suite d'étapes rattachées chacune à une page (nom de route).
 * La progression est gardée dans le navigateur : quand l'utilisateur clique sur
 * l'élément demandé et change de page, le guide reprend à l'étape suivante.
 *
 * Le projecteur est un calque fixe percé à l'emplacement de la cible (grande
 * ombre portée autour d'un rectangle transparent) : il ne dépend ni du z-index
 * ni des contextes d'empilement de l'élément présenté.
 */
const MARGE = 8;
const LARGEUR_BULLE = 380;
const ECART = 14;

function attendre(ms) {
    return new Promise((resolve) => window.setTimeout(resolve, ms));
}

function lire(cle) {
    try {
        const valeur = window.localStorage.getItem(cle);
        return valeur === null ? null : JSON.parse(valeur);
    } catch {
        return null;
    }
}

function ecrire(cle, valeur) {
    try {
        if (valeur === null) {
            window.localStorage.removeItem(cle);
        } else {
            window.localStorage.setItem(cle, JSON.stringify(valeur));
        }
    } catch {
        // Stockage indisponible (navigation privée) : la reprise est simplement perdue.
    }
}

function routeCorrespond(etape, route) {
    if (!etape || !route) return false;
    return [].concat(etape.route).some((motif) => motif === route
        || (motif.endsWith('*') && route.startsWith(motif.slice(0, -1))));
}

window.intranetTutorial = function intranetTutorial(config) {
    return {
        open: false,
        reprise: false,
        aideOuverte: false,
        guideId: null,
        index: 0,
        envoi: false,
        jeton: 0,
        mobile: false,
        cibleAbsente: false,
        pret: false,
        reduireAnimations: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        projecteur: { top: '50%', left: '50%', width: '0px', height: '0px' },
        projecteurVisible: false,
        bulle: { top: 'auto', bottom: '12px', left: '50%', transform: 'translateX(-50%)' },
        fleche: { side: null, offset: '50%' },
        termines: lire(config.doneKey) ?? [],

        get guide() {
            return config.guides[this.guideId] ?? null;
        },

        get etapes() {
            return this.guide?.steps ?? [];
        },

        get etape() {
            return this.etapes[this.index] ?? null;
        },

        get progression() {
            return this.etapes.length > 1 ? Math.round((this.index / (this.etapes.length - 1)) * 100) : 100;
        },

        get attendClic() {
            return Boolean(this.etape?.click) && !this.cibleAbsente;
        },

        // Guides qui ont au moins une étape sur la page affichée.
        get guidesDeLaPage() {
            return config.pageGuides.map((id) => config.guides[id]).filter(Boolean);
        },

        // Les étapes d'un guide ne sont chargées qu'au moment où il sert.
        async charger(id) {
            const guide = config.guides[id];
            if (!guide) return false;
            if (guide.steps) return true;
            try {
                const reponse = await fetch(config.stepsUrl.replace('__ID__', encodeURIComponent(id)), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!reponse.ok) return false;
                guide.steps = (await reponse.json()).steps;
                return true;
            } catch {
                return false;
            }
        },

        init() {
            const relancer = () => window.requestAnimationFrame(() => this.mesurer());
            window.addEventListener('resize', relancer, { passive: true });
            document.addEventListener('scroll', relancer, { passive: true, capture: true });
            document.addEventListener('transitionend', (event) => {
                if (this.open && !event.target.closest?.('.intranet-tour-card, .intranet-tour-spotlight')) relancer();
            }, true);
            window.addEventListener('open-intranet-tutorial', () => this.lancer('decouverte'));
            window.addEventListener('start-intranet-guide', (event) => this.lancer(event.detail?.id));

            window.setTimeout(() => this.reprendreAuChargement(), 400);
        },

        async reprendreAuChargement() {
            const etat = lire(config.stateKey);
            if (etat && config.guides[etat.guide] && await this.charger(etat.guide)) {
                this.guideId = etat.guide;
                const etapes = this.etapes;
                if (etat.index >= etapes.length) {
                    this.finir(false);
                    return;
                }
                // L'utilisateur est sur la page attendue, ou vient de cliquer vers la suivante,
                // ou revient sur la page précédente (erreur de formulaire).
                for (const candidat of [etat.index, etat.index + 1, etat.index - 1]) {
                    if (etapes[candidat] && routeCorrespond(etapes[candidat], config.currentRoute)) {
                        this.ouvrir(candidat);
                        return;
                    }
                }
                this.index = etat.index;
                this.reprise = true;
                return;
            }

            if (config.autoOpen && config.pageGuides.includes('decouverte') && await this.charger('decouverte')
                && routeCorrespond(config.guides.decouverte.steps[0], config.currentRoute)) {
                this.guideId = 'decouverte';
                this.ouvrir(0);
            }
        },

        async lancer(id) {
            if (!await this.charger(id)) return;
            this.aideOuverte = false;
            this.guideId = id;
            this.ouvrir(0);
        },

        ouvrir(index) {
            this.reprise = false;
            this.open = true;
            this.aller(index, 1);
        },

        sauver(index) {
            ecrire(config.stateKey, { guide: this.guideId, index });
        },

        // Conduit à la page d'une étape, ou à la dernière étape qui en donne l'adresse.
        naviguerVers(index) {
            for (let i = index; i >= 0; i--) {
                if (this.etapes[i]?.url) {
                    this.sauver(i);
                    window.location.assign(this.etapes[i].url);
                    return true;
                }
            }
            return false;
        },

        async aller(nouvelIndex, sens) {
            const jeton = ++this.jeton;
            if (nouvelIndex >= this.etapes.length) {
                this.terminer();
                return;
            }
            this.index = Math.max(0, nouvelIndex);
            const etape = this.etape;

            if (!routeCorrespond(etape, config.currentRoute)) {
                this.open = false;
                if (!this.naviguerVers(this.index)) this.reprise = true;
                return;
            }

            this.sauver(this.index);
            this.pret = false;
            this.open = true;
            this.mobile = window.innerWidth < 640;
            this.cibleAbsente = false;

            window.dispatchEvent(new CustomEvent(etape.zone === 'sidebar' ? 'intranet-tour-open-sidebar' : 'intranet-tour-close-sidebar'));
            window.dispatchEvent(new CustomEvent(etape.opens === 'profile' ? 'open-intranet-profile' : 'close-intranet-profile'));
            await attendre(this.reduireAnimations ? 20 : 260);
            if (jeton !== this.jeton) return;

            let cible = this.cible();
            for (let essai = 0; !cible && etape.target && essai < 5; essai++) {
                await attendre(100);
                if (jeton !== this.jeton) return;
                cible = this.cible();
            }

            if (etape.target && !cible) {
                // Élément absent de cette page (aucune donnée, droit manquant) :
                // une étape explicative est passée, une étape de clic reste lisible.
                const suivant = this.index + sens;
                if (!etape.click && this.etapes[suivant] && routeCorrespond(this.etapes[suivant], config.currentRoute)) {
                    this.aller(suivant, sens);
                    return;
                }
                this.cibleAbsente = true;
            }

            if (cible) {
                // Si l'élément et la bulle tiennent ensemble à l'écran, l'élément remonte
                // sous l'en-tête pour laisser la place à la bulle en dessous.
                const hauteurBulle = this.$refs.bulle?.offsetHeight || 300;
                const tientAvecBulle = cible.getBoundingClientRect().height + hauteurBulle + 110 < window.innerHeight;
                const marge = cible.style.scrollMarginTop;
                cible.style.scrollMarginTop = '88px';
                cible.scrollIntoView({ block: tientAvecBulle ? 'start' : 'center', inline: 'nearest', behavior: this.reduireAnimations ? 'auto' : 'smooth' });
                cible.style.scrollMarginTop = marge;
                await attendre(this.reduireAnimations ? 20 : 260);
                if (jeton !== this.jeton) return;
                if (etape.click) this.attendreLeClic(cible, jeton);
            }

            this.mesurer();
            this.pret = true;
            this.$nextTick(() => this.$refs.titre?.focus({ preventScroll: true }));
            for (const delai of [250, 700]) {
                window.setTimeout(() => jeton === this.jeton && this.mesurer(), delai);
            }
        },

        // L'utilisateur clique lui-même ; le guide reprend à l'étape suivante,
        // sur cette page (onglet) ou sur la page qui s'ouvre.
        attendreLeClic(cible, jeton) {
            const index = this.index;
            cible.addEventListener('click', () => {
                if (jeton !== this.jeton) return;
                this.sauver(index + 1);
                window.setTimeout(() => {
                    if (jeton === this.jeton && routeCorrespond(this.etapes[index + 1], config.currentRoute)) {
                        this.aller(index + 1, 1);
                    } else if (jeton === this.jeton && index + 1 >= this.etapes.length) {
                        this.terminer();
                    }
                }, 350);
            }, { once: true, capture: true });
        },

        cible() {
            const selecteur = this.etape?.target;
            const element = selecteur ? document.querySelector(selecteur) : null;
            if (!element) return null;

            const rect = element.getBoundingClientRect();
            const visible = rect.width > 0 && rect.height > 0
                && rect.right > 0 && rect.left < window.innerWidth;

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

            // L'élément est apparu après coup (onglet, menu animé) : l'étape redevient normale.
            if (this.cibleAbsente) {
                this.cibleAbsente = false;
                if (this.etape?.click) this.attendreLeClic(cible, this.jeton);
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
                const cibleEnBas = zone.top > window.innerHeight / 2;
                this.bulle = cibleEnBas
                    ? { top: '12px', bottom: 'auto', left: '12px', transform: 'none' }
                    : { top: 'auto', bottom: '12px', left: '12px', transform: 'none' };
                this.fleche = { side: null, offset: '50%' };
                return;
            }

            const largeur = Math.min(LARGEUR_BULLE, window.innerWidth - 24);
            const hauteur = this.$refs.bulle?.offsetHeight || 300;
            const centreX = (zone.left + zone.right) / 2;
            const centreY = (zone.top + zone.bottom) / 2;
            const borner = (valeur, min, max) => Math.min(Math.max(valeur, min), max);
            const horizontal = () => borner(centreX - largeur / 2, 12, window.innerWidth - largeur - 12);
            const vertical = () => borner(centreY - hauteur / 2, 12, window.innerHeight - hauteur - 12);

            const positions = {
                dessous: () => window.innerHeight - zone.bottom >= hauteur + ECART + 12 && (() => {
                    const left = horizontal();
                    return [{ top: `${zone.bottom + ECART}px`, left: `${left}px` }, { side: 'top', offset: `${borner(centreX - left, 20, largeur - 20)}px` }];
                })(),
                dessus: () => zone.top >= hauteur + ECART + 12 && (() => {
                    const left = horizontal();
                    return [{ top: `${zone.top - ECART - hauteur}px`, left: `${left}px` }, { side: 'bottom', offset: `${borner(centreX - left, 20, largeur - 20)}px` }];
                })(),
                droite: () => window.innerWidth - zone.right >= largeur + ECART + 12 && (() => {
                    const top = vertical();
                    return [{ top: `${top}px`, left: `${zone.right + ECART}px` }, { side: 'left', offset: `${borner(centreY - top, 20, hauteur - 20)}px` }];
                })(),
                gauche: () => zone.left >= largeur + ECART + 12 && (() => {
                    const top = vertical();
                    return [{ top: `${top}px`, left: `${zone.left - ECART - largeur}px` }, { side: null, offset: '50%' }];
                })(),
            };
            const ordre = this.etape?.zone === 'sidebar' ? ['droite', 'dessous', 'dessus'] : ['dessous', 'dessus', 'droite', 'gauche'];

            for (const nom of ordre) {
                const placement = positions[nom]();
                if (placement) {
                    this.bulle = { ...placement[0], bottom: 'auto', transform: 'none' };
                    this.fleche = placement[1];
                    return;
                }
            }

            // Élément trop grand (formulaire entier) : la bulle se range dans le coin
            // inférieur droit, là où elle masque le moins de contenu.
            this.bulle = { top: 'auto', bottom: '16px', left: `${window.innerWidth - largeur - 16}px`, transform: 'none' };
            this.fleche = { side: null, offset: '50%' };
        },

        suivant() {
            this.aller(this.index + 1, 1);
        },

        precedent() {
            if (this.index > 0) this.aller(this.index - 1, -1);
        },

        // Ferme en gardant la progression : un bandeau propose de reprendre.
        plusTard() {
            this.open = false;
            this.reprise = true;
            window.dispatchEvent(new CustomEvent('intranet-tour-close-sidebar'));
            window.dispatchEvent(new CustomEvent('close-intranet-profile'));
        },

        reprendre() {
            this.ouvrir(this.index);
        },

        abandonner() {
            ecrire(config.stateKey, null);
            this.open = false;
            this.reprise = false;
            if (this.guideId === 'decouverte') this.marquerDecouverte();
        },

        finir(annoncer) {
            ecrire(config.stateKey, null);
            if (!this.termines.includes(this.guideId)) {
                this.termines = [...this.termines, this.guideId];
                ecrire(config.doneKey, this.termines);
            }
            if (this.guideId === 'decouverte') this.marquerDecouverte();
            this.open = false;
            this.reprise = false;
            window.dispatchEvent(new CustomEvent('intranet-tour-close-sidebar'));
            window.dispatchEvent(new CustomEvent('close-intranet-profile'));
            window.dispatchEvent(new CustomEvent('intranet-guides-updated'));
            if (annoncer) {
                window.toastr?.success(`Guide « ${this.guide?.title ?? ''} » terminé. Retrouvez tous les guides dans le centre d'aide.`);
            }
        },

        terminer() {
            this.finir(true);
        },

        async marquerDecouverte() {
            if (!config.autoOpen || this.envoi) return;
            this.envoi = true;
            try {
                const reponse = await fetch(config.completeUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': config.csrf, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (reponse.ok) config.autoOpen = false;
            } catch {
                // Repli : le formulaire « Ne plus afficher le guide » reste disponible.
            } finally {
                this.envoi = false;
            }
        },
    };
};
