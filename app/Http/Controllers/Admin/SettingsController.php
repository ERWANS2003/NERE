<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use App\Models\PriorityMatrix;
use App\Models\Sla;
use App\Models\Site;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    // Phase 12 : Administration - catégories, sites, SLA, priorités, notifications

    public function categories()
    {
        return view('admin.settings.categories', ['categories' => TicketCategory::with('team')->get()]);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:ticket_categories,id',
            'team_id' => 'nullable|exists:teams,id',
        ]);

        TicketCategory::create($data);

        return back()->with('success', 'Catégorie créée.');
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

    public function slas()
    {
        return view('admin.settings.slas', ['slas' => Sla::with('priorite', 'site')->get()]);
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
