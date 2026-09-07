<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\Sla;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketHistory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DashboardDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $demandeurs = User::whereHas('role', fn($query) => $query->where('slug', 'demandeur'))->get();
        $techniciens = User::where('est_technicien', true)->where('actif', true)->get();
        $sites = Site::orderBy('id')->get();
        $categories = TicketCategory::where('actif', true)->orderBy('id')->get();
        $statuts = TicketStatus::pluck('id', 'slug');
        $priorites = TicketPriority::pluck('id', 'nom');

        if ($demandeurs->isEmpty() || $techniciens->isEmpty() || $sites->isEmpty() || $categories->isEmpty()) {
            return;
        }

        $tickets = [
            ['titre' => 'VPN instable au bureau Ouaga', 'status' => 'clos', 'priority' => 'Moyen', 'month' => 11, 'site' => 1, 'category' => 1, 'resolution' => true, 'satisfaction' => 5],
            ['titre' => 'Poste géologie sans accès réseau', 'status' => 'clos', 'priority' => 'Élevé', 'month' => 10, 'site' => 1, 'category' => 1, 'resolution' => true, 'satisfaction' => 4],
            ['titre' => 'Demande de création de compte métier', 'status' => 'clos', 'priority' => 'Faible', 'month' => 9, 'site' => 2, 'category' => 5, 'resolution' => true, 'satisfaction' => 5],
            ['titre' => 'Remplacement clavier salle de contrôle', 'status' => 'resolu', 'priority' => 'Moyen', 'month' => 8, 'site' => 1, 'category' => 2, 'resolution' => true, 'satisfaction' => 4],
            ['titre' => 'Erreur de sauvegarde serveur fichiers', 'status' => 'en_cours', 'priority' => 'Critique', 'month' => 7, 'site' => 1, 'category' => 1, 'resolution' => false, 'satisfaction' => null],
            ['titre' => 'Mise à jour logiciel maintenance', 'status' => 'en_attente', 'priority' => 'Élevé', 'month' => 6, 'site' => 2, 'category' => 3, 'resolution' => false, 'satisfaction' => null],
            ['titre' => 'Capteur SCADA non remonté', 'status' => 'assigne', 'priority' => 'Critique', 'month' => 5, 'site' => 1, 'category' => 4, 'resolution' => false, 'satisfaction' => null],
            ['titre' => 'Imprimante finance indisponible', 'status' => 'clos', 'priority' => 'Faible', 'month' => 4, 'site' => 2, 'category' => 2, 'resolution' => true, 'satisfaction' => 3],
            ['titre' => 'Accès dossier RH refusé', 'status' => 'resolu', 'priority' => 'Moyen', 'month' => 3, 'site' => 2, 'category' => 5, 'resolution' => true, 'satisfaction' => 4],
            ['titre' => 'Débit réseau atelier maintenance', 'status' => 'en_cours', 'priority' => 'Élevé', 'month' => 2, 'site' => 1, 'category' => 1, 'resolution' => false, 'satisfaction' => null],
            ['titre' => 'Ordinateur portable nouvel arrivant', 'status' => 'nouveau', 'priority' => 'Faible', 'month' => 1, 'site' => 2, 'category' => 2, 'resolution' => false, 'satisfaction' => null],
            ['titre' => 'Alarme supervision production', 'status' => 'en_attente', 'priority' => 'Critique', 'month' => 0, 'site' => 1, 'category' => 4, 'resolution' => false, 'satisfaction' => null],
        ];

        foreach ($tickets as $index => $data) {
            $ticket = Ticket::firstOrNew(['titre' => $data['titre']]);
            $createdAt = Carbon::now()->subMonths($data['month'])->startOfMonth()->addDays(9)->setTime(9 + ($index % 4), 15);
            $demandeur = $demandeurs[$index % $demandeurs->count()];
            $technicien = $techniciens[$index % $techniciens->count()];
            $category = $categories->get($data['category'] - 1) ?? $categories->first();
            $priorityId = $priorites[$data['priority']];
            $ticket->reference ??= sprintf('DEMO-%s-%02d', Carbon::now()->year, $index + 1);
            $sla = Sla::where('ticket_priority_id', $priorityId)
                ->where('actif', true)
                ->orderByRaw('site_id IS NULL')
                ->first();

            $ticket->fill([
                'description' => 'Ticket de démonstration pour alimenter les indicateurs opérationnels du support.',
                'user_id' => $demandeur->id,
                'site_id' => $sites->get($data['site'] - 1)?->id ?? $sites->first()->id,
                'departement_id' => $demandeur->departement_id,
                'ticket_category_id' => $category->id,
                'impact' => $data['priority'] === 'Critique' ? 'Critique' : $data['priority'],
                'urgence' => $data['priority'] === 'Critique' ? 'Élevé' : $data['priority'],
                'ticket_priority_id' => $priorityId,
                'ticket_status_id' => $statuts[$data['status']],
                'team_id' => $category->team_id,
                'assigned_to' => $data['status'] === 'nouveau' ? null : $technicien->id,
                'assigned_by' => $technicien->id,
                'date_assignation' => $createdAt->copy()->addHours(2),
                'sla_id' => $sla?->id,
                'date_echeance_reponse' => $createdAt->copy()->addHours(4),
                'date_echeance_resolution' => $createdAt->copy()->addHours($data['priority'] === 'Critique' ? 4 : 24),
                'date_premiere_reponse' => $createdAt->copy()->addHours(2),
                'date_resolution' => $data['resolution'] ? $createdAt->copy()->addHours(12) : null,
                'date_cloture' => $data['status'] === 'clos' ? $createdAt->copy()->addHours(18) : null,
                'sla_depasse' => in_array($data['status'], ['en_cours', 'en_attente'], true) && $index % 3 === 0,
                'satisfaction_note' => $data['satisfaction'],
                'satisfaction_commentaire' => $data['satisfaction'] ? 'Retour de démonstration.' : null,
            ]);
            $ticket->created_at = $createdAt;
            $ticket->updated_at = $createdAt->copy()->addHours(3);
            $ticket->save();

            TicketHistory::firstOrCreate([
                'ticket_id' => $ticket->id,
                'action' => 'creation',
                'nouvelle_valeur' => $ticket->reference,
            ], ['user_id' => $demandeur->id]);
        }
    }
}
