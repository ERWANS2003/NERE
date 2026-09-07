<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Departement;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Phase 12 : Administration - gestion des utilisateurs
    public function index(Request $request)
    {
        $perPage = in_array((int) $request->query('per_page'), [10, 20, 25, 50, 100], true)
            ? (int) $request->query('per_page')
            : 25;

        $users = User::with(['role', 'departement', 'site'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->query('q') . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('matricule', 'like', $term)
                        ->orWhere('poste', 'like', $term)
                        ->orWhere('telephone', 'like', $term);
                });
            })
            ->when($request->filled('role_id'), fn($q) => $q->where('role_id', $request->query('role_id')))
            ->when($request->filled('departement_id'), fn($q) => $q->where('departement_id', $request->query('departement_id')))
            ->when($request->filled('site_id'), fn($q) => $q->where('site_id', $request->query('site_id')))
            ->when($request->filled('actif'), fn($q) => $q->where('actif', $request->query('actif') === '1'))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::orderBy('nom')->get(),
            'departements' => Departement::where('actif', true)->orderBy('nom')->get(),
            'sites' => Site::where('actif', true)->orderBy('nom')->get(),
            'perPage' => $perPage,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role_id' => 'required|exists:roles,id',
            'departement_id' => 'nullable|exists:departements,id',
            'site_id' => 'nullable|exists:sites,id',
            'est_technicien' => 'boolean',
        ]);

        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        // Si le rôle attribué est Directeur de Département, lier comme directeur du département
        if ($user->hasRole('directeur_departement') && $user->departement_id) {
            Departement::where('id', $user->departement_id)->update(['directeur_id' => $user->id]);
        }

        return redirect()->route('admin.users.index')->with('success', "Utilisateur {$user->name} créé.");
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'role_id' => 'sometimes|exists:roles,id',
            'departement_id' => 'sometimes|nullable|exists:departements,id',
            'site_id' => 'sometimes|nullable|exists:sites,id',
            'est_technicien' => 'boolean',
            'disponible' => 'boolean',
            'actif' => 'boolean',
        ]);

        $user->update($data);

        // Si le rôle attribué est Directeur de Département, lier comme directeur du département
        if ($user->hasRole('directeur_departement') && $user->departement_id) {
            Departement::where('id', $user->departement_id)->update(['directeur_id' => $user->id]);
        }

        return back()->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user)
    {
        $user->update(['actif' => false]);

        return back()->with('success', 'Utilisateur désactivé.');
    }
}
