<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\Role;
use App\Models\Site;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DepartmentManagementController extends Controller
{
    /**
     * Vérifie que l'utilisateur connecté est Directeur de Département
     * et récupère son département dirigé. Redirige si non autorisé.
     */
    private function getDepartementDuDirecteur(): Departement
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            // L'admin peut accéder via ?departement_id=X
            $id = request('departement_id');
            if ($id) {
                return Departement::with(['directeur', 'teams.techniciens', 'users.role'])->findOrFail($id);
            }
        }

        $departement = $user->departementDirige()->with(['teams.techniciens', 'users.role'])->first();

        // Si l'utilisateur est DSI ou a le rôle directeur_departement sans directeur_id explicite
        if (! $departement && ($user->hasRole('dsi') || $user->hasRole('directeur_departement')) && $user->departement_id) {
            $departement = Departement::with(['directeur', 'teams.techniciens', 'users.role'])->find($user->departement_id);
        }

        // Si l'utilisateur est DSI et n'a pas de département associé, attribuer l'IT par défaut
        if (! $departement && $user->hasRole('dsi')) {
            $departement = Departement::with(['directeur', 'teams.techniciens', 'users.role'])->where('code', 'IT')->first();
        }

        abort_if(! $departement, 403, 'Vous ne dirigez aucun département.');

        return $departement;
    }

    /**
     * Tableau de bord du département : membres, équipes, création.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $departement = $this->getDepartementDuDirecteur();

        $perPage = in_array((int) $request->query('per_page'), [10, 20, 25, 50], true)
            ? (int) $request->query('per_page')
            : 20;

        // Membres du département avec recherche et pagination
        $membres = User::with(['role', 'site', 'teams'])
            ->where('departement_id', $departement->id)
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->query('q') . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('poste', 'like', $term);
                });
            })
            ->when($request->filled('role_id'), fn($q) => $q->where('role_id', $request->query('role_id')))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $totalMembres = User::where('departement_id', $departement->id)->count();
        $totalActifs = User::where('departement_id', $departement->id)->where('actif', true)->count();

        // Membres pour la sélection dans les équipes (sans pagination)
        $tousMembresDepartement = User::where('departement_id', $departement->id)->orderBy('name')->get();

        return view('departments.index', [
            'departement'           => $departement,
            'membres'               => $membres,
            'tousMembresDepartement'=> $tousMembresDepartement,
            'totalMembres'          => $totalMembres,
            'totalActifs'           => $totalActifs,
            'teams'                 => $departement->teams()->with('techniciens')->get(),
            'roles'                 => Role::whereNotIn('slug', ['admin'])->orderBy('nom')->get(),
            'sites'                 => Site::where('actif', true)->orderBy('nom')->get(),
            'perPage'               => $perPage,
        ]);
    }

    /**
     * Le Directeur crée un nouveau membre dans son département.
     */
    public function storeMember(Request $request)
    {
        $user        = auth()->user();
        $departement = $this->getDepartementDuDirecteur();

        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|min:8|confirmed',
            'role_id'        => 'required|exists:roles,id',
            'site_id'        => 'nullable|exists:sites,id',
            'poste'          => 'nullable|string|max:255',
            'matricule'      => 'nullable|string|max:50|unique:users,matricule',
            'telephone'      => 'nullable|string|max:30',
            'est_technicien' => 'boolean',
            'team_id'        => 'nullable|exists:teams,id',
        ]);

        // Sécurité : le rôle ne peut pas être admin
        $role = Role::findOrFail($data['role_id']);
        if ($role->slug === 'admin') {
            return back()->withErrors(['role_id' => 'Vous ne pouvez pas créer un administrateur.'])->withInput();
        }

        // Si un team_id est fourni, vérifier qu'il appartient bien au département
        if (! empty($data['team_id'])) {
            $team = Team::findOrFail($data['team_id']);
            if ($team->departement_id !== $departement->id) {
                return back()->withErrors(['team_id' => 'Cette équipe n\'appartient pas à votre département.'])->withInput();
            }
        }

        $newUser = User::create([
            'name'           => $data['name'],
            'email'          => $data['email'],
            'password'       => Hash::make($data['password']),
            'role_id'        => $data['role_id'],
            'departement_id' => $departement->id,   // Forcé au département du directeur
            'site_id'        => $data['site_id'] ?? null,
            'poste'          => $data['poste'] ?? null,
            'matricule'      => $data['matricule'] ?? null,
            'telephone'      => $data['telephone'] ?? null,
            'est_technicien' => $data['est_technicien'] ?? false,
            'actif'          => true,
            'disponible'     => true,
        ]);

        // Affectation à l'équipe si choisie
        if (! empty($data['team_id'])) {
            $newUser->teams()->attach($data['team_id'], ['chef_equipe' => false]);
        }

        return redirect()->route('department.index')->with('success', "Compte de {$newUser->name} créé avec succès.");
    }

    /**
     * Le Directeur ajoute un membre existant à une de ses équipes.
     */
    public function addMemberToTeam(Request $request, Team $team)
    {
        $departement = $this->getDepartementDuDirecteur();

        // Sécurité : l'équipe doit appartenir au département du directeur
        if ($team->departement_id !== $departement->id) {
            abort(403, 'Cette équipe n\'appartient pas à votre département.');
        }

        $data = $request->validate([
            'user_id'     => 'required|exists:users,id',
            'chef_equipe' => 'boolean',
        ]);

        $membre = User::findOrFail($data['user_id']);

        // Le membre doit appartenir au département
        if ($membre->departement_id !== $departement->id) {
            return back()->withErrors(['user_id' => 'Cet utilisateur n\'appartient pas à votre département.']);
        }

        // Ajouter à l'équipe s'il n'y est pas déjà
        if (! $team->techniciens()->where('user_id', $membre->id)->exists()) {
            $team->techniciens()->attach($membre->id, ['chef_equipe' => $data['chef_equipe'] ?? false]);
        }

        return back()->with('success', "{$membre->name} a été ajouté à l'équipe {$team->nom}.");
    }

    /**
     * Le Directeur retire un membre d'une de ses équipes.
     */
    public function removeMemberFromTeam(Team $team, User $user)
    {
        $departement = $this->getDepartementDuDirecteur();

        if ($team->departement_id !== $departement->id) {
            abort(403);
        }

        $team->techniciens()->detach($user->id);

        return back()->with('success', "{$user->name} a été retiré de l'équipe {$team->nom}.");
    }

    /**
     * Le Directeur désactive un compte membre de son département.
     */
    public function deactivateMember(User $user)
    {
        $departement = $this->getDepartementDuDirecteur();

        if ($user->departement_id !== $departement->id) {
            abort(403, 'Cet utilisateur n\'appartient pas à votre département.');
        }

        $user->update(['actif' => false]);

        return back()->with('success', "{$user->name} a été désactivé.");
    }
}
