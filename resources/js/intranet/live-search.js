const DEBOUNCE_DELAY = 350;

let debounceTimer;
let activeRequest;

function mainContent() {
    return document.querySelector('main');
}

function restoreFocus(main, fieldName, selectionStart, selectionEnd) {
    if (!fieldName) {
        return;
    }

    const field = [...main.querySelectorAll('input, select, textarea')]
        .find((element) => element.name === fieldName);

    if (!field) {
        return;
    }

    field.focus({ preventScroll: true });

    if (field instanceof HTMLInputElement && !['search', 'text', 'url', 'tel', 'password'].includes(field.type)) {
        return;
    }

    if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) {
        field.setSelectionRange(selectionStart, selectionEnd);
    }
}

async function navigate(url, { updateHistory = true } = {}) {
    const currentMain = mainContent();

    if (!currentMain) {
        window.location.assign(url);
        return;
    }

    activeRequest?.abort();
    activeRequest = new AbortController();
    const requestController = activeRequest;
    currentMain.setAttribute('aria-busy', 'true');
    currentMain.classList.add('opacity-60', 'transition-opacity');

    const activeElement = document.activeElement;
    const fieldName = activeElement instanceof HTMLInputElement
        || activeElement instanceof HTMLSelectElement
        || activeElement instanceof HTMLTextAreaElement
        ? activeElement.name
        : null;
    const selectionSupported = activeElement instanceof HTMLTextAreaElement
        || (activeElement instanceof HTMLInputElement
            && ['search', 'text', 'url', 'tel', 'password'].includes(activeElement.type));
    const selectionStart = selectionSupported
        ? activeElement.selectionStart
        : null;
    const selectionEnd = selectionSupported
        ? activeElement.selectionEnd
        : null;

    try {
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: requestController.signal,
        });

        if (!response.ok || response.redirected || new URL(response.url).origin !== window.location.origin) {
            window.location.assign(response.url);
            return;
        }

        const html = await response.text();
        const nextDocument = new DOMParser().parseFromString(html, 'text/html');
        const nextMain = nextDocument.querySelector('main');

        if (!nextMain) {
            window.location.assign(url);
            return;
        }

        if (window.Alpine) {
            window.Alpine.destroyTree(currentMain);
        }

        currentMain.innerHTML = nextMain.innerHTML;
        currentMain.removeAttribute('aria-busy');
        document.title = nextDocument.title;

        if (updateHistory) {
            window.history.replaceState({}, '', url);
        }

        if (window.Alpine) {
            window.Alpine.initTree(currentMain);
        }

        restoreFocus(currentMain, fieldName, selectionStart, selectionEnd);
    } catch (error) {
        if (error.name !== 'AbortError') {
            window.location.assign(url);
        }
    } finally {
        if (activeRequest === requestController) {
            activeRequest = null;
            currentMain.removeAttribute('aria-busy');
            currentMain.classList.remove('opacity-60');
        }
    }
}

function formUrl(form) {
    const url = new URL(form.getAttribute('action') || window.location.pathname, window.location.origin);

    for (const [key, value] of new FormData(form)) {
        url.searchParams.append(key, value.toString());
    }

    return url;
}

function scheduleSearch(form, delay = DEBOUNCE_DELAY) {
    window.clearTimeout(debounceTimer);
    activeRequest?.abort();
    debounceTimer = window.setTimeout(() => {
        if (!form.isConnected) {
            return;
        }

        const url = formUrl(form);
        url.searchParams.delete('page');
        navigate(url);
    }, delay);
}

document.addEventListener('input', (event) => {
    const input = event.target;

    if (!(input instanceof HTMLInputElement) || input.name !== 'search') {
        return;
    }

    const form = input.closest('form[data-live-search]');

    if (form) {
        scheduleSearch(form);
    }
});

document.addEventListener('change', (event) => {
    const control = event.target;
    const form = control instanceof HTMLElement
        ? control.closest('form[data-live-search]')
        : null;

    if (form && control instanceof HTMLSelectElement) {
        scheduleSearch(form, 0);
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.matches('form[data-live-search]')) {
        return;
    }

    event.preventDefault();
    window.clearTimeout(debounceTimer);
    navigate(formUrl(form));
});

document.addEventListener('click', (event) => {
    const link = event.target instanceof Element
        ? event.target.closest('main a[href]')
        : null;

    if (!link) {
        return;
    }

    const url = new URL(link.href);

    if (url.origin === window.location.origin && url.searchParams.has('page')) {
        event.preventDefault();
        navigate(url);
    }
});

window.addEventListener('popstate', () => {
    navigate(window.location.href, { updateHistory: false });
});
