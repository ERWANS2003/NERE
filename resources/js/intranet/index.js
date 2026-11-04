/**
 * Point d'entrée léger des modules intranet.
 * Les bibliothèques lourdes du calendrier et des graphiques sont chargées
 * uniquement lorsqu'un composant Alpine les demande.
 */

import { initGlobalSearch } from './search.js';
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
    downloadFile,
} from './utils.js';

function lazyAlpineComponent(loader, exportName, args = []) {
    return {
        loading: true,
        loadError: false,
        async init() {
            try {
                const module = await loader();
                const component = module[exportName](...args);
                const initialize = component.init;
                Object.assign(this, component);
                if (typeof initialize === 'function') {
                    await initialize.call(this);
                }
            } catch (error) {
                this.loading = false;
                this.loadError = true;
                console.error(`Impossible de charger ${exportName}`, error);
            }
        },
    };
}

export function calendarComponent(config = {}) {
    return lazyAlpineComponent(() => import('./calendar.js'), 'calendarComponent', [config]);
}

export function dashboardChartsComponent() {
    return lazyAlpineComponent(() => import('./charts.js'), 'dashboardChartsComponent');
}

export async function initCalendar(...args) {
    const module = await import('./calendar.js');
    return module.initCalendar(...args);
}

export async function createLineChart(...args) {
    const module = await import('./charts.js');
    return module.createLineChart(...args);
}

export async function createBarChart(...args) {
    const module = await import('./charts.js');
    return module.createBarChart(...args);
}

export async function createDoughnutChart(...args) {
    const module = await import('./charts.js');
    return module.createDoughnutChart(...args);
}

export {
    initGlobalSearch,
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
    downloadFile,
};

window.IntranetModules = {
    initGlobalSearch,
    calendarComponent,
    dashboardChartsComponent,
    quickLinksComponent,
    kudosComponent,
};
