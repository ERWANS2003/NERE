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

        function el(cls, css) {
            var d = document.createElement('span');
            d.className = cls;
            if (css) d.style.cssText = css;
            return d;
        }

        /* Décor éphémère : la nuit allume des étoiles, le jour lance des rayons. */
        function effects(dark, x, y, r) {
            var fx = el('theme-fx ' + (dark ? 'is-night' : 'is-day'), '--x:' + x + 'px;--y:' + y + 'px;--r:' + r + 'px');
            fx.appendChild(el('theme-fx-ring'));
            if (dark) {
                var w = innerWidth, h = innerHeight;
                for (var i = 0; i < 34; i++) {
                    var sx = Math.random() * w, sy = Math.random() * h * 0.85;
                    var dist = Math.hypot(sx - x, sy - y) / r;
                    var size = 2 + Math.random() * 4;
                    fx.appendChild(el('theme-fx-star', 'left:' + sx + 'px;top:' + sy + 'px;width:' + size + 'px;height:' + size + 'px;animation-delay:' + Math.round(dist * 620 + Math.random() * 180) + 'ms'));
                }
                fx.appendChild(el('theme-fx-comet'));
            } else {
                fx.appendChild(el('theme-fx-glow'));
                fx.appendChild(el('theme-fx-rays'));
            }
            document.body.appendChild(fx);
            setTimeout(function () { fx.remove(); }, 2200);
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

            var box = button.getBoundingClientRect();
            var x = box.left + box.width / 2, y = box.top + box.height / 2;
            var r = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));
            var dark = !root.classList.contains('dark');

            button.classList.remove('is-turning');
            void button.offsetWidth;
            button.classList.add('is-turning');

            if (!document.startViewTransition) {
                apply(); release(); effects(dark, x, y, r);
                return;
            }

            var transition = document.startViewTransition(function () {
                apply();
                effects(dark, x, y, r);
            });
            transition.ready.then(function () {
                root.animate(
                    { clipPath: ['circle(0px at ' + x + 'px ' + y + 'px)', 'circle(' + r + 'px at ' + x + 'px ' + y + 'px)'] },
                    { duration: 820, easing: 'cubic-bezier(0.65, 0, 0.35, 1)', pseudoElement: '::view-transition-new(root)' }
                );
            });
            transition.finished.then(release, release);
        };
    })();
</script>
