<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepartmentRole;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    /**
     * Liste tous les utilisateurs
     */
    public function index(Request $request)
    {
        $query = User::with(['departments', 'roles']);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('matricule', 'ILIKE', "%{$search}%");
            });
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('actif', $request->status === 'active');
        }

        // Filtre par département
        if ($request->filled('department')) {
            $query->whereHas('departments', function ($q) use ($request) {
                $q->where('departments.id', $request->department);
            });
        }

        $users = $query->paginate(20);
        $departments = Department::active()->orderBy('name')->get();

        return view('admin.users.index', compact('users', 'departments'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        $departments = Department::active()->orderBy('name')->get();
        $roles = Role::withCount('permissions')->orderBy('nom')->get();

        return view('admin.users.create', compact('departments', 'roles'));
    }

    /**
     * Enregistre un nouvel utilisateur
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'matricule' => 'nullable|string|max:50|unique:users,matricule',
            'password' => 'required|string|min:8|confirmed',
            'telephone' => 'nullable|string|max:20',
            'poste' => 'nullable|string|max:100',
            'actif' => 'boolean',
            'is_super_admin' => 'boolean',
            'menu_eligible' => 'boolean',
            'departments' => 'array',
            'departments.*' => 'exists:departments,id',
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
        ]);

        $validated['position_title'] = $validated['poste'] ?? null;
        $validated['poste_modifie_localement'] = filled($validated['position_title']);
        $validated['password'] = Hash::make($validated['password']);
        $validated['actif'] = $request->boolean('actif', true);
        $validated['is_super_admin'] = $request->boolean('is_super_admin', false);
        $validated['menu_eligible'] = $request->boolean('menu_eligible');
        $departmentIds = $validated['departments'] ?? [];
        $roleIds = $validated['roles'] ?? [];
        unset($validated['departments'], $validated['roles']);

        $user = User::create($validated);

        if ($request->has('departments_present') || $request->has('departments')) {
            $this->syncDepartmentMemberships($user, $departmentIds);
        }

        if ($request->has('roles_present') || $request->has('roles')) {
            $user->roles()->sync($roleIds);
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Utilisateur créé avec succès');
    }

    /**
     * Affiche les détails d'un utilisateur
     */
    public function show(User $user)
    {
        $user->load(['departments', 'roles']);
        $departmentRoles = DepartmentRole::options();

        return view('admin.users.show', compact('user', 'departmentRoles'));
    }

    /**
     * Affecte un utilisateur à un département sans modifier ses affectations existantes.
     */
    public function assignDepartment(Request $request, User $user)
    {
        $validated = $request->validate([
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where('is_active', true),
            ],
            'role' => ['required', Rule::enum(DepartmentRole::class)],
            'tech_level' => ['nullable', 'required_if:role,'.DepartmentRole::Technician->value, 'integer', 'between:1,3'],
        ], [
            'department_id.exists' => 'Sélectionnez un département actif.',
        ]);

        if ($user->departments()->whereKey($validated['department_id'])->exists()) {
            return back()
                ->withErrors(['department_id' => 'Cet employé est déjà affecté à ce département.'])
                ->withInput();
        }

        $user->departments()->attach($validated['department_id'], [
            'role' => $validated['role'],
            'tech_level' => $validated['role'] === DepartmentRole::Technician->value
                ? $validated['tech_level']
                : null,
        ]);
        $user->refreshAuthorisations();

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Département affecté à l’employé.');
    }

    /**
     * Formulaire d'édition
     */
    public function edit(User $user)
    {
        $user->load(['departments', 'roles']);
        $departments = Department::active()
            ->orderBy('name')
            ->get()
            ->merge($user->departments)
            ->unique('id')
            ->sortBy('name')
            ->values();
        $roles = Role::withCount('permissions')->orderBy('nom')->get();

        return view('admin.users.edit', compact('user', 'departments', 'roles'));
    }

    /**
     * Met à jour un utilisateur
     */
    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'matricule' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'telephone' => 'nullable|string|max:20',
            'poste' => 'nullable|string|max:100',
            'actif' => 'boolean',
            'is_super_admin' => 'boolean',
            'menu_eligible' => 'boolean',
            'departments' => 'array',
            'departments.*' => 'exists:departments,id',
            'department_roles' => 'sometimes|array',
            'department_roles.*' => ['required', Rule::enum(DepartmentRole::class)],
            'department_levels' => 'sometimes|array',
            'department_levels.*' => ['nullable', 'integer', 'between:1,3'],
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
        ];

        $submittedDepartmentIds = $request->input('departments', []);
        if ($request->has('department_roles') && is_array($submittedDepartmentIds)) {
            foreach ($submittedDepartmentIds as $departmentId) {
                if (filter_var($departmentId, FILTER_VALIDATE_INT) === false) {
                    continue;
                }

                $rules['department_levels.'.$departmentId] = [
                    'required_if:department_roles.'.$departmentId.','.DepartmentRole::Technician->value,
                    'nullable',
                    'integer',
                    'between:1,3',
                ];
            }
        }

        $validated = $request->validate($rules);

        if (array_key_exists('poste', $validated)) {
            $validated['position_title'] = $validated['poste'];
            // Un poste changé ici prime sur l'AD ; vidé, il redevient alimenté par l'AD.
            if ($validated['poste'] !== $user->poste) {
                $validated['poste_modifie_localement'] = filled($validated['poste']);
            }
        }

        $departmentIds = $validated['departments'] ?? [];
        $departmentRoles = $validated['department_roles'] ?? [];
        $departmentLevels = $request->input('department_levels', []);
        $roleIds = $validated['roles'] ?? [];
        unset($validated['departments'], $validated['department_roles'], $validated['department_levels'], $validated['roles']);

        $actif = $request->boolean('actif');
        $administrateur = $request->boolean('is_super_admin');
        if ($request->user()->is($user) && (! $actif || ! $administrateur)) {
            return back()->with('error', 'Vous ne pouvez pas désactiver ni retirer vos propres droits d’administrateur système.');
        }

        if ($user->is_super_admin && $user->actif && (! $actif || ! $administrateur)
            && User::where('is_super_admin', true)->where('actif', true)->count() <= 1) {
            return back()->with('error', 'Il faut conserver au moins un administrateur système actif.');
        }

        // Mise à jour du mot de passe seulement si fourni
        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['actif'] = $request->boolean('actif');
        $validated['is_super_admin'] = $request->boolean('is_super_admin');
        $validated['menu_eligible'] = $request->boolean('menu_eligible');

        $user->update($validated);

        // Synchroniser les départements
        if ($request->has('departments_present') || $request->has('departments')) {
            $this->syncDepartmentMemberships($user, $departmentIds, $departmentRoles, $departmentLevels);
        }

        if ($request->has('roles_present') || $request->has('roles')) {
            $user->roles()->sync($roleIds);
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Utilisateur mis à jour avec succès');
    }

    /**
     * Désactive un utilisateur
     */
    public function destroy(User $user)
    {
        if (request()->user()->is($user)) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte administrateur.');
        }

        if ($user->is_super_admin && $user->actif
            && User::where('is_super_admin', true)->where('actif', true)->count() <= 1) {
            return back()->with('error', 'Il faut conserver au moins un administrateur système actif.');
        }

        // Ne pas supprimer, seulement désactiver
        $user->update(['actif' => false]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur désactivé avec succès');
    }

    /**
     * Réinitialise le mot de passe
     */
    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Mot de passe réinitialisé avec succès');
    }

    /**
     * Synchronise les départements en conservant les rôles existants et en
     * donnant le rôle de base aux nouveaux rattachements.
     *
     * @param  array<int, int|string>  $departmentIds
     * @param  array<int|string, string>  $departmentRoles
     * @param  array<int|string, int|string|null>  $departmentLevels
     */
    private function syncDepartmentMemberships(
        User $user,
        array $departmentIds,
        array $departmentRoles = [],
        array $departmentLevels = []
    ): void {
        $existingMemberships = $user->departments()->get()->keyBy('id');
        $memberships = [];

        foreach ($departmentIds as $departmentId) {
            $departmentId = (int) $departmentId;
            $existingMembership = $existingMemberships->get($departmentId);
            $role = $departmentRoles[$departmentId]
                ?? $existingMembership?->pivot->role
                ?? DepartmentRole::User->value;

            $memberships[$departmentId] = [
                'role' => $role,
                'tech_level' => $role === DepartmentRole::Technician->value
                    ? ($departmentLevels[$departmentId] ?? $existingMembership?->pivot->tech_level)
                    : null,
            ];
        }

        $user->departments()->sync($memberships);
        $user->refreshAuthorisations();
    }
}
