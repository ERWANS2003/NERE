# 📚 Documentation des Modules JavaScript Intranet

## Vue d'ensemble

L'intranet NÉRÉ Mining utilise plusieurs bibliothèques JavaScript modernes pour offrir une expérience utilisateur riche et interactive.

## 🔍 Recherche Globale (Cmd+K)

### Utilisation

```html
<div x-data="window.IntranetModules.initGlobalSearch()">
    <!-- Bouton d'ouverture -->
    <button @click="open()" type="button">
        Rechercher (Cmd+K)
    </button>
    
    <!-- Modal de recherche -->
    <div x-show="isOpen" @keydown.escape="close()">
        <input 
            x-ref="searchInput"
            x-model="query"
            @input.debounce.300ms="search()"
            placeholder="Rechercher..."
        />
        
        <div x-show="results.length > 0">
            <template x-for="(result, index) in results" :key="result.id">
                <div 
                    @click="navigateTo(result)"
                    :class="{ 'selected': index === selectedIndex }"
                >
                    <span x-text="getResultIcon(result.type)"></span>
                    <span x-text="result.title"></span>
                </div>
            </template>
        </div>
    </div>
</div>
```

### Navigation clavier
- **Cmd+K / Ctrl+K** : Ouvrir la recherche
- **Échap** : Fermer
- **↑ / ↓** : Naviguer dans les résultats
- **Entrée** : Ouvrir le résultat sélectionné

## 📅 Calendrier

Calendrier en lecture seule (FullCalendar, locale française). Les événements
viennent de `GET /intranet/calendar/events?start=...&end=...` (service
`App\Services\Intranet\CalendarFeed` : événements saisis + anniversaires +
arrivées calculés depuis les fiches employés). Vue : `intranet/calendar/index.blade.php`.

### Initialisation basique

```javascript
import { initCalendar } from './intranet/calendar.js';

const calendar = initCalendar(document.getElementById('calendar'), {
    eventsUrl: '/intranet/calendar/events',   // obligatoire
    initialDate: '2026-12-11',                // optionnel (AAAA-MM-JJ)
    onEventClick: (event) => console.log(event.title, event.extendedProps),
    onError: () => console.warn('Chargement impossible'),
});
```

### Utilisation avec Alpine.js

```html
<div x-data="window.IntranetModules.calendarComponent({
        eventsUrl: '/intranet/calendar/events',
        initialDate: null,
    })">
    <div x-ref="calendar"></div>
    <!-- `selected` contient l'événement cliqué ; `close()` ferme le détail -->
</div>
```

## 📊 Graphiques (Chart.js)

### Graphique en ligne

```javascript
import { createLineChart } from './intranet/charts.js';

createLineChart('myChart', {
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
    datasets: [{
        label: 'Tickets créés',
        data: [12, 19, 3, 5, 2, 3],
        borderColor: '#C83530',
        backgroundColor: 'rgba(200, 53, 48, 0.1)'
    }]
});
```

### Dashboard avec Alpine.js

```html
<div x-data="window.IntranetModules.dashboardChartsComponent()">
    <canvas id="ticketsPerMonthChart" height="300"></canvas>
    <canvas id="ticketsByDepartmentChart" height="300"></canvas>
    <canvas id="ticketsByStatusChart" height="300"></canvas>
</div>
```

## 🗓️ Formatage de dates (Day.js)

```javascript
import { formatDate, formatRelativeDate } from './intranet/utils.js';

// Format standard
formatDate('2024-01-15'); // "15/01/2024"
formatDate('2024-01-15', 'DD MMM YYYY'); // "15 jan 2024"

// Format relatif
formatRelativeDate('2024-01-15'); // "il y a 2 jours"
```

### Utilisation avec Alpine.js

```html
<div x-data="{ date: '2024-01-15' }">
    <span x-text="window.dayjs(date).format('DD/MM/YYYY')"></span>
    <span x-text="window.dayjs(date).fromNow()"></span>
</div>
```

## 🔔 Notifications (Toastr)

