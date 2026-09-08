<?php

namespace App\Core\PluginSystem;

/**
 * Plugin Interface - Permet l'extensibilité infinie du système
 * Chaque module peut être ajouté sans modifier le core
 */
interface PluginInterface
{
    /**
     * Nom unique du plugin
     */
    public function getName(): string;

    /**
     * Version du plugin
     */
    public function getVersion(): string;

    /**
     * Description du plugin
     */
    public function getDescription(): string;

    /**
     * Dépendances requises
     */
    public function getDependencies(): array;

    /**
     * Initialisation du plugin
     */
    public function boot(): void;

    /**
     * Routes du plugin
     */
    public function registerRoutes(): void;

    /**
     * Vues du plugin
     */
    public function registerViews(): array;

    /**
     * Permissions du plugin
     */
    public function registerPermissions(): array;

    /**
     * Configuration par défaut
     */
    public function getDefaultConfig(): array;

    /**
     * Widgets du plugin pour le dashboard
     */
    public function registerWidgets(): array;

    /**
     * Hooks pour l'automatisation
     */
    public function registerHooks(): array;
}
