# 🗺️ ROADMAP COMPLÈTE - INTRANET NÉRÉ MINING

## 📊 VUE D'ENSEMBLE

**Durée totale estimée** : 16-20 semaines (4-5 mois)
**Équipe recommandée** : 1-2 développeurs fullstack
**Méthodologie** : Sprints de 2 semaines avec démos

---

## 🎯 OBJECTIFS PAR PHASE

| Phase | Objectif | Durée | Livrables |
|-------|----------|-------|-----------|
| **Phase 0** | Setup & Fondations | 1 semaine | Base technique prête |
| **Phase 1** | MVP Fonctionnel | 6 semaines | Dashboard + Navigation + Annuaire |
| **Phase 2** | Fonctionnalités Avancées | 6 semaines | Départements + Analytics + Kudos |
| **Phase 3** | Modules RH & Optimisation | 6 semaines | Time Off + Ressources + Performance |
| **Phase 4** | Polish & Déploiement | 1-2 semaines | Production ready |

---

# 📅 PHASE 0 - SETUP & FONDATIONS (1 semaine)

## 🎯 Objectif
Préparer l'environnement technique et la base de données pour toutes les fonctionnalités futures.

---

## ✅ Sprint 0 - Semaine 1 : Infrastructure

### 🗄️ **Jour 1-2 : Base de données**

#### Migrations à créer
```bash
# Annonces
php artisan make:migration create_announcements_table ✅

# Calendrier
php artisan make:migration create_calendar_events_table ✅

# Reconnaissance (Kudos)
php artisan make:migration create_kudos_table ✅

# Services par département
php artisan make:migration create_department_services_table ✅

# Formulaires département
php artisan make:migration create_department_forms_table ✅
php artisan make:migration create_form_submissions_table ✅

# Analytics
php artisan make:migration create_analytics_metrics_table

# Time Off (Congés) - pour plus tard
php artisan make:migration create_time_off_policies_table ✅
php artisan make:migration create_time_off_balances_table ✅
php artisan make:migration create_time_off_requests_table ✅

# Quick Links
php artisan make:migration create_user_quick_links_table ✅

# Resources
php artisan make:migration create_resources_table ✅
php artisan make:migration create_resource_categories_table ✅

# Organigramme
php artisan make:migration add_organizational_fields_to_users_table ✅
```

#### Modèles Eloquent à créer
```bash
php artisan make:model Announcement ✅
php artisan make:model CalendarEvent ✅
php artisan make:model Kudo ✅
php artisan make:model DepartmentService ✅
php artisan make:model DepartmentForm ✅
php artisan make:model FormSubmission ✅
php artisan make:model UserQuickLink ✅
php artisan make:model Resource ✅
php artisan make:model ResourceCategory ✅
php artisan make:model TimeOffPolicy ✅
php artisan make:model TimeOffBalance ✅
php artisan make:model TimeOffRequest ✅
```

### 🎨 **Jour 3 : Assets & Design System**

#### Tâches
- [x] Créer fichier `public/css/nere-design-system.css`
- [x] Variables CSS (couleurs NERE, spacing, shadows)
- [x] Copier illustrations département vers `public/images/departments/`
- [x] Optimiser images (compression)
- [x] Créer composants Blade de base :
  - [x] `components/badge.blade.php`
  - [x] `components/card.blade.php`
  - [x] `components/button.blade.php`
  - [x] `components/avatar.blade.php`

### 📦 **Jour 4 : Bibliothèques JavaScript**

#### Installation NPM
```bash
# Core
npm install alpinejs --save ✅

# Recherche
npm install fuse.js --save ✅

# Calendrier
npm install @fullcalendar/core @fullcalendar/daygrid --save ✅

# Charts
npm install chart.js --save ✅

# Utilitaires
npm install dayjs --save ✅

# Notifications
npm install toastr --save ✅

# Drag & Drop (optionnel)
npm install sortablejs --save ✅

# Build
npm run build ✅
```

#### Modules JavaScript créés
- [x] `resources/js/intranet/search.js` - Recherche globale Cmd+K avec Fuse.js
- [x] `resources/js/intranet/calendar.js` - Gestion calendrier avec FullCalendar
- [x] `resources/js/intranet/charts.js` - Graphiques dashboard avec Chart.js
- [x] `resources/js/intranet/utils.js` - Utilitaires (dates, notifications, drag-drop)
- [x] `resources/js/intranet/index.js` - Export de tous les modules
- [x] `resources/js/app.js` - Configuration et imports globaux
- [x] `public/css/fullcalendar-custom.css` - Styles personnalisés FullCalendar

### 🔧 **Jour 5 : Configuration & Seeders**

#### Seeders à créer
```bash
php artisan make:seeder AnnouncementSeeder ✅
php artisan make:seeder CalendarEventSeeder ✅
php artisan make:seeder DepartmentServiceSeeder ✅
php artisan make:seeder ResourceSeeder ✅
php artisan db:seed ✅
```

#### Configuration
- [x] Mettre à jour `.env` (locale fr)
- [x] Créer fichier `config/intranet.php` (settings custom)
- [x] Supprimer la feature Kudos (migration + modèle)

#### Données créées
- [x] 8 annonces réalistes pour NÉRÉ Mining
- [x] 11 événements calendrier (réunions, formations, audits)
- [x] 24 services départements (IT, RH, HSE, SCM, etc.)
- [x] 5 catégories de ressources
- [x] 9 ressources (politiques, formulaires, formations, modèles)

---

### 📋 **Checklist Phase 0**
- [ ] Toutes les migrations créées et testées
- [ ] Tous les modèles créés avec relations
- [ ] Design system CSS prêt
- [ ] Bibliothèques JS installées et compilées
- [ ] Seeders créés et exécutés
- [ ] Documentation technique de base

---

# 🚀 PHASE 1 - MVP FONCTIONNEL (6 semaines)

## 🎯 Objectif
Créer le cœur de l'intranet : navigation, dashboard, annuaire, et pages de base.

---

## ✅ Sprint 1 - Semaines 2-3 : Layout Global & Dashboard

### 🎨 **Semaine 2 : Layout Intranet**

#### Jour 1-2 : Sidebar Navigation
**Fichiers à créer/modifier** :
- [x] `resources/views/intranet/layouts/app.blade.php` (layout principal)
- [x] `resources/views/intranet/partials/sidebar.blade.php`
- [x] `resources/views/intranet/partials/header.blade.php`
- [x] `resources/views/intranet/partials/search-modal.blade.php`
- [x] `resources/views/components/intranet-layout.blade.php`
- [x] `resources/views/intranet/dashboard.blade.php`
- [x] `routes/intranet.php`

**Fonctionnalités** :
- [x] Logo NERE Mining
- [x] Menu navigation (8 items)
  - Accueil
  - Départements
  - Annuaire
  - Organigramme
  - Calendrier
  - Annonces
  - Mes tickets
  - Ressources
