<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DepartmentForm;
use App\Models\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DepartmentServiceController extends Controller
{
    public function index(Department $department): View
    {
        $this->authorize('update', $department);

        $services = $department->services()->ordered()->get();
        $forms = $department->forms()->active()->ordered()->get();

        return view('intranet.departments.manage.services', compact('department', 'services', 'forms'));
    }

    public function store(Request $request, Department $department): RedirectResponse
    {
        $this->authorize('update', $department);
        $validated = $this->validatedService($request, $department);

        $department->services()->create($validated);

        return redirect()
            ->route('intranet.departments.manage.services.index', $department)
            ->with('success', 'Le service a été ajouté.');
    }

    public function update(
        Request $request,
        Department $department,
        DepartmentService $service
    ): RedirectResponse {
        $this->authorize('update', $department);
        abort_unless($service->department_id === $department->id, 404);

        $service->update($this->validatedService($request, $department, $service));

        return redirect()
            ->route('intranet.departments.manage.services.index', $department)
            ->with('success', 'Le service a été mis à jour.');
    }

    public function destroy(Department $department, DepartmentService $service): RedirectResponse
    {
        $this->authorize('update', $department);
        abort_unless($service->department_id === $department->id, 404);

        $service->update(['is_active' => false]);

        return redirect()
            ->route('intranet.departments.manage.services.index', $department)
            ->with('success', 'Le service a été désactivé.');
    }

    /**
     * @return array{name: string, description: string|null, icon: string|null, link_type: string, link_url: string|null, order: int, is_active: bool}
     *
     * @throws ValidationException
     */
    private function validatedService(
        Request $request,
        Department $department,
        ?DepartmentService $service = null
    ): array {
        $uniqueName = Rule::unique('department_services', 'name')
            ->where('department_id', $department->id);
        if ($service !== null) {
            $uniqueName->ignore($service->id);
        }

        $validated = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                $uniqueName,
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:100'],
            'link_type' => ['required', Rule::in(['form', 'page', 'external'])],
            'link_url' => ['nullable', 'string', 'max:2048'],
            'form_id' => ['nullable', 'integer', 'exists:department_forms,id'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ])->validate();

        $destination = null;

        if ($validated['link_type'] === 'form' && filled($validated['form_id'] ?? null)) {
            $form = $department->forms()
                ->active()
                ->whereKey($validated['form_id'])
                ->first();

            if (! $form instanceof DepartmentForm) {
                throw ValidationException::withMessages([
                    'form_id' => 'Choisissez un formulaire publié de ce département.',
                ]);
            }

            $destination = route('intranet.forms.show', $form, absolute: false);
        } elseif ($validated['link_type'] !== 'form') {
            $destination = $validated['link_url'] ?? null;
        }

        if (filled($destination) && $validated['link_type'] === 'external') {
            $scheme = parse_url($destination, PHP_URL_SCHEME);
            $host = parse_url($destination, PHP_URL_HOST);

            if ($scheme !== 'https' || ! is_string($host) || filter_var($destination, FILTER_VALIDATE_URL) === false) {
                throw ValidationException::withMessages([
                    'link_url' => 'Indiquez une adresse externe HTTPS valide.',
                ]);
            }
        } elseif (filled($destination) && $validated['link_type'] === 'page') {
            if (! str_starts_with($destination, '/intranet/') || str_starts_with($destination, '//')) {
                throw ValidationException::withMessages([
                    'link_url' => 'Une page interne doit commencer par /intranet/.',
                ]);
            }
        }

        if ($request->boolean('is_active') && ! filled($destination)) {
            $field = $validated['link_type'] === 'form' ? 'form_id' : 'link_url';
            throw ValidationException::withMessages([
                $field => 'Un service publié doit avoir une destination configurée.',
            ]);
        }

        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'link_type' => $validated['link_type'],
            'link_url' => $destination,
            'order' => $validated['order']
                ?? $service?->order
                ?? (($department->services()->max('order') ?? -1) + 1),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
