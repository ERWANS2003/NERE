<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Le contenu est du texte brut (Announcement::body n'est jamais rendu
     * comme HTML, voir resources/views/intranet/announcements/*.blade.php) :
     * les retours à la ligne suffisent, pas de balises.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@neremining.bf')->first()
                 ?? User::first();

        $departments = Department::all();

        Announcement::create([
            'title' => 'Bienvenue sur le nouvel intranet NÉRÉ Mining',
            'body' => "Chers collaborateurs,\n\n"
                . "Nous sommes heureux de vous présenter notre nouvel intranet, conçu pour améliorer notre communication interne et faciliter votre travail quotidien.\n\n"
                . "Vous y trouverez toutes les informations essentielles sur nos opérations, les annonces importantes, l'annuaire des employés, et bien plus encore.\n\n"
                . "N'hésitez pas à explorer toutes les fonctionnalités !\n\n"
                . "Direction Générale",
            'priority' => 'important',
            'department_id' => null,
            'published_at' => now()->subDays(2),
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        Announcement::create([
            'title' => 'Rappel : Formation HSE obligatoire en janvier',
            'body' => "Tous les employés doivent suivre la formation HSE annuelle obligatoire.\n\n"
                . "Dates : 15-20 janvier 2026\n"
                . "Lieu : Salle de formation, bâtiment administratif\n\n"
                . "Inscription auprès du département HSE avant le 10 janvier.",
            'priority' => 'urgent',
            'department_id' => $departments->where('code', 'HSE')->first()?->id,
            'published_at' => now()->subDays(5),
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        Announcement::create([
            'title' => 'Maintenance planifiée du système IT',
            'body' => "Une maintenance planifiée du système informatique aura lieu ce samedi de 8h à 12h.\n\n"
                . "Services affectés :\n"
                . "- Email\n"
                . "- Serveurs de fichiers\n"
                . "- Applications métier\n\n"
                . "Merci de sauvegarder vos travaux avant vendredi soir.",
            'priority' => 'important',
            'department_id' => $departments->where('code', 'IT')->first()?->id,
            'published_at' => now()->subDays(3),
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        Announcement::create([
            'title' => 'Nouvelle procédure de gestion des équipements',
            'body' => "Le département SCM a mis en place une nouvelle procédure pour la gestion des équipements.\n\n"
                . "Changements principaux :\n"
                . "1. Demandes d'équipement via le portail intranet\n"
                . "2. Validation automatique pour commandes < 500 000 FCFA\n"
                . "3. Nouveau formulaire de retour d'équipement\n\n"
                . "Consultez le guide complet dans la section Ressources.",
            'priority' => 'info',
            'department_id' => $departments->where('code', 'SCM')->first()?->id,
            'published_at' => now()->subDays(7),
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        Announcement::create([
            'title' => 'Résultats de production - Décembre 2025',
            'body' => "Excellents résultats pour le mois de décembre :\n\n"
                . "- Or produit : 2 450 kg (+12% vs novembre)\n"
                . "- Taux de récupération : 94.2%\n"
                . "- Incidents sécurité : 0 (record !)\n\n"
                . "Félicitations à toutes les équipes pour ces excellents résultats !",
            'priority' => 'info',
            'department_id' => $departments->where('code', 'MINING')->first()?->id,
            'published_at' => now()->subDays(10),
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        Announcement::create([
            'title' => 'Programme de formation continue 2026',
            'body' => "Le département RH lance le programme de formation continue 2026.\n\n"
                . "Formations disponibles :\n"
                . "- Gestion de projet (Mining & Processing)\n"
                . "- Leadership et management\n"
                . "- Sécurité avancée\n"
                . "- Outils informatiques\n"
                . "- Langues (Anglais technique)\n\n"
                . "Inscriptions ouvertes jusqu'au 31 janvier sur le portail RH.",
            'priority' => 'important',
            'department_id' => $departments->where('code', 'RH')->first()?->id,
            'published_at' => now()->subDays(4),
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        Announcement::create([
            'title' => "Projet d'extension de la mine - Phase 2",
            'body' => "Le projet d'extension de la mine entre dans sa phase 2.\n\n"
                . "Objectifs 2026 :\n"
                . "- Extension de la fosse principale\n"
                . "- Installation de nouvelles infrastructures\n"
                . "- Recrutement de 50 nouveaux employés\n\n"
                . "Une réunion d'information aura lieu le 20 janvier pour tous les départements concernés.",
            'priority' => 'urgent',
            'department_id' => null,
            'published_at' => now()->subDay(),
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        Announcement::create([
            'title' => 'Actions communautaires - Écoles locales',
            'body' => "Dans le cadre de notre engagement envers les communautés locales, NÉRÉ Mining finance la rénovation de 3 écoles dans les villages environnants.\n\n"
                . "Villages concernés :\n"
                . "- Boussouma - École primaire\n"
                . "- Zorgho - Collège\n"
                . "- Kaya - Lycée technique\n\n"
                . "Les travaux commenceront en février 2026.",
            'priority' => 'info',
            'department_id' => $departments->where('code', 'RELCOM')->first()?->id,
            'published_at' => now()->subDays(6),
            'created_by' => $admin->id,
            'is_active' => true,
        ]);
    }
}
