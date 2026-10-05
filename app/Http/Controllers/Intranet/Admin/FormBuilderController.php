<?php

namespace App\Http\Controllers\Intranet\Admin;

use App\Http\Controllers\Controller;
use App\Models\Intranet\Department;
use App\Models\Intranet\Form;
use App\Models\Intranet\FormField;
use App\Models\Intranet\Service;
use App\Models\Intranet\WorkflowStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormBuilderController extends Controller
{
    /** Seuls super admin et directeur du département peuvent accéder. */
    private function authorizeBuilder(Form $form): void
    {
        $user = Auth::user();
        if ($user->is_super_admin) return;

        $dept = $form->service->department;
        $pivot = $dept->users()->where('users.id', $user->id)->wherePivot('role', 'director')->first();
        abort_unless($pivot, 403);
    }

    public function index(Department $department)
    {
        $user = Auth::user();
        if (! $user->is_super_admin) {
            abort_unless(
                $department->users()->where('users.id', $user->id)->wherePivot('role', 'director')->exists(),
                403
            );
        }

        $services = $department->services()->with(['forms'])->get();
        return view('intranet.admin.forms.index', compact('department', 'services'));
    }

    public function store(Request $request, Department $department)
    {
        $user = Auth::user();
        if (! $user->is_super_admin) {
            abort_unless(
                $department->users()->where('users.id', $user->id)->wherePivot('role', 'director')->exists(),
                403
            );
        }

        $data = $request->validate([
            'service_id'  => 'required|exists:intranet_services,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $service = Service::findOrFail($data['service_id']);
        abort_unless($service->department_id === $department->id, 422);

        $version  = Form::where('service_id', $service->id)->max('version') ?? 0;
        $code     = strtoupper($department->code . '-' . \Str::slug($data['name'], '-') . '-V' . ($version + 1));

        $form = Form::create([
            'service_id'  => $service->id,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'code'        => $code,
            'version'     => $version + 1,
            'status'      => 'draft',
        ]);

        return redirect()
            ->route('intranet.admin.forms.edit', [$department, $form])
            ->with('success', "Formulaire \"{$form->name}\" créé (brouillon).");
    }

    public function edit(Department $department, Form $form)
    {
        $this->authorizeBuilder($form);
        $form->load(['fields', 'workflowSteps', 'service']);
        return view('intranet.admin.forms.edit', compact('department', 'form'));
    }

    public function addField(Request $request, Department $department, Form $form)
    {
        $this->authorizeBuilder($form);
        abort_if($form->status === 'archived', 422, 'Formulaire archivé.');

        $data = $request->validate([
            'label'    => 'required|string|max:255',
            'type'     => 'required|in:' . implode(',', FormField::TYPES),
            'required' => 'boolean',
            'options'  => 'nullable|json',
            'rules'    => 'nullable|json',
            'help_text'=> 'nullable|string|max:500',
        ]);

        $maxPos = $form->fields()->max('position') ?? 0;

        FormField::create([
            'form_id'   => $form->id,
            'label'     => $data['label'],
            'type'      => $data['type'],
            'required'  => $data['required'] ?? false,
            'options'   => filled($data['options'] ?? null) ? json_decode($data['options'], true) : null,
            'rules'     => filled($data['rules']   ?? null) ? json_decode($data['rules'],   true) : null,
            'help_text' => $data['help_text'] ?? null,
            'position'  => $maxPos + 1,
        ]);

        return back()->with('success', 'Champ ajouté.');
    }

    public function deleteField(Department $department, Form $form, FormField $field)
    {
        $this->authorizeBuilder($form);
        abort_unless($field->form_id === $form->id, 404);
        $field->delete();
        return back()->with('success', 'Champ supprimé.');
    }

    public function reorderFields(Request $request, Department $department, Form $form)
    {
        $this->authorizeBuilder($form);
        $data = $request->validate(['order' => 'required|array', 'order.*' => 'integer']);

        foreach ($data['order'] as $position => $fieldId) {
            FormField::where('id', $fieldId)->where('form_id', $form->id)
                ->update(['position' => $position + 1]);
        }

        return response()->json(['ok' => true]);
    }

    public function publish(Department $department, Form $form)
    {
        $this->authorizeBuilder($form);
        abort_unless($form->fields()->exists(), 422, 'Un formulaire doit avoir au moins un champ.');

        $form->update(['status' => 'published']);

        return back()->with('success', "Formulaire \"{$form->name}\" publié.");
    }

    public function archive(Department $department, Form $form)
    {
        $this->authorizeBuilder($form);
        $form->update(['status' => 'archived']);
        return back()->with('success', "Formulaire archivé.");
    }
}
