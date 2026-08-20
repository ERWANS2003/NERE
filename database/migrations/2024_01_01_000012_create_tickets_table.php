<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // Ex: TCK-2026-000123

            $table->string('titre');
            $table->text('description');

            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // demandeur
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('departement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ticket_category_id')->constrained()->restrictOnDelete();

            // Phase 5 : calcul automatique de la priorité
            $table->enum('impact', ['Faible', 'Moyen', 'Élevé', 'Critique'])->default('Faible');
            $table->enum('urgence', ['Faible', 'Moyen', 'Élevé', 'Critique'])->default('Faible');
            $table->foreignId('ticket_priority_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('ticket_status_id')->constrained()->restrictOnDelete();

            // Phase 6 : affectation automatique
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_assignation')->nullable();

            $table->foreignId('sla_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('date_echeance_reponse')->nullable();
            $table->timestamp('date_echeance_resolution')->nullable();
            $table->timestamp('date_premiere_reponse')->nullable();
            $table->timestamp('date_resolution')->nullable();
            $table->timestamp('date_cloture')->nullable();
            $table->boolean('sla_depasse')->default(false);

            $table->unsignedTinyInteger('satisfaction_note')->nullable(); // 1-5
            $table->text('satisfaction_commentaire')->nullable();

            $table->softDeletes(); // "Annuler" logique / suppression douce
            $table->timestamps();

            $table->index(['ticket_status_id', 'ticket_priority_id']);
            $table->index(['assigned_to']);
            $table->index(['site_id', 'departement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
