<?php

namespace App\Core\PluginSystem;

/**
 * Base Plugin - Classe abstraite facilitant la création de plugins
 * Les développeurs héritent de cette classe au lieu d'implémenter l'interface
 */
abstract class BasePlugin implements PluginInterface
{
    protected string $name;
    protected string $version = '1.0.0';
    protected string $description = '';
    protected array $dependencies = [];
    protected array $config = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function getDefaultConfig(): array
    {
        return $this->config;
    }

    /**
     * Implémentation par défaut - peut être surchargée
     */
    public function boot(): void
    {
        // Logique d'initialisation par défaut
    }

    public function registerRoutes(): void
    {
        // Routes par défaut - à surcharger si nécessaire
    }

    public function registerViews(): array
    {
        return [];
    }

    public function registerPermissions(): array
    {
        return [];
    }

    public function registerWidgets(): array
    {
        return [];
    }

    public function registerHooks(): array
    {
        return [];
    }

    /**
     * Helper pour récupérer le chemin du plugin
     */
    protected function getPath(): string
    {
        return app_path("Plugins/{$this->name}");
    }

    /**
     * Helper pour récupérer une config du plugin
     */
    protected function getConfig(string $key, $default = null)
    {
        return config("plugins.{$this->name}.{$key}", $default);
    }
}
