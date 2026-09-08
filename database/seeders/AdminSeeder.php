<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin role if not exists
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['nom' => 'Administrator']
        );

        // Create admin user if not exists
        $admin = User::updateOrCreate(
            ['email' => 'admin@nere-mining.bf'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'actif' => true,
                'role_id' => $adminRole->id,  // Assign admin role directly
            ]
        );

        $this->command->info('✅ Admin user created/verified: admin@nere-mining.bf / admin123');
        $this->command->info('   Role: ' . $admin->role?->nom ?? 'NONE');
    }
}
