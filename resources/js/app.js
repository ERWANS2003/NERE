import focus from '@alpinejs/focus';

// Bibliothèques communes légères. Les graphiques, calendriers, la recherche
// fuzzy et le glisser-déposer sont importés à la demande par leurs modules.
import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import relativeTime from 'dayjs/plugin/relativeTime';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

// Import des modules intranet
import './intranet/index.js';
import './intranet/live-search.js';
import './intranet/auto-refresh.js';
import './intranet/push-notifications.js';
import './intranet/notification-sound.js';
import './page-skeleton.js';
import './intranet/pwa-install.js';
import './intranet/tutorial.js';
import './intranet/chat.js';
import './intranet/glass.js';
import './admin-shell.js';

// Configuration Day.js
dayjs.locale('fr');
dayjs.extend(relativeTime);

// Configuration Toastr
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: 'toast-top-right',
    timeOut: 3000,
    showMethod: 'fadeIn',
    hideMethod: 'fadeOut'
};

// Rendre les bibliothèques communes disponibles aux modules historiques
window.dayjs = dayjs;
window.toastr = toastr;

// Livewire embarque et demarre deja Alpine. Lancer Alpine.start() ici creerait
// une seconde instance : Alpine la detecte, puis desaccorde ses intercepteurs,
// ce qui rend `x-trap` inoperant dans les modales. On declare donc le plugin sur
// l'instance existante, qu'elle soit deja demarree ou pas encore.
function enregistrerPluginFocus() {
    if (window.Alpine && typeof window.Alpine.plugin === 'function') {
        window.Alpine.plugin(focus);
    }
}

if (window.Alpine) {
    enregistrerPluginFocus();
} else {
    document.addEventListener('livewire:init', enregistrerPluginFocus, { once: true });
}