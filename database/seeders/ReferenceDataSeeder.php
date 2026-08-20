<?php

namespace Database\Seeders;

use App\Models\Departement;
use App\Models\Permission;
use App\Models\PriorityMatrix;
use App\Models\Role;
use App\Models\Sla;
use App\Models\Site;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketHistory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\PriorityService;
use App\Services\SlaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Jeu de données de référence pour démarrer rapidement :
 * rôles, départements, sites (Karma + Ouaga), statuts/priorités, matrice,
 * équipes, techniciens rattachés, DSI, demandeurs et tickets de démo.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $priorityService = app(PriorityService::class);
        $assignmentService = app(AssignmentService::class);
        $slaService = app(SlaService::class);

        // Départements (Phase 1)
        $departements = collect(['Production', 'Maintenance', 'Géologie', 'RH', 'Finance', 'IT'])
            ->mapWithKeys(fn($nom) => [$nom => Departement::create(['nom' => $nom, 'code' => strtoupper(substr($nom, 0, 3))])]);

        // Sites : mine de Karma + bureau de Ouagadougou
        $siteKarma = Site::create(['nom' => 'Mine de Karma', 'code' => 'KRM', 'region' => 'Centre-Nord']);
        $siteOuaga = Site::create(['nom' => 'Bureau de Ouagadougou', 'code' => 'OUA', 'region' => 'Centre']);

        // Rôles
        $roleAdmin = Role::create(['nom' => 'Administrateur', 'slug' => 'admin']);
        $roleDsi = Role::create(['nom' => 'DSI', 'slug' => 'dsi', 'description' => 'Directeur des Systèmes d\'Information']);
        $roleTechnicien = Role::create(['nom' => 'Technicien', 'slug' => 'technicien']);
        $roleDemandeur = Role::create(['nom' => 'Demandeur', 'slug' => 'demandeur']);

        // Permissions de base
        $permissions = [];
        foreach (
            [
                ['nom' => 'Créer des tickets', 'slug' => 'tickets.create', 'module' => 'tickets'],
                ['nom' => 'Affecter des tickets', 'slug' => 'tickets.assign', 'module' => 'tickets'],
                ['nom' => 'Exporter des rapports', 'slug' => 'reports.export', 'module' => 'reports'],
                ['nom' => 'Gérer les paramètres', 'slug' => 'admin.settings', 'module' => 'admin'],
            ] as $perm
        ) {
            $permissions[$perm['slug']] = Permission::create($perm);
        }

        $roleAdmin->permissions()->attach(collect($permissions)->pluck('id'));
        $roleDsi->permissions()->attach([
            $permissions['tickets.create']->id,
            $permissions['tickets.assign']->id,
            $permissions['reports.export']->id,
        ]);
        $roleTechnicien->permissions()->attach([
            $permissions['tickets.create']->id,
        ]);
        $roleDemandeur->permissions()->attach([
            $permissions['tickets.create']->id,
        ]);

        // Statuts (Phase 4)
        $statuts = [];
        foreach (
            [
                ['nom' => 'Nouveau', 'slug' => 'nouveau', 'ordre' => 1, 'est_final' => false, 'couleur' => '#0d6efd'],
                ['nom' => 'Assigné', 'slug' => 'assigne', 'ordre' => 2, 'est_final' => false, 'couleur' => '#6f42c1'],
                ['nom' => 'En cours', 'slug' => 'en_cours', 'ordre' => 3, 'est_final' => false, 'couleur' => '#fd7e14'],
                ['nom' => 'En attente', 'slug' => 'en_attente', 'ordre' => 4, 'est_final' => false, 'couleur' => '#ffc107', 'met_en_pause_sla' => true],
                ['nom' => 'Résolu', 'slug' => 'resolu', 'ordre' => 5, 'est_final' => false, 'couleur' => '#20c997'],
                ['nom' => 'Fermé', 'slug' => 'clos', 'ordre' => 6, 'est_final' => true, 'couleur' => '#198754'],
                ['nom' => 'Annulé', 'slug' => 'annule', 'ordre' => 7, 'est_final' => true, 'couleur' => '#6c757d'],
            ] as $s
        ) {
            $statuts[$s['nom']] = TicketStatus::create($s);
        }

        // Priorités (Phase 5)
        $priorites = [];
        foreach (
            [
                ['nom' => 'Faible', 'niveau' => 1, 'couleur' => '#198754', 'delai_resolution_heures' => 72],
                ['nom' => 'Moyen', 'niveau' => 2, 'couleur' => '#ffc107', 'delai_resolution_heures' => 48],
                ['nom' => 'Élevé', 'niveau' => 3, 'couleur' => '#fd7e14', 'delai_resolution_heures' => 24],
                ['nom' => 'Critique', 'niveau' => 4, 'couleur' => '#dc3545', 'delai_resolution_heures' => 4],
            ] as $p
        ) {
            $priorites[$p['nom']] = TicketPriority::create($p);
        }

        // Matrice Impact x Urgence -> Priorité
        $niveaux = ['Faible', 'Moyen', 'Élevé', 'Critique'];
        $bareme = [1, 2, 3, 4];
        foreach ($niveaux as $i => $impact) {
            foreach ($niveaux as $j => $urgence) {
                $score = max($bareme[$i], $bareme[$j]);
                $nomPriorite = $niveaux[array_search($score, $bareme, true)];
                PriorityMatrix::create([
                    'impact' => $impact,
                    'urgence' => $urgence,
                    'ticket_priority_id' => $priorites[$nomPriorite]->id,
                ]);
            }
        }

        // SLA par priorité (global)
        foreach ($priorites as $priorite) {
            Sla::create([
                'nom' => "SLA {$priorite->nom}",
                'ticket_priority_id' => $priorite->id,
                'site_id' => null,
                'temps_reponse_heures' => max(1, (int) ($priorite->delai_resolution_heures / 4)),
                'temps_resolution_heures' => $priorite->delai_resolution_heures,
                'actif' => true,
            ]);
        }

        // Équipes (Phase 6)
        $equipeReseau = Team::create(['nom' => 'Équipe Réseau', 'departement_id' => $departements['IT']->id]);
        $equipeMateriel = Team::create(['nom' => 'Équipe Matériel', 'departement_id' => $departements['IT']->id]);
        $equipeScada = Team::create(['nom' => 'Équipe SCADA/OT', 'departement_id' => $departements['Maintenance']->id]);

        // Catégories liées à une équipe (affectation automatique)
        $catReseau = TicketCategory::create(['nom' => 'Réseau', 'team_id' => $equipeReseau->id]);
        $catMateriel = TicketCategory::create(['nom' => 'Matériel', 'team_id' => $equipeMateriel->id]);
        $catLogiciel = TicketCategory::create(['nom' => 'Logiciel', 'team_id' => $equipeMateriel->id]);
        $catScada = TicketCategory::create(['nom' => 'SCADA / OT', 'team_id' => $equipeScada->id]);
        $catAd = TicketCategory::create(['nom' => 'Compte utilisateur / Active Directory', 'team_id' => $equipeReseau->id]);

        $motDePasse = Hash::make('password');

        // Administrateur système
        User::create([
            'name' => 'Administrateur ITSM',
            'email' => 'admin@nere-mining.bf',
            'password' => $motDePasse,
            'role_id' => $roleAdmin->id,
            'departement_id' => $departements['IT']->id,
            'site_id' => $siteKarma->id,
            'est_technicien' => true,
            'disponible' => true,
        ]);

        // DSI — peut affecter manuellement les tickets aux techniciens
        $dsi = User::create([
            'name' => 'Moussa Konaté',
            'email' => 'dsi@nere-mining.bf',
            'password' => $motDePasse,
            'role_id' => $roleDsi->id,
            'departement_id' => $departements['IT']->id,
            'site_id' => $siteOuaga->id,
            'poste' => 'Directeur des Systèmes d\'Information',
            'est_technicien' => false,
            'disponible' => false,
        ]);

        // Techniciens rattachés aux équipes (par site)
        $techReseauKarma = User::create([
            'name' => 'Ibrahim Ouédraogo',
            'email' => 'ibrahim.ouedraogo@nere-mining.bf',
            'password' => $motDePasse,
            'role_id' => $roleTechnicien->id,
            'departement_id' => $departements['IT']->id,
            'site_id' => $siteKarma->id,
            'est_technicien' => true,
            'disponible' => true,
        ]);

        $techReseauOuaga = User::create([
            'name' => 'Aminata Soré',
            'email' => 'aminata.sore@nere-mining.bf',
            'password' => $motDePasse,
            'role_id' => $roleTechnicien->id,
            'departement_id' => $departements['IT']->id,
            'site_id' => $siteOuaga->id,
            'est_technicien' => true,
            'disponible' => true,
        ]);

        $techMaterielKarma = User::create([
            'name' => 'Jean Kaboré',
            'email' => 'jean.kabore@nere-mining.bf',
            'password' => $motDePasse,
            'role_id' => $roleTechnicien->id,
            'departement_id' => $departements['IT']->id,
            'site_id' => $siteKarma->id,
            'est_technicien' => true,
            'disponible' => true,
        ]);

        $techMaterielOuaga = User::create([
            'name' => 'Fatimata Zongo',
            'email' => 'fatimata.zongo@nere-mining.bf',
            'password' => $motDePasse,
            'role_id' => $roleTechnicien->id,
            'departement_id' => $departements['IT']->id,
            'site_id' => $siteOuaga->id,
            'est_technicien' => true,
            'disponible' => true,
        ]);

        $techScadaKarma = User::create([
            'name' => 'Ousmane Traoré',
            'email' => 'ousmane.traore@nere-mining.bf',
            'password' => $motDePasse,
            'role_id' => $roleTechnicien->id,
            'departement_id' => $departements['Maintenance']->id,
            'site_id' => $siteKarma->id,
            'est_technicien' => true,
            'disponible' => true,
        ]);

        // Rattachement techniciens ↔ équipes
        $equipeReseau->techniciens()->attach([
            $techReseauKarma->id => ['chef_equipe' => true],
            $techReseauOuaga->id => ['chef_equipe' => false],
        ]);
        $equipeMateriel->techniciens()->attach([
            $techMaterielKarma->id => ['chef_equipe' => true],
            $techMaterielOuaga->id => ['chef_equipe' => false],
        ]);
        $equipeScada->techniciens()->attach([
            $techScadaKarma->id => ['chef_equipe' => true],
        ]);

        // Demandeurs
        $demandeurProdKarma = User::create([
            'name' => 'Salif Diallo',
            'email' => 'salif.diallo@nere-mining.bf',
            'password' => $motDePasse,
            'role_id' => $roleDemandeur->id,
            'departement_id' => $departements['Production']->id,
            'site_id' => $siteKarma->id,
        ]);

        $demandeurRhOuaga = User::create([
            'name' => 'Aïcha Sanou',
            'email' => 'aicha.sanou@nere-mining.bf',
            'password' => $motDePasse,
            'role_id' => $roleDemandeur->id,
            'departement_id' => $departements['RH']->id,
            'site_id' => $siteOuaga->id,
        ]);

        // Tickets de démo (affectation automatique via services)
        $demos = [
            [
                'titre' => 'Coupure réseau zone broyage — Karma',
                'description' => 'Plusieurs postes de la zone broyage perdent la connexion réseau depuis 07h30. Impact production.',
                'user_id' => $demandeurProdKarma->id,
                'site_id' => $siteKarma->id,
                'departement_id' => $departements['Production']->id,
                'ticket_category_id' => $catReseau->id,
                'impact' => 'Critique',
                'urgence' => 'Élevé',
                'ticket_status_id' => $statuts['Assigné']->id,
            ],
            [
                'titre' => 'Imprimante RH ne répond plus — Ouaga',
                'description' => 'L\'imprimante du service RH au bureau de Ouagadougou affiche une erreur papier alors que le bac est plein.',
                'user_id' => $demandeurRhOuaga->id,
                'site_id' => $siteOuaga->id,
                'departement_id' => $departements['RH']->id,
                'ticket_category_id' => $catMateriel->id,
                'impact' => 'Moyen',
                'urgence' => 'Moyen',
                'ticket_status_id' => $statuts['En cours']->id,
            ],
            [
                'titre' => 'Accès Active Directory bloqué — nouveau stagiaire',
                'description' => 'Le compte AD du stagiaire géologie ne permet pas la connexion au VPN.',
                'user_id' => $demandeurRhOuaga->id,
                'site_id' => $siteOuaga->id,
                'departement_id' => $departements['RH']->id,
                'ticket_category_id' => $catAd->id,
                'impact' => 'Moyen',
                'urgence' => 'Faible',
                'ticket_status_id' => $statuts['Nouveau']->id,
            ],
            [
                'titre' => 'Alarme SCADA convoyeur n°3',
                'description' => 'Alerte récurrente sur le convoyeur n°3 — capteur de vitesse incohérent.',
                'user_id' => $demandeurProdKarma->id,
                'site_id' => $siteKarma->id,
                'departement_id' => $departements['Production']->id,
                'ticket_category_id' => $catScada->id,
                'impact' => 'Élevé',
                'urgence' => 'Critique',
                'ticket_status_id' => $statuts['Assigné']->id,
            ],
            [
                'titre' => 'Licence Office expirée — comptabilité Ouaga',
                'description' => 'Message d\'activation Office 365 sur 4 postes du service Finance au bureau de Ouaga.',
                'user_id' => $demandeurRhOuaga->id,
                'site_id' => $siteOuaga->id,
                'departement_id' => $departements['Finance']->id,
                'ticket_category_id' => $catLogiciel->id,
                'impact' => 'Faible',
                'urgence' => 'Moyen',
                'ticket_status_id' => $statuts['Nouveau']->id,
            ],
        ];

        foreach ($demos as $demo) {
            $ticket = new Ticket([
                'titre' => $demo['titre'],
                'description' => $demo['description'],
                'user_id' => $demo['user_id'],
                'site_id' => $demo['site_id'],
                'departement_id' => $demo['departement_id'],
                'ticket_category_id' => $demo['ticket_category_id'],
                'impact' => $demo['impact'],
                'urgence' => $demo['urgence'],
                'ticket_status_id' => $demo['ticket_status_id'],
            ]);

            $priorityService->appliquerAuTicket($ticket);
            $assignmentService->affecter($ticket);
            $slaService->appliquer($ticket);
            $ticket->save();

            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $demo['user_id'],
                'action' => 'creation',
                'ancienne_valeur' => null,
                'nouvelle_valeur' => $ticket->reference,
            ]);

            if ($ticket->assigned_to) {
                TicketHistory::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $demo['user_id'],
                    'action' => 'affectation',
                    'ancienne_valeur' => null,
                    'nouvelle_valeur' => (string) $ticket->assigned_to,
                ]);
            }
        }

        // Ticket réaffecté manuellement par le DSI (démonstration)
        $ticketManuel = new Ticket([
            'titre' => 'Serveur de fichiers lent — Karma',
            'description' => 'Accès aux dossiers partagés très lent sur le serveur SRV-FILES-01.',
            'user_id' => $demandeurProdKarma->id,
            'site_id' => $siteKarma->id,
            'departement_id' => $departements['IT']->id,
            'ticket_category_id' => $catReseau->id,
            'impact' => 'Élevé',
            'urgence' => 'Moyen',
            'ticket_status_id' => $statuts['Assigné']->id,
        ]);
        $priorityService->appliquerAuTicket($ticketManuel);
        $assignmentService->affecter($ticketManuel);
        $slaService->appliquer($ticketManuel);
        // Réaffectation manuelle par le DSI vers un autre technicien réseau
        $ticketManuel->assigned_to = $techReseauOuaga->id;
        $ticketManuel->assigned_by = $dsi->id;
        $ticketManuel->date_assignation = now()->subHours(2);
        $ticketManuel->save();

        TicketHistory::create([
            'ticket_id' => $ticketManuel->id,
            'user_id' => $demandeurProdKarma->id,
            'action' => 'creation',
            'ancienne_valeur' => null,
            'nouvelle_valeur' => $ticketManuel->reference,
        ]);
        TicketHistory::create([
            'ticket_id' => $ticketManuel->id,
            'user_id' => $dsi->id,
            'action' => 'affectation',
            'ancienne_valeur' => (string) $techReseauKarma->id,
            'nouvelle_valeur' => (string) $techReseauOuaga->id,
        ]);
    }
}
