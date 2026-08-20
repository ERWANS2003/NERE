# ITSM Nere Mining

Application interne de gestion des services IT pour les sites miniers de Nere Mining.
## Architecture

L'application est construite avec Laravel, PHP 8.3 et MySQL. Elle expose une interface web authentifiee et une API interne protegee par Sanctum.
```text
Utilisateurs -> Application Laravel -> API -> MySQL
									  |
									  -> Notifications (mail, interne, Slack/Teams/SMS configurables)
```

## Modules
- Authentification, roles et permissions
- Tickets: recherche, filtres, commentaires, pieces jointes, historique et workflow ITIL
- Priorite automatique via matrice impact/urgence
- Affectation automatique par categorie, equipe, site et charge
- SLA avec mise en pause sur les tickets en attente
- Tableau de bord et rapports CSV, PDF et Excel
- Actifs, licences et association actifs/tickets
- Base de connaissances avec suggestions avant creation d'un ticket
- Administration des utilisateurs, sites, categories, SLA, priorites et notifications

## Installation
Prerequis: PHP 8.3+, Composer, Node.js/npm et MySQL.

```bash
composer install
copy .env.example .env
php artisan key:generate

Configurer ensuite la connexion MySQL dans `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=itsm_nere
DB_USERNAME=root
DB_PASSWORD=

Puis initialiser l'application:

```bash
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Pour le developpement frontend, utiliser `npm run dev`.

## Comptes de demonstration
Le seeder cree plusieurs profils avec le mot de passe initial `password`:

- `admin@nere-mining.bf` : administrateur
- `dsi@nere-mining.bf` : DSI
- `ibrahim.ouedraogo@nere-mining.bf` : technicien
- `salif.diallo@nere-mining.bf` : demandeur

Changer ces mots de passe avant toute utilisation partagee.

## Tests
Les tests utilisent SQLite en memoire:

```bash
php artisan test
```

Les migrations restent compatibles avec SQLite pour les tests; l'index `FULLTEXT` de la base de connaissances est active automatiquement sur MySQL.
<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
