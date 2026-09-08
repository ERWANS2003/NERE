# 🏭 ITSM Nere Mining - Système de Gestion Complet pour Opérations Minières

> **Plateforme ITSM intuitive, moderne et infiniment extensible pour la gestion complète des opérations minières**

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-336791?logo=postgresql)](https://postgresql.org)
[![License](https://img.shields.io/badge/License-Proprietary-red)](LICENSE)

---

## 🎯 Vue d'Ensemble

**ITSM Nere Mining** est une plateforme complète de gestion des services IT et opérations minières, conçue pour être:

- ✅ **Intuitive** - Interface moderne et facile à prendre en main
- ✅ **Personnalisable** - Dashboard drag-and-drop, widgets configurables
- ✅ **Extensible à l'infini** - Architecture modulaire avec système de plugins
- ✅ **Automatisée** - Workflows intelligents et règles if-then
- ✅ **Sécurisée** - Gestion fine des rôles et permissions
- ✅ **Professionnelle** - Design épuré avec icônes de qualité

---

## 🚀 Fonctionnalités Principales

### 📊 Dashboard Personnalisable
- **Drag-and-drop** - Réorganisez les widgets par glisser-déposer
- **Widgets dynamiques** - Plus de 10 widgets disponibles (tickets, analytics, sécurité, activité...)
- **Configuration par utilisateur** - Chaque utilisateur personnalise sa vue
- **Rafraîchissement auto** - Données en temps réel
- **Bibliothèque de widgets** - Ajoutez facilement de nouveaux widgets

### 🎫 Gestion Complète des Tickets
- Création, assignation, workflow intelligent
- Statuts personnalisables (Nouveau, Ouvert, En cours, Résolu, Fermé...)
- Priorités basées sur impact/urgence
- Commentaires et pièces jointes
- Historique complet et audit trail
- SLA tracking avec alertes

### 🏢 Structure Organisationnelle
- **11 rôles hiérarchiques** (Directeur Général → Consultant)
- **32+ permissions granulaires** par module
- Départements, équipes, sites miniers
- Gestion des certifications et habilitations

### 🛠️ Gestion des Assets & Équipements
- Inventaire complet des équipements miniers
- Tracking de la maintenance préventive/corrective
- QR Codes pour identification rapide
- Historique de maintenance
- Gestion des licences et garanties

### 🔒 Sécurité & Conformité
- Gestion des incidents de sécurité
- Zones opérationnelles avec restrictions
- Audit logs complets
- Gestion des équipements de protection (PPE)
- Inspections et conformité environnementale

### 📚 Base de Connaissances
- Articles avec catégories et tags
- Recherche intelligente
- Suggestions contextuelles
- Publication par versions

### 📈 Rapports & Analytics
- Dashboard analytique avec métriques
- Exports PDF/Excel/CSV
- Métriques de performance (SLA, temps de résolution, satisfaction)
- Graphiques et visualisations

### 🔄 Automatisation & Workflows
- **Engine d'automatisation** - Règles if-then configurables
- Déclencheurs d'événements (ticket créé, SLA dépassé, etc.)
- Actions automatiques (assignation, notification, escalade...)
- Logs d'exécution des automations

### 🧩 Système de Plugins (Extensibilité Infinie)
Architecture modulaire permettant d'ajouter des fonctionnalités sans modifier le core:

**Plugins disponibles:**
- Asset Management (gestion avancée des équipements)
- Safety Management (incidents, inspections, PPE)
- Maintenance Scheduler (planification maintenance)
- Inventory Control (gestion stocks)
- Production Tracking (suivi production)
- Vehicle Fleet (gestion flotte véhicules)
- Environmental Compliance (conformité environnementale)
- Contractor Management (sous-traitants)
- Training & Certification (formations, certifications)
- Quality Assurance (contrôle qualité)

**Création de plugins facile:**
```php
class MonPlugin extends BasePlugin {
    protected string $name = 'MonPlugin';
    protected string $version = '1.0.0';
    
    public function boot() { /* ... */ }
    public function registerRoutes() { /* ... */ }
    public function registerWidgets() { /* ... */ }
    public function registerPermissions() { /* ... */ }
}
```

### 🎨 Design System
- **Thème professionnel** - Couleurs mining (charcoal, gold, earthy tones)
- **Icônes SVG** - 30+ icônes professionnelles
- **Composants Blade réutilisables** - Icons, status badges, cards...
- **Responsive** - Mobile-friendly
- **Accessibilité** - Conformité WCAG

---

## 📦 Installation

### Prérequis
- PHP >= 8.2
- Composer
- PostgreSQL >= 15
- Node.js >= 18

### Installation Locale

```bash
# Cloner le repository
git clone https://github.com/nere-mining/itsm.git
cd itsm-nere-mining

# Installer les dépendances
composer install
npm install

# Configuration environnement
cp .env.example .env
php artisan key:generate

# Configuration base de données
# Modifier .env avec vos credentials PostgreSQL

# Migrations et seeders
php artisan migrate --seed

# Compiler les assets
npm run build

# Lancer le serveur
php artisan serve
```

### Déploiement Railway (Production)

Le projet est configuré pour déploiement automatique sur Railway.

**URL Production:** https://adorable-patience-production-1697.up.railway.app

**Déploiement:**
```bash
# Push vers main déclenche auto-déploiement
git push origin main

# Railway exécute automatiquement:
# - composer install
# - php artisan migrate --force
# - php artisan db:seed --force
```

---

## 🔐 Accès par Défaut

Après installation, un compte admin est créé automatiquement:

```
Email: admin@nere-mining.bf
Password: admin123
```

⚠️ **Important:** Changez ce mot de passe en production!

---

## 🏗️ Architecture

### Structure du Projet

```
app/
├── Core/
│   ├── PluginSystem/          # Système de plugins
│   │   ├── PluginInterface.php
│   │   ├── PluginManager.php
│   │   └── BasePlugin.php
│   └── Dashboard/
│       └── WidgetManager.php  # Gestion widgets
├── Plugins/                   # Plugins extensibles
│   └── AssetManagement/
├── Models/                    # Eloquent models
├── Http/Controllers/
├── Services/                  # Business logic
├── Enums/
└── Notifications/

database/
├── migrations/                # Schema database
└── seeders/                   # Données initiales

resources/
├── views/
│   ├── layouts/
│   │   └── app-new.blade.php # Layout principal
│   ├── dashboard/
│   │   └── customizable.blade.php # Dashboard personnalisable
│   ├── components/           # Composants réutilisables
│   │   ├── icon.blade.php
│   │   └── status-badge.blade.php
│   └── tickets/
└── css/
    └── theme.css             # Theme mining professionnel

config/
└── plugins.php               # Configuration plugins
```

### Technologies

- **Backend:** Laravel 11, PHP 8.2
- **Database:** PostgreSQL 15
- **Frontend:** Blade Templates, Alpine.js, Tailwind CSS
- **Icons:** SVG custom (30+ icons professionnels)
- **Deployment:** Docker, Railway

---

## 🔌 Système de Plugins

### Ajouter un Nouveau Plugin

1. **Créer la structure:**
```bash
mkdir -p app/Plugins/MonPlugin
```

2. **Créer la classe plugin:**
```php
// app/Plugins/MonPlugin/MonPluginPlugin.php
namespace App\Plugins\MonPlugin;

use App\Core\PluginSystem\BasePlugin;

class MonPluginPlugin extends BasePlugin
{
    protected string $name = 'MonPlugin';
    protected string $version = '1.0.0';
    protected string $description = 'Description de mon plugin';

    public function boot(): void
    {
        // Initialisation
    }

    public function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])->group(function () {
            Route::get('/mon-plugin', [MonController::class, 'index']);
        });
    }

    public function registerWidgets(): array
    {
        return [
            'mon_widget' => [
                'name' => 'Mon Widget',
                'component' => 'MonWidgetComponent',
                'description' => 'Description du widget',
                'icon' => 'dashboard',
                'category' => 'custom',
                'default_size' => 'medium',
            ],
        ];
    }

    public function registerPermissions(): array
    {
        return [
            'monplugin.view' => 'Voir mon plugin',
            'monplugin.edit' => 'Modifier mon plugin',
        ];
    }
}
```

3. **Activer le plugin:**
```php
// config/plugins.php
'enabled' => [
    'MonPlugin' => true,
],
```

Le plugin est automatiquement chargé au boot de l'application!

---

## 🎨 Design System

### Composants Disponibles

#### Icon Component
```blade
<x-icon name="dashboard" size="md" />
<x-icon name="tickets" size="lg" />
<x-icon name="safety" size="sm" />
```

**Icons disponibles:** dashboard, tickets, assets, teams, safety, reports, users, settings, operations, maintenance, production, quality, environment, contractor, training, inventory, chart-bar, clock, lightning, activity, tools, cloud, edit, trash, plus, refresh, close, check, info, dots-vertical, search, filter, download, upload, etc.

#### Status Badge
```blade
<x-status-badge :status="'new'" type="status" />
<x-status-badge :status="'critical'" type="priority" />
<x-status-badge :status="'active'" type="operational" />
```

### Palette de Couleurs

```css
/* Primary Accent (Gold) */
--accent-500: #daa520;
--accent-600: #b8860b;

/* Background */
--bg-50: #fafbf8;
--bg-100: #f5f7f2;

/* Text */
--text-800: #1a1916;
--text-600: #4a4744;

/* Status Colors */
--status-success: #059669;
--status-warning: #d97706;
--status-critical: #dc2626;
--status-info: #0284c7;
```

---

## 🤖 Automatisation

### Créer une Automation

```php
WorkflowAutomation::create([
    'name' => 'Auto-escalade tickets critiques',
    'trigger_event' => 'ticket.created',
    'conditions' => [
        ['field' => 'priority', 'operator' => '=', 'value' => 'critical'],
        ['field' => 'category', 'operator' => '=', 'value' => 'securite'],
    ],
    'actions' => [
        ['type' => 'assign_ticket', 'assignee_id' => 1],
        ['type' => 'send_notification', 'recipient' => 'manager'],
        ['type' => 'escalate', 'level' => 2],
    ],
    'is_active' => true,
]);
```

### Événements Disponibles

- `ticket.created` - Ticket créé
- `ticket.updated` - Ticket modifié
- `ticket.assigned` - Ticket assigné
- `ticket.status_changed` - Statut changé
- `sla.warning` - SLA à risque
- `sla.breached` - SLA dépassé
- `asset.maintenance_due` - Maintenance due
- `safety.incident_reported` - Incident sécurité
- `user.login` - Connexion utilisateur

---

## 👥 Hiérarchie des Rôles

1. **Directeur Général** - Vision globale, tous les accès
2. **Directeur des Opérations** - Gestion opérations minières
3. **Directeur Administratif et Financier** - Gestion administrative
4. **Directeur IT** - Gestion infrastructure IT
5. **Chef de Département** - Gestion département
6. **Superviseur** - Supervision équipe
7. **Responsable Sécurité** - Gestion sécurité et conformité
8. **Technicien Senior** - Résolution tickets complexes
9. **Technicien** - Résolution tickets standards
10. **Opérateur** - Opérations terrain
11. **Consultant** - Accès limité consultation

### Permissions Principales

- **Tickets:** view, create, edit, delete, assign, resolve, close
- **Assets:** view, create, edit, delete, maintenance, depreciation
- **Teams:** view, create, edit, delete, manage_members
- **Safety:** view_incidents, report_incidents, manage_inspections
- **Reports:** view, generate, export
- **Users:** view, create, edit, delete, manage_roles
- **Settings:** view, manage_system, manage_integrations

---

## 📊 Dashboard Widgets

### Widgets Core

- **Vue d'ensemble Tickets** - Stats globales tickets
- **Mes Tickets** - Tickets assignés à l'utilisateur
- **Performance Équipe** - Métriques équipe
- **Conformité SLA** - État SLA
- **Actions Rapides** - Raccourcis fréquents
- **Activité Récente** - Dernières activités
- **Alertes Sécurité** - Incidents critiques
- **Météo Site** - Conditions météo

### Widgets Plugins

Chaque plugin peut ajouter ses propres widgets:
- Asset Overview (Asset Management)
- Maintenance Calendar (Maintenance Scheduler)
- Production Metrics (Production Tracking)
- Inventory Levels (Inventory Control)
- Vehicle Status (Vehicle Fleet)
- Environmental Compliance (Environmental)

---

## 🔧 Configuration

### Variables d'Environnement (.env)

```env
APP_NAME="ITSM Nere Mining"
APP_ENV=production
APP_URL=https://adorable-patience-production-1697.up.railway.app

DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=railway
DB_USERNAME=postgres
DB_PASSWORD=your-password

# Configuration plugins
PLUGINS_ENABLED=true
PLUGINS_AUTO_DISCOVER=true

# Configuration dashboard
DASHBOARD_CACHE_TTL=3600
WIDGET_REFRESH_INTERVAL=300
```

### Configuration Plugins (config/plugins.php)

```php
'enabled' => [
    'AssetManagement' => true,
    'SafetyManagement' => true,
    'MaintenanceScheduler' => true,
    // ...
],

'AssetManagement' => [
    'features' => [
        'depreciation_tracking' => true,
        'maintenance_scheduling' => true,
        'barcode_scanning' => true,
    ],
],
```

---

## 🧪 Tests

```bash
# Tests unitaires
php artisan test

# Tests fonctionnels
php artisan test --filter=Feature

# Tests avec coverage
php artisan test --coverage
```

---

## 📝 Roadmap

### Phase 1 ✅ - Système Core (Terminé)
- [x] Architecture modulaire avec plugins
- [x] Dashboard personnalisable
- [x] Gestion tickets complète
- [x] Rôles et permissions
- [x] Design system professionnel

### Phase 2 🚧 - En Cours
- [ ] Moteur d'automatisation avancé
- [ ] Catalogue de services visuel
- [ ] Base de connaissances avec IA
- [ ] Notifications temps réel (WebSockets)
- [ ] Analytics avancés avec graphiques

### Phase 3 📋 - Planifié
- [ ] Application mobile (PWA)
- [ ] API REST complète
- [ ] Intégrations tierces (Slack, Teams, Email)
- [ ] Marketplace de plugins
- [ ] Multi-tenancy

### Phase 4 🔮 - Futur
- [ ] IA prédictive (prédiction pannes, assignation intelligente)
- [ ] Chatbot support
- [ ] Reconnaissance vocale
- [ ] Réalité augmentée (maintenance)

---

## 🤝 Contribution

Ce projet est propriétaire et destiné à usage interne.

Pour toute suggestion ou amélioration:
1. Créer une issue sur le repository interne
2. Contacter l'équipe IT: it@nere-mining.bf

---

## 📄 License

Proprietary - © 2026 Nere Mining. Tous droits réservés.

---

## 🆘 Support

- **Email IT:** it@nere-mining.bf
- **Documentation:** https://docs.nere-mining.bf
- **Helpdesk:** Créer un ticket dans le système

---

## 🙏 Remerciements

Développé avec ❤️ par l'équipe IT Nere Mining

**Technologies utilisées:**
- Laravel Framework
- PostgreSQL
- Tailwind CSS
- Alpine.js
- Railway (Hosting)
