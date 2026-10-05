<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intranet_submission_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('intranet_submissions')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            // ex. created|submitted|assigned|status_changed|commented|escalated|resolved|closed|rejected
            $table->string('action', 40);
            $table->jsonb('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // Pas de updated_at : les événements sont immutables
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_submission_events');
    }
};
