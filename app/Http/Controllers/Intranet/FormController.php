<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Intranet\Form;
use Illuminate\Support\Facades\Auth;

class FormController extends Controller
{
    /** Affiche un formulaire publié et son rendu dynamique. */
    public function show(Form $form)
    {
        abort_unless($form->isPublished(), 404);

        $user = Auth::user();
        $dept = $form->service->department;

        // Vérifier que l'user a accès au département
        if (! $user->is_super_admin) {
            $member = $dept->users()->where('users.id', $user->id)->exists();
            abort_unless($member, 403);
        }

        $fields = $form->fields()->orderBy('position')->get();

        return view('intranet.forms.show', compact('form', 'fields', 'dept'));
    }
}
