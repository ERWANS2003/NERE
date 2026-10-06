<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // Créer quelques départements de test
        $departments = [
            [
                'name' => 'Technologies de l\'Information',
                'code' => 'IT',
                'tag' => 'TECHNIQUE',
                'description' => 'Département en charge de l\'infrastructure informatique, du développement et de la maintenance des systèmes.',
                'icon' => 'computer',
                'color' => '#3B82F6',
                'position' => 1,
                'email' => 'it@nere-mining.com',
                'phone' => '+33 1 23 45 67 89',
                'location' => 'Bâtiment A, 2ème étage',
                'budget' => 250000,
                'is_active' => true,
                'allow_ticket_creation' => true,
            ],
            [
                'name' => 'Ressources Humaines',
                'code' => 'RH',
                'tag' => 'ADMINISTRATIF',
                'description' => 'Gestion du personnel, recrutement, formation et administration des ressources humaines.',
                'icon' => 'users',
                'color' => '#10B981',
                'position' => 2,
                'email' => 'rh@nere-mining.com',
                'phone' => '+33 1 23 45 67 90',
                'location' => 'Bâtiment B, 1er étage',
                'budget' => 180000,
                'is_active' => true,
                'allow_ticket_creation' => true,
            ],
            [
                'name' => 'Finance et Comptabilité',
                'code' => 'FIN',
                'tag' => 'ADMINISTRATIF',
                'description' => 'Gestion financière, comptabilité, budgets et contrôle de gestion.',
                'icon' => 'calculator',
                'color' => '#8B5CF6',
                'position' => 3,
                'email' => 'finance@nere-mining.com',
                'phone' => '+33 1 23 45 67 91',
                'location' => 'Bâtiment A, 3ème étage',
                'budget' => 150000,
                'is_active' => true,
                'allow_ticket_creation' => true,
            ],
            [
                'name' => 'Exploitation Minière',
                'code' => 'MINE',
                'tag' => 'OPÉRATIONNEL',
                'description' => 'Operations d\'extraction, sécurité minière et gestion des sites d\'exploitation.',
                'icon' => 'truck',
                'color' => '#F59E0B',
                'position' => 4,
                'email' => 'exploitation@nere-mining.com',
                'phone' => '+33 1 23 45 67 92',
                'location' => 'Site minier principal',
                'budget' => 500000,
                'is_active' => true,
                'allow_ticket_creation' => true,
            ],
            [
                'name' => 'Santé, Sécurité et Environnement',
                'code' => 'HSE',
                'tag' => 'SÉCURITÉ',
                'description' => 'Hygiène, sécurité au travail, protection de l\'environnement et conformité réglementaire.',
                'icon' => 'shield-check',
                'color' => '#EF4444',
                'position' => 5,
                'email' => 'hse@nere-mining.com',
                'phone' => '+33 1 23 45 67 93',
                'location' => 'Bâtiment C, Rez-de-chaussée',
                'budget' => 120000,
                'is_active' => true,
                'allow_ticket_creation' => true,
            ],
            [
                'name' => 'Marketing et Communication',
                'code' => 'MKT',
                'tag' => 'COMMERCIAL',
                'description' => 'Communication externe, relations publiques et marketing commercial.',
                'icon' => 'megaphone',
                'color' => '#EC4899',
                'position' => 6,
                'email' => 'marketing@nere-mining.com',
                'phone' => '+33 1 23 45 67 94',
                'location' => 'Bâtiment B, 2ème étage',
                'budget' => 80000,
                'is_active' => true,
                'allow_ticket_creation' => true,
            ],
            [
                'name' => 'Maintenance et Logistique',
                'code' => 'LOG',
                'tag' => 'TECHNIQUE',
                'description' => 'Maintenance des équipements, gestion des stocks et logistique.',
                'icon' => 'wrench',
                'color' => '#6B7280',
                'position' => 7,
                'email' => 'maintenance@nere-mining.com',
                'phone' => '+33 1 23 45 67 95',
                'location' => 'Hangar technique',
                'budget' => 300000,
                'is_active' => true,
                'allow_ticket_creation' => true,
            ]
        ];

        foreach ($departments as $deptData) {
            // Vérifier si le département existe déjà
            $existing = Department::where('code', $deptData['code'])->first();
            if (!$existing) {
                Department::create($deptData);
                $this->command->info("Département {$deptData['name']} créé.");
            } else {
                // Mettre à jour les nouveaux champs s'ils n'existent pas
                $existing->update(array_intersect_key($deptData, array_flip([
                    'email', 'phone', 'location', 'budget', 'allow_ticket_creation', 'tag', 'icon', 'color', 'position'
                ])));
                $this->command->info("Département {$deptData['name']} mis à jour.");
            }
        }

        // Créer une hiérarchie - Support IT sous IT
        $itDept = Department::where('code', 'IT')->first();
        if ($itDept) {
            Department::create([
                'name' => 'Support Informatique',
                'code' => 'SUPPORT',
                'tag' => 'TECHNIQUE',
                'description' => 'Support technique niveau 1 et 2 pour les utilisateurs.',
                'icon' => 'headphones',
                'color' => '#06B6D4',
                'position' => 8,
                'parent_id' => $itDept->id,
                'email' => 'support@nere-mining.com',
                'phone' => '+33 1 23 45 67 96',
                'location' => 'Bâtiment A, 2ème étage - Bureau 205',
                'budget' => 50000,
                'is_active' => true,
                'allow_ticket_creation' => true,
            ]);
        }

        $this->command->info('Départements créés avec succès !');
    }
}