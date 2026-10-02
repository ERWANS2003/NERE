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
        // Les IDs de widgets utilisent des underscores (my_tickets) alors que les
        // partials sont nommés avec des tirets (my-tickets).
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

        if ($data === null && $widgetId !== 'quick_actions') {
            return response()->json([
                'html' => '<div class="text-center py-4 text-gray-500">Widget non disponible</div>',
                'error' => "Widget inconnu : {$widgetId}",
                'success' => false
            ]);
        }

        // Retourner le HTML du widget
        try {
            $viewName = 'components.widgets.' . str_replace('_', '-', $widgetId);
            $html = view($viewName, ['data' => $data])->render();
            return response()->json(['html' => $html, 'success' => true]);
        } catch (\Throwable $e) {
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
            ->with(['statut', 'priorite', 'categorie'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'total' => $tickets->count(),
            'tickets' => $tickets,
            'by_status' => $tickets->groupBy('statut.nom')->map->count(),
        ];
    }

    protected function getTicketOverviewData(): array
    {
        $tickets = \App\Models\Ticket::query()->with(['statut', 'priorite']);
        if (! auth()->user()->hasPermission('tickets.view')) {
            $tickets->where('user_id', auth()->id());
        }

        $ticketList = (clone $tickets)->get();

        // Tendance sur les 7 derniers jours (créations par jour)
        $start = now()->subDays(6)->startOfDay();
        $createdPerDay = (clone $tickets)
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn ($t) => $t->created_at->format('Y-m-d'))
            ->map->count();

        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $trend[] = [
                'date' => $date,
                'label' => now()->subDays($i)->translatedFormat('d M'),
                'count' => (int) ($createdPerDay[$date] ?? 0),
            ];
        }

        return [
            'total' => $ticketList->count(),
            'by_status' => $ticketList->groupBy('statut.nom')->map->count(),
            'by_priority' => $ticketList->groupBy('priorite.nom')->map->count(),
            'trend' => $trend,
        ];
    }

    protected function getSafetyAlertsData(): array
    {
        if (! auth()->user()->hasPermission('safety.view')) {
            return ['critical_count' => 0, 'alerts' => collect()];
        }

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
        if (! auth()->user()->hasPermission('reports.view')) {
            return ['activities' => collect()];
        }

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
        $query = \App\Models\Ticket::query();
        if (! auth()->user()->hasPermission('tickets.view')) {
            $query->where('user_id', auth()->id());
        }

        $total = (clone $query)->count();
        $breached = (clone $query)->where('sla_depasse', true)->count();

        return [
            'compliance_rate' => $total > 0 ? round((($total - $breached) / $total) * 100, 1) : 100,
            'at_risk' => (clone $query)->whereNotNull('date_echeance_resolution')->whereBetween('date_echeance_resolution', [now(), now()->addHours(24)])->count(),
            'breached' => $breached,
        ];
    }

    protected function getTeamPerformanceData(): array
    {
        $now = now();
        $resolutionRoles = [
            'technicien_maintenance',
            'chef_equipe_maintenance',
            'responsable_it',
            'consultant_systeme',
            'maintenance_manager',
        ];

        // Temps moyen de résolution (heures) sur les 30 derniers jours
        $resolvedLast30 = \App\Models\Ticket::whereNotNull('date_resolution')
            ->where('date_resolution', '>=', $now->copy()->subDays(30))
            ->get(['created_at', 'date_resolution']);

        $avgResolution = $resolvedLast30->isEmpty()
            ? 0
            : round($resolvedLast30->avg(
                fn ($t) => $t->created_at->diffInMinutes($t->date_resolution) / 60
            ), 1);

        $resolvedToday = \App\Models\Ticket::whereDate('date_resolution', $now->toDateString())->count();

        // Tendance : résolutions des 7 derniers jours vs les 7 précédents
        $current = \App\Models\Ticket::whereBetween('date_resolution', [
            $now->copy()->subDays(7)->startOfDay(),
            $now->copy()->endOfDay(),
        ])->count();

        $previous = \App\Models\Ticket::whereBetween('date_resolution', [
            $now->copy()->subDays(14)->startOfDay(),
            $now->copy()->subDays(7)->startOfDay(),
        ])->count();

        $trendPct = $previous > 0
            ? round((($current - $previous) / $previous) * 100, 1)
            : ($current > 0 ? 100.0 : 0.0);

        $teamMembers = \App\Models\User::whereHas('role', fn ($q) => $q->whereIn('slug', $resolutionRoles))
            ->where('is_actif', true)
            ->count();

        $activeTickets = \App\Models\Ticket::whereHas('statut', fn ($q) => $q->where('est_final', false))->count();

        // Satisfaction moyenne des tickets notés
        $avgSatisfaction = round(
            (float) \App\Models\Ticket::whereNotNull('satisfaction_note')
                ->where('satisfaction_note', '>', 0)
                ->avg('satisfaction_note'),
            1
        );

        return [
            'avg_resolution_time' => $avgResolution,
            'tickets_resolved_today' => $resolvedToday,
            'customer_satisfaction' => $avgSatisfaction,
            'team_members' => $teamMembers,
            'active_tickets' => $activeTickets,
            'trend_pct' => $trendPct,
            'trend_direction' => $trendPct >= 0 ? 'up' : 'down',
            'resolved_last_7d' => $current,
        ];
    }
}
