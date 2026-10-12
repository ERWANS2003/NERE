<?php

namespace Database\Seeders;

use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class CalendarEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@neremining.bf')->first() 
                 ?? User::first();
        
        $departments = Department::all();
        
        // Événements généraux
        CalendarEvent::create([
            'title' => 'Réunion Direction Générale',
            'description' => 'Réunion mensuelle de la direction pour faire le point sur les opérations.',
            'type' => 'meeting',
            'event_date' => now()->addDays(5)->toDateString(),
            'event_time' => '09:00:00',
            'all_day' => false,
            'department_id' => null,
            'user_id' => null,
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'title' => 'Formation HSE - Session 1',
            'description' => 'Formation obligatoire sur la santé, sécurité et environnement.',
            'type' => 'training',
            'event_date' => now()->addDays(15)->toDateString(),
            'event_time' => '08:00:00',
            'all_day' => false,
            'department_id' => $departments->where('code', 'HSE')->first()?->id,
            'user_id' => null,
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'title' => 'Formation HSE - Session 2',
            'description' => 'Formation obligatoire sur la santé, sécurité et environnement.',
            'type' => 'training',
            'event_date' => now()->addDays(16)->toDateString(),
            'event_time' => '08:00:00',
            'all_day' => false,
            'department_id' => $departments->where('code', 'HSE')->first()?->id,
            'user_id' => null,
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'title' => 'Maintenance IT planifiée',
            'description' => 'Maintenance des serveurs et infrastructures IT.',
            'type' => 'event',
            'event_date' => now()->next('Saturday')->toDateString(),
            'event_time' => '08:00:00',
            'all_day' => false,
            'department_id' => $departments->where('code', 'IT')->first()?->id,
            'user_id' => null,
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'title' => 'Fête Nationale du Burkina Faso',
            'description' => 'Jour férié - Bureaux fermés',
            'type' => 'event',
            'event_date' => '2026-12-11',
            'event_time' => null,
            'all_day' => true,
            'department_id' => null,
            'user_id' => null,
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'title' => 'Audit de sécurité trimestriel',
            'description' => 'Audit de sécurité et conformité par l\'équipe HSE.',
            'type' => 'event',
            'event_date' => now()->addDays(8)->toDateString(),
            'event_time' => '09:00:00',
            'all_day' => false,
            'department_id' => $departments->where('code', 'HSE')->first()?->id,
            'user_id' => null,
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'title' => 'Réunion projet d\'extension',
            'description' => 'Point d\'avancement sur le projet d\'extension de la mine.',
            'type' => 'meeting',
            'event_date' => now()->addDays(20)->toDateString(),
            'event_time' => '14:00:00',
            'all_day' => false,
            'department_id' => $departments->where('code', 'MINING')->first()?->id,
            'user_id' => null,
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'title' => 'Session de recrutement',
            'description' => 'Entretiens pour les postes d\'opérateurs et techniciens.',
            'type' => 'meeting',
            'event_date' => now()->addDays(12)->toDateString(),
            'event_time' => '08:00:00',
            'all_day' => false,
            'department_id' => $departments->where('code', 'RH')->first()?->id,
            'user_id' => null,
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'title' => 'Visite communautaire - Boussouma',
            'description' => 'Visite de suivi des projets communautaires dans le village de Boussouma.',
            'type' => 'event',
            'event_date' => now()->addDays(18)->toDateString(),
            'event_time' => '09:00:00',
            'all_day' => false,
            'department_id' => $departments->where('code', 'RELCOM')->first()?->id,
            'user_id' => null,
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'title' => 'Revue de performance Processing',
            'description' => 'Analyse des performances de l\'usine de traitement du mois.',
            'type' => 'meeting',
            'event_date' => now()->addDays(10)->toDateString(),
            'event_time' => '10:00:00',
            'all_day' => false,
            'department_id' => $departments->where('code', 'PROCESSING')->first()?->id,
            'user_id' => null,
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'title' => 'Inventaire trimestriel',
            'description' => 'Inventaire physique des stocks et équipements.',
            'type' => 'event',
            'event_date' => now()->addDays(25)->toDateString(),
            'event_time' => '08:00:00',
            'all_day' => false,
            'department_id' => $departments->where('code', 'SCM')->first()?->id,
            'user_id' => null,
            'created_by' => $admin->id,
        ]);
    }
}
