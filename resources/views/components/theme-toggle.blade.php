{{--
    Light/dark switch.

    Deliberately dependency-free: the login page has no Alpine bundle, and a
    theme switch that only works on authenticated pages is a theme switch that
    visibly does nothing on the screen users see first.

    The icons are toggled with CSS on [data-theme] so there is no flash and no
    hydration, and the click handler is registered once per page.
--}}
<button type="button" data-theme-toggle class="nm-btn nm-btn-ghost nm-btn-sm !px-2"
        aria-label="Basculer le thème clair / sombre" title="Thème clair / sombre">
    <svg data-theme-icon="light" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"
         class="h-4 w-4" aria-hidden="true">
        <circle cx="12" cy="12" r="4"/>
        <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
    </svg>

    <svg data-theme-icon="dark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"
         class="h-4 w-4" aria-hidden="true">
        <path d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z"/>
    </svg>
</button>

<style>
    /* Show the moon while light is active, the sun while dark is. */
    [data-theme='light'] [data-theme-icon='dark'] { display: none; }
    [data-theme='dark'] [data-theme-icon='light'] { display: none; }
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
