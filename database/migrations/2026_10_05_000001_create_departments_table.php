<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();

            // Code court utilise comme prefixe des references de demandes
            // (IT-2026-00042). Admin configurable, jamais code en dur.
            $table->string('code', 16)->unique();

            // Categorie affichee en petites capitales ambrees sur la carte.
            $table->string('tag', 64);

            $table->string('name');

            $table->text('description')->nullable();

            // Nom d'icone resolu par le composant Blade <x-app-icon>.
            $table->string('icon', 48)->default('building');

            // Teinte d'accent de la carte (hex). Vide = ambre par defaut.
            $table->string('color', 9)->nullable();

            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
