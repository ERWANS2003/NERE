<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RoleProfileCatalog
{
    /**
     * @return array<string, array{nom: string, description: string, permissions: list<string>}>
     */
    public static function profiles(): array
    {
        $employee = [
            'dashboard.view',
            'directory.view',
            'tickets.view',
            'tickets.create',
            'forms.view',
            'submissions.view',
            'announcements.view',
            'calendar.view',
            'resources.view',
            'departments.view',
        ];

        return [
            'employe' => [
                'nom' => 'Employé',
                'description' => 'Accès courant à l’intranet, aux formulaires et à ses demandes.',
                'permissions' => $employee,
            ],
            'technicien-departement' => [
                'nom' => 'Technicien de département',
                'description' => 'Droits employé avec traitement des demandes et tickets du département affecté.',
                'permissions' => [
                    ...$employee,
                    'tickets.update',
                ],
            ],
            'chef-departement' => [
                'nom' => 'Chef de département',
                'description' => 'Pilotage du département, validation des demandes et gestion de ses contenus.',
                'permissions' => [
                    ...$employee,
                    'analytics.view',
                    'tickets.update',
                    'tickets.assign',
                    'forms.manage',
                    'submissions.review',
                    'announcements.manage',
                    'calendar.manage',
                    'resources.manage',
                    'departments.manage',
                ],
            ],
            'administrateur-systeme' => [
                'nom' => 'Administrateur système',
                'description' => 'Accès complet à l’administration et aux fonctionnalités de l’intranet.',
                'permissions' => collect(PermissionCatalog::groupedDefinitions())
                    ->flatten(1)
                    ->pluck('slug')
                    ->all(),
            ],
        ];
    }

    public static function seed(): void
    {
        DB::transaction(function (): void {
            $permissions = Permission::query()
                ->whereIn(
                    'slug',
                    collect(self::profiles())->flatMap(fn (array $profile): array => $profile['permissions'])->unique()
                )
                ->pluck('id', 'slug');
            $missingPermissions = collect(self::profiles())
                ->flatMap(fn (array $profile): array => $profile['permissions'])
                ->unique()
                ->reject(fn (string $slug): bool => $permissions->has($slug));

            if ($missingPermissions->isNotEmpty()) {
                throw new RuntimeException(
                    'Le catalogue de permissions est incomplet : '.$missingPermissions->implode(', ')
                );
            }

            foreach (self::profiles() as $slug => $profile) {
                $role = Role::query()->firstOrCreate(
                    ['slug' => $slug],
                    [
                        'nom' => $profile['nom'],
                        'description' => $profile['description'],
                    ]
                );
                $permissionIds = collect($profile['permissions'])
                    ->map(fn (string $permissionSlug): int => $permissions->get($permissionSlug))
                    ->all();

                $role->permissions()->syncWithoutDetaching($permissionIds);
            }
        });
    }
}
