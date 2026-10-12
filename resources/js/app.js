import focus from '@alpinejs/focus';

// Import des bibliothèques intranet
import Fuse from 'fuse.js';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import Chart from 'chart.js/auto';
import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import relativeTime from 'dayjs/plugin/relativeTime';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';
import Sortable from 'sortablejs';

// Import des modules intranet
import './intranet/index.js';

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

// Rendre les bibliothèques disponibles globalement
window.Fuse = Fuse;
window.Calendar = Calendar;
window.dayGridPlugin = dayGridPlugin;
window.timeGridPlugin = timeGridPlugin;
window.interactionPlugin = interactionPlugin;
window.Chart = Chart;
window.dayjs = dayjs;
window.toastr = toastr;
window.Sortable = Sortable;

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