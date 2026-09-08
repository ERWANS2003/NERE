<?php

namespace App\Http\Controllers;

use App\Core\Dashboard\WidgetManager;
use Illuminate\Http\Request;

/**
 * Contrôleur pour personnalisation dashboard
 * Interface drag-and-drop friendly
 */
class DashboardCustomizationController extends Controller
{
    public function __construct(
        protected WidgetManager $widgetManager
    ) {}

    /**
     * Page principale du dashboard
     */
    public function index(Request $request)
    {
        $userId = auth()->id();
        $layout = $this->widgetManager->getUserDashboard($userId);
        $availableWidgets = $this->widgetManager->getAvailableWidgets();
        $categories = $this->widgetManager->getCategories();

        return view('dashboard.customizable', compact('layout', 'availableWidgets', 'categories'));
    }

    /**
     * Sauvegarde la configuration du dashboard
     */
    public function saveLayout(Request $request)
    {
        $validated = $request->validate([
            'layout' => 'required|array',
            'layout.*.widget' => 'required|string',
            'layout.*.position' => 'required|array',
            'layout.*.position.x' => 'required|integer|min:0',
            'layout.*.position.y' => 'required|integer|min:0',
            'layout.*.position.w' => 'required|integer|min:1',
            'layout.*.position.h' => 'required|integer|min:1',
        ]);

        $success = $this->widgetManager->saveDashboardLayout(
            auth()->id(),
            $validated['layout']
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Dashboard sauvegardé' : 'Erreur lors de la sauvegarde'
        ]);
    }

    /**
     * Réinitialise au layout par défaut
     */
    public function resetLayout()
    {
        $defaultLayout = $this->widgetManager->getDefaultLayout();
        
        $success = $this->widgetManager->saveDashboardLayout(
            auth()->id(),
            $defaultLayout
        );

        return response()->json([
            'success' => $success,
            'layout' => $defaultLayout
        ]);
    }

    /**
     * Récupère les widgets disponibles par catégorie
     */
    public function getWidgetsByCategory(Request $request)
    {
        $category = $request->query('category');
        $allWidgets = $this->widgetManager->getAvailableWidgets();

        if ($category) {
            $widgets = array_filter($allWidgets, function ($widget) use ($category) {
                return ($widget['category'] ?? null) === $category;
            });
        } else {
            $widgets = $allWidgets;
        }

        return response()->json($widgets);
    }

    /**
     * Données pour un widget spécifique (retourne HTML)
     */
    public function getWidgetData(Request $request, string $widgetId)
    {
        // Charger les données selon le widget
        $data = match ($widgetId) {
            'my_tickets' => $this->getMyTicketsData(),
            'ticket_overview' => $this->getTicketOverviewData(),
            'safety_alerts' => $this->getSafetyAlertsData(),
            'recent_activity' => $this->getRecentActivityData(),
            'sla_compliance' => $this->getSlaComplianceData(),
            'team_performance' => $this->getTeamPerformanceData(),
            'quick_actions' => null, // Pas de données supplémentaires
            default => null
        };

        // Retourner le HTML du widget
        try {
            $html = view("components.widgets.{$widgetId}", ['data' => $data])->render();
            return response()->json(['html' => $html, 'success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'html' => '<div class="text-center py-4 text-gray-500">Widget non disponible</div>',
                'error' => $e->getMessage(),
                'success' => false
            ]);
        }
    }

    protected function getMyTicketsData(): array
    {
        $tickets = \App\Models\Ticket::where('assigned_to', auth()->id())
            ->with(['status', 'priority', 'category'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'total' => $tickets->count(),
            'tickets' => $tickets,
            'by_status' => $tickets->groupBy('status.name')->map->count(),
        ];
    }

    protected function getTicketOverviewData(): array
    {
        $tickets = \App\Models\Ticket::with(['status', 'priority']);
        
        return [
            'total' => $tickets->count(),
            'by_status' => $tickets->get()->groupBy('status.name')->map->count(),
            'by_priority' => $tickets->get()->groupBy('priority.name')->map->count(),
            'trend' => [], // Tendance 7 derniers jours
        ];
    }

    protected function getSafetyAlertsData(): array
    {
        $alerts = \App\Models\SafetyIncident::where('severity', 'critical')
            ->whereNull('resolved_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'critical_count' => $alerts->count(),
            'alerts' => $alerts,
        ];
    }

    protected function getRecentActivityData(): array
    {
        $activities = \App\Models\AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        return [
            'activities' => $activities,
        ];
    }

    protected function getSlaComplianceData(): array
    {
        return [
            'compliance_rate' => 85.5,
            'at_risk' => 12,
            'breached' => 3,
        ];
    }

    protected function getTeamPerformanceData(): array
    {
        return [
            'avg_resolution_time' => '4.5 hours',
            'tickets_resolved_today' => 23,
            'customer_satisfaction' => 4.3,
        ];
    }
}
