<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Favoris de ressources : table pivot entre un utilisateur et une ressource.
 * Une paire (utilisateur, ressource) est unique — on ne met pas deux fois la
 * même ressource en favori. La suppression d'un côté ou de l'autre nettoie le
 * favori (cascade).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained('resources')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'resource_id']);
            $table->index('resource_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_user');
    }
};
