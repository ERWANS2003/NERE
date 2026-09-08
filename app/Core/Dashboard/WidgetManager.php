<?php

namespace App\Core\Dashboard;

use App\Core\PluginSystem\PluginManager;
use Illuminate\Support\Facades\Cache;

/**
 * Widget Manager - Gère les widgets du dashboard
 * Permet personnalisation complète par utilisateur
 */
class WidgetManager
{
    protected PluginManager $pluginManager;

    public function __construct(PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    /**
     * Récupère tous les widgets disponibles
     */
    public function getAvailableWidgets(): array
    {
        return Cache::remember('available_widgets', 3600, function () {
            $widgets = [
                // Widgets core du système
                'ticket_overview' => [
                    'name' => 'Vue d\'ensemble Tickets',
                    'component' => 'TicketOverviewWidget',
                    'description' => 'Statistiques et graphiques des tickets',
                    'icon' => 'tickets',
                    'category' => 'tickets',
                    'refresh_interval' => 300,
                    'default_size' => 'large',
                    'configurable' => true,
                ],
                'my_tickets' => [
                    'name' => 'Mes Tickets',
                    'component' => 'MyTicketsWidget',
                    'description' => 'Tickets assignés à moi',
                    'icon' => 'user-tickets',
                    'category' => 'tickets',
                    'refresh_interval' => 60,
                    'default_size' => 'medium',
                ],
                'team_performance' => [
                    'name' => 'Performance Équipe',
                    'component' => 'TeamPerformanceWidget',
                    'description' => 'Métriques de performance de l\'équipe',
                    'icon' => 'chart-bar',
                    'category' => 'analytics',
                    'refresh_interval' => 600,
                    'default_size' => 'large',
                ],
                'sla_compliance' => [
                    'name' => 'Conformité SLA',
                    'component' => 'SlaComplianceWidget',
                    'description' => 'État de conformité aux SLA',
                    'icon' => 'clock',
                    'category' => 'analytics',
                    'refresh_interval' => 300,
                    'default_size' => 'medium',
                ],
                'quick_actions' => [
                    'name' => 'Actions Rapides',
                    'component' => 'QuickActionsWidget',
                    'description' => 'Raccourcis vers actions fréquentes',
                    'icon' => 'lightning',
                    'category' => 'tools',
                    'refresh_interval' => 0,
                    'default_size' => 'small',
                ],
                'recent_activity' => [
                    'name' => 'Activité Récente',
                    'component' => 'RecentActivityWidget',
                    'description' => 'Dernières activités du système',
                    'icon' => 'activity',
                    'category' => 'monitoring',
                    'refresh_interval' => 120,
                    'default_size' => 'medium',
                ],
                'safety_alerts' => [
                    'name' => 'Alertes Sécurité',
                    'component' => 'SafetyAlertsWidget',
                    'description' => 'Incidents et alertes sécurité',
                    'icon' => 'safety',
                    'category' => 'safety',
                    'refresh_interval' => 60,
                    'default_size' => 'medium',
                    'priority' => 'high',
                ],
                'weather' => [
                    'name' => 'Météo Site',
                    'component' => 'WeatherWidget',
                    'description' => 'Conditions météo sur site',
                    'icon' => 'cloud',
                    'category' => 'operations',
                    'refresh_interval' => 1800,
                    'default_size' => 'small',
                ],
            ];

            // Ajouter les widgets des plugins
            $pluginWidgets = $this->pluginManager->getAllWidgets();
            return array_merge($widgets, $pluginWidgets);
        });
    }

    /**
     * Récupère la configuration dashboard d'un utilisateur
     */
    public function getUserDashboard(int $userId): array
    {
        return \DB::table('dashboard_layouts')
            ->where('user_id', $userId)
            ->first()?->layout ?? $this->getDefaultLayout();
    }

    /**
     * Sauvegarde la configuration dashboard
     */
    public function saveDashboardLayout(int $userId, array $layout): bool
    {
        return \DB::table('dashboard_layouts')->updateOrInsert(
            ['user_id' => $userId],
            [
                'layout' => json_encode($layout),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Layout par défaut selon le rôle
     */
    public function getDefaultLayout(): array
    {
        return [
            'grid' => [
                ['widget' => 'my_tickets', 'position' => ['x' => 0, 'y' => 0, 'w' => 6, 'h' => 4]],
                ['widget' => 'quick_actions', 'position' => ['x' => 6, 'y' => 0, 'w' => 3, 'h' => 2]],
                ['widget' => 'safety_alerts', 'position' => ['x' => 9, 'y' => 0, 'w' => 3, 'h' => 2]],
                ['widget' => 'ticket_overview', 'position' => ['x' => 0, 'y' => 4, 'w' => 8, 'h' => 4]],
                ['widget' => 'recent_activity', 'position' => ['x' => 8, 'y' => 4, 'w' => 4, 'h' => 4]],
            ],
        ];
    }

    /**
     * Récupère les catégories de widgets
     */
    public function getCategories(): array
    {
        return [
            'tickets' => ['name' => 'Tickets', 'icon' => 'tickets'],
            'analytics' => ['name' => 'Analyses', 'icon' => 'chart-bar'],
            'operations' => ['name' => 'Opérations', 'icon' => 'operations'],
            'safety' => ['name' => 'Sécurité', 'icon' => 'safety'],
            'assets' => ['name' => 'Assets', 'icon' => 'assets'],
            'monitoring' => ['name' => 'Surveillance', 'icon' => 'activity'],
            'tools' => ['name' => 'Outils', 'icon' => 'tools'],
        ];
    }
}
