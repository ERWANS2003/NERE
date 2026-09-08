<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class MiningPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Tickets Management
            ['nom' => 'Voir tous les tickets', 'slug' => 'tickets.view', 'module' => 'tickets'],
            ['nom' => 'Voir ses tickets', 'slug' => 'tickets.view_own', 'module' => 'tickets'],
            ['nom' => 'Créer un ticket', 'slug' => 'tickets.create', 'module' => 'tickets'],
            ['nom' => 'Modifier un ticket', 'slug' => 'tickets.update', 'module' => 'tickets'],
            ['nom' => 'Assigner un ticket', 'slug' => 'tickets.assign', 'module' => 'tickets'],
            ['nom' => 'Commenter un ticket', 'slug' => 'tickets.comment', 'module' => 'tickets'],
            ['nom' => 'Clôturer un ticket', 'slug' => 'tickets.close', 'module' => 'tickets'],
            ['nom' => 'Supprimer un ticket', 'slug' => 'tickets.delete', 'module' => 'tickets'],

            // Assets Management
            ['nom' => 'Voir les actifs', 'slug' => 'assets.view', 'module' => 'assets'],
            ['nom' => 'Gérer les actifs', 'slug' => 'assets.manage', 'module' => 'assets'],
            ['nom' => 'Mettre à jour les actifs', 'slug' => 'assets.update', 'module' => 'assets'],

            // Teams Management
            ['nom' => 'Voir les équipes', 'slug' => 'teams.view', 'module' => 'teams'],
            ['nom' => 'Gérer les équipes', 'slug' => 'teams.manage', 'module' => 'teams'],
            ['nom' => 'Coordonner les équipes', 'slug' => 'teams.coordinate', 'module' => 'teams'],
            ['nom' => 'Mettre à jour les membres', 'slug' => 'teams.update_members', 'module' => 'teams'],

            // Safety Management
            ['nom' => 'Voir la sécurité', 'slug' => 'safety.view', 'module' => 'safety'],
            ['nom' => 'Gérer la sécurité', 'slug' => 'safety.manage', 'module' => 'safety'],
            ['nom' => 'Gérer les incidents', 'slug' => 'safety.incidents', 'module' => 'safety'],

            // Reports
            ['nom' => 'Voir les rapports', 'slug' => 'reports.view', 'module' => 'reports'],
            ['nom' => 'Rapports maintenance', 'slug' => 'reports.maintenance', 'module' => 'reports'],
            ['nom' => 'Rapports sécurité', 'slug' => 'reports.safety', 'module' => 'reports'],
            ['nom' => 'Rapports RH', 'slug' => 'reports.hr', 'module' => 'reports'],
            ['nom' => 'Rapports financiers', 'slug' => 'reports.financial', 'module' => 'reports'],
            ['nom' => 'Exporter les rapports', 'slug' => 'reports.export', 'module' => 'reports'],

            // Schedule Management
            ['nom' => 'Voir le planning', 'slug' => 'schedule.view', 'module' => 'schedule'],
            ['nom' => 'Gérer le planning', 'slug' => 'schedule.manage', 'module' => 'schedule'],
            ['nom' => 'Mettre à jour le planning', 'slug' => 'schedule.update', 'module' => 'schedule'],

            // Users Management
            ['nom' => 'Voir les utilisateurs', 'slug' => 'users.view', 'module' => 'users'],
            ['nom' => 'Gérer les utilisateurs', 'slug' => 'users.manage', 'module' => 'users'],

            // Training
            ['nom' => 'Voir les formations', 'slug' => 'training.view', 'module' => 'training'],
            ['nom' => 'Gérer les formations', 'slug' => 'training.manage', 'module' => 'training'],

            // Budget
            ['nom' => 'Voir le budget', 'slug' => 'budget.view', 'module' => 'budget'],
            ['nom' => 'Gérer le budget', 'slug' => 'budget.manage', 'module' => 'budget'],

            // Systems
            ['nom' => 'Voir les systèmes', 'slug' => 'systems.view', 'module' => 'systems'],
            ['nom' => 'Gérer les systèmes', 'slug' => 'systems.manage', 'module' => 'systems'],

            // Knowledge Base
            ['nom' => 'Voir la base de connaissances', 'slug' => 'knowledge.view', 'module' => 'knowledge'],
            ['nom' => 'Créer des articles', 'slug' => 'knowledge.create', 'module' => 'knowledge'],
            ['nom' => 'Modifier des articles', 'slug' => 'knowledge.manage', 'module' => 'knowledge'],

            // Admin
            ['nom' => 'Administration', 'slug' => 'admin.access', 'module' => 'admin'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        $this->command->info('Mining permissions created/updated successfully');
    }
}
