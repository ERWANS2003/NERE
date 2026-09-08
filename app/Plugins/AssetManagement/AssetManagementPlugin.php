<?php

namespace App\Plugins\AssetManagement;

use App\Core\PluginSystem\BasePlugin;
use Illuminate\Support\Facades\Route;

/**
 * Plugin de Gestion des Assets
 * Exemple de plugin extensible pour gérer les équipements miniers
 */
class AssetManagementPlugin extends BasePlugin
{
    protected string $name = 'AssetManagement';
    protected string $version = '1.0.0';
    protected string $description = 'Gestion complète des équipements et assets miniers';

    public function boot(): void
    {
        // Enregistrer les vues du plugin
        $this->loadViews();
        
        // Enregistrer les migrations
        $this->loadMigrations();
        
        // Enregistrer les events listeners
        $this->registerEventListeners();
    }

    public function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])->prefix('assets')->group(function () {
            Route::get('/', [\App\Http\Controllers\AssetController::class, 'index'])->name('assets.plugin.index');
            Route::get('/create', [\App\Http\Controllers\AssetController::class, 'create'])->name('assets.plugin.create');
            Route::post('/', [\App\Http\Controllers\AssetController::class, 'store'])->name('assets.plugin.store');
            Route::get('/{asset}', [\App\Http\Controllers\AssetController::class, 'show'])->name('assets.plugin.show');
            Route::get('/{asset}/edit', [\App\Http\Controllers\AssetController::class, 'edit'])->name('assets.plugin.edit');
            Route::put('/{asset}', [\App\Http\Controllers\AssetController::class, 'update'])->name('assets.plugin.update');
            Route::delete('/{asset}', [\App\Http\Controllers\AssetController::class, 'destroy'])->name('assets.plugin.destroy');
            
            // Fonctionnalités avancées
            Route::post('/{asset}/maintenance', [\App\Http\Controllers\AssetController::class, 'scheduleMaintenance'])->name('assets.plugin.maintenance');
            Route::post('/{asset}/depreciation', [\App\Http\Controllers\AssetController::class, 'calculateDepreciation'])->name('assets.plugin.depreciation');
            Route::get('/{asset}/history', [\App\Http\Controllers\AssetController::class, 'history'])->name('assets.plugin.history');
            Route::post('/{asset}/qrcode', [\App\Http\Controllers\AssetController::class, 'generateQRCode'])->name('assets.plugin.qrcode');
        });
    }

    public function registerViews(): array
    {
        return [
            'assets.index' => 'plugins.asset-management.views.index',
            'assets.show' => 'plugins.asset-management.views.show',
            'assets.form' => 'plugins.asset-management.views.form',
        ];
    }

    public function registerPermissions(): array
    {
        return [
            'assets.view' => 'Voir les assets',
            'assets.create' => 'Créer des assets',
            'assets.edit' => 'Modifier des assets',
            'assets.delete' => 'Supprimer des assets',
            'assets.maintenance' => 'Gérer la maintenance',
            'assets.depreciation' => 'Voir les dépréciations',
            'assets.export' => 'Exporter les assets',
        ];
    }

    public function registerWidgets(): array
    {
        return [
            'asset_overview' => [
                'name' => 'Vue d\'ensemble Assets',
                'component' => 'AssetOverviewWidget',
                'description' => 'Statistiques globales des équipements',
                'refresh_interval' => 300, // 5 minutes
                'default_size' => 'medium',
            ],
            'maintenance_calendar' => [
                'name' => 'Calendrier Maintenance',
                'component' => 'MaintenanceCalendarWidget',
                'description' => 'Prochaines maintenances planifiées',
                'refresh_interval' => 600,
                'default_size' => 'large',
            ],
            'asset_alerts' => [
                'name' => 'Alertes Assets',
                'component' => 'AssetAlertsWidget',
                'description' => 'Alertes et notifications critiques',
                'refresh_interval' => 60,
                'default_size' => 'small',
            ],
        ];
    }

    public function registerHooks(): array
    {
        return [
            'asset.created' => 'onAssetCreated',
            'asset.updated' => 'onAssetUpdated',
            'asset.maintenance_due' => 'onMaintenanceDue',
            'asset.warranty_expiring' => 'onWarrantyExpiring',
        ];
    }

    protected function loadViews(): void
    {
        $viewPath = $this->getPath() . '/views';
        \View::addNamespace('asset-management', $viewPath);
    }

    protected function loadMigrations(): void
    {
        $migrationPath = $this->getPath() . '/migrations';
        if (file_exists($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }

    protected function registerEventListeners(): void
    {
        // Event listeners pour automatisation
    }

    // Hook callbacks
    public function onAssetCreated($asset): void
    {
        // Générer QR Code automatiquement
        // Créer entrée d'audit
        // Notifier le département
    }

    public function onMaintenanceDue($asset): void
    {
        // Créer ticket automatiquement
        // Notifier technicien assigné
        // Bloquer utilisation si critique
    }
}