```javascript
import { 
    showSuccessNotification,
    showErrorNotification,
    showInfoNotification,
    showWarningNotification
} from './intranet/utils.js';

// Succès
showSuccessNotification('Opération réussie!');

// Erreur
showErrorNotification('Une erreur est survenue');

// Info
showInfoNotification('Information importante');

// Avertissement
showWarningNotification('Attention!');
```

### Utilisation directe

```html
<button @click="window.toastr.success('Message de succès!')">
    Afficher notification
</button>
```

## 🔀 Drag & Drop (Sortable.js)

### Quick Links réorganisables

```html
<div x-data="window.IntranetModules.quickLinksComponent()">
    <ul id="quick-links-list">
        <li data-id="1" class="sortable-handle">
            Lien 1
        </li>
        <li data-id="2" class="sortable-handle">
            Lien 2
        </li>
    </ul>
</div>
```

## 🎉 Kudos (Reconnaissance)

```html
<div x-data="window.IntranetModules.kudosComponent()">
    <!-- Bouton d'ouverture -->
    <button @click="openModal()" type="button">
        Envoyer un Kudos
    </button>
    
    <!-- Modal -->
    <div x-show="showModal" @click.away="closeModal()">
        <form @submit.prevent="submit()">
            <select x-model="form.to_user_id" required>
                <option value="">Sélectionner un employé</option>
                <!-- Options -->
            </select>
            
            <textarea 
                x-model="form.message" 
                placeholder="Votre message..."
                required
            ></textarea>
            
            <button 
                type="submit" 
                :disabled="loading"
            >
                Envoyer
            </button>
        </form>
    </div>
</div>
```

## 🛠️ Utilitaires

### Copier dans le presse-papiers

```javascript
import { copyToClipboard } from './intranet/utils.js';

copyToClipboard('Texte à copier');
```

### Télécharger un fichier

```javascript
import { downloadFile } from './intranet/utils.js';

downloadFile('/path/to/file.pdf', 'document.pdf');
```

### Debounce pour recherche

```javascript
import { debounce } from './intranet/utils.js';

const searchFunction = debounce((query) => {
    // Effectuer la recherche
}, 300);
```

## 🎨 Styles NÉRÉ

### Couleurs principales

```css
--nere-red: #C83530
--nere-yellow: #F9C74F
--nere-dark: #1A1A1A
--nere-brown: #4A3F35
```

### Composants

```html
<!-- Badge -->
<x-badge type="success">Actif</x-badge>
<x-badge type="danger">Urgent</x-badge>
<x-badge type="warning">En attente</x-badge>

<!-- Card -->
<x-card title="Titre">
    Contenu de la carte
</x-card>

<!-- Button -->
<x-button variant="primary">Enregistrer</x-button>
<x-button variant="secondary">Annuler</x-button>

<!-- Avatar -->
<x-avatar :user="$user" size="md" />
<x-avatar name="John Doe" size="lg" show-status status="online" />
```

## 📝 Routes API requises

Pour que les modules fonctionnent correctement, créer ces routes API :

```php
// routes/api.php

// Recherche globale
Route::get('/search/data', [SearchController::class, 'data']);

// Calendrier : déjà en place, GET /intranet/calendar/events (routes/intranet.php).
// La création / modification d'événements depuis l'interface n'existe pas encore.

// Dashboard
Route::get('/dashboard/charts', [DashboardController::class, 'chartData']);

// Quick Links
Route::post('/quick-links/reorder', [QuickLinksController::class, 'reorder']);

// Kudos
Route::post('/kudos', [KudosController::class, 'store']);
```

## 🚀 Prochaines étapes

1. Créer les controllers API
2. Créer les seeders pour les données de test
3. Implémenter les pages Blade avec ces composants
4. Tester l'intégration complète

## 📖 Documentation des bibliothèques

- **Alpine.js**: https://alpinejs.dev/
- **Fuse.js**: https://fusejs.io/
- **FullCalendar**: https://fullcalendar.io/
- **Chart.js**: https://www.chartjs.org/
- **Day.js**: https://day.js.org/
- **Toastr**: https://github.com/CodeSeven/toastr
- **Sortable.js**: https://sortablejs.github.io/Sortable/