- [x] Footer sidebar (Karma · Burkina Faso / Support IT)
- [x] Highlighting item actif
- [x] Header avec recherche Cmd+K
- [x] Avatar utilisateur avec dropdown
- [x] Date du jour (dynamique)
- [x] Modal de recherche globale
- [x] Responsive (mobile + tablet + desktop)

#### Jour 3-4 : Header Global
**Fonctionnalités** :
- [x] Avatar utilisateur avec initiales
- [x] Nom utilisateur
- [x] Barre recherche avec hint "Ctrl K"
- [x] Date du jour (dynamique)
- [x] Bouton déconnexion
- [x] Dropdown profil

**Pages principales créées** :
- [x] `resources/views/intranet/departments/index.blade.php` - Liste départements
- [x] `resources/views/intranet/departments/show.blade.php` - Détail département
- [x] `resources/views/intranet/announcements/index.blade.php` - Liste annonces
- [x] `resources/views/intranet/announcements/show.blade.php` - Détail annonce
- [x] `resources/views/intranet/directory.blade.php` - Annuaire employés
- [x] `resources/views/intranet/calendar.blade.php` - Calendrier événements
- [x] `resources/views/intranet/resources/index.blade.php` - Bibliothèque ressources

#### Jour 5 : Responsive & Tests
- [x] Tests mobile (sidebar collapse via Alpine `sidebarOpen`)
- [x] Tests tablette (grilles `md:`)
- [x] Tests desktop (grilles `lg:` / `xl:`)
- [x] Ajustements CSS
- [x] Rendu serveur validé des 8 vues intranet (aucune erreur Blade/lazy-load)

**Corrections de bugs** :
- [x] `User::tickets()` → remplacé par `User::formSubmissions()` (pas de modèle Ticket)
- [x] Dashboard : compteur "Mes Demandes" via `formSubmissions()`
- [x] Routes `tickets.*` → `intranet.submissions.*` / `intranet.forms.*` (sidebar, header, dashboard)
- [x] Fusion des routes intranet préexistantes (forms/submissions/departments CRUD) avec les nouvelles
- [x] CRUD départements déplacé sous `intranet.departments.manage.*` (vues `departments/manage/`)
- [x] Vitrine départements par code sur `intranet.departments.show`
- [x] Ajout relations `services()`, `forms()`, `announcements()` sur `Department`
- [x] Routes profil ajoutées (`profile.edit`, `profil.edit`, `profil.show`, `profil.update`)
- [x] Eager loading ajouté (annonces + annuaire) pour le mode lazy-loading strict

### 🏠 **Semaine 3 : Dashboard (Page Accueil)**

#### Jour 1 : Banner Bienvenue
**Fichier** : `resources/views/intranet/dashboard/index.blade.php`
- [x] Banner rouge avec message personnalisé
- [x] Greeting basé sur heure du jour (Bonjour avant 18h, Bonsoir après)
- [x] Sous-titre explicatif + date du jour en français

#### Jour 2-3 : Grid Départements
**Contrôleur** : `app/Http/Controllers/Intranet/DashboardController.php`
- [x] Charger les 9 départements (`withCount('services')`)
- [x] Grid responsive 3 colonnes → 2 → 1
- [x] Cards avec :
  - [x] Illustration background (fallback icône si manquante)
  - [x] Tag jaune/or (catégorie)
  - [x] Titre département
  - [x] Chevron + hover effect (scale image, border nere)
  - [x] Compteur de services
  - [x] Link vers page département (par code)

#### Jour 4 : Sidebar Widgets
**Composants** :
- [x] Widget Annonces (3 dernières, bordure colorée selon priorité)
- [x] Widget Agenda (4 prochains événements avec date + heure)
- [x] ~~Widget Reconnaissance (kudos)~~ — RETIRÉ (feature Kudos supprimée)
- [x] Bonus : widget Accès rapides

#### Jour 5 : Section Mes Demandes (ex-Tickets)
- [x] Tableau 5 dernières demandes (FormSubmission, pas de système de tickets)
- [x] Colonnes : Réf, Formulaire, Département, Statut, Date
- [x] Badges statut colorés (pending/approved/rejected)
- [x] Lien "Tout voir" vers les soumissions
- [x] Empty state avec CTA nouvelle demande

**Consolidation & Tests** :
- [x] Dashboard unique : `DashboardController@index` → `intranet.dashboard.index`
- [x] `/intranet` et `/intranet/dashboard` pointent vers le contrôleur
- [x] Suppression vue redondante + 5 partiels orphelins
- [x] Vérifier données dynamiques (rendu serveur validé, 64 Ko HTML)
- [x] Responsive (grid `sm:`/`lg:`/`xl:`, table scroll-x mobile)
- [x] Build Vite OK

---

## ✅ Sprint 2 - Semaines 4-5 : Annuaire & Recherche

### 👥 **Semaine 4 : Annuaire Employés**

#### Jour 1-2 : Page Annuaire
**Fichier** : `resources/views/intranet/directory/index.blade.php`
**Contrôleur** : `app/Http/Controllers/Intranet/DirectoryController.php`

**Fonctionnalités** :
- [x] Fil d'Ariane
- [x] Barre recherche locale (nom, email, poste)
- [x] Filtres département (pills, cumulables avec la recherche)
- [x] Grid cards employés (responsive 1→2→3→4 colonnes)
- [x] Pagination (20 par page, query string conservée)

#### Jour 3 : Card Employé
**Composant** : `resources/views/components/employee-card.blade.php`
- [x] Avatar initiales (accesseur `initials` + composant `x-avatar`)
- [x] Nom et poste (accesseur `job_title` : position_title ou poste)
- [x] Badge département (code)
- [x] Email (mailto) et téléphone (tel)
- [x] Hover effect (shadow + bordure nere)

#### Jour 4-5 : Recherche & Filtres
- [x] Recherche serveur (ILIKE PostgreSQL sur nom/email/poste)
- [x] Filtres département (URL params, pills actives)
- [x] Tri alphabétique (orderBy name)
- [x] Testé : 21 employés, recherche + filtre + combinaison validés
- [x] Seeder `DemoEmployeesSeeder` (20 employés sur les 9 départements)

**Corrections / ajouts** :
- [x] Route `intranet.directory` → `DirectoryController@index` (closure retirée)
- [x] Accesseurs User : `avatar_url`, `first_name`, `initials`, `job_title`
- [x] Colonnes réelles respectées (`telephone`, `poste`, `actif`)
- [x] `DemoEmployeesSeeder` ajouté au `DatabaseSeeder`

### 🔍 **Semaine 5 : Recherche Globale**

#### Jour 1-2 : Modal Recherche
**Fichier** : `resources/views/intranet/partials/search-modal.blade.php`
**JS** : `resources/js/global-search.js`

