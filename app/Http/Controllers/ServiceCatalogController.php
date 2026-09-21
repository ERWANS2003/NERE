<?php

namespace App\Http\Controllers;

use App\Models\ServiceCatalog;
use App\Models\ServiceRequest;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use Illuminate\Http\Request;

/**
 * Contrôleur pour le Catalogue de Services
 * Interface visuelle pour soumettre des demandes
 */
class ServiceCatalogController extends Controller
{
    /**
     * Affiche le catalogue de services
     */
    public function index()
    {
        $services = ServiceCatalog::active()
            ->ordered()
            ->with('category')
            ->get()
            ->groupBy('category.name');

        $myRequests = ServiceRequest::where('requester_id', auth()->id())
            ->with(['service', 'approver'])
            ->latest()
            ->limit(5)
            ->get();

        return view('services.catalog', compact('services', 'myRequests'));
    }

    /**
     * Affiche le formulaire de demande pour un service
     */
    public function show(ServiceCatalog $service)
    {
        if (!$service->is_active) {
            abort(404, 'Service non disponible');
        }

        return view('services.request', compact('service'));
    }

    /**
     * Soumet une demande de service
     */
    public function submitRequest(Request $request, ServiceCatalog $service)
    {
        // Valider les champs dynamiques du formulaire
        $formData = $this->validateDynamicForm($request, $service->request_form);

        // Créer la demande
        $serviceRequest = ServiceRequest::create([
            'service_id' => $service->id,
            'requester_id' => auth()->id(),
            'form_data' => $formData,
            'status' => $service->requires_approval ? 'pending' : 'approved',
        ]);

        // Si pas d'approbation requise, créer le ticket directement
        if (!$service->requires_approval) {
            $ticket = $this->createTicketFromRequest($serviceRequest, $service);
            $serviceRequest->update(['ticket_id' => $ticket->id]);

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', 'Votre demande a été créée automatiquement en ticket.');
        }

        return redirect()
            ->route('services.my-requests')
            ->with('success', 'Votre demande a été soumise et est en attente d\'approbation.');
    }

    /**
     * Affiche mes demandes de service
     */
    public function myRequests()
    {
        $requests = ServiceRequest::where('requester_id', auth()->id())
            ->with(['service', 'approver', 'ticket'])
            ->latest()
            ->paginate(15);

        return view('services.my-requests', compact('requests'));
    }

    /**
     * Affiche les demandes à approuver (pour managers)
     */
    public function pendingApprovals()
    {
        $this->authorize('approve-service-requests');

        $requests = ServiceRequest::pending()
            ->with(['service', 'requester'])
            ->latest()
            ->paginate(15);

        return view('services.approvals', compact('requests'));
    }

    /**
     * Approuve une demande
     */
    public function approve(Request $request, ServiceRequest $serviceRequest)
    {
        $this->authorize('approve-service-requests');

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500'
        ]);

        $serviceRequest->approve(auth()->id(), $validated['notes'] ?? null);

        // Créer le ticket
        $ticket = $this->createTicketFromRequest($serviceRequest, $serviceRequest->service);
        $serviceRequest->update(['ticket_id' => $ticket->id]);

        return back()->with('success', 'Demande approuvée et ticket créé.');
    }

    /**
     * Rejette une demande
     */
    public function reject(Request $request, ServiceRequest $serviceRequest)
    {
        $this->authorize('approve-service-requests');

        $validated = $request->validate([
            'notes' => 'required|string|max:500'
        ]);

        $serviceRequest->reject(auth()->id(), $validated['notes']);

        return back()->with('success', 'Demande rejetée.');
    }

    /**
     * Valide un formulaire dynamique
     */
    protected function validateDynamicForm(Request $request, array $formFields): array
    {
        $rules = [];
        $data = [];

        foreach ($formFields as $field) {
            $name = $field['name'];
            $rules[$name] = [];

            if ($field['required'] ?? false) {
                $rules[$name][] = 'required';
            } else {
                $rules[$name][] = 'nullable';
            }

            // Type validation
            $rules[$name][] = match ($field['type']) {
                'email' => 'email',
                'number' => 'numeric',
                'date' => 'date',
                'url' => 'url',
                default => 'string'
            };

            if (isset($field['max'])) {
                $rules[$name][] = 'max:' . $field['max'];
            }
        }

        return $request->validate($rules);
    }

    /**
     * Crée un ticket depuis une demande de service
     */
    protected function createTicketFromRequest(ServiceRequest $serviceRequest, ServiceCatalog $service)
    {
        $formattedData = collect($serviceRequest->form_data)
            ->map(fn($value, $key) => "**{$key}:** {$value}")
            ->join("\n");

        $category = $service->category()->with('team')->first();
        $priority = TicketPriority::where('nom', 'Normale')->first() ?? TicketPriority::orderBy('niveau')->firstOrFail();
        $status = TicketStatus::where('slug', 'nouveau')->firstOrFail();

        return Ticket::create([
            'titre' => "Service: {$service->name}",
            'description' => "Demande de service soumise via le catalogue.\n\n{$formattedData}",
            'user_id' => $serviceRequest->requester_id,
            'departement_id' => $category?->team?->departement_id,
            'ticket_category_id' => $service->category_id,
            'assigned_to' => $service->default_assignee_id,
            'team_id' => $service->default_team_id,
            'ticket_status_id' => $status->id,
            'ticket_priority_id' => $priority->id,
        ]);
    }
}
