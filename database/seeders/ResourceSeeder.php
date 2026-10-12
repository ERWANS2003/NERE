<?php

namespace Database\Seeders;

use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@neremining.bf')->first() 
                 ?? User::first();
        
        $departments = Department::all();

        // Catégories de ressources
        $policies = ResourceCategory::firstOrCreate(
            ['slug' => 'policies'],
            [
                'name' => 'Politiques et Procédures',
                'description' => 'Documents officiels définissant les politiques et procédures de l\'entreprise.',
                'icon' => 'book-open',
                'order' => 1,
            ]
        );

        $forms = ResourceCategory::firstOrCreate(
            ['slug' => 'forms'],
            [
                'name' => 'Formulaires',
                'description' => 'Formulaires administratifs et opérationnels.',
                'icon' => 'file-text',
                'order' => 2,
            ]
        );

        $training = ResourceCategory::firstOrCreate(
            ['slug' => 'training'],
            [
                'name' => 'Formation',
                'description' => 'Supports de formation et guides pédagogiques.',
                'icon' => 'graduation-cap',
                'order' => 3,
            ]
        );

        $technical = ResourceCategory::firstOrCreate(
            ['slug' => 'technical'],
            [
                'name' => 'Documentation technique',
                'description' => 'Manuels techniques et spécifications.',
                'icon' => 'wrench',
                'order' => 4,
            ]
        );

        $templates = ResourceCategory::firstOrCreate(
            ['slug' => 'templates'],
            [
                'name' => 'Modèles',
                'description' => 'Modèles de documents réutilisables.',
                'icon' => 'file-copy',
                'order' => 5,
            ]
        );

        // Ressources - Politiques
        Resource::create([
            'category_id' => $policies->id,
            'department_id' => null,
            'title' => 'Politique HSE de NÉRÉ Mining',
            'description' => 'Document officiel définissant la politique de santé, sécurité et environnement de l\'entreprise.',
            'file_path' => '/resources/policies/politique-hse-2026.pdf',
            'file_type' => 'pdf',
            'file_size' => '2.45 MB',
            'tags' => ['hse', 'politique', 'sécurité'],
            'uploaded_by' => $admin->id,
            'download_count' => 156,
        ]);

        Resource::create([
            'category_id' => $policies->id,
            'department_id' => $departments->where('code', 'RH')->first()?->id,
            'title' => 'Règlement intérieur',
            'description' => 'Règlement intérieur de l\'entreprise applicable à tous les employés.',
            'file_path' => '/resources/policies/reglement-interieur-2025.pdf',
            'file_type' => 'pdf',
            'file_size' => '1.85 MB',
            'tags' => ['rh', 'règlement', 'politique'],
            'uploaded_by' => $admin->id,
            'download_count' => 342,
        ]);

        // Ressources - Formulaires
        Resource::create([
            'category_id' => $forms->id,
            'department_id' => $departments->where('code', 'RH')->first()?->id,
            'title' => 'Formulaire de demande de congé',
            'description' => 'Formulaire pour soumettre une demande de congé annuel ou exceptionnel.',
            'file_path' => '/resources/forms/demande-conge.xlsx',
            'file_type' => 'xlsx',
            'file_size' => '45 KB',
            'tags' => ['rh', 'congé', 'formulaire'],
            'uploaded_by' => $admin->id,
            'download_count' => 523,
        ]);

        Resource::create([
            'category_id' => $forms->id,
            'department_id' => $departments->where('code', 'HSE')->first()?->id,
            'title' => 'Rapport d\'incident HSE',
            'description' => 'Formulaire de déclaration d\'incident de sécurité ou environnemental.',
            'file_path' => '/resources/forms/rapport-incident-hse.docx',
            'file_type' => 'docx',
            'file_size' => '78 KB',
            'tags' => ['hse', 'incident', 'formulaire'],
            'uploaded_by' => $admin->id,
            'download_count' => 234,
        ]);

        Resource::create([
            'category_id' => $forms->id,
            'department_id' => $departments->where('code', 'SCM')->first()?->id,
            'title' => 'Demande d\'achat',
            'description' => 'Formulaire de demande d\'achat de matériel ou services.',
            'file_path' => '/resources/forms/demande-achat.xlsx',
            'file_type' => 'xlsx',
            'file_size' => '52 KB',
            'tags' => ['scm', 'achat', 'formulaire'],
            'uploaded_by' => $admin->id,
            'download_count' => 678,
        ]);

        // Ressources - Formation
        Resource::create([
            'category_id' => $training->id,
            'department_id' => $departments->where('code', 'HSE')->first()?->id,
            'title' => 'Formation HSE de base',
            'description' => 'Support de formation pour la sensibilisation HSE des nouveaux employés.',
            'file_path' => '/resources/training/formation-hse-base.pdf',
            'file_type' => 'pdf',
            'file_size' => '3.2 MB',
            'tags' => ['hse', 'formation', 'sécurité'],
            'uploaded_by' => $admin->id,
            'download_count' => 789,
        ]);

        Resource::create([
            'category_id' => $training->id,
            'department_id' => $departments->where('code', 'IT')->first()?->id,
            'title' => 'Guide utilisateur intranet',
            'description' => 'Guide complet d\'utilisation de l\'intranet NÉRÉ Mining.',
            'file_path' => '/resources/training/guide-intranet.pdf',
            'file_type' => 'pdf',
            'file_size' => '1.2 MB',
            'tags' => ['it', 'guide', 'intranet'],
            'uploaded_by' => $admin->id,
            'download_count' => 445,
        ]);

        // Ressources - Modèles
        Resource::create([
            'category_id' => $templates->id,
            'department_id' => null,
            'title' => 'Modèle de rapport mensuel',
            'description' => 'Modèle standardisé pour les rapports mensuels des départements.',
            'file_path' => '/resources/templates/rapport-mensuel.docx',
            'file_type' => 'docx',
            'file_size' => '125 KB',
            'tags' => ['modèle', 'rapport'],
            'uploaded_by' => $admin->id,
            'download_count' => 356,
        ]);

        Resource::create([
            'category_id' => $templates->id,
            'department_id' => null,
            'title' => 'Modèle de présentation PowerPoint',
            'description' => 'Modèle de présentation aux couleurs de NÉRÉ Mining.',
            'file_path' => '/resources/templates/presentation-nere.pptx',
            'file_type' => 'pptx',
            'file_size' => '456 KB',
            'tags' => ['modèle', 'présentation'],
            'uploaded_by' => $admin->id,
            'download_count' => 234,
        ]);
    }
}
