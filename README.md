# Intranet Nere Mining (ITSM)

Intranet interne de Nere Mining : portail par département, formulaires et demandes avec workflow de traitement, tickets Support IT avec SLA, congés, calendrier, base de connaissances, menu de la cantine et administration centrale.

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15%2B-336791?logo=postgresql)

Licence : propriétaire, usage interne. Voir [composer.json](composer.json).

## Fonctionnalités

- **Départements** : page par département avec services, formulaires dynamiques, calendrier, annuaire et illustrations.
- **Demandes et formulaires** : soumission, file de traitement, assignation et suivi (`FormSubmission`).
- **Tickets Support IT** : catégories, priorités, assignation, commentaires publics et notes internes, pièces jointes privées (5 fichiers max, 10 Mo chacun), historique.
  - SLA de première réponse et de résolution en heures ouvrées (lun-ven 08:00-17:00, sam 08:00-14:00), alertes à 75 % du délai puis au dépassement.
  - Objectifs réponse / résolution : urgente 15 min / 4 h, haute 1 h / 8 h, normale 4 h / 2 jours ouvrés, basse 1 jour / 5 jours ouvrés (1 jour ouvré = 8 h SLA).
  - Notifications dans l'intranet, par e-mail et par push navigateur.
- **Congés** : politiques, soldes, demandes, approbation par le manager, rappel après 48 h sans réponse.
- **Base de connaissances** : articles par catégorie, recherche.
- **Annonces, calendrier, ressources** (bibliothèque avec favoris), liens rapides, recherche globale.
- **Menu** : campagnes de menu, inscriptions et export Excel.
- **Analytics** : indicateurs et exports PDF, XLSX, CSV, JSON selon le périmètre d'accès de l'utilisateur.
- **Administration centrale** : utilisateurs, rôles et permissions, contenus, modules activables, ressources, tickets, congés, paramètres.
- **Authentification** : locale ou Active Directory (LDAP) avec synchronisation planifiée. Les rôles et départements restent gérés localement.
- **Tutoriel d'accueil** interactif, application mobile Expo (dossier `mobile/`).

## Stack

| Couche | Technologies |
|---|---|
| Backend | Laravel 13, PHP 8.3+, Livewire 3, Breeze |
| Base de données | PostgreSQL (sessions, cache et queue en base) |
| Frontend | Blade, Alpine.js, Tailwind CSS 4, Vite 8, FullCalendar, Chart.js |
| Intégrations | LdapRecord (Active Directory), web-push (VAPID), Dompdf, PhpSpreadsheet |
| Tests | Pest 4 |
| Mobile | Expo / React Native (`mobile/`) |
| Production | IIS + PHP FastCGI sur Windows Server (ou Docker / nginx) |

## Installation locale

Prérequis : PHP 8.3+, Composer, PostgreSQL 15+, Node.js 18+. L'extension PHP `ldap` n'est requise que si LDAP est activé.

```bash
git clone https://github.com/ERWANS2003/NERE.git
cd NERE
composer install
npm install
cp .env.example .env
php artisan key:generate
# Renseigner DB_* dans .env, puis :
php artisan migrate --seed
npm run build
php artisan serve
```

Application : http://127.0.0.1:8000

Pour le développement, lancer `npm run dev` en parallèle de `php artisan serve`.

> Le serveur intégré de PHP ne gère pas TLS : ne pas utiliser `https://` en local. Garder `FORCE_HTTPS=false`.

### Comptes de démonstration

Créés par `UserSeeder` :

| Compte | Mot de passe |
|---|---|
| admin@nere-mining.com | admin123 |

Changer ce mot de passe hors environnement de démonstration.

## Configuration (.env)

Voir [.env.example](.env.example). Variables principales :

