<?php

namespace Database\Seeders;

use App\Models\MealLocation;
use Illuminate\Database\Seeder;

class MealLocationSeeder extends Seeder
{
    public function run(): void
    {
        MealLocation::firstOrCreate(
            ['code' => 'OUAGA-BUREAUX'],
            ['name' => 'Bureaux de Ouaga', 'is_active' => true],
        );
    }
}
