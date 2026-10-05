import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

// Livewire inclut Alpine, mais pas le plugin Focus. Sans lui, `x-trap` des
// modales ne fonctionne pas : la tabulation sort de la modale vers la page
// derriere, ce qui rend le formulaire invisible pour les lecteurs d'ecran.
window.Alpine = Alpine;

Alpine.plugin(focus);

Alpine.start();