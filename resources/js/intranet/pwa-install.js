const installButton = document.querySelector('[data-pwa-install]');
let installPrompt;

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js')
        .catch((error) => {
            console.error('Enregistrement du service worker impossible.', error);
        });
}

if (installButton) {
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        installPrompt = event;
        installButton.hidden = false;
        installButton.classList.add('inline-flex');
    });

    window.addEventListener('appinstalled', () => {
        installPrompt = undefined;
        installButton.hidden = true;
        installButton.classList.remove('inline-flex');
    });

    installButton.addEventListener('click', async () => {
        if (!installPrompt) {
            return;
        }

        await installPrompt.prompt();
        await installPrompt.userChoice;
        installPrompt = undefined;
        installButton.hidden = true;
        installButton.classList.remove('inline-flex');
    });
}
