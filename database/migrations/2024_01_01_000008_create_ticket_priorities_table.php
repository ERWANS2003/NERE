<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_priorities', function (Blueprint $table) {
            $table->id();
            $table->string('nom'); // Faible, Normale, Élevée, Critique
            $table->unsignedTinyInteger('niveau'); // 1=faible ... 4=critique, pour tri
            $table->string('couleur')->default('#6c757d');
            $table->unsignedInteger('delai_resolution_heures')->nullable(); // SLA par défaut
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_priorities');
    }
};
