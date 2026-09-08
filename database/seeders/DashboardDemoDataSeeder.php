<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardDemoDataSeeder extends Seeder
{
    /**
     * Seed demo dashboard layouts pour les utilisateurs
     */
    public function run(): void
    {
        $this->command->info('Seeding demo dashboard layouts...');

        // Layout par défaut pour l'admin
        $admin = User::where('email', 'admin@nere-mining.bf')->first();
        
        if ($admin) {
            DB::table('dashboard_layouts')->updateOrInsert(
                ['user_id' => $admin->id],
                [
                    'layout' => json_encode([
                        'grid' => [
                            [
                                'widget' => 'ticket_overview',
                                'position' => ['x' => 0, 'y' => 0, 'w' => 8, 'h' => 4]
                            ],
                            [
                                'widget' => 'safety_alerts',
                                'position' => ['x' => 8, 'y' => 0, 'w' => 4, 'h' => 4]
                            ],
                            [
                                'widget' => 'my_tickets',
                                'position' => ['x' => 0, 'y' => 4, 'w' => 6, 'h' => 3]
                            ],
                            [
                                'widget' => 'recent_activity',
                                'position' => ['x' => 6, 'y' => 4, 'w' => 6, 'h' => 3]
                            ],
                            [
                                'widget' => 'team_performance',
                                'position' => ['x' => 0, 'y' => 7, 'w' => 6, 'h' => 3]
                            ],
                            [
                                'widget' => 'sla_compliance',
                                'position' => ['x' => 6, 'y' => 7, 'w' => 6, 'h' => 3]
                            ],
                        ]
                    ]),
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $this->command->info('✅ Admin dashboard layout created');
        }

        // Créer quelques exemples d'automations
        $this->seedAutomations();
        
        // Créer des exemples de service catalog
        $this->seedServiceCatalog();
    }

    protected function seedAutomations(): void
    {
        $admin = User::where('email', 'admin@nere-mining.bf')->first();
        if (!$admin) return;

        $automations = [
            [
                'name' => 'Auto-escalade tickets critiques',
                'description' => 'Escalade automatiquement les tickets critiques au manager',
                'trigger_event' => 'ticket.created',
                'conditions' => json_encode([
                    ['field' => 'priority', 'operator' => '=', 'value' => 'critical'],
                ]),
                'actions' => json_encode([
                    ['type' => 'send_notification', 'recipient' => 'manager'],
                    ['type' => 'escalate', 'level' => 2],
                ]),
                'is_active' => true,
                'created_by' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Notification SLA à risque',
                'description' => 'Notifie quand un ticket approche de la breach SLA',
                'trigger_event' => 'sla.warning',
                'conditions' => json_encode([]),
                'actions' => json_encode([
                    ['type' => 'send_notification', 'recipient' => 'assignee'],
                    ['type' => 'send_notification', 'recipient' => 'manager'],
                ]),
                'is_active' => true,
                'created_by' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($automations as $automation) {
            DB::table('workflow_automations')->insert($automation);
        }

        $this->command->info('✅ Demo automations created');
    }

    protected function seedServiceCatalog(): void
    {
        $services = [
            [
                'name' => 'Nouvel Équipement Minier',
                'slug' => 'nouvel-equipement-minier',
                'description' => 'Demande de mise à disposition d\'un nouvel équipement',
                'icon' => 'assets',
                'request_form' => json_encode([
                    ['type' => 'text', 'name' => 'equipment_type', 'label' => 'Type d\'équipement', 'required' => true],
                    ['type' => 'textarea', 'name' => 'justification', 'label' => 'Justification', 'required' => true],
                    ['type' => 'date', 'name' => 'needed_date', 'label' => 'Date requise', 'required' => true],
                ]),
                'requires_approval' => true,
                'estimated_time' => 480, // 8 heures
                'is_active' => true,
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Accès Zone Sécurisée',
                'slug' => 'acces-zone-securisee',
                'description' => 'Demande d\'autorisation d\'accès à une zone restreinte',
                'icon' => 'safety',
                'request_form' => json_encode([
                    ['type' => 'select', 'name' => 'zone', 'label' => 'Zone', 'options' => ['Zone A', 'Zone B', 'Zone C'], 'required' => true],
                    ['type' => 'textarea', 'name' => 'reason', 'label' => 'Raison', 'required' => true],
                    ['type' => 'date', 'name' => 'access_date', 'label' => 'Date d\'accès', 'required' => true],
                ]),
                'requires_approval' => true,
                'estimated_time' => 120, // 2 heures
                'is_active' => true,
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Formation Sécurité',
                'slug' => 'formation-securite',
                'description' => 'Inscription à une session de formation sécurité',
                'icon' => 'training',
                'request_form' => json_encode([
                    ['type' => 'select', 'name' => 'training_type', 'label' => 'Type de formation', 'options' => ['Premiers secours', 'Manipulation explosifs', 'Conduite engins'], 'required' => true],
                    ['type' => 'date', 'name' => 'preferred_date', 'label' => 'Date souhaitée', 'required' => false],
                ]),
                'requires_approval' => false,
                'estimated_time' => 1440, // 24 heures
                'is_active' => true,
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Maintenance Préventive',
                'slug' => 'maintenance-preventive',
                'description' => 'Planification d\'une maintenance préventive',
                'icon' => 'maintenance',
                'request_form' => json_encode([
                    ['type' => 'text', 'name' => 'asset_id', 'label' => 'ID Équipement', 'required' => true],
                    ['type' => 'select', 'name' => 'maintenance_type', 'label' => 'Type', 'options' => ['Mensuelle', 'Trimestrielle', 'Annuelle'], 'required' => true],
                    ['type' => 'textarea', 'name' => 'notes', 'label' => 'Notes', 'required' => false],
                ]),
                'requires_approval' => false,
                'estimated_time' => 240, // 4 heures
                'is_active' => true,
                'order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($services as $service) {
            DB::table('service_catalog')->insert($service);
        }

        $this->command->info('✅ Service catalog created');
    }
}
