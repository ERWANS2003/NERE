<?php

namespace Database\Seeders;

use App\Models\DashboardNotification;
use App\Models\User;
use Illuminate\Database\Seeder;

class DashboardNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::limit(3)->get();

        foreach ($users as $user) {
            // Ticket Created
            DashboardNotification::create([
                'user_id' => $user->id,
                'type' => 'ticket_created',
                'title' => 'Nouveau Ticket Créé',
                'message' => 'Ticket #TKT-001: Problème d\'accès à la base de données',
                'icon' => '🎫',
                'color' => 'blue',
                'action_url' => '#',
                'data' => ['ticket_id' => 1],
            ]);

            // SLA Warning
            DashboardNotification::create([
                'user_id' => $user->id,
                'type' => 'sla_warning',
                'title' => '⚠️ Alerte SLA',
                'message' => 'Ticket #TKT-002 approaching SLA limit',
                'icon' => '⏰',
                'color' => 'yellow',
                'action_url' => '#',
                'data' => ['ticket_id' => 2],
            ]);

            // Comment Added
            DashboardNotification::create([
                'user_id' => $user->id,
                'type' => 'comment_added',
                'title' => 'Nouveau Commentaire',
                'message' => 'Admin a commenté: Ticket escaladé au support technique',
                'icon' => '💬',
                'color' => 'info',
                'action_url' => '#',
                'data' => ['ticket_id' => 3],
                'read_at' => now()->subHours(2),
            ]);
        }
    }
}
