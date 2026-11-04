/**
 * Liquid glass : comportements décoratifs liés au pointeur et au défilement.
 * L’interface reste entièrement fonctionnelle lorsque ce module est désactivé.
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
const MAX_CASCADE = 12;

const reduireMouvement = window.matchMedia('(prefers-reduced-motion: reduce)');
const pointeurFin = window.matchMedia('(hover: hover) and (pointer: fine)');

function estSurface(element) {
    return element.matches(SURFACES)
        && !element.matches('input, select, textarea, button, .fixed')
        && !element.closest('.fixed, [role="dialog"]');
}

function ajouterSurface(element, surfaces) {
    if (!element.hasAttribute('data-glass') && estSurface(element)) {
        element.setAttribute('data-glass', '');
        surfaces.push(element);
    }
}

/**
 * Marque uniquement la branche reçue. Cette portée évite de reparcourir toute
 * la page à chaque mise à jour Livewire.
 */
function marquerSurfaces(racine = document) {
    const surfaces = [];

    if (racine === document) {
        document.querySelectorAll(CONTENEURS).forEach((conteneur) => {
            conteneur.querySelectorAll(SURFACES).forEach((element) => ajouterSurface(element, surfaces));
        });
        return surfaces;
    }

    if (!(racine instanceof Element)) return surfaces;

    const dansContenu = racine.closest(CONTENEURS);
    if (dansContenu) {
        ajouterSurface(racine, surfaces);
        racine.querySelectorAll(SURFACES).forEach((element) => ajouterSurface(element, surfaces));
    }

    racine.querySelectorAll(CONTENEURS).forEach((conteneur) => {
        conteneur.querySelectorAll(SURFACES).forEach((element) => ajouterSurface(element, surfaces));
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
    let surfaceCourante = null;
    let image = 0;
    let positionX = 0;
    let positionY = 0;

    const mettreAJourReflet = () => {
        image = 0;
        const surface = surfaceCourante;
        if (!surface?.isConnected) return;

        const rect = surface.getBoundingClientRect();
        if (!rect.width || !rect.height) return;

        const x = Math.max(0, Math.min(100, ((positionX - rect.left) / rect.width) * 100));
        const y = Math.max(0, Math.min(100, ((positionY - rect.top) / rect.height) * 100));
        surface.style.setProperty('--glass-x', `${x}%`);
        surface.style.setProperty('--glass-y', `${y}%`);
    };

    const eteindre = () => {
        surfaceCourante?.classList.remove('is-lit');
        surfaceCourante = null;
        if (image) {
            window.cancelAnimationFrame(image);
            image = 0;
        }
    };

    document.addEventListener('pointermove', (event) => {
        if (!pointeurFin.matches || reduireMouvement.matches || event.pointerType !== 'mouse') return;

        positionX = event.clientX;
        positionY = event.clientY;
        const surface = event.target.closest?.('[data-glass]') ?? null;

        if (surface !== surfaceCourante) {
            surfaceCourante?.classList.remove('is-lit');
            surfaceCourante = surface;
            surfaceCourante?.classList.add('is-lit');
        }

        if (surfaceCourante && !image) {
            image = window.requestAnimationFrame(mettreAJourReflet);
        }
    }, { passive: true });

    document.addEventListener('pointerleave', eteindre);
    window.addEventListener('blur', eteindre);
}

function densifierEntete() {
    const entete = document.querySelector('.intranet-header');
    const contenu = document.getElementById('main-content');
    if (!entete || !contenu) return;

    let image = 0;
    const mettreAJour = () => {
        image = 0;
        entete.classList.toggle('is-scrolled', contenu.scrollTop > 8);
    };

    contenu.addEventListener('scroll', () => {
        if (!image) image = window.requestAnimationFrame(mettreAJour);
    }, { passive: true });
    mettreAJour();
}

function observerContenu() {
    const observateur = new MutationObserver((mutations) => {
        const nouvellesSurfaces = [];
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                nouvellesSurfaces.push(...marquerSurfaces(node));
            });
        });

        // Une courte cascade rend aussi les panneaux Livewire moins abrupts.
        cascade(nouvellesSurfaces.slice(0, 6));
    });

    observateur.observe(document.body, { childList: true, subtree: true });
    window.addEventListener('pagehide', () => observateur.disconnect(), { once: true });
}

function demarrer() {
    cascade(marquerSurfaces());
    suivrePointeur();
    densifierEntete();
    observerContenu();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', demarrer, { once: true });
} else {
    demarrer();
}
