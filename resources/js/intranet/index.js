/**
 * Point d'entrée pour tous les modules intranet
 */

import { initGlobalSearch } from './search.js';
import { initCalendar, calendarComponent } from './calendar.js';
import { 
    createLineChart, 
    createBarChart, 
    createDoughnutChart,
    dashboardChartsComponent 
} from './charts.js';
import {
    formatDate,
    formatRelativeDate,
    showSuccessNotification,
    showErrorNotification,
    showInfoNotification,
    showWarningNotification,
    initSortable,
    quickLinksComponent,
    kudosComponent,
    debounce,
    copyToClipboard,
    downloadFile
} from './utils.js';

// Exporter tous les modules
export {
    // Search
    initGlobalSearch,
    
    // Calendar
    initCalendar,
    calendarComponent,
    
    // Charts
    createLineChart,
    createBarChart,
    createDoughnutChart,
    dashboardChartsComponent,
    
    // Utils
    formatDate,
    formatRelativeDate,
    showSuccessNotification,
    showErrorNotification,
    showInfoNotification,
    showWarningNotification,
    initSortable,
    quickLinksComponent,
    kudosComponent,
    debounce,
    copyToClipboard,
    downloadFile
};

// Rendre disponibles globalement pour Alpine.js
window.IntranetModules = {
    initGlobalSearch,
    calendarComponent,
    dashboardChartsComponent,
    quickLinksComponent,
    kudosComponent
};
