<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    /**
     * Affiche la liste des départements
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Department::class);

        $query = Department::with(['manager', 'parent'])
            ->withCount('users');

        // Filtres de recherche
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Tri
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');

        switch ($sort) {
            case 'created_at':
                $query->orderBy('created_at', $direction);
                break;
            case 'users_count':
                $query->orderBy('users_count', $direction);
                break;
            default:
                $query->orderBy('name', $direction);
        }

        $departments = $query->paginate(15)->withQueryString();

        // Statistiques
        $activeDepartments = Department::where('is_active', true)->count();
        $totalUsers = User::where('actif', true)->count();

        return view('intranet.departments.manage.index', compact(
            'departments',
            'activeDepartments',
            'totalUsers'
        ));
    }

    /**
     * Affiche le formulaire de création d'un département
     */
    public function create(): View
    {
        $this->authorize('create', Department::class);

        $managers = User::where('actif', true)
            ->orderBy('name')
            ->get();

        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('intranet.departments.manage.create', compact('managers', 'departments'));
    }

    /**
     * Enregistre un nouveau département
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Department::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'code' => ['nullable', 'string', 'max:10', 'unique:departments,code'],
            'description' => ['nullable', 'string'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'parent_id' => ['nullable', 'exists:departments,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'location' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'allow_ticket_creation' => ['boolean'],
        ]);

        // Nettoyer les valeurs booléennes
        $validated['is_active'] = $request->boolean('is_active');
        $validated['allow_ticket_creation'] = $request->boolean('allow_ticket_creation');

        $department = Department::create($validated);

        return redirect()
            ->route('intranet.departments.manage.show', $department)
            ->with('success', 'Département créé avec succès.');
    }

    /**
     * Affiche les détails d'un département
     */
    public function show(Department $department): View
    {
        $this->authorize('view', $department);

        $department->load(['manager', 'parent', 'children.users', 'users.roles']);

        $users = $department->users()->with('roles')->get();

        $ticketStats = [
            'open' => Ticket::query()
                ->where('department_id', $department->id)
                ->whereNotIn('status', ['resolved', 'closed'])
                ->count(),
            'this_month' => Ticket::query()
                ->where('department_id', $department->id)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
        ];

        return view('intranet.departments.manage.show', compact('department', 'users', 'ticketStats'));
    }

    /**
     * Affiche le formulaire d'édition d'un département
     */
    public function edit(Department $department): View
    {
        $this->authorize('update', $department);

        $managers = User::where('actif', true)
            ->orderBy('name')
            ->get();

        $departments = Department::where('is_active', true)
            ->where('id', '!=', $department->id)
            ->orderBy('name')
            ->get();

        return view('intranet.departments.manage.edit', compact('department', 'managers', 'departments'));
    }

    /**
     * Met à jour un département
     */
    public function update(Request $request, Department $department): RedirectResponse
    {
        $this->authorize('update', $department);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments')->ignore($department->id)],
            'code' => ['nullable', 'string', 'max:10', Rule::unique('departments')->ignore($department->id)],
            'description' => ['nullable', 'string'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'parent_id' => ['nullable', 'exists:departments,id', 'not_in:'.$department->id],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'location' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'allow_ticket_creation' => ['boolean'],
        ]);

        // Nettoyer les valeurs booléennes
        $validated['is_active'] = $request->boolean('is_active');
        $validated['allow_ticket_creation'] = $request->boolean('allow_ticket_creation');

        $department->update($validated);

        return redirect()
            ->route('intranet.departments.manage.show', $department)
            ->with('success', 'Département mis à jour avec succès.');
    }

    /**
     * Supprime un département
     */
    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        // Vérifier qu'il n'y a pas de sous-départements
        if ($department->children()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Impossible de supprimer un département qui a des sous-départements.');
        }

        // Vérifier qu'il n'y a pas d'utilisateurs assignés
        if ($department->users()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Impossible de supprimer un département qui a des utilisateurs assignés.');
        }

        $department->delete();

        return redirect()
            ->route('intranet.departments.manage.index')
            ->with('success', 'Département supprimé avec succès.');
    }
}
