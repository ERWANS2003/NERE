<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Employés de démonstration pour peupler l'annuaire.
 *
 * Idempotent : chaque employé est identifié par son email. Relancer le seeder
 * ne crée pas de doublon et réaffecte proprement le département.
 */
class DemoEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::all()->keyBy('code');

        foreach ($this->employes() as $e) {
            $user = User::updateOrCreate(
                ['email' => $e['email']],
                [
                    'name' => $e['name'],
                    'password' => Hash::make('password'),
                    'telephone' => $e['telephone'],
                    'poste' => $e['poste'],
                    'position_title' => $e['poste'],
                    'actif' => true,
                    'matricule' => $e['matricule'],
                    'birth_date' => $e['birth_date'],
                    'hire_date' => $e['hire_date'],
                ]
            );

            // Affectation au département (rôle utilisateur par défaut).
            $dept = $departments->get($e['dept']);
            if ($dept) {
                $user->departments()->syncWithoutDetaching([
                    $dept->id => ['role' => 'user', 'tech_level' => null],
                ]);
            }
        }

        $this->seedHierarchy($departments);
    }

    /**
     * Hiérarchie de démonstration pour l'organigramme.
     *
     * Convention : org_level 0 = Direction générale (sommet), 1 = chef de
     * département, 2 = collaborateur. Le premier employé listé de chaque
     * département en devient le chef. À remplacer par la vraie structure.
     */
    private function seedHierarchy($departments): void
    {
        $dg = User::updateOrCreate(
            ['email' => 'direction.generale@neremining.bf'],
            [
                'name' => 'Jean-Baptiste Zerbo',
                'password' => Hash::make('password'),
                'telephone' => '+226 70 00 00 01',
                'poste' => 'Directeur Général',
                'position_title' => 'Directeur Général',
                'matricule' => 'NM0000',
                'actif' => true,
                'org_level' => 0,
                'manager_id' => null,
            ]
        );

        $parDept = collect($this->employes())->groupBy('dept');

        foreach ($parDept as $code => $membres) {
            $dept = $departments->get($code);
            if (! $dept) {
                continue;
            }

            $chef = User::where('email', $membres->first()['email'])->first();
            $chef?->update(['org_level' => 1, 'manager_id' => $dg->id]);
            $dept->update(['manager_id' => $chef?->id]);

            // Le chef devient directeur du département sur le pivot : cohérent
            // avec l'organigramme, l'affichage de l'équipe (badge « Directeur »)
            // et les permissions (il peut publier des annonces pour son dept).
            if ($chef) {
                $dept->users()->syncWithoutDetaching([
                    $chef->id => ['role' => 'director', 'tech_level' => null],
                ]);
            }

            foreach ($membres->skip(1) as $membre) {
                User::where('email', $membre['email'])
                    ->update(['org_level' => 2, 'manager_id' => $chef?->id]);
            }
        }
    }

    /**
     * @return list<array<string, string>>
     */
    private function employes(): array
    {
        $data = [
            // ADMIN
            ['Awa Ouédraogo', 'ADMIN', 'Directrice administrative'],
            ['Boureima Sawadogo', 'ADMIN', 'Assistant de direction'],
            // RH
            ['Fatimata Compaoré', 'RH', 'Responsable RH'],
            ['Issouf Kaboré', 'RH', 'Chargé de recrutement'],
            ['Mariam Zongo', 'RH', 'Gestionnaire paie'],
            // SEC (Sûreté)
            ['Salif Traoré', 'SEC', 'Chef de la sûreté'],
            ['Rasmané Nikiéma', 'SEC', 'Agent de sécurité'],
            // MINING
            ['Adama Ouattara', 'MINING', "Chef d'exploitation"],
            ['Seydou Bance', 'MINING', 'Superviseur de production'],
            ['Yacouba Sanou', 'MINING', 'Opérateur engins'],
            // HSE
            ['Clarisse Ilboudo', 'HSE', 'Responsable HSE'],
            ['Daouda Konaté', 'HSE', 'Inspecteur sécurité'],
            // PROC (Processing)
            ['Nadège Tapsoba', 'PROC', "Responsable d'usine"],
            ['Abdoulaye Diallo', 'PROC', 'Technicien laboratoire'],
            // SCM
            ['Hamidou Barry', 'SCM', 'Responsable achats'],
            ['Aminata Sinaré', 'SCM', 'Gestionnaire de stock'],
            // IT
            ['Ousmane Belem', 'IT', 'Responsable informatique'],
            ['Prosper Yaméogo', 'IT', 'Technicien support'],
            // RELCOM
            ['Céline Kaboré', 'RELCOM', 'Responsable relations communautaires'],
            ['Moussa Ganamé', 'RELCOM', 'Agent de liaison'],
        ];

        $employes = [];
        foreach ($data as $i => [$name, $dept, $poste]) {
            $slug = Str::slug($name, '.');
            $employes[] = [
                'name' => $name,
                'email' => $slug . '@neremining.bf',
                'telephone' => '+226 70 ' . str_pad((string) (10 + $i), 2, '0', STR_PAD_LEFT) . ' ' . str_pad((string) ($i * 7 % 100), 2, '0', STR_PAD_LEFT) . ' ' . str_pad((string) ($i * 13 % 100), 2, '0', STR_PAD_LEFT),
                'poste' => $poste,
                'dept' => $dept,
                'matricule' => 'NM' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                // Dates de démonstration réparties sur l'année / les 200 derniers jours.
                'birth_date' => sprintf('%d-%02d-%02d', 1975 + ($i % 20), (($i * 5) % 12) + 1, (($i * 7) % 27) + 1),
                'hire_date' => now()->subDays((($i * 11) % 200) + 1)->toDateString(),
            ];
        }

        return $employes;
    }
}
