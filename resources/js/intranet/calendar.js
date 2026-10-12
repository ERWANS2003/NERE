/**
 * Calendrier intranet (FullCalendar, lecture seule).
 *
 * Les événements viennent de GET /intranet/calendar/events (voir CalendarFeed) :
 * événements saisis, anniversaires et arrivées. La création/modification depuis
 * l'interface n'est pas prévue à ce stade.
 */
import { Calendar } from '@fullcalendar/core';
import frLocale from '@fullcalendar/core/locales/fr';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';

/**
 * Crée et affiche un calendrier.
 *
 * @param {HTMLElement} element    conteneur du calendrier
 * @param {object}      options
 * @param {string}      options.eventsUrl    URL du flux JSON (obligatoire)
 * @param {string|null} options.initialDate  jour affiché à l'ouverture (AAAA-MM-JJ)
 * @param {Function}    options.onEventClick reçoit l'objet événement FullCalendar
 * @param {Function}    options.onError      appelée si le chargement échoue
 */
export function initCalendar(element, options = {}) {
    if (!element || !options.eventsUrl) {
        console.error('initCalendar : conteneur ou eventsUrl manquant');
        return null;
    }

    // Sur petit écran, la grille mensuelle tronque les titres (« Arri… ») :
    // on démarre sur la liste, la grille reste accessible via le bouton « Mois ».
    const compact = window.matchMedia('(max-width: 639px)').matches;

    const calendar = new Calendar(element, {
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin],
        locales: [frLocale],
        locale: 'fr',
        firstDay: 1, // lundi
        initialView: compact ? 'listMonth' : 'dayGridMonth',
        initialDate: options.initialDate || undefined,
        height: 'auto',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: compact ? 'listMonth,dayGridMonth' : 'dayGridMonth,timeGridWeek,listMonth',
        },
        buttonText: { listMonth: 'Liste' },
        editable: false,
        selectable: false,
        dayMaxEvents: 3,
        nowIndicator: true,
        navLinks: true,
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },

        events: {
            url: options.eventsUrl,
            method: 'GET',
            // Même origine : le cookie de session authentifie la requête.
            failure: () => options.onError?.(),
        },

        // Rend les événements activables au clavier (Entrée / Espace).
        eventDidMount: (info) => {
            info.el.setAttribute('tabindex', '0');
            info.el.setAttribute('role', 'button');
            info.el.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    info.el.click();
                }
            });
        },

        eventClick: (info) => {
            info.jsEvent.preventDefault();
            options.onEventClick?.(info.event);
        },
    });

    calendar.render();

    return calendar;
}

/**
 * Composant Alpine de la page calendrier.
 *
 * L'instance FullCalendar reste dans une variable locale : placée dans l'état
 * d'Alpine, elle serait enveloppée dans un Proxy et ses objets internes
 * cesseraient de fonctionner.
 */
export function calendarComponent(config = {}) {
    let calendar = null;

    return {
        selected: null,
        loadError: false,

        init() {
            calendar = initCalendar(this.$refs.calendar, {
                eventsUrl: config.eventsUrl,
                initialDate: config.initialDate,
                onEventClick: (event) => this.select(event),
                onError: () => { this.loadError = true; },
            });
        },

        select(event) {
            this.selected = {
                title: event.title,
                ...event.extendedProps,
            };
        },

        close() {
            this.selected = null;
        },

        /** Date longue en français : « jeudi 8 octobre 2026 ». */
        formatDate(iso) {
            if (!iso) return '';
            const [y, m, d] = iso.split('-').map(Number);
            return new Date(y, m - 1, d).toLocaleDateString('fr-FR', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
            });
        },

        refresh() {
            this.loadError = false;
            calendar?.refetchEvents();
        },
    };
}
