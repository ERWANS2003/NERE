<?php

namespace Database\Seeders;

use App\Models\Departement;
use App\Models\Site;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use Illuminate\Database\Seeder;

class MiningCompanyDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create mining sites
        // La table `sites` expose `region` / `adresse` : `localisation`
        // n'existe ni dans les migrations ni dans le $fillable du modèle et
        // faisait échouer le seeding sur une base neuve.
        $sites = [
            ['nom' => 'Site Principal - Extraction', 'code' => 'SITE-MAIN-EXT', 'region' => 'Zone Minière Principale'],
            ['nom' => 'Site de Traitement', 'code' => 'SITE-TRAITEMENT', 'region' => 'Usine de Traitement'],
            ['nom' => 'Site de Stockage', 'code' => 'SITE-STOCK', 'region' => 'Zone de Stockage'],
        ];

        foreach ($sites as $siteData) {
            Site::updateOrCreate(['code' => $siteData['code']], $siteData);
        }

        // Create mining departments
        $departments = [
            [
                'nom' => 'Direction Générale',
                'code' => 'DG',
                'description' => 'Direction stratégique et administration'
            ],
            [
                'nom' => 'Opérations d\'Extraction',
                'code' => 'OPE-EXT',
                'description' => 'Équipes d\'extraction et forage'
            ],
            [
                'nom' => 'Traitement et Raffinage',
                'code' => 'TRAIT-RAFF',
                'description' => 'Usine de traitement du minerai'
            ],
            [
                'nom' => 'Maintenance & Infrastructure',
                'code' => 'MAINT-INFRA',
                'description' => 'Maintenance des équipements et infrastructure'
            ],
            [
                'nom' => 'Sécurité & Environnement',
                'code' => 'SEC-ENV',
                'description' => 'Santé, sécurité et environnement'
            ],
            [
                'nom' => 'Ressources Humaines',
                'code' => 'RH',
                'description' => 'Gestion du personnel et formation'
            ],
            [
                'nom' => 'Finance & Administration',
                'code' => 'FIN-ADMIN',
                'description' => 'Comptabilité, budgets et administration'
            ],
            [
                'nom' => 'Technologies de l\'Information',
                'code' => 'IT',
                'description' => 'Informatique et systèmes'
            ],
        ];

        foreach ($departments as $deptData) {
            Departement::updateOrCreate(['code' => $deptData['code']], $deptData);
        }

        // Create ticket categories specific to mining
        // `ticket_categories` n'a pas de colonne `slug` : la clé naturelle
        // retenue est le libellé.
        $categories = [
            ['nom' => 'Maintenance Préventive'],
            ['nom' => 'Maintenance Corrective'],
            ['nom' => 'Équipement Défaillant'],
            ['nom' => 'Sécurité du Site'],
            ['nom' => 'Incident HSE'],
            ['nom' => 'Demande RH'],
            ['nom' => 'Demande IT'],
            ['nom' => 'Problème de Qualité'],
            ['nom' => 'Accès aux Systèmes'],
            ['nom' => 'Formation Requise'],
            ['nom' => 'Planification Production'],
            ['nom' => 'Problème de Logistique'],
        ];

        foreach ($categories as $catData) {
            TicketCategory::updateOrCreate(['nom' => $catData['nom']], $catData);
        }

        // Create ticket priorities
        // `niveau` est croissant avec la gravité (1 = basse, 4 = critique) :
        // Ticket::scopeCritique filtre sur `niveau >= 4` et les compteurs du
        // dashboard sur `niveau >= 3`. L'ordre inverse rendait les tickets de
        // basse priorité « critiques ».
        $priorities = [
            ['nom' => 'Basse', 'niveau' => 1, 'couleur' => '#6b7280'],
            ['nom' => 'Normale', 'niveau' => 2, 'couleur' => '#0891b2'],
            ['nom' => 'Haute', 'niveau' => 3, 'couleur' => '#d97706'],
            ['nom' => 'Critique', 'niveau' => 4, 'couleur' => '#dc2626'],
        ];

        foreach ($priorities as $prioData) {
            TicketPriority::updateOrCreate(['nom' => $prioData['nom']], $prioData);
        }

        // Create ticket statuses
        // `est_final` conditionne la fermeture des tickets, l'arrêt du SLA et
        // tous les filtres « ouverts » : sans lui aucun ticket n'était clos.
        $statuses = [
            ['nom' => 'Nouveau', 'slug' => 'nouveau', 'ordre' => 1, 'couleur' => '#3b82f6', 'est_final' => false],
            ['nom' => 'Assigné', 'slug' => 'assigne', 'ordre' => 2, 'couleur' => '#f59e0b', 'est_final' => false],
            ['nom' => 'En cours', 'slug' => 'en_cours', 'ordre' => 3, 'couleur' => '#f97316', 'est_final' => false],
            ['nom' => 'En attente', 'slug' => 'en_attente', 'ordre' => 4, 'couleur' => '#8b5cf6', 'est_final' => false],
            ['nom' => 'Résolu', 'slug' => 'resolu', 'ordre' => 5, 'couleur' => '#10b981', 'est_final' => true],
            ['nom' => 'Fermé', 'slug' => 'clos', 'ordre' => 6, 'couleur' => '#6b7280', 'est_final' => true],
            ['nom' => 'Annulé', 'slug' => 'annule', 'ordre' => 7, 'couleur' => '#ef4444', 'est_final' => true],
        ];

        foreach ($statuses as $statusData) {
            TicketStatus::updateOrCreate(['slug' => $statusData['slug']], $statusData);
        }

        $this->command->info('Mining company data seeded successfully');
    }
}
