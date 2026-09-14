<?php

namespace App\Http\Controllers;

use App\Models\SafetyIncident;
use App\Models\OperationalZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SafetyIncidentController extends Controller
{
    /**
     * Display a listing of safety incidents.
     */
    public function index(Request $request)
    {
        $query = SafetyIncident::with(['reporter', 'investigator', 'operationalZone'])
            ->latest('reported_at');

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('incident_type', $request->type);
        }

        // Filter by zone
        if ($request->filled('zone_id')) {
            $query->where('operational_zone_id', $request->zone_id);
        }

        // Search
        if ($request->filled('q')) {
            $search = '%' . $request->q . '%';
            $query->where(function ($q) use ($search) {
                $q->where('incident_number', 'like', $search)
                    ->orWhere('title', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        // Scope: unresolved only
        if ($request->boolean('unresolved')) {
            $query->unresolved();
        }

        // Scope: critical only
        if ($request->boolean('critical')) {
            $query->critical();
        }

        $incidents = $query->paginate(15)->withQueryString();
        $zones = OperationalZone::orderBy('nom')->get();
        $statuses = ['open', 'under_investigation', 'resolved', 'closed'];
        $severities = ['low', 'medium', 'high', 'critical'];
        $types = ['near_miss', 'injury', 'equipment_damage', 'environmental', 'security', 'other'];

        return view('safety.index', compact('incidents', 'zones', 'statuses', 'severities', 'types'));
    }

    /**
     * Show the form for creating a new safety incident.
     */
    public function create()
    {
        $zones = OperationalZone::orderBy('nom')->get();
        $severities = ['low', 'medium', 'high', 'critical'];
        $types = ['near_miss', 'injury', 'equipment_damage', 'environmental', 'security', 'other'];

        return view('safety.create', compact('zones', 'severities', 'types'));
    }

    /**
     * Store a newly created safety incident.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'incident_number' => 'required|unique:safety_incidents|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'severity' => 'required|in:low,medium,high,critical',
            'incident_type' => 'required|in:near_miss,injury,equipment_damage,environmental,security,other',
            'location' => 'required|string|max:255',
            'operational_zone_id' => 'nullable|exists:operational_zones,id',
            'reported_at' => 'required|date_format:Y-m-d H:i',
        ]);

        $validated['reported_by'] = Auth::id();
        $validated['status'] = 'open';

        $incident = SafetyIncident::create($validated);

        return redirect()->route('safety.show', $incident)
            ->with('success', 'Incident de sécurité enregistré avec succès.');
    }

    /**
     * Display the specified safety incident.
     */
    public function show(SafetyIncident $incident)
    {
        $incident->load(['reporter', 'investigator', 'operationalZone']);
        $zones = OperationalZone::orderBy('nom')->get();
        $statuses = ['open', 'under_investigation', 'resolved', 'closed'];

        return view('safety.show', compact('incident', 'zones', 'statuses'));
    }

    /**
     * Show the form for editing the specified safety incident.
     */
    public function edit(SafetyIncident $incident)
    {
        $incident->load(['reporter', 'investigator', 'operationalZone']);
        $zones = OperationalZone::orderBy('nom')->get();
        $statuses = ['open', 'under_investigation', 'resolved', 'closed'];

        return view('safety.edit', compact('incident', 'zones', 'statuses'));
    }

    /**
     * Update the specified safety incident.
     */
    public function update(Request $request, SafetyIncident $incident)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'severity' => 'required|in:low,medium,high,critical',
            'location' => 'required|string|max:255',
            'operational_zone_id' => 'nullable|exists:operational_zones,id',
            'status' => 'required|in:open,under_investigation,resolved,closed',
            'investigated_by' => 'nullable|exists:users,id',
            'root_cause' => 'nullable|string',
            'corrective_actions' => 'nullable|string',
        ]);

        // If marking as resolved, set resolved_at
        if ($validated['status'] === 'resolved' && !$incident->resolved_at) {
            $validated['resolved_at'] = now();
        }

        $incident->update($validated);

        return redirect()->route('safety.show', $incident)
            ->with('success', 'Incident de sécurité mis à jour avec succès.');
    }

    /**
     * Remove the specified safety incident.
     */
    public function destroy(SafetyIncident $incident)
    {
        $incident->delete();

        return redirect()->route('safety.index')
            ->with('success', 'Incident de sécurité supprimé.');
    }

    /**
     * Assign investigation to user.
     */
    public function assignInvestigation(Request $request, SafetyIncident $incident)
    {
        $validated = $request->validate([
            'investigated_by' => 'required|exists:users,id',
        ]);

        $incident->update([
            'investigated_by' => $validated['investigated_by'],
            'status' => 'under_investigation',
        ]);

        return redirect()->back()
            ->with('success', 'Investigation assignée avec succès.');
    }

    /**
     * Resolve the incident.
     */
    public function resolve(Request $request, SafetyIncident $incident)
    {
        $validated = $request->validate([
            'root_cause' => 'required|string',
            'corrective_actions' => 'required|string',
        ]);

        $incident->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'root_cause' => $validated['root_cause'],
            'corrective_actions' => $validated['corrective_actions'],
        ]);

        return redirect()->route('safety.show', $incident)
            ->with('success', 'Incident marqué comme résolu.');
    }

    /**
     * Generate incident statistics.
     */
    public function statistics()
    {
        $total = SafetyIncident::count();
        $unresolved = SafetyIncident::unresolved()->count();
        $critical = SafetyIncident::critical()->count();
        $recent30 = SafetyIncident::recent(30)->count();

        $bySeverity = SafetyIncident::groupBy('severity')
            ->selectRaw('severity, count(*) as count')
            ->get()
            ->pluck('count', 'severity');

        $byType = SafetyIncident::groupBy('incident_type')
            ->selectRaw('incident_type, count(*) as count')
            ->get()
            ->pluck('count', 'incident_type');

        $byZone = SafetyIncident::with('operationalZone')
            ->groupBy('operational_zone_id')
            ->selectRaw('operational_zone_id, count(*) as count')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->operationalZone?->nom ?? 'Unknown' => $item->count];
            });

        return view('safety.statistics', compact(
            'total',
            'unresolved',
            'critical',
            'recent30',
            'bySeverity',
            'byType',
            'byZone'
        ));
    }
}