| Variable | Rôle |
|---|---|
| `APP_URL`, `FORCE_HTTPS`, `TRUSTED_PROXIES` | URL et schéma ; activer HTTPS seulement derrière un reverse proxy qui termine le SSL |
| `DB_*` | Connexion PostgreSQL (port 5433 par défaut dans l'exemple) |
| `QUEUE_CONNECTION`, `CACHE_STORE`, `SESSION_DRIVER` | `database` par défaut |
| `MAIL_*` | Transport e-mail (`log` par défaut ; à configurer en production) |
| `LDAP_*`, `AUTH_PROVIDER_DRIVER` | Active Directory, voir [LDAP_SETUP.md](LDAP_SETUP.md) |
| `WEB_PUSH_*` | Notifications navigateur (HTTPS, VAPID et worker de queue requis) |

Générer les clés VAPID : `php artisan webpush:generate-vapid-keys`.

## Tâches planifiées et queue

En production, deux processus doivent tourner :

```bash
php artisan queue:work
php artisan schedule:work   # ou un cron / tâche planifiée Windows : php artisan schedule:run chaque minute
```

| Commande | Fréquence | Rôle |
|---|---|---|
| `intranet:check-ticket-slas` | chaque minute | alertes SLA des tickets |
| `intranet:remind-pending-time-off` | 15 min | rappel des congés en attente depuis 48 h |
| `intranet:rappels-evenements` | tous les jours à 07:00 | rappel des événements du lendemain |
| `ldap:sync` | selon les paramètres admin | synchronisation Active Directory (`--dry-run` disponible) |

## Structure du projet

```
app/
  Http/Controllers/   Admin, Api, Auth, Intranet, Menu
  Models/             Utilisateurs, départements, tickets, congés, menu, etc.
  Services/           SLA des tickets, soldes et rappels de congés, analytics, calendrier, export menu
  Ldap/               Authentification et synchronisation Active Directory
  Policies/ Enums/ Notifications/ Jobs/ Observers/
routes/               web, auth, intranet, admin, console (commandes et planification)
database/             migrations et seeders
resources/            vues Blade, JS (resources/js/intranet), CSS
mobile/               application Expo
docs/                 documentation fonctionnelle et technique
tests/Feature/        tests Pest
```

## Tests

```bash
php artisan test
```

L'intégration continue ([.github/workflows/test.yml](.github/workflows/test.yml)) exécute les tests sur PostgreSQL.

## Déploiement

- [DEPLOYMENT.md](DEPLOYMENT.md) : déploiement initial et mises à jour (`deploy.ps1`)
- [WINDOWS_SERVER_DEPLOYMENT.md](WINDOWS_SERVER_DEPLOYMENT.md) : IIS, PHP FastCGI, PostgreSQL, worker et scheduler
- [README-DEPLOYMENT.md](README-DEPLOYMENT.md), [README-UTF8-FIX.md](README-UTF8-FIX.md), [FIX-UTF8-PRODUCTION.md](FIX-UTF8-PRODUCTION.md) : notes de déploiement et correctifs d'encodage
- `Dockerfile.prod`, `docker-compose.prod.yml`, `nginx.conf`, `web.config` : alternatives de déploiement

Après chaque déploiement : `php artisan migrate --force`.

## Documentation

- [GUIDE_UTILISATEUR_PORTAIL.md](GUIDE_UTILISATEUR_PORTAIL.md) : guide utilisateur
- [LDAP_SETUP.md](LDAP_SETUP.md) et [docs/ACTIVE_DIRECTORY_SETUP.md](docs/ACTIVE_DIRECTORY_SETUP.md) : Active Directory
- [docs/ADMINISTRATION_CENTRALE.md](docs/ADMINISTRATION_CENTRALE.md) : administration
- [docs/MODULE_MENU.md](docs/MODULE_MENU.md) : module menu
- [docs/JAVASCRIPT_MODULES.md](docs/JAVASCRIPT_MODULES.md) : modules JavaScript
- [ROADMAP_IMPLEMENTATION.md](ROADMAP_IMPLEMENTATION.md) : feuille de route

## Support

it@nere-mining.bf
