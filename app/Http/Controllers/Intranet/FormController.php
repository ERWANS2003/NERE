<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormController extends Controller
{
    /**
     * Affiche la liste des formulaires
     */
    public function index(Request $request): View
    {
        // Pour l'instant, on affiche une page de placeholder
        // Les formulaires dynamiques seront implémentés plus tard
        return view('intranet.forms.index');
    }

    /**
     * Affiche le formulaire de création
     */
    public function create(): View
    {
        // À implémenter
        return view('intranet.forms.create');
    }

    /**
     * Enregistre un nouveau formulaire
     */
    public function store(Request $request)
    {
        // À implémenter
        return redirect()->route('intranet.forms.index');
    }

    /**
     * Affiche un formulaire
     */
    public function show($form): View
    {
        // À implémenter
        return view('intranet.forms.show');
    }

    /**
     * Affiche le formulaire d'édition
     */
    public function edit($form): View
    {
        // À implémenter
        return view('intranet.forms.edit');
    }

    /**
     * Met à jour un formulaire
     */
    public function update(Request $request, $form)
    {
        // À implémenter
        return redirect()->route('intranet.forms.show', $form);
    }

    /**
     * Supprime un formulaire
     */
    public function destroy($form)
    {
        // À implémenter
        return redirect()->route('intranet.forms.index');
    }
}