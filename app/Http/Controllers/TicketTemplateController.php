<?php

namespace App\Http\Controllers;

use App\Models\TicketTemplate;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TicketTemplateController extends Controller
{
    /**
     * Display list of ticket templates
     */
    public function index()
    {
        // Check if 'actif' column exists before using it
        $query = TicketTemplate::with(['category', 'priorite', 'creator']);
        
        // Only filter by actif if the column exists
        if (Schema::hasColumn('ticket_templates', 'actif')) {
            $query->where('actif', true);
        }
        
        $templates = $query->orderByDesc('created_at')->paginate(20);

        return view('templates.index', compact('templates'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $categories = TicketCategory::where('actif', true)->orderBy('nom')->get();
        $priorities = TicketPriority::orderByDesc('niveau')->get();

        return view('templates.create', compact('categories', 'priorities'));
    }

    /**
     * Store template
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:ticket_templates,name',
            'description' => 'nullable|string|max:500',
            'ticket_category_id' => 'nullable|exists:ticket_categories,id',
            'priorite_id' => 'nullable|exists:ticket_priorities,id',
            'titre_template' => 'required|string|max:255',
            'description_template' => 'required|string|min:10',
        ]);

        $data['created_by'] = auth()->id();

        TicketTemplate::create($data);

        return redirect()->route('templates.index')->with('success', 'Modèle créé avec succès.');
    }

    /**
     * Show template details
     */
    public function show(TicketTemplate $template)
    {
        $template->load(['category', 'priorite', 'creator']);

        return view('templates.show', compact('template'));
    }

    /**
     * Show edit form
     */
    public function edit(TicketTemplate $template)
    {
        $categories = TicketCategory::where('actif', true)->orderBy('nom')->get();
        $priorities = TicketPriority::orderByDesc('niveau')->get();

        return view('templates.edit', compact('template', 'categories', 'priorities'));
    }

    /**
     * Update template
     */
    public function update(Request $request, TicketTemplate $template)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:ticket_templates,name,' . $template->id,
            'description' => 'nullable|string|max:500',
            'ticket_category_id' => 'nullable|exists:ticket_categories,id',
            'priorite_id' => 'nullable|exists:ticket_priorities,id',
            'titre_template' => 'required|string|max:255',
            'description_template' => 'required|string|min:10',
        ]);

        $template->update($data);

        return redirect()->route('templates.show', $template)->with('success', 'Modèle mis à jour.');
    }

    /**
     * Delete template
     */
    public function destroy(TicketTemplate $template)
    {
        $name = $template->name;
        $template->delete();

        return redirect()->route('templates.index')->with('success', "Modèle '{$name}' supprimé.");
    }

    /**
     * Use template to create a new ticket
     */
    public function use(TicketTemplate $template)
    {
        $template->load(['category.team.departement']);

        $prefill = [
            'template_id' => $template->id,
            'ticket_category_id' => $template->ticket_category_id,
            'titre' => $template->titre_template,
            'description' => $template->description_template,
        ];

        // Le service est déduit de la catégorie du modèle pour rester cohérent
        // avec la contrainte de correspondance catégorie/service du formulaire.
        $departementId = $template->category?->team?->departement_id;
        if ($departementId) {
            $prefill['department'] = $departementId;
        }

        return redirect()->route('tickets.create', array_filter($prefill, fn ($v) => $v !== null));
    }
}
