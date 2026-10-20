<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionCatalog::groupedDefinitions() as $permissions) {
            foreach ($permissions as $permission) {
                Permission::updateOrCreate(
                    ['slug' => $permission['slug']],
                    $permission,
                );
            }
        }
    }
}
