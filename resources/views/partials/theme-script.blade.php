{{-- Appliqué avant les styles pour éviter un flash clair. Choix gardé dans le navigateur. --}}
<script>
    (function () {
        var root = document.documentElement;
        try {
            if (localStorage.getItem('nere-theme') === 'dark') root.classList.add('dark');
        } catch (e) {}

        function apply() {
            root.classList.add('theme-switching');
            var dark = root.classList.toggle('dark');
            try { localStorage.setItem('nere-theme', dark ? 'dark' : 'light'); } catch (e) {}
            return dark;
        }

        function release() {
            requestAnimationFrame(function () {
                requestAnimationFrame(function () { root.classList.remove('theme-switching'); });
            });
        }

        var DURATION = 1400;

        /* Avancée de la vague : lente au départ, rapide au milieu, douce à l'arrivée. */
        function ease(t) { return -(Math.cos(Math.PI * t) - 1) / 2; }

        /*
         * Images clés de la vague qui descend et engloutit l'écran : une crête
         * double sinusoïde dont la houle grossit à mi-course et qui roule
         * latéralement pendant qu'elle avance.
         */
        function wave(w, h) {
            var clip = [], crest = [], steps = 48, cols = 36;
            var length = Math.max(w / 2.2, 260);
            for (var i = 0; i <= steps; i++) {
                var p = ease(i / steps);
                var swell = 10 + (22 + h * 0.035) * Math.sin(Math.PI * p);
                var base = -3 * swell + p * (h + 6 * swell);
                var phase = p * Math.PI * 3.4;
                var pts = [], line = [];
                for (var c = 0; c <= cols; c++) {
                    var x = w * c / cols;
                    var y = base + swell * Math.sin(2 * Math.PI * x / length + phase)
                        + swell * 0.45 * Math.sin(2 * Math.PI * x / (length * 0.53) - phase * 1.7);
                    pts.push(x.toFixed(1) + 'px ' + y.toFixed(1) + 'px');
                    line.push((c ? 'L' : 'M') + x.toFixed(1) + ' ' + y.toFixed(1));
                }
                clip.push('polygon(0px -100px, ' + w + 'px -100px, ' + pts.reverse().join(', ') + ')');
                crest.push('path("' + line.join(' ') + '")');
            }
            return { clip: clip, crest: crest };
        }

        /* Crête lumineuse qui suit le bord de la vague. */
        function crest(dark, shape) {
            var ns = 'http://www.w3.org/2000/svg';
            var fx = document.createElement('div');
            fx.className = 'theme-fx ' + (dark ? 'is-night' : 'is-day');
            var svg = document.createElementNS(ns, 'svg');
            svg.setAttribute('class', 'theme-fx-crest');
            svg.setAttribute('viewBox', '0 0 ' + innerWidth + ' ' + innerHeight);
            ['halo', 'foam', 'line'].forEach(function (kind) {
                var path = document.createElementNS(ns, 'path');
                path.setAttribute('class', 'theme-fx-' + kind);
                svg.appendChild(path);
                if (path.animate) {
                    try { path.animate({ d: shape.crest }, { duration: DURATION, fill: 'forwards' }); } catch (e) {}
                }
            });
            fx.appendChild(svg);
            document.body.appendChild(fx);
            setTimeout(function () { fx.remove(); }, DURATION + 500);
        }

        window.nereToggleTheme = function (event) {
            var button = event && event.currentTarget;
            var still = matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (!button || !document.startViewTransition && still) { apply(); release(); return; }
            /* Animations réduites (réglage Windows) : simple fondu, sans mouvement. */
            if (still) {
                document.startViewTransition(apply).finished.then(release, release);
                return;
            }

            var dark = !root.classList.contains('dark');
            var shape = wave(innerWidth, innerHeight);

            if (!document.startViewTransition) {
                apply(); release(); crest(dark, shape);
                return;
            }

            var transition = document.startViewTransition(function () {
                apply();
                crest(dark, shape);
            });
            transition.ready.then(function () {
                root.animate(
                    { clipPath: shape.clip },
                    { duration: DURATION, pseudoElement: '::view-transition-new(root)' }
                );
            });
            transition.finished.then(release, release);
        };
    })();
</script>
