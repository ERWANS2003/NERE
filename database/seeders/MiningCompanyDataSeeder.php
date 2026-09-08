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
        $sites = [
            ['nom' => 'Site Principal - Extraction', 'code' => 'SITE-MAIN-EXT', 'localisation' => 'Zone Minière Principale'],
            ['nom' => 'Site de Traitement', 'code' => 'SITE-TRAITEMENT', 'localisation' => 'Usine de Traitement'],
            ['nom' => 'Site de Stockage', 'code' => 'SITE-STOCK', 'localisation' => 'Zone de Stockage'],
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
        $categories = [
            ['nom' => 'Maintenance Préventive', 'slug' => 'maintenance-preventive'],
            ['nom' => 'Maintenance Corrective', 'slug' => 'maintenance-corrective'],
            ['nom' => 'Équipement Défaillant', 'slug' => 'equipment-failure'],
            ['nom' => 'Sécurité du Site', 'slug' => 'site-safety'],
            ['nom' => 'Incident HSE', 'slug' => 'hse-incident'],
            ['nom' => 'Demande RH', 'slug' => 'hr-request'],
            ['nom' => 'Demande IT', 'slug' => 'it-request'],
            ['nom' => 'Problème de Qualité', 'slug' => 'quality-issue'],
            ['nom' => 'Accès aux Systèmes', 'slug' => 'system-access'],
            ['nom' => 'Formation Requise', 'slug' => 'training-required'],
            ['nom' => 'Planification Production', 'slug' => 'production-planning'],
            ['nom' => 'Problème de Logistique', 'slug' => 'logistics-issue'],
        ];

        foreach ($categories as $catData) {
            TicketCategory::updateOrCreate(['slug' => $catData['slug']], $catData);
        }

        // Create ticket priorities
        $priorities = [
            ['nom' => 'Critique', 'slug' => 'critical', 'niveau' => 1, 'couleur' => '#dc2626'],
            ['nom' => 'Haute', 'slug' => 'high', 'niveau' => 2, 'couleur' => '#d97706'],
            ['nom' => 'Normale', 'slug' => 'normal', 'niveau' => 3, 'couleur' => '#0891b2'],
            ['nom' => 'Basse', 'slug' => 'low', 'niveau' => 4, 'couleur' => '#6b7280'],
        ];

        foreach ($priorities as $prioData) {
            TicketPriority::updateOrCreate(['slug' => $prioData['slug']], $prioData);
        }

        // Create ticket statuses
        $statuses = [
            ['nom' => 'Nouveau', 'slug' => 'new', 'ordre' => 1, 'couleur' => '#3b82f6'],
            ['nom' => 'Ouvert', 'slug' => 'open', 'ordre' => 2, 'couleur' => '#f59e0b'],
            ['nom' => 'Assigné', 'slug' => 'assigned', 'ordre' => 3, 'couleur' => '#f97316'],
            ['nom' => 'En Cours', 'slug' => 'in_progress', 'ordre' => 4, 'couleur' => '#eab308'],
            ['nom' => 'En Attente', 'slug' => 'waiting', 'ordre' => 5, 'couleur' => '#8b5cf6'],
            ['nom' => 'Résolu', 'slug' => 'resolved', 'ordre' => 6, 'couleur' => '#10b981'],
            ['nom' => 'Fermé', 'slug' => 'closed', 'ordre' => 7, 'couleur' => '#6b7280'],
            ['nom' => 'Rejeté', 'slug' => 'rejected', 'ordre' => 8, 'couleur' => '#ef4444'],
        ];

        foreach ($statuses as $statusData) {
            TicketStatus::updateOrCreate(['slug' => $statusData['slug']], $statusData);
        }

        $this->command->info('Mining company data seeded successfully');
    }
}
