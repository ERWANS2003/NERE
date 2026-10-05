import focus from '@alpinejs/focus';

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