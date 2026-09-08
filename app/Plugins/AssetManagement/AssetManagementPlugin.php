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
            Route::get('/', [AssetController::class, 'index'])->name('assets.index');
            Route::get('/create', [AssetController::class, 'create'])->name('assets.create');
            Route::post('/', [AssetController::class, 'store'])->name('assets.store');
            Route::get('/{asset}', [AssetController::class, 'show'])->name('assets.show');
            Route::get('/{asset}/edit', [AssetController::class, 'edit'])->name('assets.edit');
            Route::put('/{asset}', [AssetController::class, 'update'])->name('assets.update');
            Route::delete('/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');
            
            // Fonctionnalités avancées
            Route::post('/{asset}/maintenance', [AssetController::class, 'scheduleMaintenance'])->name('assets.maintenance');
            Route::post('/{asset}/depreciation', [AssetController::class, 'calculateDepreciation'])->name('assets.depreciation');
            Route::get('/{asset}/history', [AssetController::class, 'history'])->name('assets.history');
            Route::post('/{asset}/qrcode', [AssetController::class, 'generateQRCode'])->name('assets.qrcode');
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
