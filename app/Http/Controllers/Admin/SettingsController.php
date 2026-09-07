<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use App\Models\Departement;
use App\Models\PriorityMatrix;
use App\Models\Sla;
use App\Models\Site;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    // Phase 12 : Administration - catégories, sites, SLA, priorités, notifications

    public function departements()
    {
        return view('admin.settings.departements', [
            'departements' => Departement::withCount(['users', 'teams', 'tickets'])->orderBy('nom')->get(),
        ]);
    }

    public function storeDepartement(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:departements,code',
            'description' => 'nullable|string|max:1000',
        ]);

        Departement::create($data + ['actif' => true]);

        return back()->with('success', 'Service créé.');
    }

    public function destroyDepartement(Departement $departement)
    {
        if ($departement->users()->exists() || $departement->teams()->exists() || $departement->tickets()->exists()) {
            return back()->with('error', 'Impossible de supprimer ce service : il est encore utilisé par des utilisateurs, équipes ou tickets.');
        }

        $departement->delete();
        return back()->with('success', 'Service supprimé.');
    }

    public function teams()
    {
        return view('admin.settings.teams', [
            'teams' => Team::with(['departement', 'techniciens', 'categories'])->withCount(['tickets', 'categories'])->orderBy('nom')->get(),
            'departements' => Departement::where('actif', true)->orderBy('nom')->get(),
            'users' => User::where('actif', true)->where('est_technicien', true)->with('departement')->orderBy('name')->get(),
            'categories' => TicketCategory::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function storeTeam(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'departement_id' => 'required|exists:departements,id',
            'description' => 'nullable|string|max:1000',
        ]);

        Team::create($data);

        return back()->with('success', 'Équipe créée.');
    }

    public function updateTeam(Request $request, Team $team)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'departement_id' => 'required|exists:departements,id',
            'description' => 'nullable|string|max:1000',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:ticket_categories,id',
        ]);

        $userIds = collect($data['user_ids'] ?? []);
        $invalidUsers = User::whereIn('id', $userIds)->where(function ($query) use ($data) {
            $query->where('actif', false)->orWhere('est_technicien', false)->orWhere('departement_id', '!=', $data['departement_id']);
        })->exists();

        if ($invalidUsers) {
            return back()->withErrors(['user_ids' => 'Chaque membre doit être un technicien actif du service choisi.'])->withInput();
        }

        $categoryIds = collect($data['category_ids'] ?? []);
        $invalidCategories = TicketCategory::whereIn('id', $categoryIds)->where('actif', false)->exists();
        if ($invalidCategories) {
            return back()->withErrors(['category_ids' => 'Seules les catégories actives peuvent être rattachées à une équipe.'])->withInput();
        }

        $team->update(collect($data)->except(['user_ids', 'category_ids'])->all());
        $team->techniciens()->sync($userIds->mapWithKeys(fn($id) => [$id => ['chef_equipe' => false]])->all());
        TicketCategory::where('team_id', $team->id)->whereNotIn('id', $categoryIds)->update(['team_id' => null]);
        TicketCategory::whereIn('id', $categoryIds)->update(['team_id' => $team->id]);

        return back()->with('success', 'Équipe et membres mis à jour.');
    }

    public function destroyTeam(Team $team)
    {
        if ($team->tickets()->exists() || $team->categories()->exists() || $team->techniciens()->exists()) {
            return back()->with('error', 'Impossible de supprimer cette équipe : retirez d’abord ses catégories, membres et tickets associés.');
        }

        $team->delete();
        return back()->with('success', 'Équipe supprimée.');
    }

    public function categories()
    {
        return view('admin.settings.categories', [
            'categories' => TicketCategory::with('team.departement')->orderBy('nom')->get(),
            'teams' => Team::with('departement')->orderBy('nom')->get(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:ticket_categories,id',
            'team_id' => 'required|exists:teams,id',
        ]);

        TicketCategory::create($data);

        return back()->with('success', 'Catégorie créée.');
    }

    public function destroyCategory(TicketCategory $category)
    {
        if ($category->tickets()->exists() || $category->enfants()->exists()) {
            return back()->with('error', 'Impossible de supprimer cette catégorie : elle est utilisée par des tickets ou sous-catégories.');
        }

        $category->delete();
        return back()->with('success', 'Catégorie supprimée.');
    }

    public function sites()
    {
        return view('admin.settings.sites', ['sites' => Site::all()]);
    }

    public function storeSite(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|unique:sites,code',
            'region' => 'nullable|string',
        ]);

        Site::create($data);

        return back()->with('success', 'Site minier créé.');
    }

    public function destroySite(Site $site)
    {
        if ($site->users()->exists() || $site->tickets()->exists() || $site->assets()->exists()) {
            return back()->with('error', 'Impossible de supprimer ce site : il est encore utilisé par des utilisateurs, actifs ou tickets.');
        }

        $site->delete();
        return back()->with('success', 'Site supprimé.');
    }

    public function slas()
    {
        return view('admin.settings.slas', [
            'slas' => Sla::with('priorite', 'site')->orderBy('nom')->get(),
            'priorites' => TicketPriority::orderBy('niveau')->get(),
            'sites' => Site::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function storeSla(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'ticket_priority_id' => 'required|exists:ticket_priorities,id',
            'site_id' => 'nullable|exists:sites,id',
            'temps_reponse_heures' => 'required|integer|min:1',
            'temps_resolution_heures' => 'required|integer|min:1',
        ]);

        Sla::create($data);

        return back()->with('success', 'SLA créé.');
    }

    public function destroySla(Sla $sla)
    {
        if ($sla->tickets()->exists()) {
            return back()->with('error', 'Impossible de supprimer ce SLA : il est déjà associé à des tickets.');
        }

        $sla->delete();
        return back()->with('success', 'SLA supprimé.');
    }

    // Matrice de priorités configurable (Phase 5)
    public function priorityMatrix()
    {
        return view('admin.settings.priority-matrix', [
            'matrice' => PriorityMatrix::with('priorite')->get(),
            'priorites' => TicketPriority::orderBy('niveau')->get(),
        ]);
    }

    public function updatePriorityMatrix(Request $request)
    {
        $data = $request->validate([
            'impact' => 'required|in:Faible,Moyen,Élevé,Critique',
            'urgence' => 'required|in:Faible,Moyen,Élevé,Critique',
            'ticket_priority_id' => 'required|exists:ticket_priorities,id',
        ]);

        PriorityMatrix::updateOrCreate(
            ['impact' => $data['impact'], 'urgence' => $data['urgence']],
            ['ticket_priority_id' => $data['ticket_priority_id']]
        );

        return back()->with('success', 'Règle de priorité mise à jour.');
    }

    public function destroyPriorityMatrix(PriorityMatrix $priorityMatrix)
    {
        $priorityMatrix->delete();
        return back()->with('success', 'Règle de priorité supprimée.');
    }

    public function notifications()
    {
        return view('admin.settings.notifications', ['parametres' => NotificationSetting::all()]);
    }

    public function updateNotifications(Request $request)
    {
        foreach ($request->input('parametres', []) as $id => $canaux) {
            NotificationSetting::whereKey($id)->update([
                'email_actif' => isset($canaux['email']),
                'interne_actif' => isset($canaux['interne']),
                'slack_actif' => isset($canaux['slack']),
                'teams_actif' => isset($canaux['teams']),
                'sms_actif' => isset($canaux['sms']),
            ]);
        }

        return back()->with('success', 'Paramètres de notification mis à jour.');
    }
}
