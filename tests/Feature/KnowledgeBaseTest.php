<?php

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\KnowledgeArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function knowledgeArticle(array $attributes = []): KnowledgeArticle
{
    static $sequence = 0;
    $sequence++;

    return KnowledgeArticle::query()->create([
        'title' => "Guide de test {$sequence}",
        'slug' => "guide-de-test-{$sequence}",
        'summary' => 'Résumé du guide.',
        'content' => 'Étapes de dépannage.',
        'category' => 'access',
        'is_published' => true,
        ...$attributes,
    ]);
}

it('shows published knowledge articles to employees and keeps drafts private', function () {
    $published = knowledgeArticle(['title' => 'Guide publié à rechercher']);
    $draft = knowledgeArticle([
        'title' => 'Brouillon confidentiel',
        'slug' => 'brouillon-confidentiel',
        'is_published' => false,
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('intranet.knowledge-base.index', ['search' => 'rechercher']))
        ->assertOk()
        ->assertSee($published->title)
        ->assertDontSee($draft->title);

    $this->get(route('intranet.knowledge-base.show', $published))
        ->assertOk()
        ->assertSee('Ce guide vous a-t-il aidé ?');

    $this->get(route('intranet.knowledge-base.show', $draft))
        ->assertNotFound();
});

it('allows only administrators and the IT team to manage knowledge articles', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('intranet.knowledge-base.create'))
        ->assertForbidden();

    $it = Department::factory()->create(['code' => 'IT', 'name' => 'Informatique']);
    $technician = User::factory()->withRole($it, DepartmentRole::Technician, 1)->create();

    $this->actingAs($technician)
        ->get(route('intranet.knowledge-base.create'))
        ->assertOk()
        ->assertSee('Ajouter un guide de dépannage');

    $article = knowledgeArticle();
    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('intranet.knowledge-base.edit', $article))
        ->assertOk()
        ->assertSee($article->title);
});

it('suggests published guides before a user submits a ticket', function () {
    $suggested = knowledgeArticle([
        'title' => 'Guide pour les problèmes de compte',
        'slug' => 'guide-problemes-compte',
        'category' => 'access',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('intranet.tickets.create', ['category' => 'access']))
        ->assertOk()
        ->assertSee('Avant d’envoyer votre demande')
        ->assertSee($suggested->title)
        ->assertSee(route('intranet.knowledge-base.show', $suggested));
});

it('lets a user update their feedback without creating duplicate votes', function () {
    $article = knowledgeArticle();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('intranet.knowledge-base.feedback', $article), ['helpful' => '1'])
        ->assertRedirect();

    $this->post(route('intranet.knowledge-base.feedback', $article), ['helpful' => '0'])
        ->assertRedirect();

    expect($article->feedback()->count())->toBe(1)
        ->and((bool) $article->feedback()->first()->pivot->helpful)->toBeFalse();

    $this->get(route('intranet.knowledge-base.show', $article))
        ->assertOk()
        ->assertSee('0 oui · 1 pas encore');
});

it('creates and updates a knowledge article through the IT management form', function () {
    $it = Department::factory()->create(['code' => 'IT', 'name' => 'Informatique']);
    $technician = User::factory()->withRole($it, DepartmentRole::Technician, 1)->create();

    $this->actingAs($technician)
        ->post(route('intranet.knowledge-base.store'), [
            'title' => 'Résoudre un problème courant',
            'summary' => 'Un guide de résolution simple.',
            'content' => "Étape 1.\nÉtape 2.",
            'category' => 'software',
            'keywords' => 'application, dépannage',
            'is_published' => '1',
        ])
        ->assertRedirect();

    $article = KnowledgeArticle::query()->where('slug', 'resoudre-un-probleme-courant')->firstOrFail();
    expect($article->is_published)->toBeTrue()
        ->and($article->author_id)->toBe($technician->id);

    $this->put(route('intranet.knowledge-base.update', $article), [
        'title' => 'Résoudre un problème courant',
        'summary' => 'Résumé mis à jour.',
        'content' => 'Étapes mises à jour.',
        'category' => 'software',
    ])->assertRedirect();

    expect($article->fresh()->summary)->toBe('Résumé mis à jour.')
        ->and($article->fresh()->is_published)->toBeFalse();
});

