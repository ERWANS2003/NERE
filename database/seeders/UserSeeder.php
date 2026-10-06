<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Créer un utilisateur admin de test
        $adminUser = User::firstOrCreate([
            'email' => 'admin@nere-mining.com'
        ], [
            'name' => 'Admin NERE Mining',
            'matricule' => 'ADMIN001',
            'password' => Hash::make('admin123'),
            'is_active' => true,
            'is_super_admin' => true,
        ]);

        // Créer quelques utilisateurs de test
        $users = [
            [
                'name' => 'Jean Dupont',
                'email' => 'jean.dupont@nere-mining.com',
                'matricule' => 'EMP001',
                'password' => Hash::make('password123'),
                'is_active' => true,
                'is_super_admin' => false,
            ],
            [
                'name' => 'Marie Martin',
                'email' => 'marie.martin@nere-mining.com',
                'matricule' => 'EMP002',
                'password' => Hash::make('password123'),
                'is_active' => true,
                'is_super_admin' => false,
            ],
            [
                'name' => 'Pierre Durand',
                'email' => 'pierre.durand@nere-mining.com',
                'matricule' => 'EMP003',
                'password' => Hash::make('password123'),
                'is_active' => true,
                'is_super_admin' => false,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate([
                'email' => $userData['email']
            ], $userData);
            
            $this->command->info("Utilisateur {$userData['name']} créé/mis à jour.");
        }

        // Assigner quelques utilisateurs aux départements
        $itDept = Department::where('code', 'IT')->first();
        if ($itDept && $adminUser) {
            // Utiliser syncWithoutDetaching avec des données de pivot
            // Director n'a pas de tech_level selon les contraintes DB
            $itDept->users()->syncWithoutDetaching([
                $adminUser->id => [
                    'role' => 'director',
                    'tech_level' => null
                ]
            ]);
            // Assigner admin comme manager du département IT
            $itDept->update(['manager_id' => $adminUser->id]);
        }

        $this->command->info('Utilisateurs créés avec succès !');
        $this->command->info('Connexion test: admin@nere-mining.com / admin123');
    }
}