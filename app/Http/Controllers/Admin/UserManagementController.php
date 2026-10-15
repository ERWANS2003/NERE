<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\MealLocation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * Liste les utilisateurs locaux et AD avec filtres de source.
     */
    public function index(Request $request): View
    {
        $query = User::with(['departments', 'roles']);

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('matricule', 'ILIKE', "%{$search}%")
                    ->orWhere('guid', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->filled('status') && in_array($request->status, ['active', 'inactive'], true)) {
            $query->where('actif', $request->status === 'active');
        }

        if ($request->filled('source')) {
            match ($request->source) {
                'ad' => $query->whereNotNull('guid'),
                'local' => $query->whereNull('guid'),
                default => null,
            };
        }

        if ($request->filled('department')) {
            $departmentId = $request->integer('department');
            $query->whereHas('departments', fn ($q) => $q->where('departments.id', $departmentId));
        }

        $users = $query->orderBy('name')->paginate(20)->withQueryString();
        $departments = Department::active()->orderBy('position')->orderBy('name')->get();

        return view('admin.users.index', compact('users', 'departments'));
    }

    public function create(): View
    {
        $departments = Department::active()->orderBy('position')->orderBy('name')->get();
        $roles = Role::orderBy('nom')->get();
        $mealLocations = MealLocation::where('is_active', true)->orderBy('name')->get();

        return view('admin.users.create', compact('departments', 'roles', 'mealLocations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'matricule' => ['nullable', 'string', 'max:50', 'unique:users,matricule'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'poste' => ['nullable', 'string', 'max:100'],
            'meal_location_id' => ['nullable', 'exists:meal_locations,id'],
            'actif' => ['boolean'],
            'is_super_admin' => ['boolean'],
            'departments' => ['array'],
            'departments.*' => ['exists:departments,id'],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['actif'] = $request->boolean('actif', true);
        $validated['is_super_admin'] = $request->boolean('is_super_admin', false);
        $departmentIds = $validated['departments'] ?? [];
        $roleIds = $validated['roles'] ?? [];
        unset($validated['departments'], $validated['roles']);

        $user = DB::transaction(function () use ($validated, $departmentIds, $roleIds) {
            $user = User::create($validated);

            $user->departments()->sync($this->departmentPivotFromIds($departmentIds));
            $user->roles()->sync($roleIds);

            return $user;
        });

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Utilisateur créé avec succès.');
    }

    public function show(User $user): View
    {
        $user->load(['departments', 'roles']);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $user->load(['departments', 'roles']);
        $departments = Department::active()->orderBy('position')->orderBy('name')->get();
        $roles = Role::orderBy('nom')->get();
        $mealLocations = MealLocation::where('is_active', true)->orderBy('name')->get();
        $isAdUser = filled($user->guid);

        return view('admin.users.edit', compact('user', 'departments', 'roles', 'mealLocations', 'isAdUser'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $isAdUser = filled($user->guid);

        $rules = [
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'department_assignments' => ['nullable', 'array'],
            'department_assignments.*.role' => ['required', 'in:user,technician,director'],
            'department_assignments.*.tech_level' => ['nullable', 'integer', 'between:1,3'],
            'is_super_admin' => ['boolean'],
            // La localisation repas est une donnée locale : elle s'applique aussi
            // aux comptes AD (jamais écrite dans l'annuaire).
            'meal_location_id' => ['nullable', 'exists:meal_locations,id'],
        ];

        // Les utilisateurs AD sont pilotés par l'annuaire pour leur identité,
        // leur email, leur poste, leur statut et leur mot de passe. L'intranet
        // ne modifie ici que les rôles et permissions locales.
        if (! $isAdUser) {
            $rules += [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
                'matricule' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
                'telephone' => ['nullable', 'string', 'max:20'],
                'poste' => ['nullable', 'string', 'max:100'],
                'actif' => ['boolean'],
            ];
        }

        // Les lignes sans rôle sont ignorées : elles représentent un département
        // non sélectionné dans le formulaire.
        $assignments = collect($request->input('department_assignments', []))
            ->filter(fn ($assignment) => filled($assignment['role'] ?? null))
            ->all();
        $request->merge(['department_assignments' => $assignments]);

        $validated = $request->validate($rules);
        $pivot = $this->validatedDepartmentPivot($assignments);
        $isSuperAdmin = $request->boolean('is_super_admin');

        $this->protectLastSuperAdmin($request, $user, $isSuperAdmin);
        $beforePermissions = $this->permissionSnapshot($user);

        DB::transaction(function () use ($request, $user, $validated, $pivot, $isAdUser, $isSuperAdmin, $beforePermissions) {
            $attributes = [
                'is_super_admin' => $isSuperAdmin,
                'meal_location_id' => $validated['meal_location_id'] ?? null,
            ];

            if (! $isAdUser) {
                $attributes += [
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'matricule' => $validated['matricule'] ?? null,
                    'telephone' => $validated['telephone'] ?? null,
                    'poste' => $validated['poste'] ?? null,
                    'actif' => $request->boolean('actif'),
                ];

                if ($request->filled('password')) {
                    $attributes['password'] = Hash::make($validated['password']);
                }
            }

            $user->update($attributes);
            $user->departments()->sync($pivot);
            $user->roles()->sync($request->input('roles', []));

            $afterPermissions = $this->permissionSnapshot($user->fresh());
            if ($beforePermissions !== $afterPermissions) {
                AuditLog::create([
                    'user_id' => $request->user()?->id,
                    'action' => 'user.permissions.updated',
                    'model_type' => User::class,
                    'model_id' => $user->id,
                    'anciennes_valeurs' => $beforePermissions,
                    'nouvelles_valeurs' => $afterPermissions,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        });

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', $isAdUser
                ? 'Rôles et permissions intranet mis à jour. L\'Active Directory n\'a pas été modifié.'
                : 'Utilisateur mis à jour avec succès.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if (filled($user->guid)) {
            return redirect()
                ->route('admin.users.show', $user)
                ->with('error', 'Le statut d\'un utilisateur AD est géré par Active Directory. Aucune modification AD n\'est effectuée ici.');
        }

        $this->protectLastSuperAdmin($request, $user, false);

        $user->update(['actif' => false]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur local désactivé.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        if (filled($user->guid)) {
            return redirect()
                ->route('admin.users.show', $user)
                ->with('error', 'Le mot de passe d\'un utilisateur AD est géré par Active Directory.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Mot de passe réinitialisé avec succès.');
    }

    /**
     * @param  array<int|string, mixed>  $departmentIds
     * @return array<int, array{role: string, tech_level: int|null}>
     */
    private function departmentPivotFromIds(array $departmentIds): array
    {
        return collect($departmentIds)
            ->mapWithKeys(fn ($id) => [(int) $id => ['role' => 'user', 'tech_level' => null]])
            ->all();
    }

    /**
     * @param  array<int|string, array{role?: string, tech_level?: int|string|null}>  $assignments
     * @return array<int, array{role: string, tech_level: int|null}>
     */
    private function validatedDepartmentPivot(array $assignments): array
    {
        $activeDepartmentIds = Department::active()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $pivot = [];

        foreach ($assignments as $departmentId => $assignment) {
            if (! in_array((string) $departmentId, $activeDepartmentIds, true)) {
                throw ValidationException::withMessages([
                    'department_assignments' => 'Un département sélectionné est invalide ou inactif.',
                ]);
            }

            $role = $assignment['role'];
            $techLevel = filled($assignment['tech_level'] ?? null)
                ? (int) $assignment['tech_level']
                : null;

            if ($role === 'technician' && $techLevel === null) {
                throw ValidationException::withMessages([
                    "department_assignments.{$departmentId}.tech_level" => 'Un niveau technique est obligatoire pour un technicien.',
                ]);
            }

            if ($role !== 'technician') {
                $techLevel = null;
            }

            $pivot[(int) $departmentId] = [
                'role' => $role,
                'tech_level' => $techLevel,
            ];
        }

        return $pivot;
    }

    /**
     * Capture uniquement les droits locaux, jamais le mot de passe ou les
     * attributs sensibles de l'utilisateur.
     *
     * @return array{is_super_admin: bool, roles: list<int>, departments: array<string, array{role: string, tech_level: int|null}>}
     */
    private function permissionSnapshot(User $user): array
    {
        return [
            'is_super_admin' => (bool) $user->is_super_admin,
            'roles' => $user->roles()->orderBy('id')->pluck('roles.id')->map(fn ($id) => (int) $id)->values()->all(),
            'departments' => $user->departments()
                ->get(['departments.id'])
                ->mapWithKeys(fn ($department) => [
                    (string) $department->id => [
                        'role' => (string) $department->pivot->role,
                        'tech_level' => $department->pivot->tech_level === null ? null : (int) $department->pivot->tech_level,
                    ],
                ])
                ->sortKeys()
                ->all(),
        ];
    }

    private function protectLastSuperAdmin(Request $request, User $user, bool $willBeSuperAdmin): void
    {
        if ($user->is_super_admin && ! $willBeSuperAdmin
            && User::where('is_super_admin', true)->where('id', '!=', $user->id)->doesntExist()) {
            throw ValidationException::withMessages([
                'is_super_admin' => 'Impossible de retirer le dernier super-administrateur.',
            ]);
        }

        if ($request->user()?->is($user) && $user->is_super_admin && ! $willBeSuperAdmin) {
            throw ValidationException::withMessages([
                'is_super_admin' => 'Vous ne pouvez pas retirer vos propres droits de super-administrateur.',
            ]);
        }
    }
}
