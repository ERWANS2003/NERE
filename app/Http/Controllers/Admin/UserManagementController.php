<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepartmentRole;
use App\Http\Controllers\Controller;
use App\Models\AdminSavedFilter;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserManagementController extends Controller
{
    /**
     * Liste tous les utilisateurs
     */
    public function index(Request $request)
    {
        if ($request->filled('filter')) {
            $savedFilter = AdminSavedFilter::query()
                ->where('user_id', $request->user()->id)
                ->where('scope', 'users')
                ->findOrFail($request->integer('filter'));
            $request->merge(array_merge($savedFilter->criteria, ['filter' => $savedFilter->id]));
        }

        $query = User::with(['departments', 'roles']);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('matricule', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('actif', $request->input('status') === 'active');
        }

        if ($request->filled('department')) {
            $query->whereHas('departments', fn ($q) => $q->where('departments.id', $request->integer('department')));
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('roles.id', $request->integer('role')));
        }

        if ($request->filled('source')) {
            $request->input('source') === 'ldap'
                ? $query->whereNotNull('guid')
                : $query->whereNull('guid');
        }

        if ($request->filled('last_login')) {
            match ($request->input('last_login')) {
                'never' => $query->whereNull('last_login_at'),
                '30d' => $query->where('last_login_at', '>=', now()->subDays(30)),
                '90d' => $query->where('last_login_at', '<', now()->subDays(90)),
                default => null,
            };
        }

        if ($request->filled('menu')) {
            $query->where('menu_eligible', $request->input('menu') === 'yes');
        }

        $users = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'departments' => Department::active()->orderBy('name')->get(),
            'roles' => Role::orderBy('nom')->get(),
            'savedFilters' => AdminSavedFilter::query()->where('user_id', $request->user()->id)->where('scope', 'users')->orderBy('name')->get(),
        ]);
    }

    /**
     * Coche ou décoche « Choisit ses plats au menu du personnel » depuis la liste.
     */
    public function toggleMenu(User $user)
    {
        $before = ['menu_eligible' => $user->menu_eligible];
        $user->update(['menu_eligible' => ! $user->menu_eligible]);
        AdminAudit::record('modification accès menu', 'utilisateurs', $user, $before, ['menu_eligible' => $user->menu_eligible]);

        return back()->with('success', $user->menu_eligible
            ? "{$user->name} choisit désormais ses plats au menu du personnel."
            : "{$user->name} ne choisit plus ses plats au menu du personnel.");
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
        $validated['desactive_localement'] = ! $validated['actif'];
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

        AdminAudit::record('création compte', 'utilisateurs', $user, after: [
            'attributes' => $user->only(['name', 'email', 'matricule', 'actif', 'is_super_admin', 'menu_eligible']),
            'departments' => $departmentIds,
            'roles' => $roleIds,
        ]);

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
        AdminAudit::record('affectation département', 'utilisateurs', $user, after: $validated);

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
        $user->loadMissing(['departments', 'roles']);
        $before = [
            'attributes' => $user->only(['name', 'email', 'matricule', 'telephone', 'poste', 'actif', 'is_super_admin', 'menu_eligible']),
            'departments' => $user->departments->pluck('id')->all(),
            'roles' => $user->roles->pluck('id')->all(),
        ];

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
        $validated['desactive_localement'] = ! $validated['actif'];
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

        $user->refresh()->load(['departments', 'roles']);
        AdminAudit::record('mise à jour compte', 'utilisateurs', $user, $before, [
            'attributes' => $user->only(['name', 'email', 'matricule', 'telephone', 'poste', 'actif', 'is_super_admin', 'menu_eligible']),
            'departments' => $user->departments->pluck('id')->all(),
            'roles' => $user->roles->pluck('id')->all(),
        ]);

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
        $before = ['actif' => $user->actif, 'desactive_localement' => $user->desactive_localement];
        $user->update(['actif' => false, 'desactive_localement' => true]);
        AdminAudit::record('désactivation compte', 'utilisateurs', $user, $before, ['actif' => false, 'desactive_localement' => true]);

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
        AdminAudit::record('réinitialisation mot de passe', 'utilisateurs', $user);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Mot de passe réinitialisé avec succès');
    }

    public function bulkAction(Request $request): RedirectResponse|StreamedResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'action' => ['required', Rule::in(['activate', 'deactivate', 'assign_department', 'add_role', 'remove_role', 'menu_yes', 'menu_no', 'export'])],
            'department_id' => ['nullable', 'required_if:action,assign_department', 'exists:departments,id'],
            'role_id' => ['nullable', 'required_if:action,add_role,remove_role', 'exists:roles,id'],
        ]);

        $users = User::query()->whereKey($validated['user_ids'])->get();
        $action = $validated['action'];

        if ($action === 'export') {
            AdminAudit::record('export utilisateurs', 'utilisateurs', after: ['user_ids' => $users->modelKeys(), 'count' => $users->count()]);

            return response()->streamDownload(function () use ($users): void {
                $output = fopen('php://output', 'wb');
                fwrite($output, "\xEF\xBB\xBF");
                fputcsv($output, ['Nom', 'E-mail', 'Matricule', 'Statut', 'Source', 'Dernière connexion'], ';', '"', '');
                foreach ($users as $user) {
                    fputcsv($output, [$user->name, $user->email, $user->matricule, $user->actif ? 'Actif' : 'Inactif', $user->guid ? 'Active Directory' : 'Local', $user->last_login_at?->format('d/m/Y H:i')], ';', '"', '');
                }
                fclose($output);
            }, 'utilisateurs-selection-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        if ($action === 'deactivate' && $users->contains(fn (User $user) => $request->user()->is($user))) {
            return back()->with('error', 'Votre propre compte ne peut pas être désactivé par une action groupée.');
        }

        DB::transaction(function () use ($users, $action, $validated): void {
            foreach ($users as $user) {
                match ($action) {
                    'activate' => $user->update(['actif' => true, 'desactive_localement' => false]),
                    'deactivate' => $user->update(['actif' => false, 'desactive_localement' => true]),
                    'assign_department' => $user->departments()->whereKey((int) $validated['department_id'])->exists()
                        ? null
                        : $user->departments()->attach((int) $validated['department_id'], ['role' => DepartmentRole::User->value, 'tech_level' => null]),
                    'add_role' => $user->roles()->syncWithoutDetaching([(int) $validated['role_id']]),
                    'remove_role' => $user->roles()->detach((int) $validated['role_id']),
                    'menu_yes' => $user->update(['menu_eligible' => true]),
                    'menu_no' => $user->update(['menu_eligible' => false]),
                };

                if ($action === 'assign_department') {
                    $user->refreshAuthorisations();
                }
            }
        });

        AdminAudit::record('action groupée '.$action, 'utilisateurs', after: [
            'user_ids' => $users->modelKeys(),
            'count' => $users->count(),
            'department_id' => $validated['department_id'] ?? null,
            'role_id' => $validated['role_id'] ?? null,
        ]);

        return back()->with('success', $users->count().' compte(s) mis à jour.');
    }

    public function saveFilter(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'criteria' => ['required', 'array'],
            'criteria.*' => ['nullable', 'string', 'max:255'],
        ]);

        $allowed = ['search', 'status', 'department', 'role', 'source', 'last_login', 'menu'];
        $filter = AdminSavedFilter::updateOrCreate(
            ['user_id' => $request->user()->id, 'scope' => 'users', 'name' => $validated['name']],
            ['criteria' => collect($validated['criteria'])->only($allowed)->filter(fn ($value) => filled($value))->all()]
        );

        AdminAudit::record('enregistrement filtre utilisateurs', 'préférences', $filter, after: $filter->criteria, targetLabel: $filter->name);

        return redirect()->route('admin.users.index', ['filter' => $filter->id])->with('success', 'Vue enregistrée.');
    }

    public function deleteFilter(Request $request, AdminSavedFilter $filter): RedirectResponse
    {
        abort_unless($filter->user_id === $request->user()->id && $filter->scope === 'users', 404);
        $name = $filter->name;
        $filter->delete();
        AdminAudit::record('suppression filtre utilisateurs', 'préférences', targetLabel: $name);

        return redirect()->route('admin.users.index')->with('success', 'Vue enregistrée supprimée.');
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
