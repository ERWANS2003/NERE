<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AdminAudit;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleManagementController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request): View
    {
        $query = Role::query()->with('permissions')->withCount('users');

        // Recherche
        if ($search = $request->input('search')) {
            $query->where(function ($query) use ($search): void {
                $query->where('nom', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%");
            });
        }

        $roles = $query->orderBy('nom')->paginate(20);

        return view('admin.roles.index', compact('roles'));
    }

    public function matrix(): View
    {
        return view('admin.roles.matrix', [
            'roles' => Role::with('permissions')->withCount('users')->orderBy('nom')->get(),
            'permissionGroups' => PermissionCatalog::group(Permission::with('roles')->orderBy('nom')->get()),
            'sensitivePermissions' => ['users.manage', 'roles.manage', 'settings.manage', 'departments.manage'],
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(): View
    {
        $permissionGroups = PermissionCatalog::group(Permission::orderBy('nom')->get());

        return view('admin.roles.create', compact('permissionGroups'));
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:roles,slug'],
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'nom' => $validated['nom'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
        ]);

        // Attacher les permissions
        if (! empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }
        AdminAudit::record('création rôle', 'rôles et permissions', $role, after: [
            'attributes' => $role->only(['nom', 'slug', 'description']),
            'permissions' => $validated['permissions'] ?? [],
        ]);

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('success', 'Rôle créé avec succès.');
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role): View
    {
        $role->load(['permissions', 'users']);

        return view('admin.roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role): View
    {
        $permissionGroups = PermissionCatalog::group(Permission::orderBy('nom')->get());
        $role->load('permissions');

        return view('admin.roles.edit', compact('role', 'permissionGroups'));
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $role->load('permissions');
        $before = [
            'attributes' => $role->only(['nom', 'slug', 'description']),
            'permissions' => $role->permissions->pluck('id')->all(),
        ];
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:roles,slug,'.$role->id],
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'nom' => $validated['nom'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
        ]);

        // Synchroniser les permissions
        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        } else {
            $role->permissions()->sync([]);
        }
        $role->refresh()->load('permissions');
        AdminAudit::record('mise à jour rôle', 'rôles et permissions', $role, $before, [
            'attributes' => $role->only(['nom', 'slug', 'description']),
            'permissions' => $role->permissions->pluck('id')->all(),
        ]);

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('success', 'Rôle mis à jour avec succès.');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role): RedirectResponse
    {
        // Vérifier si le rôle a des utilisateurs
        if ($role->users()->count() > 0) {
            return redirect()
                ->route('admin.roles.show', $role)
                ->with('error', 'Impossible de supprimer ce rôle car il est attribué à des utilisateurs.');
        }

        $label = $role->nom;
        $before = $role->only(['nom', 'slug', 'description']);
        $role->delete();
        AdminAudit::record('suppression rôle', 'rôles et permissions', before: $before, targetLabel: $label);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Rôle supprimé avec succès.');
    }
}
