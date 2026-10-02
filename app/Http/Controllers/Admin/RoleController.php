<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with(['permissions'])->withCount('users')->orderBy('nom')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function show(Role $role)
    {
        $role->load('permissions');
        $permissions = $role->permissions;
        $users = $role->users()->orderBy('name')->get();

        return view('admin.roles.show', compact('role', 'permissions', 'users'));
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
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['nom'], '_');
        }

        // Le slug est comparé strictement par User::hasRole() et par le
        // middleware CheckRole : une collision doit rester une erreur de
        // validation, pas une QueryException. On valide le slug *résolu*,
        // pas l'input brut — sinon un rôle créé sans slug explicite échouerait
        // sur la règle `required`.
        Validator::make(['slug' => $data['slug']], [
            'slug' => 'required|string|max:255|unique:roles,slug',
        ])->validate();

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
            'slug' => 'nullable|string|max:255|unique:roles,slug,' . $role->id,
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // Le slug d'un rôle système est figé : le renommer casserait
        // silencieusement tous les hasRole() et le middleware role:.
        $slug = $role->slug;

        if (! $role->estSysteme()) {
            // `?? $role->slug` ne fonctionne pas : un champ soumis vide est
            // converti en null, donc le slug existant aurait toujours gagné et
            // le nom ne serait jamais régénéré. On distingue « champ absent »
            // de « champ soumis vide ».
            $slugSoumis = $request->input('slug');
            $slugVide = array_key_exists('slug', $request->all()) && blank($slugSoumis);

            if (filled($slugSoumis)) {
                $slug = $slugSoumis;
            } elseif ($slugVide) {
                $slug = Str::slug($data['nom'], '_');
            }

            if ($slug !== $role->slug) {
                Validator::make(['slug' => $slug], [
                    'slug' => 'required|string|max:255|unique:roles,slug,' . $role->id,
                ])->validate();
            }
        }

        $role->update([
            'nom' => $data['nom'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', "Le rôle {$role->nom} a été mis à jour.");
    }

    public function destroy(Role $role)
    {
        if ($role->estSysteme()) {
            return back()->with('error', "Le rôle système {$role->nom} ne peut pas être supprimé.");
        }

        if ($role->users()->exists()) {
            return back()->with('error', "Impossible de supprimer le rôle {$role->nom} : des utilisateurs y sont rattachés.");
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', "Le rôle {$role->nom} a été supprimé.");
    }
}
