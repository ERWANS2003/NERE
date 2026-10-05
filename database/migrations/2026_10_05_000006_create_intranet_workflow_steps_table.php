<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intranet_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('intranet_forms')->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(1);
            $table->string('name');
            // responsible_role : user | technician | director
            $table->string('responsible_role', 20)->default('technician');
            $table->unsignedTinyInteger('tech_level')->nullable();
            $table->boolean('needs_approval')->default(false);
            // SLA en heures ouvrées (null = pas de SLA)
            $table->unsignedSmallInteger('sla_hours')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_workflow_steps');
    }
};
