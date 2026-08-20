<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Matrice Impact x Urgence -> Priorité, configurable sans toucher au code (Phase 5)
    public function up(): void
    {
        Schema::create('priority_matrices', function (Blueprint $table) {
            $table->id();
            $table->string('impact');   // Faible, Moyen, Élevé, Critique
            $table->string('urgence');  // Faible, Moyen, Élevé, Critique
            $table->foreignId('ticket_priority_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['impact', 'urgence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('priority_matrices');
    }
};
