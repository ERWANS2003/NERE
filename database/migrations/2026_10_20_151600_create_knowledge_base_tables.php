<?php

use Database\Seeders\KnowledgeBaseSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        Schema::create('knowledge_article_feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('knowledge_article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('helpful');
            $table->timestamps();

            $table->unique(['knowledge_article_id', 'user_id']);
        });

        app(KnowledgeBaseSeeder::class)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_article_feedback');
        Schema::dropIfExists('knowledge_articles');
    }
};
