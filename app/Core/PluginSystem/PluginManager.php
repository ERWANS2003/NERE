<?php

namespace App\Core\PluginSystem;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Plugin Manager - Gère le cycle de vie des plugins
 * Permet d'ajouter des fonctionnalités sans modifier le core
 */
class PluginManager
{
    protected array $plugins = [];
    protected array $loadedPlugins = [];
    protected string $pluginsPath;

    public function __construct()
    {
        $this->pluginsPath = app_path('Plugins');
    }

    /**
     * Découvre tous les plugins disponibles
     */
    public function discover(): void
    {
        if (!File::exists($this->pluginsPath)) {
            File::makeDirectory($this->pluginsPath, 0755, true);
            return;
        }

        $pluginDirectories = File::directories($this->pluginsPath);

        foreach ($pluginDirectories as $directory) {
            $pluginName = basename($directory);
            $pluginClass = "App\\Plugins\\{$pluginName}\\{$pluginName}Plugin";

            if (class_exists($pluginClass)) {
                $this->plugins[$pluginName] = $pluginClass;
            }
        }
    }

    /**
     * Charge et initialise tous les plugins actifs
     */
    public function loadAll(): void
    {
        $this->discover();

        foreach ($this->plugins as $name => $class) {
            if ($this->isEnabled($name)) {
                $this->load($name);
            }
        }
    }

    /**
     * Charge un plugin spécifique
     */
    public function load(string $name): bool
    {
        if (isset($this->loadedPlugins[$name])) {
            return true;
        }

        if (!isset($this->plugins[$name])) {
            Log::warning("Plugin {$name} not found");
            return false;
        }

        try {
            $plugin = new $this->plugins[$name]();
            
            // Vérifier les dépendances
            $dependencies = $plugin->getDependencies();
            foreach ($dependencies as $dependency) {
                if (!$this->isLoaded($dependency)) {
                    $this->load($dependency);
                }
            }

            // Initialiser le plugin
            $plugin->boot();
            $plugin->registerRoutes();

            $this->loadedPlugins[$name] = $plugin;

            Log::info("Plugin {$name} loaded successfully");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to load plugin {$name}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si un plugin est chargé
     */
    public function isLoaded(string $name): bool
    {
        return isset($this->loadedPlugins[$name]);
    }

    /**
     * Vérifie si un plugin est activé dans la config
     */
    public function isEnabled(string $name): bool
    {
        return config("plugins.enabled.{$name}", false);
    }

    /**
     * Active un plugin
     */
    public function enable(string $name): bool
    {
        // Sauvegarder dans la config ou DB
        return true;
    }

    /**
     * Désactive un plugin
     */
    public function disable(string $name): bool
    {
        unset($this->loadedPlugins[$name]);
        return true;
    }

    /**
     * Récupère tous les plugins chargés
     */
    public function getLoaded(): array
    {
        return $this->loadedPlugins;
    }

    /**
     * Récupère tous les widgets des plugins
     */
    public function getAllWidgets(): array
    {
        $widgets = [];
        foreach ($this->loadedPlugins as $plugin) {
            $widgets = array_merge($widgets, $plugin->registerWidgets());
        }
        return $widgets;
    }

    /**
     * Récupère toutes les permissions des plugins
     */
    public function getAllPermissions(): array
    {
        $permissions = [];
        foreach ($this->loadedPlugins as $plugin) {
            $permissions = array_merge($permissions, $plugin->registerPermissions());
        }
        return $permissions;
    }
}