**Fonctionnalités** :
- [x] Modal overlay
- [x] Input recherche focus auto
- [x] Raccourci Cmd/Ctrl + K (listener unique, doublon supprimé)
- [x] Fermeture Esc
- [x] Navigation clavier (↑↓ Entrée)

#### Jour 3-4 : API Recherche
**Route API** : `routes/api.php`
**Contrôleur** : `app/Http/Controllers/Api/SearchController.php`

**Rechercher dans** (endpoint `GET /api/search/data`, `Api\SearchController`, protégé par `auth`) :
- [x] Employés (nom, email, département)
- [x] Annonces (titre, contenu)
- [x] Calendrier (événements)
- [x] ~~Tickets~~ → remplacé par Ressources/documents (pas de système de tickets)
- [x] Départements

**Retour JSON** :
```json
{
  "results": [
    {
      "type": "employee",
      "id": 1,
      "title": "Prénom Nom",
      "description": "Poste - Département",
      "url": "/annuaire?highlight=1"
    }
  ]
}
```

#### Jour 5 : Intégration & Tests
- [x] Intégrer Fuse.js pour fuzzy search (accents ignorés : « kabore » trouve « Kaboré »)
- [x] Highlighting résultats (`<mark>`, texte échappé avant insertion)
- [x] Tests : 6 vérifications OK avec la vraie librairie (accents, surlignage, échappement HTML, seuil 2 car.)
- [x] Débounce input (300ms)
- [x] Badge de type en français, 20 résultats max

