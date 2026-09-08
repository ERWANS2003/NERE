<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class MiningRoleHierarchySeeder extends Seeder
{
    public function run(): void
    {
        // Define the complete role hierarchy for mining operations
        $roles = [
            [
                'nom' => 'Directeur Général',
                'slug' => 'directeur_general',
                'description' => 'Accès complet à tous les systèmes - Contrôle stratégique de la mine',
                'permissions' => ['*'] // Wildcard for all permissions
            ],
            [
                'nom' => 'Directeur d\'Opérations',
                'slug' => 'directeur_operations',
                'description' => 'Gestion complète des opérations - Supervision des équipes et actifs',
                'permissions' => [
                    'tickets.view', 'tickets.create', 'tickets.assign', 'tickets.close',
                    'assets.view', 'assets.manage',
                    'teams.view', 'teams.manage',
                    'reports.view', 'reports.export',
                    'schedule.view', 'schedule.manage',
                    'safety.view', 'safety.manage'
                ]
            ],
            [
                'nom' => 'Gestionnaire de Maintenance',
                'slug' => 'maintenance_manager',
                'description' => 'Gestion des tickets de maintenance et des équipes techniques',
                'permissions' => [
                    'tickets.view', 'tickets.create', 'tickets.assign', 'tickets.update', 'tickets.close',
                    'assets.view', 'assets.update',
                    'teams.view', 'teams.coordinate',
                    'reports.view', 'reports.maintenance',
                    'schedule.view', 'schedule.update'
                ]
            ],
            [
                'nom' => 'Chef d\'Équipe Maintenance',
                'slug' => 'chef_equipe_maintenance',
                'description' => 'Supervision directe d\'une équipe - Assignation des tâches techniques',
                'permissions' => [
                    'tickets.view', 'tickets.create', 'tickets.update', 'tickets.comment',
                    'assets.view',
                    'teams.view', 'teams.update_members',
                    'reports.view'
                ]
            ],
            [
                'nom' => 'Technicien Maintenance',
                'slug' => 'technicien_maintenance',
                'description' => 'Exécution des tâches de maintenance - Mise à jour d\'état des tickets',
                'permissions' => [
                    'tickets.view', 'tickets.update', 'tickets.comment',
                    'assets.view',
                    'reports.view'
                ]
            ],
            [
                'nom' => 'Responsable HSE',
                'slug' => 'responsable_hse',
                'description' => 'Gestion de la sécurité et de l\'environnement - Suivi des incidents',
                'permissions' => [
                    'tickets.view', 'tickets.create', 'tickets.assign', 'tickets.update',
                    'safety.view', 'safety.manage', 'safety.incidents',
                    'reports.view', 'reports.safety', 'reports.export',
                    'assets.view'
                ]
            ],
            [
                'nom' => 'Responsable RH',
                'slug' => 'responsable_rh',
                'description' => 'Gestion des ressources humaines - Paie et formations',
                'permissions' => [
                    'tickets.view',
                    'users.view', 'users.manage',
                    'training.view', 'training.manage',
                    'reports.view', 'reports.hr', 'reports.export'
                ]
            ],
            [
                'nom' => 'Responsable Finance',
                'slug' => 'responsable_finance',
                'description' => 'Gestion financière - Budgets et dépenses',
                'permissions' => [
                    'assets.view',
                    'reports.view', 'reports.export', 'reports.financial',
                    'budget.view', 'budget.manage'
                ]
            ],
            [
                'nom' => 'Responsable IT',
                'slug' => 'responsable_it',
                'description' => 'Gestion informatique et des systèmes',
                'permissions' => [
                    'tickets.view', 'tickets.create', 'tickets.assign', 'tickets.update', 'tickets.close',
                    'systems.view', 'systems.manage',
                    'users.view', 'users.manage',
                    'reports.view'
                ]
            ],
            [
                'nom' => 'Demandeur - Service',
                'slug' => 'demandeur_service',
                'description' => 'Création de demandes pour des services internes',
                'permissions' => [
                    'tickets.view_own', 'tickets.create', 'tickets.comment',
                    'knowledge.view'
                ]
            ],
            [
                'nom' => 'Consultable - Système',
                'slug' => 'consultant_systeme',
                'description' => 'Accès en lecture seule - Consultation du système',
                'permissions' => [
                    'tickets.view', 'assets.view', 'reports.view', 'knowledge.view'
                ]
            ]
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::updateOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );

            // Sync permissions
            if ($permissions !== ['*']) {
                $permissionIds = Permission::whereIn('slug', $permissions)->pluck('id')->toArray();
                $role->permissions()->sync($permissionIds);
            }
        }

        $this->command->info('Mining role hierarchy created/updated successfully');
    }
}
