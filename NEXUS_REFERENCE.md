# Référence Nexus - Intranet Dashboard Moderne

## 📍 Emplacement
Le projet Nexus a été cloné dans : `C:\Users\erwan\OneDrive\Bureau\NEREINTERNE\nexus-reference`

## 📖 À propos
Nexus est un tableau de bord d'intranet moderne construit avec **React 19**, **TypeScript**, et **Tailwind CSS v4**.

## 🎯 Fonctionnalités clés

### 1. Dashboard Hub
- Design responsive (mobile, tablette, desktop)
- Mode sombre/clair avec persistance
- Mode focus pour productivité
- Drag & Drop pour layout personnalisable

### 2. Engagement Employés
- Système de reconnaissance entre pairs (Kudos)
- Annuaire employés avec filtres
- Organigramme interactif
- Gestion de profils

### 3. Collaboration Équipe
- Calendrier d'équipe avec événements
- Système d'annonces prioritaires
- Liens rapides personnalisables
- Fil d'actualités personnalisé

### 4. Expérience Utilisateur
- Recherche globale (Cmd/Ctrl+K)
- Navigation au clavier
- États de chargement fluides
- Design accessible (WCAG)

## 🛠 Stack Technique

### Frontend
- **React 19** - Dernière version avec fonctionnalités concurrentes
- **TypeScript 5.8** - Développement type-safe
- **Vite** - Build ultra-rapide
- **React Router DOM** - Routing côté client

### Styling & UI
- **Tailwind CSS v4** - Dernière version
- **Radix UI** - Composants accessibles
- **shadcn/ui** - Composants réutilisables
- **Lucide React** - Icônes

### État & Stockage
- **React Context API** - Gestion d'état global
- **localStorage** - Persistance des préférences
- **Custom Hooks** - Logique réutilisable

## 📁 Structure du Projet

```
nexus-reference/
├── src/
│   ├── components/         # Composants UI réutilisables
│   │   ├── ui/            # Composants base shadcn/ui
│   │   ├── CompanyAnnouncements.tsx
│   │   ├── EmployeeDirectory.tsx
│   │   ├── Header.tsx
│   │   ├── KudosFeed.tsx
│   │   └── ...
│   ├── pages/             # Composants de pages
│   ├── contexts/          # Contextes React
│   ├── hooks/             # Hooks personnalisés
│   ├── lib/               # Fonctions utilitaires
│   ├── types/             # Définitions TypeScript
│   └── data/              # Données mock et constantes
└── public/                # Assets statiques
```

## 🎨 Composants Principaux

| Composant | Description | Fonctionnalités |
|-----------|-------------|-----------------|
| **For You Feed** | Contenu personnalisé | Mises à jour, tâches, actualités |
| **Company Announcements** | Communications officielles | Niveaux de priorité, recherche/filtre |
| **Employee Directory** | Annuaire équipe | Filtres département, recherche |
| **Kudos Feed** | Reconnaissance pairs | Donner/recevoir kudos, temps réel |
| **Team Calendar** | Gestion événements | Anniversaires, événements entreprise |
| **Quick Links** | Favoris | Drag & drop, ajout/suppression |

## 💡 Idées à Intégrer dans Notre Projet

### Court terme
1. **Mode Focus** - Cacher certaines sections pour concentration
2. **Drag & Drop Cards** - Réorganisation des cartes du dashboard
3. **Recherche Globale** - Cmd+K pour recherche rapide
4. **Quick Links** - Section de liens favoris personnalisables

### Moyen terme
1. **Kudos/Recognition System** - Reconnaissance entre collègues
2. **Calendrier d'équipe** - Avec anniversaires et événements
3. **Fil d'actualités** - Feed personnalisé par département
4. **Onboarding Checklist** - Pour nouveaux utilisateurs

### Long terme
1. **Organigramme Interactif** - Visualisation hiérarchie
2. **Système de Widgets** - Dashboard totalement personnalisable
3. **Notifications en Temps Réel** - WebSockets pour updates live
4. **Analytics Dashboard** - Métriques d'utilisation

## 🔍 Points d'Intérêt Technique

### 1. Architecture des Composants
- Composants atomiques réutilisables
- Séparation claire entre logique et présentation
- TypeScript strict pour type safety

### 2. Gestion d'État
- Context API pour état global
- Custom hooks pour logique métier
- localStorage pour persistance

### 3. Styling
- Tailwind CSS v4 avec @theme
- Variants avec class-variance-authority
- Système de tokens de design cohérent

### 4. UX/UI
- Design system cohérent
- Animations subtiles avec Tailwind
- Accessibilité (WCAG compliance)
- Mobile-first approach

## 📚 Documentation Technique

### Fichiers Importants
- `DASHBOARD_ICONS.md` - Documentation des icônes
- `WAVE_ANIMATION_SYSTEM.md` - Système d'animations
- `components.json` - Configuration shadcn/ui

### Pour Démarrer
```bash
cd C:\Users\erwan\OneDrive\Bureau\NEREINTERNE\nexus-reference
npm install
npm run dev
```

## 🎯 Prochaines Étapes

1. **Analyser les composants** - Explorer les composants React pour inspiration
2. **Étudier l'architecture** - Comprendre la structure de contextes et hooks
3. **Extraire des patterns** - Identifier les patterns réutilisables
4. **Adapter au PHP/Laravel** - Transposer les concepts dans notre stack

## 📝 Notes

- Projet React moderne avec dernières best practices
- Bonne séparation des responsabilités
- Code TypeScript bien typé
- Design system cohérent et moderne
- Accessible et responsive
- Peut servir d'inspiration pour notre UI/UX Laravel

## ⚠️ À Ne Pas Oublier

- **Ne pas copier directement** - S'inspirer mais adapter à notre contexte Laravel
- **Respect des licences** - MIT License, attribution requise si utilisation directe
- **Stack différent** - React vs Laravel Blade, adapter les concepts
- **Besoins spécifiques** - Notre système est ITSM, pas juste intranet générique

---

**Référence clonée le** : 2026-10-08
**Emplacement** : `C:\Users\erwan\OneDrive\Bureau\NEREINTERNE\nexus-reference`
**GitHub** : https://github.com/praveen-sripati/nexus
**License** : MIT
