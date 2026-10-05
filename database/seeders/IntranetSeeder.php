<?php

namespace Database\Seeders;

use App\Models\Intranet\Department;
use App\Models\Intranet\Form;
use App\Models\Intranet\FormField;
use App\Models\Intranet\Service;
use App\Models\Intranet\WorkflowStep;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class IntranetSeeder extends Seeder
{
    public function run(): void
    {
        /* ── 1. Utilisateurs de test ─────────────────────────────── */

        $base = ['actif' => true, 'est_technicien' => false, 'disponible' => true];

        $admin = User::firstOrCreate(
            ['email' => 'intranet-admin@nere-mining.bf'],
            array_merge($base, [
                'name'           => 'Admin Intranet',
                'matricule'      => 'ADM-001',
                'password'       => Hash::make('password'),
                'is_super_admin' => true,
            ])
        );

        $director = User::firstOrCreate(
            ['email' => 'directeur.it@nere-mining.bf'],
            array_merge($base, [
                'name'      => 'Directeur IT',
                'matricule' => 'IT-DIR-01',
                'password'  => Hash::make('password'),
            ])
        );

        $tech1 = User::firstOrCreate(
            ['email' => 'tech1.it@nere-mining.bf'],
            array_merge($base, [
                'name'           => 'Technicien N1',
                'matricule'      => 'IT-T01',
                'password'       => Hash::make('password'),
                'est_technicien' => true,
            ])
        );

        $tech2 = User::firstOrCreate(
            ['email' => 'tech2.it@nere-mining.bf'],
            array_merge($base, [
                'name'           => 'Technicien N2',
                'matricule'      => 'IT-T02',
                'password'       => Hash::make('password'),
                'est_technicien' => true,
            ])
        );

        $userDemo = User::firstOrCreate(
            ['email' => 'utilisateur@nere-mining.bf'],
            array_merge($base, [
                'name'      => 'Utilisateur Démo',
                'matricule' => 'USR-001',
                'password'  => Hash::make('password'),
            ])
        );

        $rhDirector = User::firstOrCreate(
            ['email' => 'directeur.rh@nere-mining.bf'],
            array_merge($base, [
                'name'      => 'Directeur RH',
                'matricule' => 'RH-DIR-01',
                'password'  => Hash::make('password'),
            ])
        );

        /* ── 2. Départements ─────────────────────────────────────── */

        $departments = [
            ['code' => 'ADMIN', 'tag' => 'ADMINISTRATION', 'name' => 'Administration de la mine',          'icon' => 'building-office',   'color' => '#6b7280', 'position' => 1],
            ['code' => 'RH',    'tag' => 'RESSOURCES HUMAINES', 'name' => 'Ressources humaines',            'icon' => 'users',             'color' => '#0ea5e9', 'position' => 2],
            ['code' => 'SURETE','tag' => 'SÛRETÉ',          'name' => 'Département Sécurité',               'icon' => 'shield-check',      'color' => '#dc2626', 'position' => 3],
            ['code' => 'OPS',   'tag' => 'OPÉRATIONS',      'name' => 'Département Mining',                 'icon' => 'bolt',              'color' => '#d97706', 'position' => 4],
            ['code' => 'HSE',   'tag' => 'HSE',             'name' => 'Hygiène, Santé, Sécurité et Environnement', 'icon' => 'heart', 'color' => '#16a34a', 'position' => 5],
            ['code' => 'TRAIT', 'tag' => 'TRAITEMENT',      'name' => 'Département Processing',             'icon' => 'cog-6-tooth',       'color' => '#9333ea', 'position' => 6],
            ['code' => 'SCM',   'tag' => 'APPROVISIONNEMENT','name' => 'Chaîne d\'approvisionnement (SCM)',  'icon' => 'truck',             'color' => '#ca8a04', 'position' => 7],
            ['code' => 'IT',    'tag' => 'TECHNOLOGIES',    'name' => 'Département IT',                     'icon' => 'computer-desktop',  'color' => '#2563eb', 'position' => 8],
            ['code' => 'DL',    'tag' => 'DIALOGUE LOCAL',  'name' => 'Relations communautaires',           'icon' => 'chat-bubble-left-right', 'color' => '#0d9488', 'position' => 9],
        ];

        $deptModels = [];
        foreach ($departments as $d) {
            $deptModels[$d['code']] = Department::updateOrCreate(
                ['code' => $d['code']],
                array_merge($d, ['is_active' => true])
            );
        }

        /* ── 3. Affecter les rôles ───────────────────────────────── */

        $itDept = $deptModels['IT'];
        $rhDept = $deptModels['RH'];

        // Admin général : toujours un rôle "director" dans tous les depts
        foreach ($deptModels as $dept) {
            $dept->users()->syncWithoutDetaching([
                $admin->id => ['role' => 'director', 'tech_level' => null],
            ]);
        }

        // Directeur IT
        $itDept->users()->syncWithoutDetaching([
            $director->id => ['role' => 'director', 'tech_level' => null],
        ]);

        // Techniciens IT
        $itDept->users()->syncWithoutDetaching([
            $tech1->id => ['role' => 'technician', 'tech_level' => 1],
            $tech2->id => ['role' => 'technician', 'tech_level' => 2],
        ]);

        // Directeur RH
        $rhDept->users()->syncWithoutDetaching([
            $rhDirector->id => ['role' => 'director', 'tech_level' => null],
        ]);

        // Utilisateur démo : accès "user" à tous les départements
        foreach ($deptModels as $dept) {
            $dept->users()->syncWithoutDetaching([
                $userDemo->id => ['role' => 'user', 'tech_level' => null],
            ]);
        }

        /* ── 4. Services ─────────────────────────────────────────── */

        $itServices = [
            ['name' => 'Support informatique', 'description' => 'Incidents et pannes matériel/logiciel'],
            ['name' => 'Accès et comptes',      'description' => 'Gestion des accès système et comptes'],
            ['name' => 'Matériel informatique', 'description' => 'Demandes de matériel IT'],
        ];

        foreach ($itServices as $pos => $s) {
            Service::firstOrCreate(
                ['department_id' => $itDept->id, 'name' => $s['name']],
                array_merge($s, ['department_id' => $itDept->id, 'is_active' => true, 'position' => $pos + 1])
            );
        }

        $rhServices = [
            ['name' => 'Congés et absences', 'description' => 'Demandes de congés, RTT, absences'],
            ['name' => 'Administration RH',  'description' => 'Attestations, certificats, acomptes'],
        ];

        foreach ($rhServices as $pos => $s) {
            Service::firstOrCreate(
                ['department_id' => $rhDept->id, 'name' => $s['name']],
                array_merge($s, ['department_id' => $rhDept->id, 'is_active' => true, 'position' => $pos + 1])
            );
        }

        $adminDept     = $deptModels['ADMIN'];
        $adminServices = [
            ['name' => 'Véhicules et déplacements', 'description' => 'Réservation de véhicules, missions'],
            ['name' => 'Cantine',                    'description' => 'Choix de menu hebdomadaire'],
            ['name' => 'Fournitures de bureau',      'description' => 'Demandes de fournitures'],
        ];

        foreach ($adminServices as $pos => $s) {
            Service::firstOrCreate(
                ['department_id' => $adminDept->id, 'name' => $s['name']],
                array_merge($s, ['department_id' => $adminDept->id, 'is_active' => true, 'position' => $pos + 1])
            );
        }

        /* ── 5. Formulaires + champs + workflow ──────────────────── */

        // 5a. IT — Incident informatique
        $itSupport  = Service::where('department_id', $itDept->id)->where('name', 'Support informatique')->first();
        $formIT     = Form::firstOrCreate(
            ['code' => 'IT-INCIDENT-V1'],
            ['service_id' => $itSupport->id, 'name' => 'Signalement d\'incident informatique', 'version' => 1, 'status' => 'published', 'description' => 'Signalez tout dysfonctionnement matériel ou logiciel.']
        );

        if ($formIT->fields()->count() === 0) {
            $itFields = [
                ['label' => 'Titre de l\'incident',    'type' => 'text',     'required' => true,  'position' => 1, 'rules' => ['max' => 255]],
                ['label' => 'Description détaillée',   'type' => 'textarea', 'required' => true,  'position' => 2, 'rules' => ['max' => 2000]],
                ['label' => 'Urgence',                  'type' => 'select',   'required' => true,  'position' => 3,
                    'options' => [['label' => 'Faible', 'value' => 'low'], ['label' => 'Normale', 'value' => 'normal'], ['label' => 'Haute', 'value' => 'high'], ['label' => 'Critique', 'value' => 'critical']]],
                ['label' => 'Poste concerné',           'type' => 'text',     'required' => false, 'position' => 4, 'rules' => ['max' => 100]],
                ['label' => 'Pièce jointe (capture)',   'type' => 'file',     'required' => false, 'position' => 5, 'rules' => ['mimes' => 'jpg,jpeg,png,pdf', 'max' => 5120]],
            ];
            foreach ($itFields as $f) {
                FormField::create(array_merge(['form_id' => $formIT->id], $f));
            }
        }

        if ($formIT->workflowSteps()->count() === 0) {
            WorkflowStep::create(['form_id' => $formIT->id, 'order' => 1, 'name' => 'Diagnostic N1',    'responsible_role' => 'technician', 'tech_level' => 1, 'needs_approval' => false, 'sla_hours' => 4]);
            WorkflowStep::create(['form_id' => $formIT->id, 'order' => 2, 'name' => 'Résolution N2',    'responsible_role' => 'technician', 'tech_level' => 2, 'needs_approval' => false, 'sla_hours' => 8]);
            WorkflowStep::create(['form_id' => $formIT->id, 'order' => 3, 'name' => 'Validation finale','responsible_role' => 'director',   'tech_level' => null, 'needs_approval' => true, 'sla_hours' => 2]);
        }

        // 5b. RH — Demande de congé
        $rhConges   = Service::where('department_id', $rhDept->id)->where('name', 'Congés et absences')->first();
        $formConge  = Form::firstOrCreate(
            ['code' => 'RH-CONGE-V1'],
            ['service_id' => $rhConges->id, 'name' => 'Demande de congé', 'version' => 1, 'status' => 'published', 'description' => 'Soumettez votre demande de congé annuel ou exceptionnel.']
        );

        if ($formConge->fields()->count() === 0) {
            $rhFields = [
                ['label' => 'Type de congé',  'type' => 'select',   'required' => true,  'position' => 1,
                    'options' => [['label' => 'Congé annuel', 'value' => 'annuel'], ['label' => 'Congé maladie', 'value' => 'maladie'], ['label' => 'Congé exceptionnel', 'value' => 'exceptionnel']]],
                ['label' => 'Date de début',  'type' => 'date',     'required' => true,  'position' => 2],
                ['label' => 'Date de fin',    'type' => 'date',     'required' => true,  'position' => 3],
                ['label' => 'Motif / précision', 'type' => 'textarea', 'required' => false, 'position' => 4, 'rules' => ['max' => 500]],
                ['label' => 'Justificatif',   'type' => 'file',     'required' => false, 'position' => 5, 'rules' => ['mimes' => 'pdf,jpg,jpeg', 'max' => 5120]],
            ];
            foreach ($rhFields as $f) {
                FormField::create(array_merge(['form_id' => $formConge->id], $f));
            }
        }

        if ($formConge->workflowSteps()->count() === 0) {
            WorkflowStep::create(['form_id' => $formConge->id, 'order' => 1, 'name' => 'Validation RH', 'responsible_role' => 'director', 'tech_level' => null, 'needs_approval' => true, 'sla_hours' => 24]);
        }

        // 5c. Admin — Réservation de véhicule
        $adminVeh   = Service::where('department_id', $adminDept->id)->where('name', 'Véhicules et déplacements')->first();
        $formVeh    = Form::firstOrCreate(
            ['code' => 'ADMIN-VEH-V1'],
            ['service_id' => $adminVeh->id, 'name' => 'Réservation de véhicule', 'version' => 1, 'status' => 'published', 'description' => 'Réservez un véhicule de service pour un déplacement professionnel.']
        );

        if ($formVeh->fields()->count() === 0) {
            $vehFields = [
                ['label' => 'Destination',      'type' => 'text',     'required' => true,  'position' => 1, 'rules' => ['max' => 255]],
                ['label' => 'Date de départ',   'type' => 'date',     'required' => true,  'position' => 2],
                ['label' => 'Date de retour',   'type' => 'date',     'required' => true,  'position' => 3],
                ['label' => 'Nombre de passagers', 'type' => 'number', 'required' => true, 'position' => 4, 'rules' => ['min' => 1, 'max' => 20]],
                ['label' => 'Objet du déplacement', 'type' => 'textarea', 'required' => true, 'position' => 5, 'rules' => ['max' => 500]],
            ];
            foreach ($vehFields as $f) {
                FormField::create(array_merge(['form_id' => $formVeh->id], $f));
            }
        }

        if ($formVeh->workflowSteps()->count() === 0) {
            WorkflowStep::create(['form_id' => $formVeh->id, 'order' => 1, 'name' => 'Approbation Administration', 'responsible_role' => 'director', 'tech_level' => null, 'needs_approval' => true, 'sla_hours' => 8]);
        }

        $this->command->info('✅ IntranetSeeder terminé — 9 départements, utilisateurs de test, 3 formulaires d\'exemple.');
        $this->command->table(
            ['Email', 'Rôle', 'Mot de passe'],
            [
                ['intranet-admin@nere-mining.bf',    'Super Admin',    'password'],
                ['directeur.it@nere-mining.bf',      'Directeur IT',   'password'],
                ['directeur.rh@nere-mining.bf',      'Directeur RH',   'password'],
                ['tech1.it@nere-mining.bf',          'Technicien IT N1','password'],
                ['tech2.it@nere-mining.bf',          'Technicien IT N2','password'],
                ['utilisateur@nere-mining.bf',       'Utilisateur démo','password'],
            ]
        );
    }
}
