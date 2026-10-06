<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    /**
     * Affiche la liste des soumissions
     */
    public function index(Request $request): View
    {
        // À implémenter quand les formulaires seront créés
        return view('intranet.submissions.index');
    }

    /**
     * Enregistre une nouvelle soumission
     */
    public function store(Request $request, $form)
    {
        // À implémenter
        return redirect()->route('intranet.forms.show', $form);
    }

    /**
     * Affiche une soumission
     */
    public function show($submission): View
    {
        // À implémenter
        return view('intranet.submissions.show');
    }

    /**
     * Met à jour une soumission
     */
    public function update(Request $request, $submission)
    {
        // À implémenter
        return redirect()->route('intranet.submissions.show', $submission);
    }

    /**
     * Supprime une soumission
     */
    public function destroy($submission)
    {
        // À implémenter
        return redirect()->route('intranet.submissions.index');
    }
}