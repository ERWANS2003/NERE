<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->id();
            $table->string('nom'); // Ex: Réseau, Matériel, Logiciel, SCADA, RH...
            $table->foreignId('parent_id')->nullable()->constrained('ticket_categories')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete(); // équipe par défaut pour affectation auto
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_categories');
    }
};
