<?php

namespace App\Http\Controllers;

use App\Models\WorkflowAutomation;
use Illuminate\Http\Request;

/**
 * Contrôleur pour la gestion des automations workflow
 * Interface visuelle no-code pour créer des règles
 */
class WorkflowAutomationController extends Controller
{
    /**
     * Liste toutes les automations
     */
    public function index()
    {
        $this->authorize('manage-automations');

        $automations = WorkflowAutomation::with('creator')
            ->latest()
            ->paginate(15);

        return view('automations.index', compact('automations'));
    }

    /**
     * Affiche l'interface de création
     */
    public function create()
    {
        $this->authorize('manage-automations');

        $availableEvents = $this->getAvailableEvents();
        $availableActions = $this->getAvailableActions();
        $availableFields = $this->getAvailableFields();

        return view('automations.create', compact('availableEvents', 'availableActions', 'availableFields'));
    }

    /**
     * Sauvegarde une nouvelle automation
     */
    public function store(Request $request)
    {
        $this->authorize('manage-automations');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'trigger_event' => 'required|string',
            'conditions' => 'required|array',
            'conditions.*.field' => 'required|string',
            'conditions.*.operator' => 'required|string',
            'conditions.*.value' => 'required',
            'actions' => 'required|array|min:1',
            'actions.*.type' => 'required|string',
            'actions.*.data' => 'required|array',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();

        $automation = WorkflowAutomation::create($validated);

        return redirect()
            ->route('automations.index')
            ->with('success', 'Automation créée avec succès');
    }

    /**
     * Affiche une automation
     */
    public function show(WorkflowAutomation $automation)
    {
        $this->authorize('manage-automations');

        $automation->load(['creator', 'logs' => fn($q) => $q->latest()->limit(20)]);

        return view('automations.show', compact('automation'));
    }

    /**
     * Affiche le formulaire d'édition
     */
    public function edit(WorkflowAutomation $automation)
    {
        $this->authorize('manage-automations');

        $availableEvents = $this->getAvailableEvents();
        $availableActions = $this->getAvailableActions();
        $availableFields = $this->getAvailableFields();

        return view('automations.edit', compact('automation', 'availableEvents', 'availableActions', 'availableFields'));
    }

