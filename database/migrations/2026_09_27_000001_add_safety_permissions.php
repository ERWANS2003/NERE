<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The safety module is guarded by `permission:safety.view` and
 * `permission:safety.manage`, but neither slug existed in the permissions
 * table, so every route in resources/views/safety was unreachable (403 for all
 * roles, admin included, because Role::hasPermission() only grants 'admin' by
 * role slug and still needs the row to exist for the other roles).
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private array $permissions = [
        'safety.view' => 'Consulter les incidents de sécurité',
        'safety.manage' => 'Déclarer et gérer les incidents de sécurité',
    ];

    /** @var array<string, list<string>> */
    private array $grants = [
        'safety.view' => ['admin', 'dsi', 'directeur_departement', 'technicien'],
        'safety.manage' => ['admin', 'dsi'],
    ];

    public function up(): void
    {
        foreach ($this->permissions as $slug => $nom) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $slug],
                ['nom' => $nom, 'module' => 'safety', 'updated_at' => now(), 'created_at' => now()],
            );
        }

        foreach ($this->grants as $slug => $roleSlugs) {
            $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');

            foreach ($roleSlugs as $roleSlug) {
                $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');

                if (! $roleId) {
                    continue;
                }

                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    [],
                );
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('slug', array_keys($this->permissions))->pluck('id');

        DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('slug', array_keys($this->permissions))->delete();
    }
};
