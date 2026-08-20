        .page-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.6rem 1rem;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: background .15s, color .15s, border-color .15s;
            text-decoration: none;
        }

        .btn-primaire {
            background: var(--ambre);
            color: var(--graphite);
        }

        .btn-primaire:hover { background: var(--ambre-clair); }

        .btn-secondaire {
            background: transparent;
            color: var(--texte-clair);
            border: 1px solid var(--graphite-line);
        }

        .btn-secondaire:hover {
            border-color: var(--ambre);
            color: var(--ambre-clair);
        }

        .btn-danger {
            background: rgba(214,69,69,0.15);
            color: #f0a0a0;
            border: 1px solid rgba(214,69,69,0.35);
        }

        .btn-danger:hover { background: rgba(214,69,69,0.25); }

        .btn-sm { padding: 0.4rem 0.7rem; font-size: 0.78rem; }

        .carte {
            background: var(--graphite-card);
            border: 1px solid var(--graphite-line);
            border-radius: 12px;
            padding: 1.25rem;
        }

        .filtres {
            display: grid;
            grid-template-columns: 2fr repeat(4, 1fr) auto;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            align-items: end;
        }

        @media (max-width: 1100px) {
            .filtres { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 600px) {
            .filtres { grid-template-columns: 1fr; }
        }

        .champ label {
            display: block;
            font-size: 0.75rem;
            color: var(--texte-att);
            margin-bottom: 0.35rem;
        }

        .champ input,
        .champ select,
        .champ textarea {
            width: 100%;
            padding: 0.62rem 0.75rem;
            border: 1px solid var(--graphite-line);
            border-radius: 8px;
            background: var(--graphite-soft);
            color: var(--texte-clair);
            font-family: 'Inter', sans-serif;
            font-size: 0.88rem;
            outline: none;
        }

        .champ input:focus,
        .champ select:focus,
        .champ textarea:focus {
            border-color: var(--ambre);
        }

        .champ textarea { min-height: 120px; resize: vertical; }

        .table-wrap { overflow-x: auto; }

        table.tickets {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        table.tickets th {
            text-align: left;
            padding: 0.75rem 1rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.65rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--texte-att);
            border-bottom: 1px solid var(--graphite-line);
        }

        table.tickets td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--graphite-line);
            vertical-align: middle;
        }

        table.tickets tr:hover td { background: rgba(255,255,255,0.02); }

        table.tickets tr:last-child td { border-bottom: none; }

        .ref {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.78rem;
            color: var(--ambre-clair);
        }

        .badge {
            display: inline-block;
            padding: 0.22rem 0.55rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .badge-sla {
            background: rgba(214,69,69,0.15);
            color: #f0a0a0;
            border: 1px solid rgba(214,69,69,0.3);
        }

        .vide {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--texte-att);
        }

        .vide p { margin: 0.5rem 0 1.25rem; }

        .pagination {
            display: flex;
            gap: 0.35rem;
            justify-content: center;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            padding: 0.45rem 0.75rem;
            border-radius: 6px;
            font-size: 0.82rem;
            border: 1px solid var(--graphite-line);
            color: var(--texte-att);
        }

        .pagination a:hover {
            border-color: var(--ambre);
            color: var(--ambre-clair);
        }

        .pagination .active span {
            background: rgba(200,150,62,0.15);
            border-color: var(--ambre);
            color: var(--ambre-clair);
        }

        .grille-2 {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 1.25rem;
            align-items: start;
        }

        @media (max-width: 960px) {
            .grille-2 { grid-template-columns: 1fr; }
        }

        .meta-liste {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .meta-liste li {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.55rem 0;
            border-bottom: 1px solid var(--graphite-line);
            font-size: 0.84rem;
        }

        .meta-liste li:last-child { border-bottom: none; }
        .meta-liste .label { color: var(--texte-att); flex-shrink: 0; }

        .section-titre {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            margin: 0 0 1rem;
        }

        .commentaire {
            padding: 0.85rem 0;
            border-bottom: 1px solid var(--graphite-line);
        }

        .commentaire:last-child { border-bottom: none; }

        .commentaire-entete {
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.4rem;
            font-size: 0.8rem;
        }

        .commentaire-auteur { font-weight: 500; }
        .commentaire-date { color: var(--texte-att); }

        .commentaire-interne {
            background: rgba(200,150,62,0.08);
            border-left: 3px solid var(--ambre);
            padding-left: 0.75rem;
            margin-left: -0.75rem;
        }

        .historique-item {
            display: flex;
            gap: 0.75rem;
            padding: 0.65rem 0;
            border-bottom: 1px solid var(--graphite-line);
            font-size: 0.82rem;
        }

        .historique-item:last-child { border-bottom: none; }

        .historique-point {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--ambre);
            margin-top: 0.35rem;
            flex-shrink: 0;
        }

        .pj-liste { list-style: none; margin: 0; padding: 0; }

        .pj-liste li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.55rem 0;
            border-bottom: 1px solid var(--graphite-line);
            font-size: 0.84rem;
        }

        .pj-liste li:last-child { border-bottom: none; }

        .pj-liste a { color: var(--ambre-clair); }

        .suggestions {
            margin-top: 0.75rem;
            padding: 0.75rem;
            background: rgba(74,144,217,0.08);
            border: 1px solid rgba(74,144,217,0.25);
            border-radius: 8px;
            display: none;
        }

        .suggestions.visible { display: block; }

        .suggestions h4 {
            margin: 0 0 0.5rem;
            font-size: 0.8rem;
            color: var(--bleu);
        }

        .suggestions ul {
            margin: 0;
            padding-left: 1.1rem;
            font-size: 0.82rem;
        }

        .suggestions a { color: var(--ambre-clair); }

        .form-grille {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 700px) {
            .form-grille { grid-template-columns: 1fr; }
        }

        .form-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.25rem;
            flex-wrap: wrap;
        }

        .description {
            white-space: pre-wrap;
            line-height: 1.6;
            color: var(--texte-clair);
            font-size: 0.9rem;
        }

        .alerte-form {
            background: rgba(214,69,69,0.12);
            border: 1px solid rgba(214,69,69,0.3);
            color: #f0a0a0;
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .alerte-form ul { margin: 0; padding-left: 1.2rem; }
