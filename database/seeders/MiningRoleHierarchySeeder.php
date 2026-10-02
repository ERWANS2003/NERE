<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Rôles applicatifs.
 *
 * Les slugs ci-dessous sont ceux que le code compare strictement :
 * `User::hasRole()`, le middleware `role:`, la navigation du portail et les
 * Gates d'`AppServiceProvider`. Les ajouter ici sans les déclarer dans
 * `Role::SLUGS_SYSTEME` créerait des rôles invisibles pour l'application.
 */
class MiningRoleHierarchySeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'nom' => 'Administrateur',
                'slug' => Role::SLUG_ADMIN,
                'description' => 'Accès complet : administration, rôles, utilisateurs et paramétrage.',
                'permissions' => ['*'],
            ],
            [
                'nom' => 'DSI',
                'slug' => Role::SLUG_DSI,
                'description' => 'Direction des Systèmes d\'Information : supervision du parc, des accès et du helpdesk.',
                'permissions' => [
                    'tickets.view', 'tickets.view_own', 'tickets.create', 'tickets.update',
                    'tickets.assign', 'tickets.comment', 'tickets.close',
                    'assets.view', 'assets.manage', 'assets.update',
                    'teams.view', 'teams.manage', 'teams.coordinate', 'teams.update_members',
                    'users.view', 'users.manage',
                    'systems.view', 'systems.manage',
                    'knowledge.view', 'knowledge.create', 'knowledge.manage',
                    'reports.view', 'reports.export', 'reports.maintenance', 'reports.safety',
                    'schedule.view', 'schedule.manage', 'schedule.update',
                    'safety.view', 'safety.manage', 'safety.incidents',
                    'admin.access',
                ],
            ],
            [
                'nom' => 'Directeur de département',
                'slug' => Role::SLUG_DIRECTEUR,
                'description' => 'Pilotage de son département : tickets, équipe, actifs et rapports.',
                'permissions' => [
                    'tickets.view', 'tickets.create', 'tickets.update', 'tickets.assign',
                    'tickets.comment', 'tickets.close',
                    'assets.view', 'assets.manage',
                    'teams.view', 'teams.manage', 'teams.coordinate', 'teams.update_members',
                    'users.view',
                    'reports.view', 'reports.export', 'reports.maintenance',
                    'schedule.view', 'schedule.manage',
                    'safety.view', 'safety.manage', 'safety.incidents',
                    'knowledge.view',
                ],
            ],
            [
                'nom' => 'Technicien',
                'slug' => Role::SLUG_TECHNICIEN,
                'description' => 'Traitement technique des tickets, accès aux actifs et aux connaissances.',
                'permissions' => [
                    'tickets.view', 'tickets.view_own', 'tickets.create', 'tickets.update',
                    'tickets.comment',
                    'assets.view', 'assets.update',
                    'knowledge.view', 'knowledge.create',
                    'safety.view', 'safety.incidents',
                ],
            ],
            [
                'nom' => 'Demandeur',
                'slug' => Role::SLUG_DEMANDEUR,
                'description' => 'Crée et suit ses propres demandes de support.',
                'permissions' => [
                    'tickets.view_own', 'tickets.create', 'tickets.comment',
                    'knowledge.view',
                ],
            ],
        ];

        $allPermissions = Permission::pluck('id', 'slug');

        foreach ($roles as $roleData) {
            $slugs = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::updateOrCreate(['slug' => $roleData['slug']], $roleData);

            // `['*']` doit être matérialisé en base : Role::hasPermission()
            // cherche une ligne '*' dans permission_role, et la page de
            // détail n'afficherait sinon aucune permission pour l'admin.
            $permissionIds = $slugs === ['*']
                ? $allPermissions->values()->all()
                : $allPermissions->only($slugs)->values()->all();

            $role->permissions()->sync($permissionIds);
        }

        $this->command->info(sprintf(
            'Rôles applicatifs créés/mis à jour : %s',
            Role::whereIn('slug', Role::SLUGS_SYSTEME)->pluck('slug')->implode(', ')
        ));
    }
}
