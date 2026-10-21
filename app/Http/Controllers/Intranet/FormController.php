<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DepartmentForm;
use App\Models\User;
use App\Support\DynamicFormSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FormController extends Controller
{
    public function index(Request $request): View
    {
        $manageableDepartments = $this->manageableDepartments($request->user());
        $manageableDepartmentIds = $manageableDepartments->modelKeys();
        $activeDepartmentIds = Department::query()->active()->pluck('id');
        $departments = Department::query()->active()->ordered()->get();

        $formsQuery = DepartmentForm::query()
            ->with('department')
            ->whereIn('department_id', $activeDepartmentIds)
            ->where(function ($query) use ($manageableDepartmentIds): void {
                $query->where('is_active', true);

                if ($manageableDepartmentIds !== []) {
                    $query->orWhereIn('department_id', $manageableDepartmentIds);
                }
            });

        if ($request->filled('department')) {
            $formsQuery->where('department_id', $request->integer('department'));
        }

        $forms = $formsQuery
            ->orderBy('order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('intranet.forms.index', compact(
            'forms',
            'departments',
            'manageableDepartments',
            'manageableDepartmentIds',
        ));
    }

    public function create(Request $request): View
    {
        $departments = $this->manageableDepartments($request->user(), activeOnly: true);
        abort_if($departments->isEmpty(), 403);

        return view('intranet.forms.create', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedForm($request);
        $department = Department::query()->active()->findOrFail($validated['department_id']);
        $this->authorize('update', $department);

        $form = DepartmentForm::create([
            'department_id' => $department->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'form_schema' => ['fields' => $validated['fields']],
            'is_active' => $request->boolean('is_active', true),
            'order' => $validated['order'] ?? 0,
        ]);

        return redirect()
            ->route('intranet.forms.edit', $form)
            ->with('success', 'Le formulaire a été créé.');
    }

    public function show(DepartmentForm $form): View
    {
        $form->loadMissing('department');

        abort_unless($form->is_active && $form->department?->is_active, 404);

        $fields = DynamicFormSchema::fields($form->form_schema);

        return view('intranet.forms.department-show', compact('form', 'fields'));
    }

    public function edit(DepartmentForm $form): View
    {
        $form->loadMissing('department');
        $this->authorize('update', $form->department);

        return view('intranet.forms.edit', compact('form'));
    }

    public function update(Request $request, DepartmentForm $form): RedirectResponse
    {
        $form->loadMissing('department');
        $this->authorize('update', $form->department);
        $validated = $this->validatedForm($request, includeDepartment: false);

        $form->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'form_schema' => ['fields' => $validated['fields']],
            'is_active' => $request->boolean('is_active'),
            'order' => $validated['order'] ?? 0,
        ]);

        return redirect()
            ->route('intranet.forms.edit', $form)
            ->with('success', 'Le formulaire a été mis à jour.');
    }

    public function destroy(DepartmentForm $form): RedirectResponse
    {
        $form->loadMissing('department');
        $this->authorize('update', $form->department);

        if ($form->submissions()->exists()) {
            $form->update(['is_active' => false]);

            return redirect()
                ->route('intranet.forms.index')
                ->with('success', 'Le formulaire a été archivé pour préserver l’historique des demandes.');
        }

        $form->delete();

        return redirect()
            ->route('intranet.forms.index')
            ->with('success', 'Le formulaire a été supprimé.');
    }

    private function manageableDepartments(User $user, bool $activeOnly = false): Collection
    {
        $query = Department::query()
            ->when($activeOnly, fn ($departments) => $departments->active())
            ->ordered();

        if (! $user->is_super_admin) {
            $query->whereKey($user->directedDepartments()->pluck('id')->all());
        }

        return $query->get();
    }

    /**
     * @return array{name: string, description?: string|null, department_id?: int, order?: int, fields: array}
     *
     * @throws ValidationException
     */
    private function validatedForm(Request $request, bool $includeDepartment = true): array
    {
        $validator = Validator::make($request->all(), DynamicFormSchema::editorRules($includeDepartment));
        $validator->after(function ($validator) use ($request): void {
            foreach ($request->input('fields', []) as $index => $field) {
                if (! is_array($field) || ($field['type'] ?? null) !== 'select') {
                    continue;
                }

                $options = $field['options'] ?? [];
                if (! is_array($options) || $options === []) {
                    $validator->errors()->add("fields.{$index}.options", 'Ajoutez au moins une option.');

                    continue;
                }

                $values = array_column($options, 'value');
                if (count($values) !== count(array_unique($values))) {
                    $validator->errors()->add("fields.{$index}.options", 'Les valeurs des options doivent être uniques.');
                }
            }
        });

        $validated = $validator->validate();
        $validated['fields'] = DynamicFormSchema::normalizeFields($validated['fields']);

        return $validated;
    }
}
