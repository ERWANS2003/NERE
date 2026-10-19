<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DepartmentForm;
use App\Support\DepartmentRequestCatalog;
use Illuminate\Database\Seeder;

/**
 * Formulaires de démonstration pour peupler l'onglet « Formulaires » des pages
 * département. Idempotent (identifiés par département + nom). Le module de
 * remplissage dynamique n'étant pas encore livré, ils servent de vitrine.
 */
class DepartmentFormSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = DepartmentRequestCatalog::forms();
        $parCode = [
            'IT' => [
                "Demande d'accès à une application",
                'Signalement de panne matérielle',
            ],
            'RH' => array_keys($catalog['RH']),
            'HSE' => [
                "Déclaration d'incident",
                'Observation de sécurité',
                'Demande de permis de travail',
            ],
            'SCM' => [
                "Demande d'achat",
                'Référencement fournisseur',
            ],
            'ADMIN' => array_keys($catalog['ADMIN']),
            'MINING' => [
                "Demande d'intervention engin",
            ],
            'PROC' => [
                'Signalement de dysfonctionnement usine',
            ],
            'SEC' => [
                'Demande de badge',
                'Déclaration de vol ou perte',
            ],
            'RELCOM' => [
                'Demande de don ou parrainage',
                'Plainte communautaire',
            ],
        ];

        foreach ($parCode as $code => $noms) {
            $departement = Department::where('code', $code)->first();

            if (! $departement) {
                continue;
            }

            foreach ($noms as $index => $nom) {
                DepartmentForm::updateOrCreate(
                    ['department_id' => $departement->id, 'name' => $nom],
                    [
                        'description' => $catalog[$code][$nom]['description']
                            ?? 'Formulaire '.mb_strtolower($nom).' du département '.$departement->name.'.',
                        'form_schema' => isset($catalog[$code][$nom])
                            ? ['fields' => $catalog[$code][$nom]['fields']]
                            : [],
                        'is_active' => true,
                        'order' => $index,
                    ]
                );
            }
        }
    }
}
