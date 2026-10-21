const pushControls = document.querySelector('[data-web-push]');

if (pushControls) {
    const button = pushControls.querySelector('[data-web-push-toggle]');
    const status = pushControls.querySelector('[data-web-push-status]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    let registration;
    let busy = false;
    button.disabled = true;

    const setStatus = (message) => {
        status.textContent = message;
    };

    const setBusy = (value) => {
        busy = value;
        button.disabled = busy;
    };

    const postSubscription = async (subscription) => {
        const response = await fetch(pushControls.dataset.subscribeUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(subscription),
        });

        if (!response.ok) {
            throw new Error(`Enregistrement de l’abonnement refusé (HTTP ${response.status}).`);
        }
    };

    const deleteSubscription = async (subscription) => {
        const response = await fetch(pushControls.dataset.unsubscribeUrl, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ endpoint: subscription.endpoint }),
        });

        if (!response.ok) {
            throw new Error(`Suppression de l’abonnement refusée (HTTP ${response.status}).`);
        }
    };

    const updateButton = (subscribed) => {
        button.textContent = subscribed
            ? 'Désactiver les notifications push'
            : 'Activer les notifications push';
        button.setAttribute('aria-pressed', String(subscribed));
    };

    const base64UrlToBytes = (value) => {
        const padding = '='.repeat((4 - (value.length % 4)) % 4);
        const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = window.atob(base64);

        return Uint8Array.from(raw, (character) => character.charCodeAt(0));
    };

    if (!csrfToken) {
        button.disabled = true;
        setStatus('Le jeton de sécurité de la page est indisponible.');
    } else if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        button.disabled = true;
        setStatus('Les notifications push ne sont pas prises en charge par ce navigateur.');
    } else if (!window.isSecureContext) {
        button.disabled = true;
        setStatus('Les notifications push nécessitent HTTPS, sauf en développement local.');
    } else if (Notification.permission === 'denied') {
        button.disabled = true;
        setStatus('Les notifications sont bloquées dans les réglages de ce navigateur.');
    } else {
        navigator.serviceWorker.register('/service-worker.js')
            .then(async (serviceWorker) => {
                registration = serviceWorker;
                const subscription = await registration.pushManager.getSubscription();

                if (subscription && Notification.permission === 'granted') {
                    await postSubscription(subscription);
                    updateButton(true);
                    setStatus('Les notifications push sont activées sur cet appareil.');
                    button.disabled = false;
                    return;
                }

                updateButton(false);
                setStatus('Activez les notifications push pour recevoir les alertes sur cet appareil.');
                button.disabled = false;
            })
            .catch((error) => {
                console.error('Initialisation des notifications push impossible.', error);
                button.disabled = true;
                setStatus('Impossible de préparer les notifications push. Rechargez la page ou contactez le support.');
            });

        button.addEventListener('click', async () => {
            if (busy || !registration) {
                return;
            }

            setBusy(true);

            try {
                const existingSubscription = await registration.pushManager.getSubscription();

                if (existingSubscription) {
                    await deleteSubscription(existingSubscription);
                    await existingSubscription.unsubscribe();
                    updateButton(false);
                    setStatus('Les notifications push sont désactivées sur cet appareil.');
                    return;
                }

                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    updateButton(false);
                    setStatus(permission === 'denied'
                        ? 'Autorisation refusée. Vous pouvez la modifier dans les réglages du navigateur.'
                        : 'Autorisation non accordée.');
                    return;
                }

                const keyResponse = await fetch(pushControls.dataset.publicKeyUrl, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                });
                if (!keyResponse.ok) {
                    throw new Error(`Clé VAPID indisponible (HTTP ${keyResponse.status}).`);
                }

                const { publicKey } = await keyResponse.json();
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: base64UrlToBytes(publicKey),
                });

                try {
                    await postSubscription(subscription);
                } catch (error) {
                    await subscription.unsubscribe();
                    throw error;
                }

                updateButton(true);
                setStatus('Les notifications push sont activées sur cet appareil.');
            } catch (error) {
                console.error('Modification de l’abonnement push impossible.', error);
                setStatus('L’opération a échoué. Vérifiez votre connexion puis réessayez.');
            } finally {
                setBusy(false);
                if (Notification.permission === 'denied') {
                    button.disabled = true;
                }
            }
        });
    }
}
