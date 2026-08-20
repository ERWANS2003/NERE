<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('nom'); // Nouveau, Assigné, En cours, En attente, Résolu, Fermé, Annulé
            $table->string('couleur')->default('#6c757d');
            $table->unsignedTinyInteger('ordre')->default(0);
            $table->boolean('est_final')->default(false); // Fermé/Annulé
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_statuses');
    }
};
