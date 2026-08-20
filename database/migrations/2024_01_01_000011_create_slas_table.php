<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slas', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->foreignId('ticket_priority_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('temps_reponse_heures');   // délai de première réponse
            $table->unsignedInteger('temps_resolution_heures'); // délai de résolution
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slas');
    }
};
