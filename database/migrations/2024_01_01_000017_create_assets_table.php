<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_type_id')->constrained()->restrictOnDelete();
            $table->string('nom');
            $table->string('code_inventaire')->unique();
            $table->string('marque')->nullable();
            $table->string('modele')->nullable();
            $table->string('numero_serie')->nullable();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();       // attribué à
            $table->foreignId('departement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('statut', ['En stock', 'En service', 'En maintenance', 'Hors service', 'Réformé'])
                ->default('En stock');
            $table->date('date_acquisition')->nullable();
            $table->date('date_garantie_fin')->nullable();
            $table->decimal('valeur_acquisition', 12, 2)->nullable();
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });

        // Un ticket peut concerner plusieurs actifs, et un actif peut apparaître dans plusieurs tickets
        Schema::create('asset_ticket', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['asset_id', 'ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_ticket');
        Schema::dropIfExists('assets');
    }
};
