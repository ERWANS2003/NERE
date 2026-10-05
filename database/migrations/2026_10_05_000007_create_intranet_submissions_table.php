<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intranet_submissions', function (Blueprint $table) {
            $table->id();
            // ex. IT-2026-00042
            $table->string('reference', 30)->unique();
            $table->foreignId('form_id')->constrained('intranet_forms');
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('current_step_id')->nullable()->constrained('intranet_workflow_steps')->nullOnDelete();
            // brouillon|soumise|en_validation|assignee|en_cours|en_attente|escaladee|resolue|cloturee|rejetee|annulee
            $table->string('status', 20)->default('brouillon');
            // basse|normale|haute|critique
            $table->string('priority', 20)->default('normale');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'due_at']);
            $table->index('requester_id');
            $table->index('assignee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_submissions');
    }
};
