# 🏢 ITSM Néré Mining

**Plateforme de Gestion des Services Informatiques (ITSM) moderne et extensible**

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP: 8.3+](https://img.shields.io/badge/PHP-8.3%2B-blue.svg)](https://www.php.net)
[![Laravel: 13](https://img.shields.io/badge/Laravel-13-red.svg)](https://laravel.com)
[![PostgreSQL: 13+](https://img.shields.io/badge/PostgreSQL-13%2B-336791.svg)](https://www.postgresql.org)

## 🎯 Vue d'ensemble

ITSM Néré Mining est une application web complète pour gérer:
- **Tickets support** avec workflow intelligent
- **Gestion des actifs** (inventaire IT)
- **Incidents de sécurité** avec investigation
- **SLA avancé** avec pause/escalade
- **Kanban Board** drag-drop pour les tickets
- **Recherche avancée** avec sauvegarde de filtres
- **Modèles de tickets** réutilisables
- **Rapports & Analytics** avec exports PDF/Excel
- **Notifications real-time** et audit trail complet

## ✨ Caractéristiques Principales

### 🎫 Gestion des Tickets
- Création/édition/suppression de tickets
- Workflow d'état (Ouvert → En cours → Fermé)
- Priorités et catégories
- Assignation intelligente
- Commentaires et pièces jointes
- Historique complet des modifications

### 📊 Analytics & Rapports
- Tableaux de bord avec statistiques
- Graphiques (trends, distributions)
- Export PDF et Excel
- Performance des techniciens
- Respect des SLA

### 🎯 SLA & Escalade
- Gestion des accords de niveau de service
- Gauges visuels de progression (%)
- Pause et reprise SLA
- Escalade automatique de priorité
- Alertes 75% et dépassement

### 🔍 Recherche & Filtrage
- Recherche full-text (ref, titre, description)
- Filtres multi-critères
- Saved searches par utilisateur
- Quick search API (typeahead)
- Tri avancé

### 📋 Modèles & Templates
- Modèles réutilisables de tickets
- Pré-remplissage automatique
- Grid layout avec preview
- Gestion centralisée

### 🛒 Gestion des Actifs
- Inventaire IT complet
- Suivi des garanties
- Association utilisateur/département/site
- Historique des modifications

### ⚠️ Incident Security
- Enregistrement d'incidents de sécurité
- Investigation workflow
- Statistiques et trends
- Rapport détaillé

### 🔔 Notifications
- Real-time notifications (polling)
- Bell component avec badge count
- Actions (marquer lu, supprimer)
- Types: tickets, SLA, commentaires, incidents

## 🚀 Démarrage Rapide

### Localement

1. **Cloner le repository**
   ```bash
   git clone https://github.com/ERWANS2003/NERE.git
   cd NERE
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   npm install
   ```

3. **Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Base de données** (PostgreSQL)
   ```bash
   php artisan migrate --seed
   ```

5. **Assets**
   ```bash
   npm run build
   # ou en développement
   npm run dev
   ```

6. **Serveur de développement**
   ```bash
   php artisan serve
   ```

Accédez à http://localhost:8000

### Sur Railway (Production)

Voir `RAILWAY_DEPLOYMENT.md` pour la procédure complète (5 minutes)

```bash
# TL;DR
npm i -g @railway/cli
railway init
railway add # PostgreSQL
railway variables # Ajouter les secrets
railway up
```

## 📁 Structure du Projet

```
├── app/
│   ├── Models/              # Éloquent models (20+)
│   ├── Http/
│   │   ├── Controllers/     # RESTful controllers (15+)
│   │   └── Middleware/      # Auth, role, permission
│   └── Services/            # Business logic (SLA, assignment, etc.)
├── database/
│   ├── migrations/          # Schema migrations (20+)
│   ├── seeders/            # Sample data
│   └── factories/          # Model factories
├── resources/
│   ├── views/              # Blade templates (40+ pages)
│   │   ├── layouts/        # Portal layout unifié
│   │   ├── tickets/        # Ticket management
│   │   ├── kanban/         # Kanban board
│   │   ├── templates/      # Ticket templates
│   │   └── ...
│   ├── css/                # Tailwind CSS
│   └── js/                 # Alpine.js components
├── routes/
│   ├── web.php            # Web routes (80+)
│   ├── api.php            # API endpoints
│   └── channels.php       # Broadcasting
├── tests/
│   └── Feature/           # Integration tests
├── Procfile               # Railway deployment
├── DEPLOYMENT.md          # Deployment guide
└── RAILWAY_DEPLOYMENT.md  # 5-minute setup
```

## 🔐 Authentification & Autorisation

- **Authentification**: Laravel Sanctum (Web + API)
- **Autorisations**: Rôles et permissions (Admin, DSI, Tech, User)
- **Middleware**: CheckRole, CheckPermission
- **Session**: Base de données (persistante)

### Rôles Par Défaut
- **Admin**: Accès total
- **DSI**: Rapports, SLA, administration
- **Technicien**: Tickets, assets, incidents
- **Utilisateur**: Créer tickets, voir ses tickets

## 📊 Modèles de Données Clés

```
User
├── Tickets (créés + assignés)
├── Comments
├── SavedSearches
└── DashboardNotifications

Ticket
├── Status → TicketStatus
├── Priority → TicketPriority
├── Category → TicketCategory
├── Assignee → User
├── Creator → User
├── SLA → SLA
├── Comments
├── Attachments
├── History → TicketHistory
└── Assets (many-to-many)

Asset
├── Type → AssetType
├── Owner → User
├── Department → Departement
└── Site → Site

SLA
├── Priority → TicketPriority
└── Site → Site (optionnel)

DashboardNotification
├── User
└── Data (JSON)

SavedSearch
├── User
└── Filters (JSON)

TicketTemplate
├── Category → TicketCategory
├── Priority → TicketPriority
└── Creator → User
```

## 🧪 Tests

```bash
# Tous les tests
php artisan test

# Tests spécifiques
php artisan test tests/Feature/NotificationApiTest.php

# Avec couverture
php artisan test --coverage
```

## 🔧 Configuration

### Variables d'Environnement Critiques
```env
APP_ENV=production|local
APP_DEBUG=false|true
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_DATABASE=nere_mining_itsm
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### Caching
```bash
php artisan config:cache      # Config
php artisan view:cache        # Views
php artisan event:cache       # Events
php artisan route:cache       # Routes
```

## 📈 Performance

- **Indexes**: Sur colonnes critiques (user_id, status_id, created_at)
- **Eager Loading**: Relations chargées avec `with()`
- **Pagination**: 20 items par défaut
- **Caching**: Config, views, routes cachés en production
- **Session**: Base de données pour scalabilité

## 🚨 Monitoring

### Logs
```bash
# Voir les logs
tail -f storage/logs/laravel.log

# Railway
railway logs --tail 50
```

### Health Check
```bash
# Test DB
php artisan tinker
>>> DB::connection()->getPdo()

# Test Cache
php artisan tinker
>>> Cache::put('test', 'value', 60)
>>> Cache::get('test')
```

## 🐛 Dépannage

| Problème | Solution |
|----------|----------|
| Migrations échouées | `php artisan migrate:rollback` puis `migrate` |
| Assets ne se chargent pas | `npm run build` et `php artisan view:clear` |
| Session perte | Vérifier `SESSION_DRIVER=database` |
| Cache invalide | `php artisan cache:clear config:clear view:clear` |
| DB connexion | Vérifier `.env` et `DB_HOST` |

## 📚 Documentation

- **Déploiement complet**: `DEPLOYMENT.md`
- **Quick Railway**: `RAILWAY_DEPLOYMENT.md`
- **Résumé implémentation**: `IMPLEMENTATION_SUMMARY.md`
- **Code API**: Voir routes dans `routes/api.php`

## 🤝 Contribution

1. Fork le repository
2. Créez une branche feature (`git checkout -b feature/amazing`)
3. Commit les changements (`git commit -m 'Add amazing feature'`)
4. Push la branche (`git push origin feature/amazing`)
5. Ouvrez une Pull Request

## 📄 License

MIT - Voir `LICENSE` pour détails

## 👥 Auteur

**Erwan S**  
GitHub: [@ERWANS2003](https://github.com/ERWANS2003)

## 📞 Support

- **Issues**: GitHub Issues
- **Documentation**: Voir fichiers `.md` du projet
- **Railway Support**: https://discord.gg/railway

---

## 🎯 Roadmap Futur

- [ ] #6 Améliorer Intelligence Tickets
- [ ] #7 Email Integration (IMAP)
- [ ] #10 Chatbot Support
- [ ] #12 Custom Fields Framework
- [ ] #14 Multi-Language Support
- [ ] #15 AI-Powered Routing
- [ ] #16 Semantic Search
- [ ] #17 Gamification

---

**Status**: ✅ Production Ready  
**Version**: 1.0  
**Dernière mise à jour**: Septembre 2026
