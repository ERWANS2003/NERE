<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Phase 10 : configuration des canaux de notification par événement (Phase 12 : Administration)
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('evenement')->unique(); // ticket_cree, ticket_assigne, changement_statut, ticket_resolu, sla_depasse
            $table->string('libelle');
            $table->boolean('email_actif')->default(true);
            $table->boolean('interne_actif')->default(true);
            $table->boolean('slack_actif')->default(false);
            $table->boolean('teams_actif')->default(false);
            $table->boolean('sms_actif')->default(false);
            $table->timestamps();
        });

        // Table standard des notifications internes Laravel (utilisée par Notifiable::notify())
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_settings');
    }
};
