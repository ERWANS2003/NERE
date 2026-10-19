<?php

namespace App\Http\Controllers\Admin;

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
        $roles = Role::orderBy('nom')->get();

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
            'departments' => 'array',
            'departments.*' => 'exists:departments,id',
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['actif'] = $request->boolean('actif', true);
        $validated['is_super_admin'] = $request->boolean('is_super_admin', false);

        $user = User::create($validated);

        // Attacher les départements
        if ($request->filled('departments')) {
            $user->departments()->attach($request->departments);
        }

        // Attacher les rôles
        if ($request->filled('roles')) {
            $user->roles()->attach($request->roles);
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

        return view('admin.users.show', compact('user'));
    }

    /**
     * Formulaire d'édition
     */
    public function edit(User $user)
    {
        $user->load(['departments', 'roles']);
        $departments = Department::active()->orderBy('name')->get();
        $roles = Role::orderBy('nom')->get();

        return view('admin.users.edit', compact('user', 'departments', 'roles'));
    }

    /**
     * Met à jour un utilisateur
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'matricule' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'telephone' => 'nullable|string|max:20',
            'poste' => 'nullable|string|max:100',
            'actif' => 'boolean',
            'is_super_admin' => 'boolean',
            'departments' => 'array',
            'departments.*' => 'exists:departments,id',
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
        ]);

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

        $user->update($validated);

        // Synchroniser les départements
        if ($request->has('departments')) {
            $user->departments()->sync($request->departments);
        }

        // Synchroniser les rôles
        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
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
}
