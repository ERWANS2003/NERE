<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Departement;
use App\Models\Site;
use Illuminate\Http\Request;

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
            // Payload des modales d'édition : évite un aller-retour par utilisateur.
            'usersJson' => $users->getCollection()->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role_id' => $u->role_id,
                'departement_id' => $u->departement_id,
                'site_id' => $u->site_id,
                'matricule' => $u->matricule,
                'telephone' => $u->telephone,
                'poste' => $u->poste,
                'est_technicien' => (bool) $u->est_technicien,
                'disponible' => (bool) $u->disponible,
                'actif' => (bool) $u->actif,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateUserData($request, null);

        // Le cast `hashed` du modèle hashere le mot de passe : le hacher ici
        // aussi rendait le hash final non idempotent dès que la même valeur
        // passait deux fois dans la chaîne.
        $data['est_technicien'] = $request->boolean('est_technicien');
        // Sémantique HTML : une case décochée n'est pas soumise, donc
        // `boolean()` la vaut false. La case est cochée par défaut dans le
        // formulaire, un client qui omet le champ obtient un compte inactif.
        $data['actif'] = $request->boolean('actif');
        $data['disponible'] = $request->boolean('disponible');

        $user = User::create($data);

        $this->syncDirecteur($user);

        return redirect()->route('admin.users.index')->with('success', "Utilisateur {$user->name} créé.");
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateUserData($request, $user, partial: true);

        $estSoiMeme = $user->is($request->user());
        $avertissements = [];

        if ($estSoiMeme && array_key_exists('role_id', $data)) {
            // Un administrateur ne doit pas se verrouiller hors du système.
            unset($data['role_id']);
            $avertissements[] = 'votre rôle reste inchangé';
        }

        if ($estSoiMeme && array_key_exists('actif', $data) && ! $data['actif']) {
            unset($data['actif']);
            $avertissements[] = 'vous ne pouvez pas désactiver votre propre compte';
        }

        if ($this->derniereProtectionAdmin($user, $data)) {
            unset($data['role_id'], $data['actif']);
            $avertissements[] = 'il doit rester au moins un administrateur actif';
        }

        // Un champ soumis vide est converti en null puis renvoyé par
        // validate() : sans ce unset, `password` null écrasait le mot de
        // passe par un hash de chaîne vide.
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        $this->syncDirecteur($user);

        $message = 'Utilisateur mis à jour.';
        if ($avertissements !== []) {
            $message .= ' Attention : ' . implode(', ', $avertissements) . '.';
        }

        return back()->with('success', $message);
    }

    /**
     * Un administrateur ne doit pas pouvoir se retirer le dernier rôle admin
     * actif, sinon plus personne ne peut administrer l'application.
     */
    private function derniereProtectionAdmin(User $user, array $data): bool
    {
        if (! $user->hasRole(Role::SLUG_ADMIN) || ! $user->actif) {
            return false;
        }

        $changeRole = array_key_exists('role_id', $data)
            && (int) $data['role_id'] !== (int) $user->role_id;
        $desactive = array_key_exists('actif', $data) && ! $data['actif'];

        if (! $changeRole && ! $desactive) {
            return false;
        }

        $resteAdminActif = User::where('role_id', $user->role_id)
            ->where('actif', true)
            ->whereKeyNot($user->getKey())
            ->exists();

        return ! $resteAdminActif;
    }

    /**
     * Fields shared by the create form and the edit form. On creation the
     * identity fields are required; on edit they stay optional so a partial
     * update (toggle, role change) never wipes data.
     */
    private function validateUserData(Request $request, ?User $user, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => $required . '|string|max:255',
            'email' => $required . '|email|max:255|unique:users,email,' . ($user?->id ?? 'NULL'),
            'password' => ($partial ? 'nullable' : 'required') . '|min:8|confirmed',
            'role_id' => $required . '|exists:roles,id',
            'departement_id' => 'nullable|exists:departements,id',
            'site_id' => 'nullable|exists:sites,id',
            'matricule' => 'nullable|string|max:50|unique:users,matricule,' . ($user?->id ?? 'NULL'),
            'telephone' => 'nullable|string|max:30',
            'poste' => 'nullable|string|max:255',
            'est_technicien' => 'boolean',
            'disponible' => 'boolean',
            'actif' => 'boolean',
        ]);
    }

    /**
     * Un Directeur de Département se devient automatiquement directeur de
     * son département ; le lien précédent est retiré pour éviter deux
     * directeurs sur un même service. Inversement, perdre le rôle ou le
     * département détache le lien, sinon le département garderait un
     * directeur fantôme.
     */
    private function syncDirecteur(User $user): void
    {
        // `update()` a déjà consulté la relation via hasRole() : sans cette
        // invalidation, syncDirecteur voyait l'ancien rôle et laissait un
        // directeur fantôme après un changement de rôle ou de département.
        $user->unsetRelation('role');

        if (! $user->hasRole(Role::SLUG_DIRECTEUR) || ! $user->departement_id) {
            Departement::where('directeur_id', $user->id)->update(['directeur_id' => null]);

            return;
        }

        Departement::where('directeur_id', $user->id)
            ->where('id', '!=', $user->departement_id)
            ->update(['directeur_id' => null]);

        Departement::where('id', $user->departement_id)->update(['directeur_id' => $user->id]);
    }

    public function destroy(User $user)
    {
        if ($user->is(request()->user())) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $user->update(['actif' => false]);

        return back()->with('success', 'Utilisateur désactivé.');
    }

    public function toggle(User $user)
    {
        if ($user->is(request()->user()) && $user->actif) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $user->update(['actif' => !$user->actif]);

        $status = $user->actif ? 'activé' : 'désactivé';
        return back()->with('success', "Utilisateur {$user->name} {$status}.");
    }
}
