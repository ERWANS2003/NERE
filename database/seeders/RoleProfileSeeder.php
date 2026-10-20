<?php

namespace Database\Seeders;

use App\Support\RoleProfileCatalog;
use Illuminate\Database\Seeder;

class RoleProfileSeeder extends Seeder
{
    public function run(): void
    {
        RoleProfileCatalog::seed();
    }
}
