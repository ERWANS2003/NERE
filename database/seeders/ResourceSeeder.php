<?php

namespace Database\Seeders;

use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        Resource::query()
            ->whereIn('file_path', [
                '/resources/policies/politique-hse-2026.pdf',
                '/resources/policies/reglement-interieur-2025.pdf',
                '/resources/forms/demande-conge.xlsx',
                '/resources/forms/rapport-incident-hse.docx',
                '/resources/forms/demande-achat.xlsx',
                '/resources/training/formation-hse-base.pdf',
                '/resources/training/guide-intranet.pdf',
                '/resources/templates/rapport-mensuel.docx',
                '/resources/templates/presentation-nere.pptx',
            ])
            ->update(['is_active' => false]);

        $category = ResourceCategory::firstOrCreate(
            ['slug' => 'quick-access'],
            [
                'name' => 'Accès rapides',
                'description' => 'Raccourcis vers les services et outils de l’intranet.',
                'icon' => 'link',
                'order' => 6,
            ],
        );

        $uploaderId = User::query()->firstOrFail()->id;

        $resources = [
            [
                'title' => 'Catalogue des formulaires de service',
                'description' => 'Retrouvez les formulaires disponibles et soumettez une demande au département concerné.',
                'path' => route('intranet.forms.index', [], false),
                'tags' => ['formulaires', 'demandes', 'services'],
            ],
            [
                'title' => 'Base de connaissances',
                'description' => 'Consultez les articles et solutions pratiques publiés dans l’intranet.',
                'path' => route('intranet.knowledge-base.index', [], false),
                'tags' => ['guides', 'aide', 'informatique'],
            ],
            [
                'title' => 'Créer une demande de support informatique',
                'description' => 'Signalez un problème ou demandez de l’aide au support informatique.',
                'path' => route('intranet.tickets.create', [], false),
                'tags' => ['support', 'informatique', 'ticket'],
            ],
            [
                'title' => 'Annuaire des départements',
                'description' => 'Découvrez les départements de NÉRÉ Mining et les services proposés.',
                'path' => route('intranet.departments.index', [], false),
                'tags' => ['départements', 'services', 'annuaire'],
            ],
        ];

        foreach ($resources as $resource) {
            Resource::updateOrCreate(
                ['category_id' => $category->id, 'title' => $resource['title']],
                [
                    'description' => $resource['description'],
                    'file_type' => 'link',
                    'file_path' => $resource['path'],
                    'file_size' => null,
                    'tags' => $resource['tags'],
                    'department_id' => null,
                    'uploaded_by' => $uploaderId,
                    'is_active' => true,
                ],
            );
        }
    }
}
