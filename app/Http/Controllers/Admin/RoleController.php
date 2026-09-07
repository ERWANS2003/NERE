<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with(['permissions'])->withCount('users')->orderBy('nom')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::all()->groupBy('module');

        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255|unique:roles,nom',
            'slug' => 'nullable|string|max:255|unique:roles,slug',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['nom'], '_');
        }

        $role = Role::create([
            'nom' => $data['nom'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
        ]);

        if (! empty($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        return redirect()->route('admin.roles.index')->with('success', "Le rôle {$role->nom} a été créé.");
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all()->groupBy('module');
        $rolePermissionIds = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissionIds'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255|unique:roles,nom,' . $role->id,
            'slug' => 'required|string|max:255|unique:roles,slug,' . $role->id,
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'nom' => $data['nom'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', "Le rôle {$role->nom} a été mis à jour.");
    }

    public function destroy(Role $role)
    {
        if ($role->users()->exists()) {
            return back()->with('error', "Impossible de supprimer le rôle {$role->nom} : des utilisateurs y sont rattachés.");
        }

        if (in_array($role->slug, ['admin', 'dsi'], true)) {
            return back()->with('error', "Le rôle système {$role->nom} ne peut pas être supprimé.");
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', "Le rôle {$role->nom} a été supprimé.");
    }
}
