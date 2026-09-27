<?php

namespace App\Http\Controllers;

use App\Models\OperationalZone;
use App\Models\SafetyIncident;
use App\Models\User;
use Illuminate\Http\Request;

class SafetyIncidentController extends Controller
{
    /**
     * Display a listing of safety incidents.
     *
     * The filters and the views used to be written against columns that do not
     * exist in the schema (`status`, `incident_type`, `location`,
     * `incident_number`, `title`); every one of those requests was a 500. They
     * are now expressed against the real `statut` / `severity` / `titre` columns.
     */
    public function index(Request $request)
    {
        $query = SafetyIncident::with(['reporter', 'investigator', 'operationalZone'])
            ->latest('reported_at');

        $query->when(
            $request->filled('severity'),
            fn ($q) => $q->where('severity', $request->string('severity')->toString())
        );

        $query->when(
            $request->filled('statut'),
            fn ($q) => $q->where('statut', $request->string('statut')->toString())
        );

        $query->when(
            $request->filled('zone_id'),
            fn ($q) => $q->where('operational_zone_id', $request->integer('zone_id'))
        );

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->toString().'%';
            $query->where(fn ($q) => $q
                ->where('titre', 'ILIKE', $search)
                ->orWhere('description', 'ILIKE', $search));
        }

        $query->when($request->boolean('unresolved'), fn ($q) => $q->unresolved());
        $query->when($request->boolean('critical'), fn ($q) => $q->critical());

        $incidents = $query->paginate(15)->withQueryString();

        return view('safety.index', [
            'incidents' => $incidents,
            'zones' => $this->zones(),
            'statuses' => SafetyIncident::statuts(),
            'severities' => SafetyIncident::severites(),
        ]);
    }

    public function create()
    {
        return view('safety.create', [
            'zones' => $this->zones(),
            'severities' => SafetyIncident::severites(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'severity' => ['required', 'in:'.implode(',', array_keys(SafetyIncident::severites()))],
            'operational_zone_id' => ['required', 'integer', 'exists:operational_zones,id'],
            'incident_at' => ['required', 'date'],
        ]);

        $incident = SafetyIncident::create([
            'titre' => $validated['titre'],
            'description' => $validated['description'],
            'severity' => $validated['severity'],
            'operational_zone_id' => $validated['operational_zone_id'],
            'incident_at' => $validated['incident_at'],
            'reported_by' => $request->user()->id,
            'reported_at' => now(),
            'statut' => 'reported',
        ]);

        return redirect()->route('safety.show', $incident)
            ->with('success', 'Incident de sÃ©curitÃ© enregistrÃ©.');
    }

    public function show(SafetyIncident $incident)
    {
        $incident->load(['reporter', 'investigator', 'operationalZone']);

        return view('safety.show', [
            'incident' => $incident,
            'zones' => $this->zones(),
            'statuses' => SafetyIncident::statuts(),
            'severities' => SafetyIncident::severites(),
            'investigateurs' => $this->investigateurs(),
        ]);
    }

    public function edit(SafetyIncident $incident)
    {
        $incident->load(['reporter', 'investigator', 'operationalZone']);

        return view('safety.edit', [
            'incident' => $incident,
            'zones' => $this->zones(),
            'statuses' => SafetyIncident::statuts(),
            'severities' => SafetyIncident::severites(),
            'investigateurs' => $this->investigateurs(),
        ]);
    }

    public function update(Request $request, SafetyIncident $incident)
    {
        $validated = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'severity' => ['required', 'in:'.implode(',', array_keys(SafetyIncident::severites()))],
            'statut' => ['required', 'in:'.implode(',', array_keys(SafetyIncident::statuts()))],
            'operational_zone_id' => ['required', 'integer', 'exists:operational_zones,id'],
            'investigated_by' => ['nullable', 'integer', 'exists:users,id'],
            'investigation_notes' => ['nullable', 'string'],
        ]);

        // Keep resolved_at consistent with the statut instead of letting the two
        // drift apart.
        $validated['resolved_at'] = in_array($validated['statut'], ['resolved', 'closed'], true)
            ? ($incident->resolved_at ?? now())
            : null;

        $incident->update($validated);

        return redirect()->route('safety.show', $incident)
            ->with('success', 'Incident de sÃ©curitÃ© mis Ã  jour.');
    }

    public function destroy(SafetyIncident $incident)
    {
        $incident->delete();

        return redirect()->route('safety.index')
            ->with('success', 'Incident de sÃ©curitÃ© supprimÃ©.');
    }

    public function assignInvestigation(Request $request, SafetyIncident $incident)
    {
        $validated = $request->validate([
            'investigated_by' => ['required', 'integer', 'exists:users,id'],
        ]);

        $incident->update([
            'investigated_by' => $validated['investigated_by'],
            'statut' => 'investigating',
        ]);

        return back()->with('success', 'Investigation assignÃ©e.');
    }

    public function resolve(Request $request, SafetyIncident $incident)
    {
        $validated = $request->validate([
            'investigation_notes' => ['required', 'string'],
            'corrective_actions' => ['nullable', 'array'],
            'corrective_actions.*' => ['string', 'max:500'],
        ]);

        $incident->update([
            'statut' => 'resolved',
            'resolved_at' => now(),
            'investigated_by' => $incident->investigated_by ?? $request->user()->id,
            'investigation_notes' => $validated['investigation_notes'],
            'corrective_actions' => $validated['corrective_actions'] ?? [],
        ]);

        return redirect()->route('safety.show', $incident)
            ->with('success', 'Incident marquÃ© comme rÃ©solu.');
    }

    public function statistics()
    {
        $total = SafetyIncident::count();
        $unresolved = SafetyIncident::unresolved()->count();
        $critical = SafetyIncident::critical()->count();
        $recent30 = SafetyIncident::recent(30)->count();

        // `byType` grouped on a column that does not exist; the closest real
        // dimension is the operational zone, so that is what the page shows.
        $bySeverity = SafetyIncident::selectRaw('severity, count(*) as total')
            ->groupBy('severity')
            ->pluck('total', 'severity');

        $byStatut = SafetyIncident::selectRaw('statut, count(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $byZone = SafetyIncident::selectRaw('operational_zone_id, count(*) as total')
            ->groupBy('operational_zone_id')
            ->pluck('total', 'operational_zone_id')
            ->mapWithKeys(fn ($total, $zoneId) => [
                OperationalZone::whereKey($zoneId)->value('nom') ?? 'Zone supprimÃ©e' => $total,
            ]);

        return view('safety.statistics', compact(
            'total', 'unresolved', 'critical', 'recent30', 'bySeverity', 'byStatut', 'byZone'
        ) + [
            'severites' => SafetyIncident::severites(),
            'statuses' => SafetyIncident::statuts(),
        ]);
    }

    /** @return \Illuminate\Support\Collection */
    protected function zones()
    {
        return OperationalZone::where('actif', true)->orderBy('nom')->get();
    }

    /** @return \Illuminate\Support\Collection */
    protected function investigateurs()
    {
        // There is no dedicated HSE role in this schema, so investigators are
        // the technicians plus the roles that can manage safety.
        return User::where('actif', true)
            ->where(function ($q) {
                $q->where('est_technicien', true)
                    ->orWhereHas('role', fn ($r) => $r->whereIn('slug', ['admin', 'dsi']));
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
