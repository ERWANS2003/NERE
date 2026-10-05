<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

/**
 * Référentiel des services du portail.
 *
 * Donnée et non schéma : ces lignes sont modifiables par un administrateur
 * (libellé, ordre, accent, activation). Le seeder garantit l'état initial sur
 * une base neuve, il n'impose rien ensuite.
 *
 * L'accent n'est posé qu'à la création. Relancer le seeder ne doit pas effacer
 * une teinte choisie par l'administration, ni réactiver un service qu'elle a
 * volontairement coupé : sur une base déjà peuplée, seuls les libellés, la
 * description et l'ordre sont remis à jour.
 */
class DepartmentsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->services() as $rang => $service) {
            $departement = Department::firstOrNew(['code' => $service['code']]);

            if (! $departement->exists) {
                $departement->color = $service['color'];
            }

            $departement->name = $service['name'];
            $departement->tag = $service['tag'];
            $departement->description = $service['description'];
            $departement->icon = $service['icon'];
            $departement->position = ($rang + 1) * 10;

            $departement->save();
        }
    }

    /**
     * @return list<array<string, string>>
     */
    private function services(): array
    {
        return [
            [
                'code' => 'IT',
                'name' => 'Informatique',
                'tag' => 'Informatique',
                'description' => 'Ordinateurs, réseau, logiciels, accès aux applications et téléphonie.',
                'icon' => 'informatique',
                // Teintes volontairement ternes et de luminance voisine : l'accent ne
                // colore que la pastille d'icône, jamais la carte, et l'icône
                // reste redondante avec le nom du service.
                'color' => '#2f6f9f',
            ],
            [
                'code' => 'RH',
                'name' => 'Ressources humaines',
                'tag' => 'Ressources humaines',
                'description' => 'Congés, attestations, contrats, paie, formation et recrutement.',
                'icon' => 'personnes',
                'color' => '#6d5aa8',
            ],
            [
                'code' => 'HSE',
                'name' => 'HSE',
                'tag' => 'Sécurité',
                'description' => "Déclaration d'accident et de presqu'accident, inspections, EPI et environnement.",
                'icon' => 'securite',
                'color' => '#a33b3b',
            ],
            [
                'code' => 'MNT',
                'name' => 'Maintenance',
                'tag' => 'Maintenance',
                'description' => "Panne d'équipement, demande d'intervention et maintenance préventive.",
                'icon' => 'maintenance',
                'color' => '#4f6b52',
            ],
            [
                'code' => 'FIN',
                'name' => 'Finance',
                'tag' => 'Finance',
                'description' => 'Achats, engagement de dépenses, notes de frais et facturation.',
                'icon' => 'finance',
                'color' => '#1f7a5c',
            ],
            [
                'code' => 'LOG',
                'name' => 'Logistique',
                'tag' => 'Logistique',
                'description' => 'Véhicules, transport, carburant et expéditions.',
                'icon' => 'logistique',
                'color' => '#166a7a',
            ],
        ];
    }
}
