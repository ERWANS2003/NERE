<?php

namespace Database\Seeders;

use App\Models\TimeOffBalance;
use App\Models\TimeOffPolicy;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Politiques de congés et soldes initiaux.
 *
 * Idempotent : les politiques sont identifiées par leur type, les soldes par
 * (utilisateur, politique, année). Les jours consommés sont des valeurs de
 * démonstration pour que le tableau de bord ait du contenu.
 */
class TimeOffSeeder extends Seeder
{
    public function run(): void
    {
        $annee = (int) now()->year;

        $politiques = [
            [
                'name' => 'Congés annuels',
                'type' => 'vacation',
                'description' => 'Congés payés annuels. Au Burkina Faso, le droit légal est de 2,5 jours ouvrables par mois travaillé.',
                'annual_allocation' => 30,
                'carry_over_limit' => 10,
                'requires_approval' => true,
                'advance_notice_days' => 7,
            ],
            [
                'name' => 'Congés maladie',
                'type' => 'sick',
                'description' => 'Absences pour raison de santé, sur présentation d\'un justificatif médical.',
                'annual_allocation' => 15,
                'carry_over_limit' => 0,
                'requires_approval' => true,
                'advance_notice_days' => 0,
            ],
            [
                'name' => 'Congés personnels',
                'type' => 'personal',
                'description' => 'Absences exceptionnelles pour événements familiaux ou personnels.',
                'annual_allocation' => 5,
                'carry_over_limit' => 0,
                'requires_approval' => true,
                'advance_notice_days' => 2,
            ],
        ];

        foreach ($politiques as $p) {
            $policy = TimeOffPolicy::updateOrCreate(
                ['type' => $p['type']],
                [...$p, 'is_active' => true],
            );

            // Solde pour chaque employé actif.
            User::where('actif', true)->get()->each(function (User $user, int $index) use ($policy, $annee) {
                // Jours consommés de démonstration, déterministes, bornés à l'allocation.
                $utilises = match ($policy->type) {
                    'vacation' => min($policy->annual_allocation, ($index * 3) % 20),
                    'sick' => min($policy->annual_allocation, $index % 5),
                    default => min($policy->annual_allocation, $index % 3),
                };

                TimeOffBalance::updateOrCreate(
                    ['user_id' => $user->id, 'policy_id' => $policy->id, 'year' => $annee],
                    [
                        'total_days' => $policy->annual_allocation,
                        'used_days' => $utilises,
                        'remaining_days' => $policy->annual_allocation - $utilises,
                        'carry_over_days' => 0,
                    ],
                );
            });
        }
    }
}
