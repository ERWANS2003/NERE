<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Tests\TestCase;

class ViewSectionNameConsistencyTest extends TestCase
{
    public function test_app_new_views_use_expected_section_names(): void
    {
        $badFiles = [];

        $files = [
            resource_path('views/services/catalog.blade.php'),
            resource_path('views/services/request.blade.php'),
            resource_path('views/tickets/create-smart.blade.php'),
            resource_path('views/automations/index.blade.php'),
            resource_path('views/automations/create.blade.php'),
            resource_path('views/knowledge/index-new.blade.php'),
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            if (Str::contains($contents, "@section('title'") || Str::contains($contents, "@section('content'") || Str::contains($contents, "@section('content')")) {
                $badFiles[] = basename($file);
            }
        }

        $this->assertSame([], $badFiles, 'Les vues app-new doivent utiliser les sections Blade de app-new: titre + contenu.');
    }
}
