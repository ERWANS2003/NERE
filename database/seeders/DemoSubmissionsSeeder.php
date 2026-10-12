<?php

namespace Database\Seeders;

use App\Models\DepartmentForm;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demandes de démonstration (soumissions de formulaires) pour alimenter le
 * tableau de bord analytique : volume par semaine, répartition par statut et
 * par département.
 *
 * Idempotent : on repart d'une base vide de soumissions de démo (identifiées
 * par le marqueur 'demo' dans data) avant de regénérer, pour éviter que les
 * relances n'empilent les volumes.
 */
class DemoSubmissionsSeeder extends Seeder
{
    public function run(): void
    {
        $forms = DepartmentForm::where('is_active', true)->get();
        $users = User::where('actif', true)->get();
        $reviewers = $users->where('is_super_admin', false); // approbateurs fictifs

        if ($forms->isEmpty() || $users->isEmpty()) {
            return;
        }

        // Purge des demandes de démo précédentes (relance propre).
        FormSubmission::whereJsonContains('data->demo', true)->delete();

        // Distribution déterministe sur ~8 semaines (56 jours).
        // Statuts : ~55% approuvées, ~20% rejetées, ~25% en attente.
        $total = 70;

        for ($i = 0; $i < $total; $i++) {
            $form = $forms[$i % $forms->count()];
            $user = $users[($i * 7) % $users->count()];

            // Réparti sur 56 jours : chaque demande recule d'environ 0,8 jour
            // de plus que la précédente, pour couvrir ~8 semaines.
            $joursDansLePasse = (int) round($i * 56 / $total);
            $createdAt = Carbon::now()->subDays($joursDansLePasse)->subHours($i % 24);

            // Statut pseudo-aléatoire mais reproductible.
            $r = $i % 20;
            if ($r < 11) {
                $status = 'approved';
            } elseif ($r < 15) {
                $status = 'rejected';
            } else {
                $status = 'pending';
            }

            $traite = $status !== 'pending';

            FormSubmission::create([
                'form_id' => $form->id,
                'user_id' => $user->id,
                'data' => ['demo' => true, 'exemple' => 'Demande de démonstration #' . ($i + 1)],
                'status' => $status,
                'reviewed_by' => $traite ? $reviewers->random()->id : null,
                // Traitée entre 1h et 3 jours après la soumission.
                'reviewed_at' => $traite ? (clone $createdAt)->addHours(1 + ($i % 72)) : null,
                'review_comment' => $status === 'rejected' ? 'Demande incomplète, à représenter.' : null,
                'created_at' => $createdAt,
                'updated_at' => $traite ? (clone $createdAt)->addHours(1 + ($i % 72)) : $createdAt,
            ]);
        }
    }
}
