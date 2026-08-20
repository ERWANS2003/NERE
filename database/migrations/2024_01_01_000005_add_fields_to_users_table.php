<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('departement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('matricule')->unique()->nullable();
            $table->string('telephone')->nullable();
            $table->string('poste')->nullable();
            $table->boolean('est_technicien')->default(false);
            $table->boolean('disponible')->default(true); // pour l'affectation automatique
            $table->boolean('actif')->default(true);
            $table->timestamp('derniere_connexion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['departement_id']);
            $table->dropForeign(['site_id']);
            $table->dropColumn([
                'role_id', 'departement_id', 'site_id', 'matricule', 'telephone',
                'poste', 'est_technicien', 'disponible', 'actif', 'derniere_connexion',
            ]);
        });
    }
};