    /**
     * Met à jour une automation
     */
    public function update(Request $request, WorkflowAutomation $automation)
    {
        $this->authorize('manage-automations');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'trigger_event' => 'required|string',
            'conditions' => 'required|array',
            'actions' => 'required|array|min:1',
            'is_active' => 'boolean',
        ]);

        $automation->update($validated);

        return redirect()
            ->route('automations.show', $automation)
            ->with('success', 'Automation mise à jour');
    }

    /**
     * Active/désactive une automation
     */
    public function toggle(WorkflowAutomation $automation)
    {
        $this->authorize('manage-automations');

        $automation->update(['is_active' => !$automation->is_active]);

        return back()->with('success', $automation->is_active ? 'Automation activée' : 'Automation désactivée');
    }

    /**
     * Supprime une automation
     */
    public function destroy(WorkflowAutomation $automation)
    {
        $this->authorize('manage-automations');

        $automation->delete();

        return redirect()
            ->route('automations.index')
            ->with('success', 'Automation supprimée');
    }

    /**
     * Teste une automation
     */
    public function test(Request $request, WorkflowAutomation $automation)
    {
        $this->authorize('manage-automations');

        $testContext = $request->input('context', []);

        // Tester les conditions
        $conditionsMatch = $automation->evaluateConditions($testContext);

        // Simuler les actions (sans les exécuter)
        $simulatedActions = collect($automation->actions)->map(function ($action) {
            return [
                'type' => $action['type'],
                'description' => $this->getActionDescription($action),
                'would_execute' => true
            ];
        });

        return response()->json([
            'conditions_match' => $conditionsMatch,
            'actions' => $simulatedActions,
            'message' => $conditionsMatch 
                ? 'Les conditions correspondent, les actions seraient exécutées' 
                : 'Les conditions ne correspondent pas, aucune action ne serait exécutée'
        ]);
    }

    /**
     * Événements disponibles
     */
    protected function getAvailableEvents(): array
    {
        return [
            'ticket.created' => ['name' => 'Ticket Créé', 'icon' => 'plus', 'fields' => ['priority', 'category', 'title', 'description']],
            'ticket.updated' => ['name' => 'Ticket Modifié', 'icon' => 'edit', 'fields' => ['priority', 'category', 'status']],
            'ticket.assigned' => ['name' => 'Ticket Assigné', 'icon' => 'user', 'fields' => ['assigned_to', 'priority']],
            'ticket.status_changed' => ['name' => 'Statut Changé', 'icon' => 'refresh', 'fields' => ['status', 'old_status']],
            'sla.warning' => ['name' => 'SLA à Risque', 'icon' => 'clock', 'fields' => ['time_remaining', 'priority']],
            'sla.breached' => ['name' => 'SLA Dépassé', 'icon' => 'alert', 'fields' => ['breach_time', 'priority']],
            'asset.maintenance_due' => ['name' => 'Maintenance Due', 'icon' => 'tools', 'fields' => ['asset_id', 'days_remaining']],
            'safety.incident_reported' => ['name' => 'Incident Sécurité', 'icon' => 'safety', 'fields' => ['severity', 'location']],
        ];
    }

    /**
     * Actions disponibles
     */
    protected function getAvailableActions(): array
    {
        return [
            'assign_ticket' => ['name' => 'Assigner Ticket', 'icon' => 'user', 'params' => ['user_id']],
            'change_status' => ['name' => 'Changer Statut', 'icon' => 'refresh', 'params' => ['status_id']],
            'change_priority' => ['name' => 'Changer Priorité', 'icon' => 'flag', 'params' => ['priority_id']],
            'send_notification' => ['name' => 'Envoyer Notification', 'icon' => 'bell', 'params' => ['recipient', 'message']],
            'create_ticket' => ['name' => 'Créer Ticket', 'icon' => 'plus', 'params' => ['title', 'description', 'category_id']],
            'add_comment' => ['name' => 'Ajouter Commentaire', 'icon' => 'message', 'params' => ['content']],
            'escalate' => ['name' => 'Escalader', 'icon' => 'arrow-up', 'params' => ['level']],
            'send_email' => ['name' => 'Envoyer Email', 'icon' => 'mail', 'params' => ['to', 'subject', 'body']],
        ];
    }

    /**
     * Champs disponibles pour conditions
     */
    protected function getAvailableFields(): array
    {
        return [
            'priority' => ['name' => 'Priorité', 'type' => 'select', 'options' => ['critical', 'high', 'normal', 'low']],
            'category' => ['name' => 'Catégorie', 'type' => 'select', 'options' => []],
            'status' => ['name' => 'Statut', 'type' => 'select', 'options' => []],
            'assigned_to' => ['name' => 'Assigné à', 'type' => 'user'],
            'title' => ['name' => 'Titre', 'type' => 'text'],
            'description' => ['name' => 'Description', 'type' => 'text'],
            'severity' => ['name' => 'Sévérité', 'type' => 'select', 'options' => ['critical', 'high', 'medium', 'low']],
            'location' => ['name' => 'Localisation', 'type' => 'text'],
        ];
    }

    /**
     * Génère une description lisible d'une action
     */
    protected function getActionDescription(array $action): string
    {
        return match ($action['type']) {
            'assign_ticket' => "Assigner à l'utilisateur ID {$action['data']['user_id']}",
            'change_status' => "Changer le statut vers ID {$action['data']['status_id']}",
            'send_notification' => "Envoyer une notification à {$action['data']['recipient']}",
            'escalate' => "Escalader au niveau {$action['data']['level']}",
            default => "Exécuter {$action['type']}"
        };
    }
}
