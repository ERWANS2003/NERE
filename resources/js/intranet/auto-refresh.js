const refreshControls = document.querySelector('[data-auto-refresh]');

if (refreshControls) {
    const refreshButton = refreshControls.querySelector('[data-auto-refresh-toggle]');
    const status = refreshControls.querySelector('[data-auto-refresh-status]');
    const interval = Number.parseInt(refreshControls.dataset.interval ?? '', 10);
    let paused = false;
    let timer;

    const scheduleRefresh = () => {
        window.clearTimeout(timer);

        if (paused || document.hidden) {
            status.textContent = paused
                ? 'Actualisation automatique suspendue.'
                : 'Actualisation automatique suspendue tant que cet onglet est masqué.';
            return;
        }

        status.textContent = 'Actualisation automatique active, toutes les 60 secondes.';
        timer = window.setTimeout(() => window.location.reload(), interval);
    };

    refreshButton.addEventListener('click', () => {
        paused = !paused;
        refreshButton.setAttribute('aria-pressed', String(paused));
        refreshButton.textContent = paused ? 'Reprendre l’actualisation' : 'Suspendre l’actualisation';
        scheduleRefresh();
    });

    document.addEventListener('visibilitychange', scheduleRefresh);
    scheduleRefresh();
}
