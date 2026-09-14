<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dashboard_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); // ticket_created, ticket_assigned, sla_warning, sla_breached, comment_added, incident_created
            $table->string('title');
            $table->text('message');
            $table->string('icon')->nullable();
            $table->string('color')->default('info'); // blue, primary, yellow, red, info, success, danger
            $table->string('action_url')->nullable();
            $table->json('data')->nullable(); // Additional data like ticket_id, etc
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'read_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_notifications');
    }
};
