<?php

namespace App\Http\Controllers;

use App\Models\TicketTemplate;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use Illuminate\Http\Request;

class TicketTemplateController extends Controller
{
    /**
     * Display list of ticket templates
     */
    public function index()
    {
        $templates = TicketTemplate::with(['category', 'priorite', 'creator'])
            ->where('actif', true)
            ->orderByDesc('created_at')
            ->paginate(20);

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
        return redirect()->route('tickets.create', [
            'template_id' => $template->id,
            'titre' => $template->titre_template,
            'description' => $template->description_template,
            'category' => $template->ticket_category_id,
            'priority' => $template->priorite_id,
        ]);
    }
}
