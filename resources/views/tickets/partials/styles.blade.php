<style>
    /* Page Actions Header */
    .page-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        animation: fadeInUp 0.6s ease-out;
    }

    /* Buttons - Using Light Blue Style */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.6rem 1rem;
        border-radius: 8px;
        font-family: var(--font-sans);
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        border: none;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-primaire {
        background: linear-gradient(135deg, #ffd700 0%, #e5c100 100%);
        color: #0d0d0d;
        box-shadow: 0 2px 4px rgba(255, 215, 0, 0.2);
    }

    .btn-primaire:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(255, 215, 0, 0.3);
    }

    .btn-secondaire {
        background: rgba(0, 0, 0, 0.3);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.15);
    }

    .btn-secondaire:hover {
        border-color: rgba(255, 215, 0, 0.4);
        background: rgba(255, 215, 0, 0.1);
        color: #ffd700;
    }

    .btn-danger {
        background: rgba(211, 47, 47, 0.15);
        color: #ff6b6b;
        border: 1px solid rgba(211, 47, 47, 0.3);
    }

    .btn-danger:hover {
        background: rgba(211, 47, 47, 0.25);
        border-color: rgba(211, 47, 47, 0.5);
    }

    .btn-sm {
        padding: 0.4rem 0.7rem;
        font-size: 0.78rem;
    }

    /* Widget Cards */
    .carte {
        background: rgba(0, 0, 0, 0.24);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        animation: fadeInUp 0.6s ease-out;
    }

    /* Filters */
    .filtres {
        display: grid;
        grid-template-columns: 2fr repeat(4, 1fr) auto;
        gap: 0.75rem;
        margin-bottom: 1.25rem;
        align-items: end;
    }

    @media (max-width: 1100px) {
        .filtres {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 600px) {
        .filtres {
            grid-template-columns: 1fr;
        }
    }

    /* Form Elements */
    .champ label {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .champ input,
    .champ select,
    .champ textarea {
        width: 100%;
        padding: 0.625rem 0.75rem;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 8px;
        background: rgba(0, 0, 0, 0.3);
        color: #fff;
        font-family: var(--font-sans);
        font-size: 0.875rem;
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .champ input:focus,
    .champ select:focus,
    .champ textarea:focus {
        border-color: rgba(255, 215, 0, 0.4);
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
    }

    .champ input::placeholder,
    .champ textarea::placeholder {
        color: rgba(255, 255, 255, 0.4);
    }

    .champ textarea {
        min-height: 120px;
        resize: vertical;
        line-height: 1.6;
    }

    /* Tables */
    .table-wrap {
        overflow-x: auto;
    }

    table.tickets {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }

    table.tickets th {
        text-align: left;
        padding: 0.75rem 1rem;
        font-family: var(--font-sans);
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #ffd700;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    table.tickets td {
        padding: 0.875rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        vertical-align: middle;
        color: rgba(255, 255, 255, 0.9);
    }

    table.tickets tr:hover td {
        background: rgba(255, 215, 0, 0.05);
    }

    table.tickets tr:last-child td {
        border-bottom: none;
    }

    /* Reference & Badges */
    .ref {
        font-family: var(--font-sans), monospace;
        font-size: 0.8rem;
        font-weight: 700;
        color: #ffd700;
    }

    .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .badge-sla {
        background: rgba(211, 47, 47, 0.15);
        color: #ff6b6b;
        border: 1px solid rgba(211, 47, 47, 0.3);
    }

    /* Empty State */
    .vide {
        text-align: center;
        padding: 3rem 1rem;
        color: rgba(255, 255, 255, 0.6);
    }

    .vide p {
        margin: 0.5rem 0 1.25rem;
        color: rgba(255, 255, 255, 0.5);
    }

    /* Pagination */
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
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.7);
        transition: all 0.2s ease;
    }

    .pagination a:hover {
        border-color: rgba(255, 215, 0, 0.4);
        color: #ffd700;
        background: rgba(255, 215, 0, 0.05);
    }

    .pagination .active {
        background: rgba(255, 215, 0, 0.15);
        border-color: rgba(255, 215, 0, 0.4);
        color: #ffd700;
        font-weight: 600;
    }

    /* Layout Grid */
    .grille-2 {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 1.25rem;
        align-items: start;
    }

    @media (max-width: 960px) {
        .grille-2 {
            grid-template-columns: 1fr;
        }
    }

    /* Meta List */
    .meta-liste {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .meta-liste li {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.625rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        font-size: 0.85rem;
    }

    .meta-liste li:last-child {
        border-bottom: none;
    }

    .meta-liste .label {
        color: rgba(255, 255, 255, 0.6);
        flex-shrink: 0;
    }

    .meta-liste span:last-child {
        color: #fff;
        font-weight: 500;
    }

    /* Section Title */
    .section-titre {
        font-family: var(--font-sans);
        font-size: 1rem;
        font-weight: 600;
        margin: 0 0 1rem;
        color: #fff;
        letter-spacing: -0.01em;
    }

    /* Comments */
    .commentaire {
        padding: 1rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .commentaire:last-child {
        border-bottom: none;
    }

    .commentaire-entete {
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
    }

    .commentaire-auteur {
        font-weight: 600;
        color: #fff;
    }

    .commentaire-date {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.8rem;
    }

    .commentaire-interne {
        background: rgba(255, 215, 0, 0.08);
        border-left: 3px solid #ffd700;
        padding: 0.75rem 0.75rem 0.75rem 1rem;
        margin-left: -1rem;
        border-radius: 0 4px 4px 0;
    }

    /* History */
    .historique-item {
        display: flex;
        gap: 0.75rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        font-size: 0.85rem;
    }

    .historique-item:last-child {
        border-bottom: none;
    }

    .historique-point {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #ffd700;
        margin-top: 0.4rem;
        flex-shrink: 0;
        box-shadow: 0 0 4px rgba(255, 215, 0, 0.5);
    }

    .historique-item div {
        color: rgba(255, 255, 255, 0.8);
    }

    .historique-item small {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.75rem;
    }

    /* Attachments */
    .pj-liste {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .pj-liste li {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.625rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        font-size: 0.85rem;
    }

    .pj-liste li:last-child {
        border-bottom: none;
    }

    .pj-liste a {
        color: #ffd700;
        text-decoration: none;
        transition: opacity 0.2s ease;
    }

    .pj-liste a:hover {
        opacity: 0.8;
    }

    /* Suggestions */
    .suggestions {
        margin-top: 0.75rem;
        padding: 1rem;
        background: rgba(66, 165, 245, 0.1);
        border: 1px solid rgba(66, 165, 245, 0.25);
        border-radius: 8px;
        display: none;
    }

    .suggestions.visible {
        display: block;
    }

    .suggestions h4 {
        margin: 0 0 0.75rem;
        font-size: 0.85rem;
        color: #42a5f5;
        font-weight: 600;
    }

    .suggestions ul {
        margin: 0;
        padding-left: 1.25rem;
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.8);
    }

    .suggestions a {
        color: #ffd700;
    }

    /* Form Grid */
    .form-grille {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    @media (max-width: 700px) {
        .form-grille {
            grid-template-columns: 1fr;
        }
    }

    /* Form Actions */
    .form-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }

    /* Description */
    .description {
        white-space: pre-wrap;
        line-height: 1.65;
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.9rem;
    }

    /* Alert Form */
    .alerte-form {
        background: rgba(211, 47, 47, 0.12);
        border: 1px solid rgba(211, 47, 47, 0.3);
        color: #ff6b6b;
        padding: 1rem;
        border-radius: 8px;
        font-size: 0.85rem;
        margin-bottom: 1rem;
    }

    .alerte-form ul {
        margin: 0.5rem 0 0;
        padding-left: 1.25rem;
    }

    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Additional Responsive */
    @media (max-width: 640px) {
        .page-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .page-actions > div {
            width: 100%;
        }
    }
</style>
