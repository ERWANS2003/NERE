<?php

use App\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (PermissionCatalog::groupedDefinitions() as $permissions) {
            foreach ($permissions as $permission) {
                DB::table('permissions')->updateOrInsert(
                    ['slug' => $permission['slug']],
                    [
                        'nom' => $permission['nom'],
                        'description' => $permission['description'],
                        'updated_at' => $now,
                        'created_at' => $now,
                    ],
                );
            }
        }
    }

    public function down(): void
    {
        $slugs = collect(PermissionCatalog::groupedDefinitions())
            ->flatten(1)
            ->pluck('slug');

        DB::table('permission_role')
            ->whereIn('permission_id', DB::table('permissions')->select('id')->whereIn('slug', $slugs))
            ->delete();

        DB::table('permissions')->whereIn('slug', $slugs)->delete();
    }
};
