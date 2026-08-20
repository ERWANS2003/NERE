<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Phase 12 : Administration - gestion des utilisateurs
    public function index(Request $request)
    {
        $users = User::with(['role', 'departement', 'site'])
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->query('q').'%'))
            ->when($request->filled('role_id'), fn ($q) => $q->where('role_id', $request->query('role_id')))
            ->paginate(25);

        return view('admin.users.index', compact('users'));
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

        return back()->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user)
    {
        $user->update(['actif' => false]);

        return back()->with('success', 'Utilisateur désactivé.');
    }
}
