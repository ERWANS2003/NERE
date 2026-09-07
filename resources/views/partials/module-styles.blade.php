        @include('tickets.partials.styles')

        .module-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1rem; margin-bottom:1.25rem; }
        .module-card { background:var(--graphite-card); border:1px solid var(--graphite-line); border-radius:12px; padding:1.25rem; }
        .module-card h2 { margin:0 0 .85rem; font:600 1rem 'Space Grotesk',sans-serif; }
        .module-card h3 { margin:0 0 .45rem; font:600 1.05rem 'Space Grotesk',sans-serif; }
        .module-card p { color:var(--texte-att); line-height:1.55; margin:.35rem 0; }
        .metric { color:var(--ambre-clair); font:700 1.8rem 'Space Grotesk',sans-serif; }
        .article-list { display:grid; gap:.75rem; }
        .article-list a { display:block; padding:1rem; border:1px solid var(--graphite-line); border-radius:8px; background:var(--graphite-soft); }
        .article-list a:hover { border-color:var(--ambre); }
        .report-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1.25rem; }
        .report-card { background:var(--graphite-card); border:1px solid var(--graphite-line); border-radius:12px; padding:1.25rem; }
        .report-card.full { grid-column:1/-1; }
        .report-chart { height:260px; position:relative; }
        @media (max-width:800px) { .report-grid { grid-template-columns:1fr; } .report-card.full { grid-column:auto; } }
