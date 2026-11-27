{{-- Appliqué avant les styles pour éviter un flash clair. Choix gardé dans le navigateur. --}}
<script>
    (function () {
        var root = document.documentElement;
        try {
            if (localStorage.getItem('nere-theme') === 'dark') root.classList.add('dark');
        } catch (e) {}
        window.nereToggleTheme = function () {
            root.classList.add('theme-switching');
            var dark = root.classList.toggle('dark');
            requestAnimationFrame(function () {
                requestAnimationFrame(function () { root.classList.remove('theme-switching'); });
            });
            try { localStorage.setItem('nere-theme', dark ? 'dark' : 'light'); } catch (e) {}
        };
    })();
</script>
