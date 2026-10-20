<?php

use Database\Seeders\KnowledgeBaseSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('knowledge_articles')) {
            Schema::create('knowledge_articles', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('summary', 500);
                $table->text('content');
                $table->string('category', 40)->nullable();
                $table->string('keywords')->nullable();
                $table->boolean('is_published')->default(false);
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['is_published', 'category']);
            });
        } else {
            $this->completeExistingArticlesTable();
        }

        if (! Schema::hasTable('knowledge_article_feedback')) {
            Schema::create('knowledge_article_feedback', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('knowledge_article_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->boolean('helpful');
                $table->timestamps();

                $table->unique(['knowledge_article_id', 'user_id']);
            });
        }

        app(KnowledgeBaseSeeder::class)->run();
    }

    private function completeExistingArticlesTable(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table): void {
            if (! Schema::hasColumn('knowledge_articles', 'title')) {
                $table->string('title')->nullable();
            }

            if (! Schema::hasColumn('knowledge_articles', 'slug')) {
                $table->string('slug')->nullable();
            }

            if (! Schema::hasColumn('knowledge_articles', 'summary')) {
                $table->string('summary', 500)->nullable();
            }

            if (! Schema::hasColumn('knowledge_articles', 'content')) {
                $table->text('content')->nullable();
            }

            if (! Schema::hasColumn('knowledge_articles', 'category')) {
                $table->string('category', 40)->nullable();
            }

            if (! Schema::hasColumn('knowledge_articles', 'keywords')) {
                $table->string('keywords')->nullable();
            }

            if (! Schema::hasColumn('knowledge_articles', 'is_published')) {
                $table->boolean('is_published')->default(true);
            }

            if (! Schema::hasColumn('knowledge_articles', 'author_id')) {
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('knowledge_articles', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('knowledge_articles', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        $this->normalizeLegacyArticleFields();
        $this->ensureArticleSlugs();

        if (! Schema::hasIndex('knowledge_articles', 'knowledge_articles_slug_unique', 'unique')) {
            Schema::table('knowledge_articles', function (Blueprint $table): void {
                $table->unique('slug');
            });
        }

        if (! Schema::hasIndex('knowledge_articles', ['is_published', 'category'])) {
            Schema::table('knowledge_articles', function (Blueprint $table): void {
                $table->index(['is_published', 'category']);
            });
        }
    }

    private function normalizeLegacyArticleFields(): void
    {
        if (Schema::hasColumn('knowledge_articles', 'auteur_id')) {
            DB::table('knowledge_articles')
                ->whereNull('author_id')
                ->whereNotNull('auteur_id')
                ->update(['author_id' => DB::raw('auteur_id')]);

            Schema::table('knowledge_articles', function (Blueprint $table): void {
                $table->dropColumn('auteur_id');
            });
        }

        if (Schema::hasColumn('knowledge_articles', 'titre')) {
            DB::table('knowledge_articles')
                ->where(function ($query): void {
                    $query->whereNull('title')->orWhere('title', '');
                })
                ->whereNotNull('titre')
                ->update(['title' => DB::raw('titre')]);

            Schema::table('knowledge_articles', function (Blueprint $table): void {
                $table->dropColumn('titre');
            });
        }

        if (Schema::hasColumn('knowledge_articles', 'contenu')) {
            DB::table('knowledge_articles')
                ->where(function ($query): void {
                    $query->whereNull('content')->orWhere('content', '');
                })
                ->whereNotNull('contenu')
                ->update(['content' => DB::raw('contenu')]);

            Schema::table('knowledge_articles', function (Blueprint $table): void {
                $table->dropColumn('contenu');
            });
        }

        DB::table('knowledge_articles')
            ->where(function ($query): void {
                $query->whereNull('title')->orWhere('title', '');
            })
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $article): void {
                DB::table('knowledge_articles')
                    ->where('id', $article->id)
                    ->update(['title' => 'Article existant '.$article->id]);
            });
    }

    private function ensureArticleSlugs(): void
    {
        $usedSlugs = [];

        DB::table('knowledge_articles')
            ->orderBy('id')
            ->get(['id', 'title', 'slug'])
            ->each(function (object $article) use (&$usedSlugs): void {
                $slug = is_string($article->slug) ? trim($article->slug) : '';

                if ($slug === '' || isset($usedSlugs[$slug])) {
                    $base = Str::slug($slug !== '' ? $slug : (string) $article->title) ?: 'article';
                    $slug = $base;
                    $suffix = 2;

                    while (isset($usedSlugs[$slug]) || DB::table('knowledge_articles')
                        ->where('slug', $slug)
                        ->where('id', '<>', $article->id)
                        ->exists()) {
                        $slug = $base.'-'.$suffix++;
                    }

                    DB::table('knowledge_articles')
                        ->where('id', $article->id)
                        ->update(['slug' => $slug]);
                }

                $usedSlugs[$slug] = true;
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_article_feedback');
        Schema::dropIfExists('knowledge_articles');
    }
};