it('can safely rerun the knowledge base migration when its tables already exist', function () {
    $existingArticle = knowledgeArticle(['slug' => 'article-existant']);
    $articlesBefore = KnowledgeArticle::query()->count();

    $migration = require database_path('migrations/2026_10_20_151600_create_knowledge_base_tables.php');
    $migration->up();

    expect(KnowledgeArticle::query()->count())->toBe($articlesBefore)
        ->and(KnowledgeArticle::query()->whereKey($existingArticle->id)->exists())->toBeTrue()
        ->and(Schema::hasTable('knowledge_article_feedback'))->toBeTrue();
});

it('repairs an older knowledge articles table before seeding guides', function () {
    Schema::dropIfExists('knowledge_article_feedback');
    Schema::dropIfExists('knowledge_articles');

    Schema::create('knowledge_articles', function ($table): void {
        $table->id();
        $table->string('title');
        $table->string('summary', 500);
        $table->text('content');
        $table->string('category', 40)->nullable();
        $table->string('keywords')->nullable();
        $table->timestamps();
    });

    $legacyId = DB::table('knowledge_articles')->insertGetId([
        'title' => 'Guide historique existant',
        'summary' => 'Résumé conservé.',
        'content' => 'Instructions conservées.',
        'category' => 'access',
        'keywords' => 'compte',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_10_20_151600_create_knowledge_base_tables.php');
    $migration->up();

    $legacyArticle = KnowledgeArticle::query()->findOrFail($legacyId);
    expect($legacyArticle->slug)->toBe('guide-historique-existant')
        ->and($legacyArticle->is_published)->toBeTrue()
        ->and($legacyArticle->summary)->toBe('Résumé conservé.')
        ->and(Schema::hasColumn('knowledge_articles', 'author_id'))->toBeTrue()
        ->and(Schema::hasTable('knowledge_article_feedback'))->toBeTrue();
});

it('migrates legacy French article titles and removes the required legacy column', function () {
    Schema::dropIfExists('knowledge_article_feedback');
    Schema::dropIfExists('knowledge_articles');

    Schema::create('knowledge_articles', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('auteur_id');
        $table->string('titre');
        $table->string('summary', 500);
        $table->text('contenu');
        $table->string('category', 40)->nullable();
        $table->string('keywords')->nullable();
        $table->timestamps();
    });

    $author = User::factory()->create();
    $legacyId = DB::table('knowledge_articles')->insertGetId([
        'auteur_id' => $author->id,
        'titre' => 'Ancien guide de connexion',
        'summary' => 'Résumé historique.',
        'contenu' => 'Instructions historiques.',
        'category' => 'access',
        'keywords' => 'connexion',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_10_20_151600_create_knowledge_base_tables.php');
    $migration->up();

    $article = KnowledgeArticle::query()->findOrFail($legacyId);
    expect($article->title)->toBe('Ancien guide de connexion')
        ->and($article->slug)->toBe('ancien-guide-de-connexion')
        ->and($article->summary)->toBe('Résumé historique.')
        ->and($article->content)->toBe('Instructions historiques.')
        ->and($article->author_id)->toBe($author->id)
        ->and(Schema::hasColumn('knowledge_articles', 'titre'))->toBeFalse()
        ->and(Schema::hasColumn('knowledge_articles', 'contenu'))->toBeFalse()
        ->and(Schema::hasColumn('knowledge_articles', 'auteur_id'))->toBeFalse()
        ->and(KnowledgeArticle::query()->count())->toBeGreaterThan(1);
});
