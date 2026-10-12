<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'nom' => 'Administrateur Système',
                'slug' => 'admin-systeme',
                'description' => 'Accès complet à tous les modules et paramètres du système',
            ],
            [
                'nom' => 'Manager',
                'slug' => 'manager',
                'description' => 'Gestion d\'équipe et validation de tickets dans son département',
            ],
            [
                'nom' => 'Technicien',
                'slug' => 'technicien',
                'description' => 'Traitement et résolution de tickets',
            ],
            [
                'nom' => 'Utilisateur',
                'slug' => 'utilisateur',
                'description' => 'Création et suivi de tickets',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }
    }
}
