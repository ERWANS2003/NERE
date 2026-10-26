/**
 * Liquid glass : comportements liés au pointeur et au défilement.
 *
 * - Marque les surfaces de verre (data-glass) pour le reflet qui suit le
 *   pointeur et l'éclat de survol.
 * - Fait apparaître en cascade les cartes visibles au chargement.
 * - Densifie l'en-tête dès que le contenu principal défile.
 *
 * Tout reste décoratif : sans ce script, les surfaces gardent leur verre fixe.
 */
const SURFACES = [
    '.carte',
    '.carte-plate',
    '.nere-card',
    '.nere-card-flat',
    '.intranet-filters',
    '.intranet-table-container',
    '.bg-white.rounded-lg',
    '.bg-white.rounded-xl',
    '.bg-white.rounded-2xl',
].join(',');

const CONTENEURS = '#main-content, .admin-shell main, .app-shell main';
const MAX_CASCADE = 14;

const reduireMouvement = window.matchMedia('(prefers-reduced-motion: reduce)');
const pointeurFin = window.matchMedia('(hover: hover) and (pointer: fine)');

function estSurface(element) {
    return element.matches(SURFACES)
        && !element.matches('input, select, textarea, button, .fixed')
        && !element.closest('.fixed, [role="dialog"]');
}

function marquerSurfaces(racine = document) {
    const surfaces = [];

    racine.querySelectorAll(CONTENEURS).forEach((conteneur) => {
        conteneur.querySelectorAll(SURFACES).forEach((element) => {
            if (!element.hasAttribute('data-glass') && estSurface(element)) {
                element.setAttribute('data-glass', '');
                surfaces.push(element);
            }
        });
    });

    return surfaces;
}

function cascade(surfaces) {
    if (reduireMouvement.matches) return;

    const hauteur = window.innerHeight;
    surfaces
        .filter((element) => element.getBoundingClientRect().top < hauteur)
        .slice(0, MAX_CASCADE)
        .forEach((element, index) => {
            element.style.setProperty('--glass-i', String(index));
            element.setAttribute('data-glass-enter', '');
            element.addEventListener('animationend', () => {
                element.removeAttribute('data-glass-enter');
                element.style.removeProperty('--glass-i');
            }, { once: true });
        });
}

function suivrePointeur() {
    let courante = null;
    let image = 0;

    const eteindre = () => {
        courante?.classList.remove('is-lit');
        courante = null;
    };

    document.addEventListener('pointermove', (event) => {
        if (!pointeurFin.matches || reduireMouvement.matches || event.pointerType !== 'mouse') return;

        const surface = event.target.closest?.('[data-glass]');
        if (surface !== courante) {
            eteindre();
            courante = surface;
            surface?.classList.add('is-lit');
        }
        if (!surface || image) return;

        // Une mise à jour par image affichée suffit pour un reflet fluide.
        image = window.requestAnimationFrame(() => {
            image = 0;
            const rect = surface.getBoundingClientRect();
            surface.style.setProperty('--glass-x', `${((event.clientX - rect.left) / rect.width) * 100}%`);
            surface.style.setProperty('--glass-y', `${((event.clientY - rect.top) / rect.height) * 100}%`);
        });
    }, { passive: true });

    document.addEventListener('pointerleave', eteindre);
    window.addEventListener('blur', eteindre);
}

function densifierEntete() {
    const entete = document.querySelector('.intranet-header');
    const contenu = document.getElementById('main-content');
    if (!entete || !contenu) return;

    const mettreAJour = () => entete.classList.toggle('is-scrolled', contenu.scrollTop > 8);
    contenu.addEventListener('scroll', mettreAJour, { passive: true });
    mettreAJour();
}

function demarrer() {
    cascade(marquerSurfaces());
    suivrePointeur();
    densifierEntete();

    // Contenu ajouté après coup (Livewire, listes filtrées) : on marque sans cascade.
    let attente = 0;
    new MutationObserver(() => {
        if (attente) return;
        attente = window.requestAnimationFrame(() => {
            attente = 0;
            marquerSurfaces();
        });
    }).observe(document.body, { childList: true, subtree: true });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', demarrer, { once: true });
} else {
    demarrer();
}
