<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentsSeeder::class,
            DemoEmployeesSeeder::class,
            AnnouncementSeeder::class,
            CalendarEventSeeder::class,
            DepartmentFormSeeder::class,
            DepartmentServiceSeeder::class,
            DemoSubmissionsSeeder::class,
            ResourceSeeder::class,
            TimeOffSeeder::class,
        ]);
    }
}
