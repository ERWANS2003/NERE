// Son propre à l'intranet, joué quand le service worker reçoit une
// notification push et qu'un onglet de l'intranet est ouvert. Les sons des
// notifications Windows elles-mêmes ne sont pas personnalisables depuis un
// site : Windows joue son son système.
let audioContext;

const getAudioContext = () => {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    if (!AudioContextClass) {
        return null;
    }
    audioContext ??= new AudioContextClass();

    return audioContext;
};

// Les navigateurs n'autorisent le son qu'après une interaction avec la page.
document.addEventListener('pointerdown', () => getAudioContext()?.resume(), { once: true });

const playTone = (context, frequency, start, duration) => {
    const oscillator = context.createOscillator();
    const gain = context.createGain();
    oscillator.type = 'triangle';
    oscillator.frequency.value = frequency;
    gain.gain.setValueAtTime(0.0001, start);
    gain.gain.exponentialRampToValueAtTime(0.35, start + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
    oscillator.connect(gain).connect(context.destination);
    oscillator.start(start);
    oscillator.stop(start + duration + 0.05);
};

export const playIntranetChime = (urgent = false) => {
    const context = getAudioContext();
    if (!context || context.state !== 'running') {
        return;
    }

    // Carillon montant (do - mi - sol) ; alerte urgente : deux notes aiguës répétées.
    const notes = urgent
        ? [[880, 0], [1175, 0.18], [880, 0.42], [1175, 0.6], [880, 0.84], [1175, 1.02]]
        : [[523, 0], [659, 0.14], [784, 0.28]];
    const now = context.currentTime;
    notes.forEach(([frequency, offset]) => playTone(context, frequency, now + offset, urgent ? 0.16 : 0.3));
};

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('message', (event) => {
        if (event.data?.type === 'intranet-notification') {
            playIntranetChime(Boolean(event.data.urgent));
        }
    });
}

// Bandeau invitant à activer les notifications Windows, tant que le
// navigateur ne les a ni autorisées ni bloquées.
const banner = document.querySelector('[data-push-banner]');
if (banner && 'Notification' in window && 'PushManager' in window && Notification.permission === 'default') {
    let dismissed = false;
    try {
        dismissed = window.localStorage.getItem('intranet-push-banner-dismissed') === '1';
    } catch {
        // Stockage indisponible : le bandeau reste affiché.
    }

    if (!dismissed) {
        banner.hidden = false;
        banner.querySelector('[data-push-banner-dismiss]')?.addEventListener('click', () => {
            banner.hidden = true;
            try {
                window.localStorage.setItem('intranet-push-banner-dismissed', '1');
            } catch {
                // Rien à faire.
            }
        });
    }
}
