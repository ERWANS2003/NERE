<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            MiningPermissionsSeeder::class,
            MiningRoleHierarchySeeder::class,
            MiningCompanyDataSeeder::class,
            ReferenceDataSeeder::class,
            DashboardDemoDataSeeder::class,
        ]);
    }
}
