<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Intranet\Form;

class FormController extends Controller
{
    public function show(Form $form)
    {
        abort_unless($form->isPublished(), 404);
        $this->authorize('view', $form);

        $dept   = $form->service->department;
        $fields = $form->fields()->orderBy('position')->get();

        return view('intranet.forms.show', compact('form', 'fields', 'dept'));
    }
}
