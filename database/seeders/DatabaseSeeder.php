<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentsSeeder::class,
            MealLocationSeeder::class,
            AnnouncementSeeder::class,
            CalendarEventSeeder::class,
            DepartmentServiceSeeder::class,
            DepartmentFormSeeder::class,
            DemoSubmissionsSeeder::class,
            ResourceSeeder::class,
            TimeOffSeeder::class,
        ]);
    }
}
