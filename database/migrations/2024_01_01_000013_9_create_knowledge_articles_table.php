<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Phase 9 : Base de connaissances
    public function up(): void
    {
        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->longText('contenu');
            $table->foreignId('ticket_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('auteur_id')->constrained('users')->cascadeOnDelete();
            $table->string('mots_cles')->nullable(); // séparés par des virgules, ou migrer vers table pivot dédiée
            $table->unsignedInteger('vues')->default(0);
            $table->unsignedInteger('utile_count')->default(0);
            $table->boolean('publie')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('knowledge_articles', function (Blueprint $table) {
                $table->fullText(['titre', 'contenu', 'mots_cles']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_articles');
    }
};
