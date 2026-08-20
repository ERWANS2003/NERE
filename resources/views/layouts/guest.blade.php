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
        :root {
            --graphite: #1b1f23;
            --graphite-soft: #262b31;
            --graphite-line: #383f47;
            --ivoire: #f7f5f1;
            --ambre: #c8963e;
            --ambre-clair: #e0b563;
            --vert-actif: #3fa66b;
            --rouge-alerte: #d64545;
            --texte-clair: #eceae6;
            --texte-att: #a9aeb4;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            height: 100%;
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--texte-clair);
            background: var(--graphite);
        }

        .ecran {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.05fr 1fr;
        }

        @media (max-width: 860px) {
            .ecran { grid-template-columns: 1fr; }
            .panneau-marque { display: none; }
        }

        /* ---------- Panneau gauche : identité / statut des sites ---------- */
        .panneau-marque {
            position: relative;
            background: var(--graphite);
            padding: 3.5rem 3.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            border-right: 1px solid var(--graphite-line);
        }

        .panneau-marque::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 18% 15%, rgba(200,150,62,0.14), transparent 42%),
                repeating-radial-gradient(circle at 78% 68%, transparent 0, transparent 26px, rgba(255,255,255,0.028) 27px, transparent 28px, transparent 54px);
            pointer-events: none;
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
            color: var(--texte-att);
            font-size: 0.96rem;
            line-height: 1.6;
            margin: 0;
        }

        /* Flux d'état des sites — élément signature */
        .flux-sites {
            position: relative;
            border-top: 1px solid var(--graphite-line);
            padding-top: 1.4rem;
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
        }

        .flux-titre {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.68rem;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            color: var(--texte-att);
            margin-bottom: 0.2rem;
        }

        .ligne-site {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            color: var(--texte-att);
        }

        .point {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--vert-actif);
            box-shadow: 0 0 0 0 rgba(63,166,107,0.55);
            animation: pulse 2.4s ease-out infinite;
            flex-shrink: 0;
        }

        .point.alerte {
            background: var(--rouge-alerte);
            box-shadow: 0 0 0 0 rgba(214,69,69,0.55);
            animation-name: pulse-alerte;
        }

        .ligne-site .nom-site { color: var(--texte-clair); }
        .ligne-site .valeur { margin-left: auto; color: var(--ambre-clair); }

        @keyframes pulse {
            0%   { box-shadow: 0 0 0 0 rgba(63,166,107,0.5); }
            70%  { box-shadow: 0 0 0 7px rgba(63,166,107,0); }
            100% { box-shadow: 0 0 0 0 rgba(63,166,107,0); }
        }

        @keyframes pulse-alerte {
            0%   { box-shadow: 0 0 0 0 rgba(214,69,69,0.5); }
            70%  { box-shadow: 0 0 0 7px rgba(214,69,69,0); }
            100% { box-shadow: 0 0 0 0 rgba(214,69,69,0); }
        }

        @media (prefers-reduced-motion: reduce) {
            .point { animation: none; }
        }

        /* ---------- Panneau droit : formulaire ---------- */
        .panneau-formulaire {
            background: var(--ivoire);
            color: var(--graphite);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.5rem;
        }

        .conteneur-formulaire {
            width: 100%;
            max-width: 380px;
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
            color: #8a8579;
            text-align: center;
        }

        .pied-formulaire a { color: #6b6558; }
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

            <div class="flux-sites">
                <div class="flux-titre">État des sites — en direct</div>
                <div class="ligne-site">
                    <span class="point"></span>
                    <span class="nom-site">Mine de Karma</span>
                    <span class="valeur">Opérationnel</span>
                </div>
                <div class="ligne-site">
                    <span class="point"></span>
                    <span class="nom-site">Bureau de Ouagadougou</span>
                    <span class="valeur">Opérationnel</span>
                </div>
                <div class="ligne-site">
                    <span class="point alerte"></span>
                    <span class="nom-site">SLA critiques</span>
                    <span class="valeur">2 en dépassement</span>
                </div>
            </div>
        </aside>

        <main class="panneau-formulaire">
            <div class="conteneur-formulaire">
                <div class="marque-mobile">
                    <img src="{{ asset('images/logo-nere-mining.png') }}" alt="Néré Mining">
                </div>

                @yield('contenu')

                <p class="pied-formulaire">
                    Néré Mining &middot; Karma, Centre-Nord &middot; <a href="mailto:it-support@nere-mining.bf">it-support@nere-mining.bf</a>
                </p>
            </div>
        </main>
    </div>
</body>
</html>
