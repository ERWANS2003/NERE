// Écran squelette : remplace le contenu de la page pendant le chargement de la
// suivante (clic sur un lien interne ou envoi de formulaire). Il n'apparaît
// qu'après un court délai, pour ne pas clignoter sur les pages rapides.
const SHOW_DELAY_MS = 180;
// Un téléchargement (export, pièce jointe) ne change pas de page : le
// squelette disparaît de lui-même au bout de ce délai.
const FALLBACK_HIDE_MS = 8000;

const main = document.querySelector('[data-page-skeleton-root]');

if (main) {
    let showTimer;
    let hideTimer;

    const hide = () => {
        clearTimeout(showTimer);
        clearTimeout(hideTimer);
        main.classList.remove('is-navigating');
        main.removeAttribute('aria-busy');
    };

    const show = () => {
        clearTimeout(showTimer);
        showTimer = setTimeout(() => {
            main.scrollTop = 0;
            main.classList.add('is-navigating');
            main.setAttribute('aria-busy', 'true');
            hideTimer = setTimeout(hide, FALLBACK_HIDE_MS);
        }, SHOW_DELAY_MS);
    };

    const isPageNavigation = (link) => {
        if (!link.href || link.target && link.target !== '_self' || link.hasAttribute('download')) {
            return false;
        }
        if (link.closest('[data-no-skeleton]')) {
            return false;
        }

        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin || !['http:', 'https:'].includes(url.protocol)) {
            return false;
        }
        // Ancre sur la même page, ou fichier à télécharger.
        if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) {
            return false;
        }

        return !/(export|telecharger|download|piece-jointe|attachments|\.(pdf|csv|xlsx|docx?|zip))(\/|$|\?)/i.test(url.pathname);
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a');
        if (!link || event.defaultPrevented || event.button !== 0
            || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }
        if (isPageNavigation(link)) {
            show();
        }
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        // Laisse passer les confirmations (onsubmit="return confirm(...)") avant de décider.
        setTimeout(() => {
            if (event.defaultPrevented || form.target && form.target !== '_self' || form.closest('[data-no-skeleton]')) {
                return;
            }
            show();
        }, 0);
    });

    // Retour arrière : le navigateur peut restaurer la page telle qu'elle était.
    window.addEventListener('pageshow', hide);
}
