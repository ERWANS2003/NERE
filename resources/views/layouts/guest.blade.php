<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titre', 'Connexion') · ITSM Néré Mining</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-nere-mining.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root { --ink: #17211f; --ink-soft: #22302d; --paper: #f4f2ed; --copper: #b96b2c; --copper-light: #e2a05b; --teal: #176b67; --muted: #71807b; --line: #e0e3de; }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            height: 100%;
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--ink);
            background: var(--paper);
        }

        .ecran {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(420px, .9fr) minmax(420px, 1.1fr);
            background: var(--paper);
        }

        @media (max-width: 860px) {
            .ecran { grid-template-columns: 1fr; }
            .panneau-marque { display: none; }
        }

        /* ---------- Panneau gauche : identité / statut des sites ---------- */
        .panneau-marque {
            position: relative;
            background: var(--ink);
            padding: 3.5rem 3.25rem;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            overflow: hidden;
            border-right: 1px solid rgba(255,255,255,.1);
        }

        .panneau-marque::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 18% 15%, rgba(226,160,91,.22), transparent 42%),
                repeating-radial-gradient(circle at 78% 68%, transparent 0, transparent 26px, rgba(255,255,255,.035) 27px, transparent 28px, transparent 54px);
            background-size: 150% 150%, 135% 135%;
            animation: mouvement-fond 14s ease-in-out infinite alternate;
            pointer-events: none;
        }

        .panneau-marque::after {
            content: "";
            position: absolute;
            inset: -20%;
            background:
                radial-gradient(ellipse at 18% 30%, rgba(185,107,44,.22), transparent 30%),
                radial-gradient(ellipse at 82% 75%, rgba(23,107,103,.2), transparent 32%);
            background-size: 125% 125%, 140% 140%;
            opacity: 0.72;
            animation: halo-fond 9s ease-in-out infinite alternate;
            pointer-events: none;
        }

        @keyframes mouvement-fond {
            0% { background-position: -8% -5%, 8% 4%; }
            100% { background-position: 18% 10%, -14% -8%; }
        }

        @keyframes halo-fond {
            0% { transform: translate(-5%, -3%) scale(0.94); opacity: 0.42; }
            100% { transform: translate(6%, 5%) scale(1.08); opacity: 0.82; }
        }

        .marque {
            position: relative;
        }

        .marque img {
            max-width: 220px;
            height: auto;
            display: block;
        }

        .accroche {
            position: relative;
            max-width: 30ch;
            align-self: center;
            text-align: center;
            margin-top: auto;
            margin-bottom: auto;
        }

        .accroche h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 600;
            font-size: 2.15rem;
            line-height: 1.22;
            margin: 0 0 0.85rem;
            letter-spacing: -0.01em;
        }

        .accroche p {
            color: rgba(239,242,237,.68);
            font-size: 0.96rem;
            line-height: 1.6;
            margin: 0;
        }

        @media (prefers-reduced-motion: reduce) {
            .panneau-marque::before { animation: none; }
            .panneau-marque::after { animation: none; }
        }

        /* ---------- Panneau droit : formulaire ---------- */
        .panneau-formulaire {
            background: var(--paper);
            color: var(--ink);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.5rem;
        }

        .conteneur-formulaire {
            width: 100%;
            max-width: 430px;
        }

        .marque-mobile {
            display: none;
            margin-bottom: 2rem;
        }

        @media (max-width: 860px) {
            .marque-mobile { display: block; }
        }

        .pied-formulaire {
            margin-top: 2.25rem;
            font-size: 0.78rem;
            color: var(--muted);
            text-align: center;
        }

        .pied-formulaire a { color: var(--copper); }
    </style>
</head>
<body>
    <div class="ecran">
        <aside class="panneau-marque">
            <div class="marque">
                <img src="{{ asset('images/logo-nere-mining.png') }}" alt="Néré Mining — Mining in Burkina Faso">
            </div>

            <div class="accroche">
                <h1>Un seul point d'entrée pour signaler, suivre et résoudre.</h1>
                <p>Production, Maintenance, Géologie, RH, Finance, IT — tous les incidents et demandes du groupe centralisés au même endroit.</p>
            </div>

        </aside>

        <main class="panneau-formulaire">
            <div class="conteneur-formulaire">
                <div class="marque-mobile">
                    <img src="{{ asset('images/logo-nere-mining.png') }}" alt="Néré Mining">
                </div>

                @yield('contenu')

                <p class="pied-formulaire">
                    <a href="mailto:it-support@nere-mining.bf">it-support@nere-mining.bf</a>
                </p>
            </div>
        </main>
    </div>
</body>
</html>
