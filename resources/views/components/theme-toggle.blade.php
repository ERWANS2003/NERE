{{--
    Switcher thème clair / sombre.

    Design : pill avec icône soleil/lune et label visible.
    - Zéro dépendance Alpine : fonctionne aussi sur la page de connexion.
    - L'icône active et le label changent via CSS sur [data-theme], pas via JS,
      ce qui évite tout flash ou scintillement à l'hydratation.
    - NereTheme est exposé globalement pour que d'autres scripts puissent
      appeler NereTheme.apply('dark') si besoin.
--}}

<button
    type="button"
    data-theme-toggle
    aria-label="Basculer le thème clair / sombre"
    title="Changer le thème"
    class="nm-theme-pill"
>
    {{-- Icône Soleil (thème clair actif) --}}
    <span data-theme-icon="light" class="nm-theme-icon" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="4"/>
            <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
        </svg>
    </span>

    {{-- Icône Lune (thème sombre actif) --}}
    <span data-theme-icon="dark" class="nm-theme-icon" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z"/>
        </svg>
    </span>

    {{-- Label textuel --}}
    <span data-theme-label="light" class="nm-theme-label">Clair</span>
    <span data-theme-label="dark"  class="nm-theme-label">Sombre</span>
</button>

<style>
/* ---- Toggle pill ---- */
.nm-theme-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.3125rem 0.75rem;
    border-radius: 9999px;
    border: 1px solid var(--line-default);
    background-color: var(--surface-inset);
    color: var(--text-muted);
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition:
        background-color 0.2s ease,
        border-color 0.2s ease,
        color 0.2s ease,
        box-shadow 0.2s ease;
}

.nm-theme-pill:hover {
    background-color: var(--surface-raised);
    border-color: var(--line-strong);
    color: var(--text-strong);
    box-shadow: 0 1px 4px rgb(0 0 0 / 0.08);
}

.nm-theme-pill:active {
    transform: scale(0.97);
}

/* Icône */
.nm-theme-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.nm-theme-icon svg {
    width: 0.9375rem;
    height: 0.9375rem;
}

/* Label */
.nm-theme-label {
    line-height: 1;
}

/* ---- Visibilité selon le thème actif ---- */
/* En thème clair : montrer soleil + label "Clair", cacher lune + "Sombre" */
[data-theme='light'] [data-theme-icon='dark'],
[data-theme='light'] [data-theme-label='dark'] { display: none; }

/* En thème sombre : montrer lune + label "Sombre", cacher soleil + "Clair" */
[data-theme='dark'] [data-theme-icon='light'],
[data-theme='dark'] [data-theme-label='light'] { display: none; }

/* Couleur de l'icône active */
[data-theme='light'] .nm-theme-pill {
    color: var(--spark);
    border-color: rgb(196 125 7 / 0.35);
    background-color: var(--spark-soft);
}

[data-theme='dark'] .nm-theme-pill {
    color: var(--color-accent-300);
    border-color: rgb(255 210 90 / 0.3);
    background-color: var(--spark-soft);
}
</style>

@once
    @push('scripts')
        <script>
            window.NereTheme = {
                current: function () {
                    return document.documentElement.getAttribute('data-theme') || 'light';
                },
                apply: function (theme) {
                    document.documentElement.setAttribute('data-theme', theme);
                    try { localStorage.setItem('nere-theme', theme); } catch (e) {}
                    // Animer légèrement la page lors du changement
                    document.body.style.transition = 'background-color 0.25s ease, color 0.25s ease';
                    setTimeout(function () { document.body.style.transition = ''; }, 300);
                },
                toggle: function () {
                    this.apply(this.current() === 'dark' ? 'light' : 'dark');
                }
            };

            document.addEventListener('click', function (event) {
                if (event.target.closest('[data-theme-toggle]')) {
                    window.NereTheme.toggle();
                }
            });
        </script>
    @endpush
@endonce
