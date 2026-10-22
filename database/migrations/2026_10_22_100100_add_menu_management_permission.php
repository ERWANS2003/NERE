<?php

use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionCatalog;
use App\Support\RoleProfileCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permission = collect(PermissionCatalog::groupedDefinitions())
            ->flatten(1)
            ->firstWhere('slug', 'menu.manage');

        if ($permission === null) {
            throw new RuntimeException('La permission menu.manage est absente du catalogue.');
        }

        Permission::updateOrCreate(['slug' => $permission['slug']], $permission);
        RoleProfileCatalog::seed();
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $permission = DB::table('permissions')->where('slug', 'menu.manage')->first();
            if ($permission === null) {
                return;
            }

            DB::table('permission_role')->where('permission_id', $permission->id)->delete();

            $managerRole = Role::query()->where('slug', 'gestionnaire-menu')->first();
            if ($managerRole !== null && ! DB::table('role_user')->where('role_id', $managerRole->id)->exists()) {
                DB::table('permission_role')->where('role_id', $managerRole->id)->delete();
                $managerRole->delete();
            }

            DB::table('permissions')->where('id', $permission->id)->delete();
        });
    }
};
