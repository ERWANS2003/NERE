<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

/**
 * Référentiel des départements du portail NÉRÉ Mining.
 *
 * Les 9 départements correspondent à la maquette validée. Donnée et non
 * schéma : ces lignes sont modifiables par un administrateur (libellé, ordre,
 * accent, activation). Le seeder garantit l'état initial sur une base neuve.
 *
 * L'accent n'est posé qu'à la création : relancer le seeder ne doit pas
 * effacer une teinte choisie par l'administration. Sur une base déjà peuplée,
 * seuls le nom, le tag, la description, l'icône et la position sont remis à jour.
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
            $departement->position = $rang + 1;
            $departement->is_active = true;

            $departement->save();
        }
    }

    /**
     * Les 9 départements de la maquette, dans l'ordre d'affichage.
     *
     * @return list<array<string, string>>
     */
    private function services(): array
    {
        return [
            [
                'code' => 'IT',
                'name' => 'Département IT',
                'tag' => 'Technologies',
                'description' => 'Ordinateurs, réseau, logiciels, comptes et support technique.',
                'icon' => 'informatique',
                'color' => '#2f6f9f',
            ],
            [
                'code' => 'ADMIN',
                'name' => 'Administration',
                'tag' => 'Administration',
                'description' => 'Direction générale, réservations de salles et de véhicules, courrier et services généraux.',
                'icon' => 'batiment',
                'color' => '#c83530',
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
                'code' => 'SEC',
                'name' => 'Département Sécurité',
                'tag' => 'Sûreté',
                'description' => 'Contrôle des accès, gardiennage, badges et sûreté des sites.',
                'icon' => 'bouclier',
                'color' => '#4a3f35',
            ],
            [
                'code' => 'MINING',
                'name' => 'Département Mining',
                'tag' => 'Opérations',
                'description' => "Exploitation minière, production, planning des équipes et rapports d'extraction.",
                'icon' => 'pioche',
                'color' => '#8b5a2b',
            ],
            [
                'code' => 'HSE',
                'name' => 'Hygiène, Santé, Sécurité et Environnement',
                'tag' => 'HSE',
                'description' => "Déclaration d'incident, observations sécurité, EPI, inspections et environnement.",
                'icon' => 'securite',
                'color' => '#5a8f4e',
            ],
            [
                'code' => 'PROC',
                'name' => 'Département Processing',
                'tag' => 'Traitement',
                'description' => "Usine de traitement, suivi de production et analyses de laboratoire.",
                'icon' => 'usine',
                'color' => '#3a6e8f',
            ],
            [
                'code' => 'SCM',
                'name' => "Chaîne d'approvisionnement (SCM)",
                'tag' => 'Approvisionnement',
                'description' => "Demandes d'achat, gestion des stocks, fournisseurs et magasin.",
                'icon' => 'logistique',
                'color' => '#166a7a',
            ],
            [
                'code' => 'RELCOM',
                'name' => 'Relations communautaires',
                'tag' => 'Dialogue local',
                'description' => 'Projets communautaires, dialogue avec les villages et gestion des plaintes.',
                'icon' => 'poignee-de-main',
                'color' => '#c98f10',
            ],
        ];
    }
}