**Corrections** :
- [x] `@livewireScripts` / `@livewireStyles` ajoutés au layout intranet (Livewire démarre Alpine : sans lui, sidebar mobile, menu profil et Cmd+K ne fonctionnaient pas)
- [x] Listener Cmd+K dupliqué supprimé (le modal s'ouvrait puis se refermait)
- [x] `SubmissionController` implémenté + vues `submissions/index` et `submissions/show`
- [x] Doublons de seed nettoyés (8 annonces, 11 événements)

---

## ✅ Sprint 3 - Semaines 6-7 : Organigramme & Calendrier

### 📊 **Semaine 6 : Organigramme**

#### Jour 1-2 : Structure Hiérarchique
**Fichier** : `resources/views/intranet/organization/index.blade.php`
**Contrôleur** : `app/Http/Controllers/Intranet/OrganizationController.php`

**Données** :
- [x] Charger structure depuis DB (`departments.manager_id`, `users.org_level`, relations `User::manager()` / `directReports()`)
- [x] Direction générale (utilisateur actif `org_level = 0`)
- [x] 4 branches principales (définies dans `config/intranet.php` → `organization.branches`, **regroupement à valider**)
- [x] Départements sous chaque branche (un département non classé tombe dans « Autres »)

#### Jour 3-4 : Visualisation
**CSS** : Design boxes + lignes connexion
- [x] Box Direction (rouge, centré)
- [x] Boxes branches (blanc, ombre)
- [x] Boxes départements (gris clair, liseré couleur d'accent)
- [x] Lignes hiérarchiques (CSS pur, arbre ≥ 1280 px, empilé en dessous)

#### Jour 5 : Interactivité (optionnelle)
- [x] Click box = modal détails (équipe, responsable, liens département / annuaire ; Échap, focus piégé)
- [x] Tooltip sur hover (attribut `title`)
- [x] Export PDF : bouton « Imprimer / PDF » (feuille de style d'impression, pas de lib PDF)

**Fichiers** : `OrganizationController`, `intranet/organization/index.blade.php`, route `intranet.orgchart`
**Tests** : 4 branches / 9 départements, cas « Autres », cas sans DG, JSON du modal évalué avec Node (apostrophes/accents OK)
**Bug trouvé en test** : `except()` sur une collection Eloquent filtre sur l'id, pas le code → corrigé avec `reject()`
**Corrections annexes** : `manager_id`, `position_title`, `org_level`, `hire_date`, `birth_date` ajoutés au `$fillable` de User (le seeder les ignorait en silence)

### 📅 **Semaine 7 : Calendrier**

#### Jour 1-2 : Setup FullCalendar
**Fichier** : `resources/views/intranet/calendar/index.blade.php`
**JS** : `resources/js/calendar.js`

**Configuration** :
- [x] Vue mensuelle par défaut (+ semaine et liste)
- [x] Locale française (import explicite : sans lui le calendrier restait en anglais)
- [x] Couleurs par type événement (`config/intranet.php` → `calendar.event_types`)
- [x] Navigation mois

#### Jour 3-4 : Événements
**Contrôleur** : `app/Http/Controllers/Intranet/CalendarController.php`
**API** : `GET /intranet/calendar/events?start=...&end=...` (session + middleware `active`, plage max 400 jours)
**Service** : `App\Services\Intranet\CalendarFeed` (événements saisis + anniversaires + arrivées)

**Types événements** :
- [x] Réunions (rouge)
- [x] Anniversaires (jaune, calculés depuis `users.birth_date`, jour/mois uniquement, jamais l'âge)
- [x] Arrivées (jaune foncé, depuis `users.hire_date`)
- [x] Événements (noir) + Formations (vert)

#### Jour 5 : Sidebar "À venir"
- [x] Liste 4 prochains événements (60 jours)
- [x] Badge type
- [x] Date et titre
- [x] Link vers jour dans calendrier (`?date=AAAA-MM-JJ`)

**Tests** : 16 vérifications (29 février, plage à cheval sur 2 années, utilisateur inactif exclu, année de naissance absente du flux, validation des paramètres)
**Corrections** : types de la config alignés sur l'enum de la base ; anciens endpoints `/api/calendar/*` inexistants et édition par glisser-déposer retirés (calendrier en lecture seule) ; instance FullCalendar sortie de l'état réactif Alpine ; plugin `@fullcalendar/list` ajouté (6.1.21)
**Hors périmètre** : création / modification d'événements depuis l'interface

---

---

### 🔐 **Chantier transverse : connexion Active Directory** (à lancer après validation)

**Principe** : l'AD est la source de l'**identité et du mot de passe** ; la base locale reste la source des **autorisations** (rôles, départements, organigramme). Le site ne crée ni ne modifie rien dans l'AD.

**État des lieux (constaté)** :
- Le login est 100 % local : `LoginRequest` n'appelle jamais `LdapService`.
- Extension PHP `ldap` non chargée sur le poste de dev (impossible de tester ici contre l'AD).
- `LdapService` à réécrire : DN de bind incorrect, aucun `ldap_escape` (injection de filtre), pas de pagination (limite AD 1000), pas de LDAPS, colonne `ldap_synced` inexistante, comptes désactivés / supprimés ignorés, écrase les modifications faites sur le site.
- Route `admin/ldap/sync` : la vue `admin.ldap.sync` n'existe pas.
- Déjà en place et conservé : CRUD utilisateurs et rôles dans `/admin`, pivot `department_user` (user / technician / director).

**Étapes** :
- [ ] 0. Récupérer les informations AD (domaine, contrôleurs, LDAPS, groupe ou OU à importer, identifiant de connexion, champ matricule)
- [ ] 1. Activer `extension=ldap` (php.ini Windows) + faire approuver le certificat de l'AD par le serveur (LDAPS)
- [ ] 2. Migration : `users.auth_source` (`local`/`ldap`), `ldap_guid` (objectGUID, unique), `ldap_username`, `ldap_synced_at`
- [ ] 3. Connexion : si le compte est `ldap`, validation par bind LDAPS ; les comptes `local` (admin de secours, prestataires) restent inchangés ; verrouillage et limiteur de débit conservés
- [ ] 4. Synchronisation (planifiée + bouton admin) : compte de service lecture seule, recherche paginée, comptes activés uniquement, lien par objectGUID puis e-mail pour éviter les doublons
- [ ] 5. Règle de propriété des champs : AD = nom, e-mail, téléphone, titre ; site = rôles, départements, organigramme, `actif` local. Compte disparu ou désactivé dans l'AD → `actif = false` (jamais supprimé : historique des demandes)
- [ ] 6. Ajout d'utilisateur depuis le site : onglet « Depuis l'AD » (recherche → sélection → compte lié, sans mot de passe) + onglet « Compte local »
- [ ] 7. Écran admin de synchronisation (vue manquante) : dernier passage, créés / mis à jour / désactivés, erreurs
- [ ] 8. (Option) groupes AD → rôle par défaut dans un département, appliqué à la création seulement
- [ ] 9. (Option) SSO Windows via IIS plutôt que saisie du mot de passe

**Décision technique à confirmer** : adopter `directorytree/ldaprecord-laravel` (pagination, échappement, objectGUID gérés) plutôt que réparer le service maison.

---

### 📋 **Checklist Phase 1 (Fin Sprint 3)**

Vérifié dans un vrai navigateur (Edge headless, piloté par script) sur le code final : 26 pages, 76 liens internes, 35 vérifications d'interaction, 18 vérifications responsive, 0 erreur JavaScript.

- [x] Layout global fonctionnel (sidebar, en-tête, fil d'Ariane ; compatible `<x-intranet-layout>` et `@extends`)
- [x] Dashboard complet avec widgets (bandeau, 9 départements, annonces, agenda, demandes récentes)
- [x] Annuaire avec recherche/filtres (recherche, pills département, cumul, réinitialisation)
- [x] Recherche globale Cmd+K opérationnelle (Ctrl+K, saisie sans accents, surlignage, flèches, Entrée, Échap)
- [x] Organigramme visible (4 branches, 9 départements, modal de détail)
- [x] Calendrier avec événements (français, navigation, détail, vue liste, `?date=`)
- [x] Navigation fluide entre pages (aucune page en erreur, aucun lien cassé sur 76 testés)
- [x] Design responsive (390 / 820 / 1280 / 1440 px : aucun débordement horizontal, tiroir mobile)
- [~] **DÉMO PRÉSENTABLE** : prête, avec les réserves ci-dessous

**Défauts découverts pendant cette vérification (tous corrigés)** :
- Layout intranet écrasé à la Semaine 2 : les vues héritées (formulaires, CRUD départements, profil) plantaient
- Pages en erreur 500 : `/intranet/forms`, `/profil`, `/profile`, `/intranet/time-off*`, `/intranet/resources/{id}`, `/intranet/departments/manage*`
- Sidebar sans style (`@apply` dans un `<style>` de template n'est jamais compilé) → déplacé dans `resources/css/app.css`
- Sidebar ouverte d'office sur mobile → tiroir fermé par défaut, toujours visible dès 1024 px
- Avatars sans pastille : le composant utilisait `.avatar-*` alors que le design system définit `.nere-avatar-*`
- Badges de priorité / statut tous identiques : alias `danger` / `warning` / `success` ajoutés
- Dates en anglais (« 2 days ago ») : `.env.local`, chargé par `php artisan serve`, était resté en `APP_LOCALE=en`
- Noms de départements tronqués (« Hygiène, Santé, Sécur… ») → passent sur plusieurs lignes comme la maquette
- Débordements mobile (annonces, page département) ; calendrier illisible en grille sur mobile → vue Liste par défaut
- Police chargée depuis Internet (bunny.net) retirée : l'intranet tourne sur le réseau local de la mine
- Tags des ressources stockés en double encodage JSON ; 20 services de départements en doublon ; 22 services (et non 24)
- Lien de téléchargement qui incrémentait le compteur à chaque consultation de la fiche

**Réserves connues (hors Phase 1)** :
- [ ] Les 22 services de départements sont affichés « Bientôt disponible » : aucune page de destination n'existe encore
- [ ] Module formulaires (`/intranet/forms`) : page « à venir ». Ses vues héritées utilisent des icônes Font Awesome, qui ne s'affichent plus (CDN retiré, pas d'Internet) ; à installer en local si on les garde
- [ ] Les fichiers des ressources n'existent pas sur disque : la fiche l'indique, le téléchargement n'est pas testable
- [ ] Congés (time-off) retirés du menu jusqu'à la Phase 3
- [ ] Pages « Gestion des départements » (`/intranet/departments/manage`) encore dans l'ancien style visuel (boutons bleus)
- [ ] Code mort de l'ancien portail : `accueil.blade.php`, `departement.blade.php`, `layouts/navigation.blade.php`
- [ ] Données de démonstration : 20 employés fictifs (mot de passe `password`) et un faux Directeur Général, à remplacer avant toute mise en ligne

---

# 💪 PHASE 2 - FONCTIONNALITÉS AVANCÉES (6 semaines)

## 🎯 Objectif
Ajouter annonces, pages départements, analytics, et système de reconnaissance.

---

## ✅ Sprint 4 - Semaines 8-9 : Annonces & Notifications

### 📢 **Semaine 8 : Système d'Annonces**

#### Jour 1-2 : CRUD Annonces
**Contrôleur** : `app/Http/Controllers/Intranet/AnnouncementController.php`
**Vues** :
- [ ] `index.blade.php` - Liste avec filtres
- [ ] `create.blade.php` - Création (admin/managers)
- [ ] `edit.blade.php` - Édition
- [ ] `show.blade.php` - Détails

**Fonctionnalités** :
- [ ] Liste avec filtres priorité (Toutes/Urgent/Important/Info)
- [ ] Barre couleur par priorité
- [ ] Accordéon expandable
- [ ] Recherche dans annonces
- [ ] Pagination

#### Jour 3-4 : Permissions & Workflow
**Permissions (AnnouncementPolicy)** : Qui peut créer/modifier ?
- [x] Super admin : toutes annonces (y compris "toute l'entreprise")
- [x] Directeurs : annonces de leur(s) département(s) uniquement
- [~] Managers : rôle "manager" inexistant dans DepartmentRole (user/technician/director) → admin + directeur seulement

**Notifications** :
- [x] Email pour annonces urgentes (canal mail conditionnel, sinon database seul)
- [x] Badge notification dans header (compteur + dropdown)
- [x] Marquer comme lu (par notification + tout marquer)
- [x] Sécurité : corps affiché en texte échappé (faille XSS stockée corrigée)
- [x] L'auteur n'est pas auto-notifié de sa propre annonce

#### Jour 5 : Widget Dashboard
- [x] Afficher 3 dernières annonces (déjà en place depuis la Semaine 3)
- [x] Link "Voir toutes"
- [~] Auto-refresh : non implémenté (optionnel)

### 🔔 **Semaine 9 : Système de Notifications** ✅

#### Jour 1-3 : Infrastructure Notifications
**Table** : `notifications` (Laravel native, déjà migrée)
**Canaux** :
- [x] Database (toutes les notifications)
- [x] Email (annonces urgentes + demandes traitées)
- [~] Browser push : non implémenté (optionnel)

**Types notifications** (format `data` normalisé : category/title/message/url/icon) :
- [x] Nouvelle annonce (`AnnouncementPublished`)
- [~] Ticket assigné/mis à jour → pas de système de tickets dans ce projet
- [~] Kudos reçu → feature Kudos retirée par le client
- [x] Événement calendrier proche (`UpcomingEventReminder` + commande `intranet:rappels-evenements`)
- [x] Demande traitée approuvée/refusée (`SubmissionReviewed`) — équivalent "congé approuvé/refusé"

#### Jour 4-5 : UI Notifications
**Composant** : `resources/views/components/notification-dropdown.blade.php`
- [x] Badge compteur dans header
- [x] Dropdown liste notifications (8 dernières non lues, tout type)
- [x] Marquer comme lu / tout marquer lu
- [x] Link vers contenu concerné (champ `url` normalisé)
- [x] Design propre (icône par catégorie, état vide)
- [x] Page liste complète paginée + `NotificationController` + routes

---

## ✅ Sprint 5 - Semaines 10-11 : Pages Départements

### 🏢 **Semaine 10 : Template Département**

#### Jour 1-2 : Structure Page ✅
**Fichier** : `resources/views/intranet/departments/show.blade.php`
**Contrôleur** : `app/Http/Controllers/Intranet/DepartmentShowController.php` (vitrine, distinct du CRUD /manage)

**Sections** :
- [x] Hero banner avec illustration (+ bande d'accent couleur du département)
- [x] Badge catégorie
- [x] Titre et description
- [x] Onglets navigation (Services / Formulaires / Équipe) avec compteurs — bascule Alpine sans rechargement
- [~] Onglet "Tickets" prévu à l'origine : abandonné, pas de système de tickets dans ce projet

#### Jour 3 : Onglet Services ✅
**Table** : `department_services`
- [x] Grid 2-3 colonnes (responsive 1/2/3)
- [x] Cards services (icône + titre + description)
- [x] Bouton "Ouvrir →" si le service a une URL, sinon badge "Bientôt disponible"

#### Jour 4 : Onglet Formulaires ✅
**Table** : `department_forms` (seeder `DepartmentFormSeeder` : 7 formulaires de démo)
- [x] Liste formulaires disponibles (nom + description)
- [x] Status actif/archivé respecté (scope `active`)
- [~] Bouton "Remplir" : affiché "Bientôt disponible" tant que le module de formulaires dynamiques n'est pas livré
- [~] Permissions par rôle : à brancher avec le module de remplissage

#### Jour 5 : Onglet Équipe ✅
- [x] Afficher membres département (triés : directeur → technicien → utilisateur, puis nom)
- [x] Hiérarchie visuelle (badge rôle + niveau technique Nx, surbrillance du responsable)
- [x] Avatar + nom + poste
- [x] Lien vers l'annuaire filtré par département
- [~] Lien vers profil individuel : pas de page profil public (seul /profil de l'utilisateur courant existe)
- [x] Correctif seeder : le chef de chaque département a désormais le rôle `director` sur le pivot (cohérent organigramme + permissions annonces)

### 🔄 **Semaine 11 : Réplication Départements**

#### Jour 1-3 : 9 Pages Département ✅
**Approche** : pas de copie de 9 vues. Le template unique `departments/show.blade.php`
sert les 9 départements via leur code (route `/intranet/departments/{code}`).
La « réplication » consiste donc à enrichir les données de chaque département.

**Départements (tous rendus et vérifiés en navigateur)** :
- [x] Administration (ADMIN) — 5 services
- [x] Ressources humaines (RH) — 6 services
- [x] Sécurité (SEC) — 5 services
- [x] Mining (MINING) — 5 services
- [x] HSE — 6 services
- [x] Processing (PROC) — 5 services
- [x] SCM / Approvisionnement — 5 services
- [x] IT — 6 services
- [x] Relations communautaires (RELCOM) — 5 services

**Par département** :
- [x] Template commun (aucune duplication à maintenir)
- [x] Services adaptés : 5-6 par département, propres au métier (seeder `DepartmentServiceSeeder`)
- [x] Formulaires spécifiques : 1-3 par département (seeder `DepartmentFormSeeder`, 18 au total)
- [x] Équipe importée depuis la base (pivot `department_user`, triée par rôle)
- [x] Nettoyage d'un service en doublon (ancien « Matériel de sécurité » HSE)

#### Jour 4-5 : Tests & Ajustements ✅
- [x] Vérifier tous les liens (fil d'Ariane, onglets, lien annuaire)
- [x] Tester navigation (9 pages rendues + bascule onglets en navigateur réel)
- [x] Uniformiser design (template unique → cohérence garantie par construction)
- [x] Validation contenu (48 services, 18 formulaires ; apostrophe du nom SCM gérée)
- [x] Aucun doublon d'ordre d'affichage des services
- [ ] Validation contenu

---

## ✅ Sprint 6 - Semaines 12-13 : Analytics 

### 📊 **Semaine 12 : Dashboard Analytics**

#### Jour 1-2 : KPI Cards ✅
**Fichier** : `resources/views/intranet/analytics/index.blade.php`
**Contrôleur** : `app/Http/Controllers/Intranet/AnalyticsController.php`

**Métriques (4 cards)** — adaptées aux données réelles (pas de système de tickets) :
- [x] Demandes reçues sur la période (+ variation % vs période précédente)
- [x] Taux de traitement (% de demandes traitées)
- [x] Délai moyen de traitement (en heures)
- [x] Demandes en attente
- [~] « Satisfaction / Projets actifs » : abandonnés, aucune donnée source dans le projet

**Design** :
- [x] Icône, valeur principale, variation ▲▼ %, label

#### Jour 3-4 : Graphiques ✅
**Bibliothèque** : Chart.js (déjà installé, animations désactivées pour un rendu stable)

**Graphiques créés** (sur les demandes / départements / effectifs) :
1. [x] **Demandes par semaine** (line chart) — réparties sur la période choisie
2. [x] **Demandes par département** (bar chart horizontal)
3. [x] **Répartition par statut** (doughnut) — Approuvées / Rejetées / En attente
4. [x] **Effectifs par département** (bar chart)

#### Jour 5 : Filtres & Export
- [x] Filtres période (7j / 30j / 3m / 1an) — rechargement serveur, repli sur 30j si valeur invalide
- [~] Export PDF : non implémenté (optionnel)
- [~] Bouton refresh : non implémenté (le changement de période recharge déjà les données)

**Données de démo** : `DemoSubmissionsSeeder` (70 demandes réparties sur ~8 semaines, statuts variés)
**Vérifs** : 14 tests contrôleur OK ; JSON injecté validé ; page chargée en navigateur (KPI affichés, 4 canvas, Chart.js sans erreur)

---

### 📋 **Checklist Phase 2 (Fin Sprint 6)**
- [ ] Système annonces complet
- [ ] Notifications fonctionnelles
- [ ] 9 pages département créées
- [ ] Dashboard analytics avec graphiques
- [ ] Système kudos opérationnel
- [ ] Toutes les fonctionnalités testées
- [ ] **DÉMO AVANCÉE** 🎯

---

# 🏆 PHASE 3 - MODULES RH & OPTIMISATION (6 semaines)

## 🎯 Objectif
Ajouter gestion congés, bibliothèque ressources, et optimiser performance.

---

## ✅ Sprint 7 - Semaines 14-15 : Gestion Congés (Time Off)

### 🏖️ **Semaine 14 : Infrastructure Congés**

#### Jour 1-2 : Base de données ✅
**Tables** (créées en Phase 0, modèles existants) :
- [x] `time_off_policies` (politiques par type)
- [x] `time_off_balances` (soldes par utilisateur)
- [x] `time_off_requests` (demandes)

**Seeder** (`TimeOffSeeder`) :
- [x] Politiques par défaut : Congés annuels (30 j), Maladie (15 j), Personnels (5 j)
- [x] Soldes initiaux pour tous les employés actifs (66 soldes = 22 × 3), avec jours consommés de démo

#### Jour 3-4 : Dashboard Congés ✅
**Fichier** : `resources/views/intranet/time-off/index.blade.php`
**Contrôleur** : `app/Http/Controllers/Intranet/TimeOffController.php`

**Sections** :
- [x] Cards soldes (3 types réels : annuels / maladie / personnels)
  - Restant mis en avant, utilisé/total, barre de progression + % consommé
- [x] Bouton "Demander un congé"
- [x] Historique demandes (tableau paginé, badge de statut)
- [x] Entrée "Mes congés" dans le menu profil de l'en-tête

#### Jour 5 : Formulaire Demande ✅
**Fichier** : `resources/views/intranet/time-off/create.blade.php`

**Champs** :
- [x] Type congé (select, avec préavis et solde affichés)
- [x] Date début / Date fin (date pickers natifs, bornés à aujourd'hui)
- [x] Nombre de jours calculé auto (jours ouvrés lun-ven, demi-journée = 0,5) — live via Alpine + revérifié serveur
- [x] Motif (textarea) + contact d'urgence
- [x] Validations serveur : préavis de la politique, solde disponible, période non vide/non 100% week-end, cohérence demi-journée

**Vérifs** : 11 tests contrôleur (dont 4 cas de rejet) + 9 tests navigateur (dashboard, calcul live, soumission réelle) — tous OK
**Note** : le décrément effectif du solde se fait à l'approbation (Semaine 15, workflow manager) ; jours fériés non déduits du calcul

### ✅ **Semaine 15 : Workflow Approbation** — FAIT

#### Jour 1-2 : Approbation Manager ✅
**Vue** : `resources/views/intranet/time-off/approvals.blade.php`
**Contrôleur** : `app/Http/Controllers/Intranet/TimeOffApprovalController.php`

**Fonctionnalités** :
- [x] Liste demandes à approuver (manager direct via `users.manager_id` OU super-admin)
- [x] Détails demande (dates, type, jours, motif privé)
- [x] Boutons Approuver / Refuser + commentaire optionnel
- [x] Décrément du solde à l'approbation (transaction DB + `lockForUpdate()`, idempotent : seules les demandes `pending` sont traitables, double-approbation → 409)

**Note statut** : le refus utilise le statut `denied` (et non `rejected`) — l'enum de la migration est `['pending','approved','denied','cancelled']`.

#### Jour 3-4 : Notifications & Emails ✅
**Notification** : `app/Notifications/TimeOffReviewed.php` (database + mail)
- [x] Email/notif décision → employé (approuvé ou refusé, format normalisé category/icon/title/message/url)
- [x] Entrée dans le dropdown de notifications intranet
- [ ] Rappel manager 48h (non implémenté — hors périmètre, à planifier si besoin)

> ⚠️ `MAIL_MAILER=log` en dev : les emails sont écrits dans les logs, pas réellement envoyés.

#### Jour 5 : Calendrier Absences ✅
**Service** : `app/Services/Intranet/CalendarFeed.php` → méthode `timeOff()`
- [x] Afficher absences approuvées (événements multi-jours, `end` FullCalendar exclusif → +1 jour)
- [x] Couleur distincte bleu clair (`#577590`), type `time_off`
- [x] Tooltip avec nom + type (motif gardé **privé** — seul le nom est affiché)
- [x] Config `config/intranet.php` → type `time_off` ajouté aux `calendar.event_types`

**Vérifs** : 14 tests contrôleur (dont décrément, idempotence, autorisations) + 6 tests navigateur — tous OK. Approbation vérifiée en base (status=approved, used=3, remaining=27, 1 notification). Données de test nettoyées (demandes=0, soldes=66).

---

## ✅ Sprint 8 - Semaines 16-17 : Bibliothèque Ressources

### ✅ **Semaine 16 : Upload & Catégorisation** — FAIT

#### Jour 1-2 : Structure Ressources ✅
**Tables** (migrations déjà présentes, exécutées en base) :
- [x] `resource_categories`
- [x] `resources`

**Catégories** (seeder `ResourceSeeder`, 5 catégories / 9 ressources de démo) :
- [x] Politiques et Procédures
- [x] Formulaires
- [x] Formation
- [x] Documentation technique
- [x] Modèles

> Note : l'index plein-texte PostgreSQL (`fullText`) est désormais conditionné
> au driver (`pgsql`/`mysql`) pour ne pas casser une base SQLite de test.

#### Jour 3-4 : Upload Documents ✅
**Vue** : `resources/views/intranet/resources/create.blade.php`
**Contrôleur** : `ResourceController@create` + `@store`

**Fonctionnalités** :
- [x] Upload fichier (PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX, vidéo mp4/mov/avi/webm)
- [x] Titre et description
- [x] Catégorie (select)
- [x] Mots-clés (champ texte « a, b, c » → tableau JSON dédoublonné, 10 max)
- [x] Département (select ; « toute l'entreprise » réservé à l'admin général)
- [x] Validation taille (max 50 Mo) + validation du format (mimes)

#### Jour 5 : Storage & Sécurité ✅
**Config** : `config/filesystems.php`
- [x] Disque **privé** (`private`) : fichiers hors de la racine publique, servis
      uniquement via la route authentifiée `resources.download`
- [x] Permissions par policy (`ResourcePolicy`) : employé consulte/télécharge,
      admin général gère tout, directeur gère les ressources de ses départements
- [x] Suppression (`@destroy`) : efface l'enregistrement **et** le fichier disque
- [ ] Virus scan / versioning : non implémentés (optionnels, hors périmètre)

**Vérifs** (script manuel contre la base de dev réelle, puis retiré) : 24 contrôles
OK — droits (employé/admin/directeur), upload + stockage privé, parsing des tags,
règle « directeur ≠ toute l'entreprise » (403), incrément du compteur au
téléchargement, suppression fichier + enregistrement, rendu des vues index/create.
Données inchangées (9 ressources).

### ✅ **Semaine 17 : Recherche & Consultation** — FAIT

#### Jour 1-2 : Page Bibliothèque ✅
**Vue** : `resources/views/intranet/resources/index.blade.php` (refondue)
**Contrôleur** : `ResourceController@index` (recherche + filtres)

**Layout** :
- [x] Sidebar catégories avec compteurs (ressources actives par catégorie)
- [x] Grille documents (cards 2 colonnes)
- [x] Barre de recherche (titre, description, mots-clés via `whereJsonContains`)
- [x] Filtres : catégorie (sidebar), type de fichier, département + bouton
      « Réinitialiser » ; onglet « Mes favoris »

#### Jour 3-4 : Card Document ✅
- [x] Icône + teinte selon le type de fichier
- [x] Titre + description (tronquée)
- [x] Badge catégorie
- [x] Tags (3 premiers)
- [x] Métadonnées : type, taille, date, compteur de téléchargements
- [x] Bouton favori (étoile, bascule via POST `resources.favorite`)
- [x] Lien vers la fiche (le téléchargement reste sur la fiche, via la route authentifiée)

#### Jour 5 : Fonctionnalités Avancées ✅
- [x] Favoris utilisateur : table pivot `resource_user` + relations
      `Resource::favoritedBy()` / `User::favoriteResources()` + route toggle
      (idempotente, message flash → toast)
- [x] Documents populaires : scope `popular()` (top par `download_count`), encart sidebar
- [x] Documents récents : encart sidebar (5 derniers)
- [x] Tracking téléchargements : déjà en place (compteur incrémenté par `download()`)
- [ ] Notifications « nouveaux documents » : non implémentées (optionnel ; pourrait
      réutiliser le même mécanisme que les annonces si souhaité plus tard)

**Vérifs** (script contre la base de dev, puis retiré) : 17 contrôles OK — liste
complète, compteurs catégories = total, blocs populaires/récents, recherche texte,
filtres catégorie/type, bascule des favoris (on/off), onglet favoris, rendu des
vues index + fiche. Données inchangées (9 ressources, 0 favori résiduel).

---

## ✅ Sprint 9 - Semaines 18-19 : Optimisation & Performance

### ✅ **Semaine 18 : Performance Backend** — FAIT

#### Jour 1-2 : Optimisation Requêtes ✅
- [x] Eager loading relations (N+1) : déjà en place sur les contrôleurs chauds
      (Dashboard `with(department, creator, form.department)`, Directory
      `with(departments)`, Organization `with(manager, users)`). Vérifié, rien à corriger.
- [x] Index DB sur colonnes recherchées — migration
      `add_performance_indexes_to_users_table` : `users(actif, name)` (annuaire),
      `users(org_level)` (organigramme), `users(manager_id)` (congés). La table
      `departments` était déjà bien indexée (`is_active_position`, `code`, `manager_id`...).
- [x] Pagination efficace : déjà en place (annuaire 20/page, annonces 10/page).

> Note honnête : sur la base de dev (≈22 employés) le planificateur PostgreSQL
> choisit encore un *Seq Scan* — c'est le bon choix pour une si petite table.
> Les index deviennent payants à l'échelle réelle (centaines d'employés) ;
> ils sont posés pour ce moment-là.

> Adaptation : les KPI « tickets / kudos » du roadmap n'existent pas dans ce
> projet (pas de billetterie, Kudos retiré). Optimisation ciblée sur annuaire,
> organigramme et tableau de bord.

#### Jour 3-4 : Cache Strategy ✅
**Service** : `app/Services/Intranet/IntranetCache.php` (store `file`, cf. `.env`)
- [x] Cache liste des départements actifs + compteurs (clé `departments.active`)
- [x] Cache organigramme — construction complète des branches, TTL **1 h**
      (clé `organization.branches`)
- [x] Cache compteurs tableau de bord : helper `stats()` TTL **5 min** disponible
- [x] Invalidation appropriée : `DepartmentObserver` + `UserObserver` appellent
      `flushStructure()` dès qu'un département ou un utilisateur pertinent change
      (statut actif, rattachement, niveau, nom). Pas de donnée périmée après écriture.

#### Jour 5 : Tests Performance ✅ (allégé)
- [x] Vérification par script dédié : index présents, cache servi au 2ᵉ appel,
      invalidation effective au `touch` d'un département, plan de requête inspecté (EXPLAIN).
- [ ] Telescope / Debugbar / tests de charge Artillery : non installés (outils de
      profiling, à ajouter au moment de la montée en charge réelle).

### ✅ **Semaine 19 : Optimisation Frontend** — FAIT (partiel)

#### Jour 1-2 : Assets Optimization ✅
- [x] Minify CSS/JS : `npm run build` (Vite 8, production) OK — manifest généré,
      CSS 70 ko → 12,5 ko gzip, JS 659 ko → 213 ko gzip.
- [x] Lazy loading images : `loading="lazy"` + `decoding="async"` sur les
      illustrations de départements (grille accueil + index départements) et sur
      le composant avatar (listes d'annuaire). Logos above-the-fold laissés en eager.
- [ ] Compression images / CDN : non traité (optionnel ; réseau local de la mine,
      pas de CDN). Le bundle JS dépasse 500 ko — piste de code-splitting notée
      ci-dessous (FullCalendar/Chart.js/Fuse chargés globalement).

#### Jour 3 : Responsive Final ✅ (déjà couvert)
- [x] Les grilles utilisent déjà les points de rupture Tailwind
      (`grid-cols-1 md:grid-cols-2 lg:grid-cols-3`), sidebar repliable sur mobile
      (`x-data sidebarOpen`). Vérifié visuellement en Phase 1.

#### Jour 4-5 : Progressive Enhancement ✅ (partiel)
- [x] **Toasts notifications** : pont `flash-toasts.blade.php` → relaie les
      messages flash de session (`success`/`error`/`warning`/`status`) en toasts
      toastr. Jusqu'ici les `->with('success', …)` des contrôleurs n'étaient
      jamais affichés. Encodage JSON (anti-XSS) vérifié.
- [ ] Service Worker / offline, loading skeletons, animations : non traités
      (améliorations cosmétiques, hors périmètre immédiat).

> **Piste perf JS** (à planifier) : code-splitting des libs lourdes
> (FullCalendar, Chart.js, Fuse, Sortable) via `import()` dynamique par page,
> au lieu du chargement global sur `window`. Gain estimé important sur le poids
> initial, mais refactor touchant calendrier/analytics/recherche → à faire avec
> tests de non-régression.

---

### 📋 **Checklist Phase 3 (Fin Sprint 9)**
- [ ] Module congés complet
- [ ] Bibliothèque ressources fonctionnelle
- [ ] Performance optimisée
- [ ] Responsive tous devices
- [ ] Cache strategy implémentée
- [ ] Tests charge effectués
- [ ] **SYSTÈME COMPLET** 💯

---

# 🚢 PHASE 4 - POLISH & DÉPLOIEMENT (1-2 semaines)

## 🎯 Objectif
Finaliser, tester, documenter, et déployer en production.

---

## ✅ Sprint 10 - Semaines 20-21 : Finalisation

### 🧪 **Semaine 20 : Tests & QA**

#### Jour 1-2 : Tests Fonctionnels
**Checklist complète** :
- [ ] Toutes les pages accessibles
- [ ] Tous les formulaires validés
- [ ] Tous les liens fonctionnent
- [ ] Permissions correctes
- [ ] Emails envoyés
- [ ] Notifications affichées

#### Jour 3 : Tests Utilisateurs
**User Acceptance Testing (UAT)** :
- [ ] 5-10 utilisateurs pilotes
- [ ] Feedback formulaire
- [ ] Liste bugs/améliorations
- [ ] Priorisation fixes

#### Jour 4-5 : Corrections Bugs
- [ ] Fixer bugs critiques
- [ ] Améliorer UX si nécessaire
- [ ] Tests régression

### 📚 **Semaine 21 : Documentation & Déploiement**

#### Jour 1-2 : Documentation
**Documents à créer** :
- [ ] `USER_GUIDE.md` - Guide utilisateur
- [ ] `ADMIN_GUIDE.md` - Guide admin
- [ ] `API_DOCUMENTATION.md` - Endpoints API
- [ ] `DEPLOYMENT.md` - Procédure déploiement
- [ ] `CHANGELOG.md` - Historique versions

#### Jour 3 : Préparation Déploiement
**Checklist** :
- [ ] Variables `.env` production
- [ ] Backups DB configurés
- [ ] SSL/HTTPS configuré
- [ ] Monitoring configuré (Sentry optionnel)
- [ ] Logs rotation configurée
- [ ] Emails production (SMTP)

#### Jour 4 : Déploiement Production
**Étapes** :
1. [ ] Backup DB production actuelle
2. [ ] Merge branche `main`
3. [ ] Run migrations production
4. [ ] Run seeders (si nécessaire)
5. [ ] Build assets (`npm run build`)
6. [ ] Clear caches (`php artisan optimize`)
7. [ ] Tests smoke production
8. [ ] Monitoring 24h

#### Jour 5 : Formation & Lancement
**Formation** :
- [ ] Session formation admins (2h)
- [ ] Session formation users (1h)
- [ ] Vidéos tutoriels (optionnel)
- [ ] Email annonce lancement
- [ ] Support disponible

---

### 🎉 **Checklist Finale**
- [ ] Tous les sprints complétés
- [ ] Tous les tests passés
- [ ] Documentation complète
- [ ] Production déployée
- [ ] Utilisateurs formés
- [ ] Support en place
- [ ] **INTRANET LANCÉ** 🚀🎊

---

# 📊 TABLEAU RÉCAPITULATIF GLOBAL

| Phase | Sprints | Semaines | Fonctionnalités Principales | Statut |
|-------|---------|----------|----------------------------|--------|
| **Phase 0** | Sprint 0 | 1 | Setup, Migrations, Assets | ⏳ |
| **Phase 1** | Sprint 1-3 | 6 | Layout, Dashboard, Annuaire, Recherche, Calendrier | ⏳ |
| **Phase 2** | Sprint 4-6 | 6 | Annonces, Départements, Analytics, Kudos | ⏳ |
| **Phase 3** | Sprint 7-9 | 6 | Congés, Ressources, Performance | ⏳ |
| **Phase 4** | Sprint 10 | 2 | Tests, Documentation, Déploiement | ⏳ |
| **TOTAL** | **10 sprints** | **21 semaines** | **Intranet complet** | - |

---

# 🎯 PRIORITÉS & FLEXIBILITÉ

## ⚡ Approche MVP First

Si **contraintes de temps**, prioriser dans cet ordre :

### 🔴 **Critique - MVP Phase 1 (6 semaines)**
1. Layout global + navigation
2. Dashboard
3. Annuaire
4. Recherche globale
5. Organigramme
6. Calendrier

### 🟡 **Important - Phase 2 Partielle (4 semaines)**
7. Annonces
8. 3 pages département (IT, RH, HSE)
9. Analytics basique (KPI cards seulement)

### 🟢 **Nice to Have - Phase 3 (selon budget)**
10. Système kudos
11. Gestion congés
12. Bibliothèque ressources
13. Toutes pages département

---

# 📝 MÉTHODE DE TRAVAIL

## Daily Routine
- **9h-9h15** : Standup (si équipe)
- **9h15-12h** : Développement focus
- **12h-13h** : Pause
- **13h-17h** : Développement + tests
- **17h-17h30** : Commit + documentation

## Fin de Sprint
- **Vendredi après-midi** :
  - [ ] Demo fonctionnalités
  - [ ] Rétrospective
  - [ ] Planning sprint suivant
  - [ ] Mise à jour roadmap

## Outils Recommandés
- **Gestion projet** : Trello, Jira, ou GitHub Projects
- **Versioning** : Git + branches par feature
- **Tests** : PHPUnit + Laravel Dusk (optionnel)
- **Communication** : Slack ou Teams

---

# 🎊 CONCLUSION

Cette roadmap est **réaliste, détaillée et exécutable**. Elle vous guide étape par étape du setup jusqu'au déploiement.

## Prochaine Étape
**Commençons par la Phase 0 - Sprint 0** :
1. Créer toutes les migrations
2. Setup design system
3. Installer bibliothèques JS

**Prêt à démarrer ? Dis-moi par quelle tâche commencer !** 🚀

---

**Document créé le** : 8 octobre 2026
**Projet** : Intranet NERE Mining
**Durée estimée** : 21 semaines (5 mois)
**Status** : PRÊT À EXÉCUTER ✅
